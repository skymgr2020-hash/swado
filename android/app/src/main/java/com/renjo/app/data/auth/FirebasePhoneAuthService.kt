package com.renjo.app.data.auth

import android.app.Activity
import com.google.firebase.FirebaseException
import com.google.firebase.auth.FirebaseAuth
import com.google.firebase.auth.PhoneAuthCredential
import com.google.firebase.auth.PhoneAuthOptions
import com.google.firebase.auth.PhoneAuthProvider
import kotlinx.coroutines.suspendCancellableCoroutine
import kotlinx.coroutines.tasks.await
import java.util.concurrent.TimeUnit
import javax.inject.Inject
import javax.inject.Singleton
import kotlin.coroutines.resume

sealed interface PhoneAuthStartResult {
    data class CodeSent(val verificationId: String) : PhoneAuthStartResult
    /** Instant/auto verification — mainly fires for Firebase test phone numbers. */
    data class AutoVerified(val idToken: String) : PhoneAuthStartResult
    data class Failed(val message: String) : PhoneAuthStartResult
}

/**
 * Wraps Firebase Phone Auth (used when GET /app/branding returns
 * otp_service_provider="firebase" — see docs/api-contract/auth.md). Holds the
 * in-flight verificationId between the phone-entry and code-verify screens
 * since it's a single linear flow — no need to thread it through nav args.
 */
@Singleton
class FirebasePhoneAuthService @Inject constructor() {
    private val auth: FirebaseAuth by lazy { FirebaseAuth.getInstance() }
    private var verificationId: String? = null
    private var resendToken: PhoneAuthProvider.ForceResendingToken? = null

    suspend fun startVerification(activity: Activity, phoneNumber: String): PhoneAuthStartResult =
        suspendCancellableCoroutine { cont ->
            val callbacks = object : PhoneAuthProvider.OnVerificationStateChangedCallbacks() {
                override fun onVerificationCompleted(credential: PhoneAuthCredential) {
                    auth.signInWithCredential(credential)
                        .addOnSuccessListener { result ->
                            result.user?.getIdToken(false)
                                ?.addOnSuccessListener { tokenResult ->
                                    if (cont.isActive) {
                                        cont.resume(PhoneAuthStartResult.AutoVerified(tokenResult.token.orEmpty()))
                                    }
                                }
                                ?.addOnFailureListener {
                                    if (cont.isActive) cont.resume(PhoneAuthStartResult.Failed(it.message ?: "Verification failed."))
                                }
                        }
                        .addOnFailureListener {
                            if (cont.isActive) cont.resume(PhoneAuthStartResult.Failed(it.message ?: "Verification failed."))
                        }
                }

                override fun onVerificationFailed(e: FirebaseException) {
                    if (cont.isActive) cont.resume(PhoneAuthStartResult.Failed(e.message ?: "Verification failed."))
                }

                override fun onCodeSent(id: String, token: PhoneAuthProvider.ForceResendingToken) {
                    verificationId = id
                    resendToken = token
                    if (cont.isActive) cont.resume(PhoneAuthStartResult.CodeSent(id))
                }
            }

            val optionsBuilder = PhoneAuthOptions.newBuilder(auth)
                .setPhoneNumber(phoneNumber)
                .setTimeout(60L, TimeUnit.SECONDS)
                .setActivity(activity)
                .setCallbacks(callbacks)
            resendToken?.let { optionsBuilder.setForceResendingToken(it) }
            PhoneAuthProvider.verifyPhoneNumber(optionsBuilder.build())
        }

    /** Returns the Firebase ID token to hand to the backend's phone-verify/login-with-phone endpoints. */
    suspend fun confirmCode(code: String): String {
        val id = verificationId ?: error("No verification in progress — send a code first.")
        val credential = PhoneAuthProvider.getCredential(id, code)
        val result = auth.signInWithCredential(credential).await()
        val token = result.user?.getIdToken(false)?.await()?.token
            ?: error("Couldn't obtain a verification token.")
        // The token has round-tripped to our backend already once returned;
        // no need to keep a parallel Firebase Auth session alongside our own.
        auth.signOut()
        verificationId = null
        resendToken = null
        return token
    }
}
