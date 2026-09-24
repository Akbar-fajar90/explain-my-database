# 🧠 ExplainMyDatabase

> An intelligent, AI-powered database schema analyzer built with **Laravel**, **FastAPI**, and **TailwindCSS**. Upload your database schema (SQL) and instantly get comprehensive ERDs, relationship explanations, normalization analysis, potential issues, index recommendations, and migration documentation!

---

## 🌟 Features

- **📂 SQL Schema Parsing**: Upload schema dumps or SQL definitions easily.
- **📊 Interactive ERDs**: Powered by Mermaid.js for gorgeous entity-relationship diagrams.
- **🤖 AI-Powered Relationship Analysis**: Deep dive into table relationships and cardinalities.
- **🔍 Normalization Checks**: Automated analysis for 1NF, 2NF, 3NF compliance.
- **⚠️ Potential Issues & Performance**: Spot anti-patterns, missing indexes, and foreign key mismatches.
- **📝 Migration Documentation**: Auto-generated documentation and migration snippets.

---

## 🏗️ Architecture

```
                 ┌───────────────────────────┐
                 │     Browser / Client      │
                 └─────────────┬─────────────┘
                               │
                       HTTP (Tailwind / JS)
                               ▼
                 ┌───────────────────────────┐
                 │     Laravel Application    │
                 │   (Views, Controllers)    │
                 └─────────────┬─────────────┘
                               │
                           REST API
                               ▼
                 ┌───────────────────────────┐
                 │     FastAPI Microservice   │
                 │  (AI / Parsing / Analysis)│
                 └───────────────────────────┘
```

---

## 🚀 Quick Start (Docker)

1. Clone the repository:
   ```bash
   git clone https://github.com/Akbar-fajar90/explainmydatabase.git
   cd explainmydatabase
   ```
2. Build and run containers:
   ```bash
   docker-compose up --build -d
   ```
3. Access the dashboard:
   - **Laravel App**: [http://localhost:8000](http://localhost:8000)
   - **FastAPI Docs**: [http://localhost:8000/docs](http://localhost:8000/docs)

---

## 🛠️ Tech Stack

- **Backend**: Laravel 11, FastAPI (Python)
- **Frontend**: TailwindCSS, Alpine.js / Blade, Mermaid.js
- **Containerization**: Docker & Docker Compose

---

## 📄 License

MIT License.
