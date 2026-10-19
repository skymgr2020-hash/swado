package com.renjo.app

import android.app.NotificationManager
import android.content.Context
import android.content.Intent
import android.content.pm.PackageManager
import android.media.AudioAttributes
import android.media.AudioDeviceInfo
import android.media.AudioManager
import android.net.Uri
import android.os.Build
import android.os.PowerManager
import android.provider.Settings
import android.speech.tts.TextToSpeech
import androidx.core.content.ContextCompat
import io.flutter.embedding.android.FlutterActivity
import io.flutter.embedding.engine.FlutterEngine
import io.flutter.plugin.common.MethodChannel
import io.flutter.plugins.googlemobileads.GoogleMobileAdsPlugin
import java.util.Locale

class MainActivity : FlutterActivity() {
    private val audioChannelName = "com.renjo.app/order_audio"
    private val alertChannelName = "com.renjo.app/order_alerts"
    private val configChannelName = "com.renjo.app/app_config"
    private val assistantVoiceChannelName = "com.renjo.app/assistant_voice"
    // Must match the factoryId Dart passes to NativeAd(...) in
    // lib/widgets/common/native_ad_widget.dart.
    private val nativeAdFactoryId = "listTileNativeAd"
    private var assistantTts: TextToSpeech? = null
    private var assistantLiveAudio: AssistantLiveAudioBridge? = null
    private var previousRingerMode: Int? = null
    private var previousAudioMode: Int? = null
    private var previousSpeakerphone: Boolean? = null
    private var previousAlarmVolume: Int? = null
    private var previousNotificationVolume: Int? = null
    private var previousMusicVolume: Int? = null

    override fun configureFlutterEngine(flutterEngine: FlutterEngine) {
        super.configureFlutterEngine(flutterEngine)
        assistantLiveAudio = AssistantLiveAudioBridge(this, flutterEngine.dartExecutor.binaryMessenger)

        GoogleMobileAdsPlugin.registerNativeAdFactory(
            flutterEngine,
            nativeAdFactoryId,
            ListTileNativeAdFactory(layoutInflater)
        )

        MethodChannel(
            flutterEngine.dartExecutor.binaryMessenger,
            audioChannelName
        ).setMethodCallHandler { call, result ->
            when (call.method) {
                "prepareUrgentOrderAudio" -> {
                    prepareUrgentOrderAudio()
                    result.success(true)
                }
                "restoreNormalAudio" -> {
                    restoreNormalAudio()
                    result.success(true)
                }
                else -> result.notImplemented()
            }
        }

        MethodChannel(
            flutterEngine.dartExecutor.binaryMessenger,
            alertChannelName
        ).setMethodCallHandler { call, result ->
            when (call.method) {
                "startForegroundService" -> {
                    val status = call.argument<String>("status")
                        ?: "Online and listening for orders"
                    val fullScreen = call.argument<Boolean>("fullScreen") ?: false
                    startOrderAlertService(status, fullScreen)
                    result.success(true)
                }
                "updateServiceNotification" -> {
                    val status = call.argument<String>("status")
                        ?: "Online and listening for orders"
                    val fullScreen = call.argument<Boolean>("fullScreen") ?: false
                    startOrderAlertService(status, fullScreen)
                    result.success(true)
                }
                "stopForegroundService" -> {
                    stopService(Intent(this, OrderAlertForegroundService::class.java))
                    result.success(true)
                }
                "bringAppToFront" -> {
                    bringAppToFront()
                    result.success(true)
                }
                "canDrawOverlays" -> {
                    result.success(canDrawOverlays())
                }
                "requestOverlayPermission" -> {
                    openOverlayPermissionSettings()
                    result.success(true)
                }
                "requestBatteryOptimizationExemption" -> {
                    requestBatteryOptimizationExemption()
                    result.success(true)
                }
                "openAppNotificationSettings" -> {
                    openAppNotificationSettings()
                    result.success(true)
                }
                else -> result.notImplemented()
            }
        }

        MethodChannel(
            flutterEngine.dartExecutor.binaryMessenger,
            configChannelName
        ).setMethodCallHandler { call, result ->
            when (call.method) {
                "getGoogleMapsApiKey" -> result.success(getGoogleMapsApiKey())
                else -> result.notImplemented()
            }
        }

        MethodChannel(
            flutterEngine.dartExecutor.binaryMessenger,
            assistantVoiceChannelName
        ).setMethodCallHandler { call, result ->
            when (call.method) {
                "speak" -> {
                    val text = call.argument<String>("text") ?: ""
                    val language = call.argument<String>("language") ?: "en"
                    speakAssistantText(text, language)
                    result.success(true)
                }
                "stop" -> {
                    assistantTts?.stop()
                    result.success(true)
                }
                else -> result.notImplemented()
            }
        }
    }

    override fun cleanUpFlutterEngine(flutterEngine: FlutterEngine) {
        GoogleMobileAdsPlugin.unregisterNativeAdFactory(flutterEngine, nativeAdFactoryId)
        super.cleanUpFlutterEngine(flutterEngine)
    }

    override fun onDestroy() {
        assistantLiveAudio?.disposeAudio()
        assistantLiveAudio = null
        assistantTts?.stop()
        assistantTts?.shutdown()
        assistantTts = null
        super.onDestroy()
    }

    private fun startOrderAlertService(status: String, fullScreen: Boolean = false) {
        val intent = Intent(this, OrderAlertForegroundService::class.java)
            .putExtra(OrderAlertForegroundService.EXTRA_STATUS, status)
            .putExtra(OrderAlertForegroundService.EXTRA_FULL_SCREEN, fullScreen)
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            ContextCompat.startForegroundService(this, intent)
        } else {
            startService(intent)
        }
    }

    private fun canDrawOverlays(): Boolean {
        return Build.VERSION.SDK_INT < Build.VERSION_CODES.M || Settings.canDrawOverlays(this)
    }

    private fun openOverlayPermissionSettings() {
        if (Build.VERSION.SDK_INT < Build.VERSION_CODES.M || canDrawOverlays()) return
        val intent = Intent(
            Settings.ACTION_MANAGE_OVERLAY_PERMISSION,
            Uri.parse("package:$packageName")
        ).addFlags(Intent.FLAG_ACTIVITY_NEW_TASK)
        startActivity(intent)
    }

    private fun requestBatteryOptimizationExemption() {
        if (Build.VERSION.SDK_INT < Build.VERSION_CODES.M) return
        val powerManager = getSystemService(Context.POWER_SERVICE) as PowerManager
        if (powerManager.isIgnoringBatteryOptimizations(packageName)) return
        val intent = Intent(Settings.ACTION_REQUEST_IGNORE_BATTERY_OPTIMIZATIONS)
            .setData(Uri.parse("package:$packageName"))
            .addFlags(Intent.FLAG_ACTIVITY_NEW_TASK)
        startActivity(intent)
    }

    private fun openAppNotificationSettings() {
        val intent = if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            Intent(Settings.ACTION_APP_NOTIFICATION_SETTINGS)
                .putExtra(Settings.EXTRA_APP_PACKAGE, packageName)
        } else {
            Intent(Settings.ACTION_APPLICATION_DETAILS_SETTINGS)
                .setData(Uri.parse("package:$packageName"))
        }.addFlags(Intent.FLAG_ACTIVITY_NEW_TASK)
        startActivity(intent)
    }

    private fun bringAppToFront() {
        val intent = Intent(this, MainActivity::class.java)
            .addFlags(Intent.FLAG_ACTIVITY_NEW_TASK or Intent.FLAG_ACTIVITY_SINGLE_TOP)
        startActivity(intent)
    }

    private fun getGoogleMapsApiKey(): String {
        return try {
            val appInfo = packageManager.getApplicationInfo(
                packageName,
                PackageManager.GET_META_DATA
            )
            appInfo.metaData?.getString("com.google.android.geo.API_KEY") ?: ""
        } catch (_: Exception) {
            ""
        }
    }

    private fun speakAssistantText(text: String, language: String) {
        if (text.isBlank()) return

        val speak = {
            val requestedLocale = when (language.lowercase(Locale.ROOT)) {
                "hi", "hi-in", "hinglish" -> Locale.forLanguageTag("hi-IN")
                "bho", "bho-in", "bhojpuri" -> Locale.forLanguageTag("bho-IN")
                "bn", "bn-in", "bangla", "bengali" -> Locale.forLanguageTag("bn-IN")
                "ta", "ta-in", "tamil" -> Locale.forLanguageTag("ta-IN")
                "te", "te-in", "telugu" -> Locale.forLanguageTag("te-IN")
                "ur", "ur-in", "urdu" -> Locale.forLanguageTag("ur-IN")
                "ar", "ar-sa", "arabic", "saudi" -> Locale.forLanguageTag("ar-SA")
                "en-us", "american" -> Locale.forLanguageTag("en-US")
                "en-ca", "canadian" -> Locale.forLanguageTag("en-CA")
                "en-ng", "nigerian" -> Locale.forLanguageTag("en-NG")
                else -> Locale.forLanguageTag("en-IN")
            }
            assistantTts?.let { tts ->
                tts.setAudioAttributes(
                    AudioAttributes.Builder()
                        .setUsage(AudioAttributes.USAGE_MEDIA)
                        .setContentType(AudioAttributes.CONTENT_TYPE_SPEECH)
                        .build()
                )
                val languageResult = tts.setLanguage(requestedLocale)
                if (
                    languageResult == TextToSpeech.LANG_MISSING_DATA ||
                    languageResult == TextToSpeech.LANG_NOT_SUPPORTED
                ) {
                    tts.setLanguage(Locale.forLanguageTag("en-IN"))
                }
                tts.setPitch(1.0f)
                tts.setSpeechRate(0.96f)
                tts.speak(
                    text,
                    TextToSpeech.QUEUE_FLUSH,
                    null,
                    "assistant-${System.currentTimeMillis()}"
                )
            }
        }

        val current = assistantTts
        if (current == null) {
            assistantTts = TextToSpeech(this) { status ->
                if (status == TextToSpeech.SUCCESS) {
                    speak()
                }
            }
        } else {
            speak()
        }
    }

    private fun prepareUrgentOrderAudio() {
        val audioManager = getSystemService(Context.AUDIO_SERVICE) as AudioManager

        if (previousAudioMode == null) {
            previousAudioMode = audioManager.mode
            if (Build.VERSION.SDK_INT < Build.VERSION_CODES.S) {
                @Suppress("DEPRECATION")
                previousSpeakerphone = audioManager.isSpeakerphoneOn
            }
            previousAlarmVolume = audioManager.getStreamVolume(AudioManager.STREAM_ALARM)
            previousNotificationVolume =
                audioManager.getStreamVolume(AudioManager.STREAM_NOTIFICATION)
            previousMusicVolume = audioManager.getStreamVolume(AudioManager.STREAM_MUSIC)
            previousRingerMode = audioManager.ringerMode
        }

        setMaxVolume(audioManager, AudioManager.STREAM_ALARM)
        setMaxVolume(audioManager, AudioManager.STREAM_NOTIFICATION)
        setMaxVolume(audioManager, AudioManager.STREAM_MUSIC)
        setRingerNormalIfAllowed(audioManager)

        audioManager.mode = AudioManager.MODE_IN_COMMUNICATION

        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.S) {
            val speaker = audioManager.availableCommunicationDevices.firstOrNull {
                it.type == AudioDeviceInfo.TYPE_BUILTIN_SPEAKER
            }
            if (speaker != null) {
                audioManager.setCommunicationDevice(speaker)
            }
        } else {
            @Suppress("DEPRECATION")
            audioManager.isSpeakerphoneOn = true
        }
    }

    private fun restoreNormalAudio() {
        val audioManager = getSystemService(Context.AUDIO_SERVICE) as AudioManager

        previousAlarmVolume?.let {
            audioManager.setStreamVolume(AudioManager.STREAM_ALARM, it, 0)
        }
        previousNotificationVolume?.let {
            audioManager.setStreamVolume(AudioManager.STREAM_NOTIFICATION, it, 0)
        }
        previousMusicVolume?.let {
            audioManager.setStreamVolume(AudioManager.STREAM_MUSIC, it, 0)
        }

        previousRingerMode?.let { mode ->
            try {
                if (canChangeNotificationPolicy()) {
                    audioManager.ringerMode = mode
                }
            } catch (_: SecurityException) {
            }
        }

        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.S) {
            audioManager.clearCommunicationDevice()
        }

        if (Build.VERSION.SDK_INT < Build.VERSION_CODES.S) {
            previousSpeakerphone?.let {
                @Suppress("DEPRECATION")
                audioManager.isSpeakerphoneOn = it
            }
        }
        previousAudioMode?.let { audioManager.mode = it }

        previousRingerMode = null
        previousAudioMode = null
        previousSpeakerphone = null
        previousAlarmVolume = null
        previousNotificationVolume = null
        previousMusicVolume = null
    }

    private fun setMaxVolume(audioManager: AudioManager, stream: Int) {
        val maxVolume = audioManager.getStreamMaxVolume(stream)
        audioManager.setStreamVolume(stream, maxVolume, 0)
    }

    private fun setRingerNormalIfAllowed(audioManager: AudioManager) {
        try {
            if (canChangeNotificationPolicy()) {
                audioManager.ringerMode = AudioManager.RINGER_MODE_NORMAL
            }
        } catch (_: SecurityException) {
        }
    }

    private fun canChangeNotificationPolicy(): Boolean {
        if (Build.VERSION.SDK_INT < Build.VERSION_CODES.M) return true
        val notificationManager =
            getSystemService(Context.NOTIFICATION_SERVICE) as NotificationManager
        return notificationManager.isNotificationPolicyAccessGranted
    }
}
