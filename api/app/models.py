from pydantic import BaseModel, Field


class ChatRequest(BaseModel):
    client_id: str = Field(pattern=r"^C-\d{2,6}$", examples=["C-01"])
    question: str = Field(min_length=3, max_length=1000)


class Citation(BaseModel):
    report_id: str
    title: str
    page: int
    snippet: str


class ChatResponse(BaseModel):
    answer: str
    citations: list[Citation] = []
    confidence: float = Field(ge=0.0, le=1.0)
    conflicts: list[dict] = []
    derived_values: list[dict] = []
    escalated: bool = False
    escalation_id: int | None = None
