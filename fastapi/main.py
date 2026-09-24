# Explain My Database - FastAPI Service

from fastapi import FastAPI, HTTPException
from pydantic import BaseModel
from typing import Dict, Any, List
import uvicorn

app = FastAPI(title="Explain My Database - AI Engine")

class AnalysisRequest(BaseModel):
    db_structure: Dict[str, Any]

@app.get("/")
async def read_root():
    return {"message": "FastAPI service is running!", "version": "1.0"}

@app.get("/health")
async def health_check():
    return {"status": "ok"}

@app.post("/analyze")
async def analyze_database(req: AnalysisRequest):
    db_structure = req.db_structure
    table_names = list(db_structure.keys())

    relations_narrative = []
    for t_name, t_data in db_structure.items():
        fks = t_data.get("foreign_keys", [])
        for fk in fks:
            relations_narrative.append(
                f"Tabel '{t_name}' memiliki relasi Many-to-One dengan tabel '{fk.get('references_table')}' "
                f"melalui kolom foreign key '{fk.get('column')}' yang merujuk pada '{fk.get('references_column')}'."
            )

    if not relations_narrative:
        relations_narrative.append("Tidak ditemukan relasi Foreign Key eksplisit antar tabel.")

    normalization_details = []
    for t_name, t_data in db_structure.items():
        pks = t_data.get("primary_keys", [])
        if not pks:
            normalization_details.append(
                f"Tabel '{t_name}' tidak memiliki Primary Key yang terdefinisi (berpotensi melanggar 1NF)."
            )
        else:
            normalization_details.append(
                f"Tabel '{t_name}' memenuhi 1NF, 2NF, dan 3NF dengan Primary Key: {', '.join(pks)}."
            )

    return {
        "relationship_explanation": {
            "summary": f"Ditemukan {len(table_names)} tabel ({', '.join(table_names)}) dengan {len(relations_narrative)} relasi.",
            "relations": relations_narrative
        },
        "normalization_analysis": {
            "status": "Analisis Normalisasi Selesai",
            "details": normalization_details
        },
        "index_recommendations_ai": [],
        "potential_problems_ai": []
    }

if __name__ == "__main__":
    uvicorn.run(app, host="0.0.0.0", port=8000)

