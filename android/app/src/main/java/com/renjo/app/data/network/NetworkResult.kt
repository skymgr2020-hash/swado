package com.renjo.app.data.network

import kotlinx.serialization.Serializable
import kotlinx.serialization.json.Json
import retrofit2.HttpException

sealed class NetworkResult<out T> {
    data class Success<T>(val data: T) : NetworkResult<T>()
    data class Error(val message: String, val cause: Throwable? = null) : NetworkResult<Nothing>()
}

@Serializable
private data class ErrorEnvelope(val success: Boolean = false, val message: String? = null)

private val errorJson = Json { ignoreUnknownKeys = true }

suspend fun <T> safeApiCall(block: suspend () -> T): NetworkResult<T> = try {
    NetworkResult.Success(block())
} catch (t: Throwable) {
    NetworkResult.Error(t.toUserMessage(), t)
}

private fun Throwable.toUserMessage(): String {
    if (this is HttpException) {
        val body = response()?.errorBody()?.string()
        val parsedMessage = body?.let {
            runCatching { errorJson.decodeFromString<ErrorEnvelope>(it).message }.getOrNull()
        }
        if (!parsedMessage.isNullOrBlank()) return parsedMessage
    }
    return message ?: "Something went wrong. Please try again."
}
