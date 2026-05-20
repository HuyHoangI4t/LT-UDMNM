# TNU Admission Chatbot

Laravel + React application for an admission consulting chatbot. The backend stores crawled admission knowledge, normalizes major data, calls Gemini for answers, and records chat analytics. The frontend provides the chat UI for candidates.

## Project Structure

```text
backend/   Laravel API, chatbot services, database migrations, import commands
frontend/  React chat UI and crawled admission source files
run.bat    Local setup/start helper for Windows
```

## Backend

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

Important environment variables:

```env
GEMINI_API_KEY=
GEMINI_MODEL=gemini-2.5-flash
GEMINI_TIMEOUT=60
GEMINI_TEMPERATURE=0.2
GEMINI_TOP_P=0.8
GEMINI_TOP_K=40

AI_ENABLE_EMBEDDING_SEARCH=false
GEMINI_EMBEDDING_MODEL=gemini-embedding-001
EMBEDDING_LOCAL_PREFILTER_LIMIT=200
VECTOR_STORE_DRIVER=local
CHAT_CACHE_TTL_SECONDS=3600

ADMIN_API_AUTH=true
ADMIN_NAME=Administrator
ADMIN_EMAIL=admin@example.com
ADMIN_PASSWORD=admin123456
```

Production PHP extensions checklist:

```text
intl, mbstring, openssl, pdo_mysql, tokenizer, xml, ctype, json, fileinfo
```

Keep `APP_DEBUG=false` and `ADMIN_API_AUTH=true` in production. Dashboard, chat log, and export routes are admin surfaces and should not be public.

Useful commands:

```bash
php artisan knowledge:import ../frontend/src/ttn_data/ttn_all_data.json
php artisan import:pdf-extracted ../frontend/src/ttn_data/pdf_extracted.json
php artisan admission:majors-normalize --year=2026
php artisan embedding:knowledge --limit=100
php artisan faq:generate --limit=50
php artisan db:seed
php artisan test
```

If `admission_majors.subject_groups` was imported before the normalization fix and contains nested JSON strings, rerun:

```bash
php artisan admission:majors-normalize --year=2025
```

Use the correct year for the current dataset. Multi-year score answers require importing one normalized dataset per admission year.

## Frontend

```bash
cd frontend
npm install
cp .env.example .env
npm start
```

Optional frontend `.env`:

```env
REACT_APP_API_URL=http://127.0.0.1:8000/api
```

## Main API

| Method | Endpoint | Purpose |
| --- | --- | --- |
| GET | `/api/health` | Backend health check |
| POST | `/api/login` | Admin login, returns Sanctum token |
| GET | `/api/me` | Current admin profile |
| POST | `/api/logout` | Revoke current token |
| POST | `/api/register` | Create another admin account |
| POST | `/api/chat` | Ask chatbot |
| GET | `/api/faq-questions` | Suggested questions |
| GET | `/api/chat-logs` | Chat logs |
| GET | `/api/chat-logs/{id}` | Chat log detail |
| DELETE | `/api/chat-logs/{id}` | Delete chat log |
| CRUD | `/api/knowledge-bases` | Admin CMS for RAG knowledge |
| CRUD | `/api/admission-majors` | Admin CMS for admission major data |
| GET | `/api/dashboard/overview` | Dashboard totals |
| GET | `/api/dashboard/top-majors` | Most asked majors |
| GET | `/api/dashboard/questions-by-intent` | Intent statistics |
| GET | `/api/dashboard/questions-by-day` | Daily statistics |

Set `ADMIN_API_AUTH=true` to protect admin, CMS, chat log, and dashboard routes with Sanctum.

## Chatbot Flow

1. React sends `message`, `platform`, and recent `history` to `/api/chat`.
2. Backend analyzes intent, major, year, and category.
3. Backend retrieves context from `admission_majors` and `knowledge_bases`.
4. Optional embedding search can add semantic matches when enabled. Local mode uses `EMBEDDING_LOCAL_PREFILTER_LIMIT` before cosine scoring in PHP; set `VECTOR_STORE_DRIVER` to document the target indexed store when moving to Qdrant, FAISS, pgvector, or another vector backend.
5. Gemini receives the system prompt, retrieved context, recent history, and user question.
6. Backend stores the interaction in `chat_logs`.
7. Frontend renders the answer and fetches related FAQ suggestions.

## Database

Main tables:

- `chat_logs`: conversation analytics and answers.
- `knowledge_bases`: website/PDF knowledge chunks and optional embeddings.
- `admission_majors`: normalized major data such as code, subject groups, scores, quota, tuition.
- `faq_questions`: generated suggested questions.
