"""All configuration comes from environment variables (see .env.example)."""
from functools import lru_cache

from pydantic_settings import BaseSettings, SettingsConfigDict


class Settings(BaseSettings):
    model_config = SettingsConfigDict(env_file=".env", extra="ignore")

    api_key: str
    gemini_api_key: str
    llm_model: str = "gemini-2.5-flash"
    embed_model: str = "gemini-embedding-001"
    embed_dim: int = 768

    mysql_host: str = "mysql"
    mysql_port: int = 3306
    mysql_database: str = "atlas"
    mysql_app_user: str = "atlas_app"
    mysql_app_password: str
    mysql_ro_user: str = "atlas_ro"
    mysql_ro_password: str

    chroma_host: str = "chroma"
    chroma_port: int = 8000

    reports_dir: str = "/app/data/reports"
    top_k: int = 6
    max_output_tokens: int = 900
    request_timeout_s: int = 25
    escalation_threshold: float = 0.35


@lru_cache
def get_settings() -> Settings:
    return Settings()  # type: ignore[call-arg]
