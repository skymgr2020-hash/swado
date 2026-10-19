package com.renjo.app

import android.Manifest
import android.content.Context
import android.content.pm.PackageManager
import android.media.AudioAttributes
import android.media.AudioFormat
import android.media.AudioManager
import android.media.AudioRecord
import android.media.AudioTrack
import android.media.MediaRecorder
import android.media.audiofx.AcousticEchoCanceler
import android.media.audiofx.NoiseSuppressor
import android.os.Handler
import android.os.Looper
import androidx.core.content.ContextCompat
import io.flutter.plugin.common.BinaryMessenger
import io.flutter.plugin.common.EventChannel
import io.flutter.plugin.common.MethodChannel
import java.util.concurrent.Executors
import java.util.concurrent.atomic.AtomicBoolean

class AssistantLiveAudioBridge(
    private val context: Context,
    messenger: BinaryMessenger,
) : EventChannel.StreamHandler {
    private val recording = AtomicBoolean(false)
    private val mainHandler = Handler(Looper.getMainLooper())
    private val playbackExecutor = Executors.newSingleThreadExecutor()
    private var eventSink: EventChannel.EventSink? = null
    private var audioRecord: AudioRecord? = null
    private var audioTrack: AudioTrack? = null
    private var recordingThread: Thread? = null
    private var echoCanceler: AcousticEchoCanceler? = null
    private var noiseSuppressor: NoiseSuppressor? = null
    private var previousAudioMode: Int? = null

    init {
        EventChannel(messenger, INPUT_CHANNEL).setStreamHandler(this)
        MethodChannel(messenger, CONTROL_CHANNEL).setMethodCallHandler { call, result ->
            when (call.method) {
                "startCapture" -> runCatching { startCapture() }
                    .onSuccess { result.success(true) }
                    .onFailure { result.error("capture_start_failed", it.message, null) }
                "stopCapture" -> {
                    stopCapture()
                    result.success(true)
                }
                "playChunk" -> {
                    val bytes = call.argument<ByteArray>("bytes")
                    if (bytes == null || bytes.isEmpty()) {
                        result.success(false)
                    } else {
                        playChunk(bytes)
                        result.success(true)
                    }
                }
                "clearPlayback" -> {
                    clearPlayback()
                    result.success(true)
                }
                "stopAudio" -> {
                    disposeAudio()
                    result.success(true)
                }
                else -> result.notImplemented()
            }
        }
    }

    override fun onListen(arguments: Any?, events: EventChannel.EventSink?) {
        eventSink = events
    }

    override fun onCancel(arguments: Any?) {
        eventSink = null
        stopCapture()
    }

    private fun startCapture() {
        if (recording.get()) return
        check(ContextCompat.checkSelfPermission(context, Manifest.permission.RECORD_AUDIO) == PackageManager.PERMISSION_GRANTED) {
            "Microphone permission is required."
        }

        val minBuffer = AudioRecord.getMinBufferSize(INPUT_RATE, INPUT_CHANNEL_CONFIG, PCM_FORMAT)
        check(minBuffer > 0) { "This device does not support 16 kHz PCM recording." }
        val bufferSize = maxOf(minBuffer, INPUT_CHUNK_BYTES * 4)
        val recorder = AudioRecord(
            MediaRecorder.AudioSource.VOICE_COMMUNICATION,
            INPUT_RATE,
            INPUT_CHANNEL_CONFIG,
            PCM_FORMAT,
            bufferSize,
        )
        check(recorder.state == AudioRecord.STATE_INITIALIZED) { "Microphone could not be initialized." }

        val audioManager = context.getSystemService(Context.AUDIO_SERVICE) as AudioManager
        previousAudioMode = audioManager.mode
        audioManager.mode = AudioManager.MODE_IN_COMMUNICATION
        audioRecord = recorder
        if (AcousticEchoCanceler.isAvailable()) {
            echoCanceler = AcousticEchoCanceler.create(recorder.audioSessionId)?.apply { enabled = true }
        }
        if (NoiseSuppressor.isAvailable()) {
            noiseSuppressor = NoiseSuppressor.create(recorder.audioSessionId)?.apply { enabled = true }
        }
        recording.set(true)
        recorder.startRecording()
        recordingThread = Thread({ captureLoop(recorder) }, "gemini-live-microphone").apply { start() }
    }

    private fun captureLoop(recorder: AudioRecord) {
        val buffer = ByteArray(INPUT_CHUNK_BYTES)
        while (recording.get()) {
            val count = recorder.read(buffer, 0, buffer.size)
            if (count > 0) {
                val chunk = if (count == buffer.size) buffer.copyOf() else buffer.copyOf(count)
                mainHandler.post { eventSink?.success(chunk) }
            } else if (count < 0) {
                mainHandler.post { eventSink?.error("capture_read_failed", "Microphone read failed: $count", null) }
                break
            }
        }
    }

    private fun stopCapture() {
        if (!recording.getAndSet(false)) return
        runCatching { audioRecord?.stop() }
        audioRecord?.release()
        audioRecord = null
        echoCanceler?.release()
        echoCanceler = null
        noiseSuppressor?.release()
        noiseSuppressor = null
        recordingThread = null
        restoreAudioMode()
    }

    private fun ensureAudioTrack(): AudioTrack {
        audioTrack?.let { return it }
        val minBuffer = AudioTrack.getMinBufferSize(OUTPUT_RATE, OUTPUT_CHANNEL_CONFIG, PCM_FORMAT)
        val track = AudioTrack.Builder()
            .setAudioAttributes(
                AudioAttributes.Builder()
                    .setUsage(AudioAttributes.USAGE_MEDIA)
                    .setContentType(AudioAttributes.CONTENT_TYPE_SPEECH)
                    .build()
            )
            .setAudioFormat(
                AudioFormat.Builder()
                    .setEncoding(PCM_FORMAT)
                    .setSampleRate(OUTPUT_RATE)
                    .setChannelMask(OUTPUT_CHANNEL_CONFIG)
                    .build()
            )
            .setBufferSizeInBytes(maxOf(minBuffer, OUTPUT_RATE * 2))
            .setTransferMode(AudioTrack.MODE_STREAM)
            .build()
        check(track.state == AudioTrack.STATE_INITIALIZED) { "Speaker output could not be initialized." }
        track.setVolume(1.0f)
        track.play()
        audioTrack = track
        return track
    }

    private fun playChunk(bytes: ByteArray) {
        playbackExecutor.execute {
            runCatching { ensureAudioTrack().write(bytes, 0, bytes.size, AudioTrack.WRITE_BLOCKING) }
                .onFailure { mainHandler.post { eventSink?.error("playback_failed", it.message, null) } }
        }
    }

    private fun clearPlayback() {
        audioTrack?.let { track ->
            runCatching { track.pause() }
            runCatching { track.flush() }
            runCatching { track.play() }
        }
    }

    fun disposeAudio() {
        stopCapture()
        playbackExecutor.execute {
            audioTrack?.let { track ->
                runCatching { track.stop() }
                track.release()
            }
            audioTrack = null
        }
        restoreAudioMode()
    }

    private fun restoreAudioMode() {
        val manager = context.getSystemService(Context.AUDIO_SERVICE) as AudioManager
        previousAudioMode?.let { manager.mode = it }
        previousAudioMode = null
    }

    companion object {
        private const val INPUT_CHANNEL = "com.renjo.app/assistant_pcm_input"
        private const val CONTROL_CHANNEL = "com.renjo.app/assistant_pcm_audio"
        private const val INPUT_RATE = 16000
        private const val OUTPUT_RATE = 24000
        private const val INPUT_CHUNK_BYTES = 1280 // 40 ms, PCM16 mono at 16 kHz.
        private const val INPUT_CHANNEL_CONFIG = AudioFormat.CHANNEL_IN_MONO
        private const val OUTPUT_CHANNEL_CONFIG = AudioFormat.CHANNEL_OUT_MONO
        private const val PCM_FORMAT = AudioFormat.ENCODING_PCM_16BIT
    }
}
