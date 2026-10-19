from functools import lru_cache

from pydantic import AnyHttpUrl, Field
from pydantic_settings import BaseSettings, SettingsConfigDict


class Settings(BaseSettings):
    model_config = SettingsConfigDict(env_file=".env", env_file_encoding="utf-8")

    app_env: str = "production"
    ai_api_secret: str = Field(default="", repr=False)
    public_base_url: str = "http://127.0.0.1:8080"

    laravel_api_url: AnyHttpUrl
    laravel_ai_secret: str = Field(default="", repr=False)

    redis_url: str = "redis://127.0.0.1:6379/2"
    session_ttl_seconds: int = 1800
    max_concurrent_sessions: int = 10
    settings_cache_ttl_seconds: int = 30

    llm_base_url: str = "http://127.0.0.1:8001/v1"
    llm_api_key: str = Field(default="local", repr=False)
    llm_model: str = "Qwen/Qwen3-8B-AWQ"
    llm_context_tokens: int = 8192

    whisper_model: str = "large-v3-turbo"
    whisper_device: str = "cuda"
    whisper_compute_type: str = "float16"

    tts_model: str = "kokoro"
    tts_voice: str = "af_heart"

    audio_sample_rate: int = 16000
    audio_channels: int = 1
    audio_sample_width_bytes: int = 2

    log_level: str = "INFO"


@lru_cache
def get_settings() -> Settings:
    return Settings()

