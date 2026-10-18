"""Vector store access. Entitlement filtering happens HERE, before any text reaches the LLM."""
import chromadb

from .config import get_settings


def collection():
    s = get_settings()
    client = chromadb.HttpClient(host=s.chroma_host, port=s.chroma_port)
    return client.get_or_create_collection("reports", metadata={"hnsw:space": "cosine"})


def search(query_vec: list[float], entitled: list[str], k: int) -> list[dict]:
    if not entitled:
        return []
    res = collection().query(
        query_embeddings=[query_vec],
        n_results=k,
        where={"report_id": {"$in": entitled}},
        include=["documents", "metadatas", "distances"],
    )
    hits = []
    for doc, meta, dist in zip(res["documents"][0], res["metadatas"][0], res["distances"][0]):
        hits.append({"text": doc, "meta": meta, "distance": dist})
    return hits
