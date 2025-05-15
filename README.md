# LMS Platform (Laravel + React + Azure DevOps)

A minimal portfolio-ready project showcasing a Laravel API backend, a lightweight React (Vite) frontend, and a simple Azure DevOps CI pipeline. No Docker. Minimal dependencies. Mostly JavaScript.

## Stack

- Backend: Laravel 10 (PHP 8.2), Composer
- Frontend: React 18 + Vite (JavaScript)
- CI: Azure DevOps Pipelines (`azure-pipelines.yml`)

## Run locally (Windows)

Option A — Quick script

1. Install: PHP 8.2+, Composer, Node.js 18+
2. In PowerShell, run:
```pwsh
./start-local.ps1
```
This will install deps and start: Laravel at http://127.0.0.1:8000 and Vite at http://127.0.0.1:3000.

Option B — Manual

Backend
```pwsh
cd backend
composer install
copy .env.example .env
php artisan key:generate
php artisan serve --host=127.0.0.1 --port=8000
```

Frontend (new terminal)
```pwsh
cd frontend
npm install
npm run dev
```

## Verify

- API: `GET http://127.0.0.1:8000/api/test` returns a small JSON payload.
- UI: Open http://127.0.0.1:3000 — it calls `/api/test` via Vite proxy.

## Azure DevOps CI

The pipeline builds backend and frontend (no deploy): see `azure-pipelines.yml`.

## Notes (AI assistance)

Used GitHub Copilot and Sweep AI to speed up scaffolding, small code snippets, and review suggestions.

## Project goal

Keep it fresh, minimal, and focused on the title: Laravel + React + Azure DevOps.
