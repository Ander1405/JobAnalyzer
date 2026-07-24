# JobHunter

A personal, local-only job search automation tool. It fetches job offers (JSearch/RapidAPI, LaraJobs RSS, optionally InfoJobs), analyzes each one against your profile with an AI provider of your choice, and walks you through a full application workflow — a Marketplace to discover and shortlist offers, a Kanban-style tracker for the ones you apply to, and a profile module with CV tailoring and ATS scoring — while backing up qualifying offers to Notion.

Everything runs on your machine — there is no cloud deployment, no scraping of LinkedIn/Indeed. The default AI provider (`claude_cli`) shells out to your local `claude` CLI session, so analysis costs $0 beyond your existing Claude subscription. The app is single-user and sits behind a login screen (see "Authentication" below).

## Setup

### 1. Install dependencies

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
```

`db:seed` creates the single login user from `OWNER_NAME`/`OWNER_EMAIL`/`OWNER_PASSWORD` in `.env` (defaults to `owner@example.com` / `password` if left unset) — **change these in `.env` before seeding** if this isn't just a local trial. Re-running the seeder is safe; it won't duplicate the user or touch its password once created (update the password from `/profile` → Seguridad instead).

`storage/app/perfil.md` already ships with a starter profile so the app works out of the box — replace it with your real background by uploading your CV from `/profile` → Mi CV (see "CV & profile" below). Whichever profile variant is active is the single source of truth the AI uses for matching.

### 2. Configure job sources

- **RapidAPI (JSearch)**: sign up at [rapidapi.com](https://rapidapi.com), subscribe to the JSearch API, and put your key in `RAPIDAPI_KEY`.
- **LaraJobs**: no configuration needed, it's a public RSS feed.
- **InfoJobs** (optional): disabled by default (`INFOJOBS_ENABLED=false`). Set it to `true` and provide `INFOJOBS_CLIENT_ID`/`INFOJOBS_CLIENT_SECRET` once you have official API credentials.
- Adjust `JOB_SEARCH_QUERIES` (comma-separated) and `JOB_SEARCH_COUNTRY` to your search criteria.

### 3. Configure the AI provider

`AI_PROVIDER` selects the implementation (`claude_cli` | `gemini` | `openrouter`).

- **`claude_cli` (default, $0)**: uses your local `claude` CLI session, no API key needed. Verify it works before running the pipeline:
  ```bash
  claude --version
  claude -p "say hello"
  ```
  If that fails, log in with the Claude CLI first. Optionally set `CLAUDE_CLI_MODEL` to pin a specific model.
- **`gemini`**: get a free-tier key at [Google AI Studio](https://aistudio.google.com) and set `GEMINI_API_KEY`.
- **`openrouter`**: get a key at [openrouter.ai](https://openrouter.ai) and set `OPENROUTER_API_KEY` + `OPENROUTER_MODEL` (use a model with a `:free` suffix to stay at $0).

The `.env` values above only seed the *first* run. Provider and model can be switched live from the UI (top of the Marketplace page) — that selection is persisted to the database and takes effect on the next analysis, no restart needed. The model dropdown is populated from each provider's real model list (a curated static list for Claude CLI, live API lookups for Gemini/OpenRouter), with a filter box and free/pricing badges.

### 4. Configure Notion (backup destination)

1. Create an integration at [notion.so/my-integrations](https://www.notion.so/my-integrations) and copy its token into `NOTION_TOKEN`.
2. Create a database in Notion and share it with your integration (`···` menu → *Connections* → add your integration).
3. Copy the database ID from its URL (`notion.so/<workspace>/<DATABASE_ID>?v=...`) into `NOTION_DATABASE_ID`.
4. Create these exact properties on the database (name and type must match exactly):

   | Property | Type |
   |---|---|
   | `Cargo` | Title |
   | `Empresa` | Rich text |
   | `Fuente` | Select |
   | `URL` | URL |
   | `Match %` | Number |
   | `Tipo de contrato` | Select |
   | `Salario` | Rich text |
   | `Moneda` | Select |
   | `Idioma` | Select |
   | `Inglés requerido` | Select |
   | `Alerta inglés` | Checkbox |
   | `Estado` | Select — options: `Nueva`, `CV adaptado`, `Aplicada`, `Entrevista`, `Cerrada` |
   | `Fecha detectada` | Date |

   New pages are always created with `Estado = Nueva`; JobHunter never updates a page after creating it, so anything beyond that is tracked manually in Notion. Only jobs at or above `MIN_MATCH_TO_PUBLISH` (see below) are sent to Notion at all — everything else stays local, still visible in the UI.

## Running it

Start both processes:

```bash
php artisan serve
npm run dev
```

Then open `http://127.0.0.1:8000` and log in with the owner credentials from `.env` (see "Authentication" below). You'll land on `/marketplace`.

### Navigation

The app is a single-page client (Inertia serves one shell page; [vue-router](https://router.vuejs.org) handles routing between modules against the existing JSON API) with a persistent sidebar and three modules:

| Sidebar item | Route | Purpose |
|---|---|---|
| 🔍 Marketplace | `/marketplace`, `/marketplace/:id` | Every fetched/analyzed offer — filter, sort, bulk-select, and shortlist the ones worth tracking. |
| 📌 Mis vacantes | `/tracking`, `/tracking/:id` | Only the offers you've shortlisted, as a Kanban board (drag between `Sin aplicar → Apliqué → En proceso → Rechazado/Oferta`) or a list, with a per-offer timeline of comments and automatic status-change entries. |
| 👤 Mi perfil | `/profile` | Tabs for personal data, CV management (upload/edit/AI review), ATS scoring, and account security. |

**Typical flow:** browse/filter the Marketplace → click ☆ on an offer (or bulk-select several) to shortlist it → it now appears in Mis vacantes as `Sin aplicar` → drag it to `Apliqué` once you've applied (this stamps `applied_at` and logs an automatic timeline entry) → keep the bitácora (comments/next actions) updated as the process moves along.

Filters, the Marketplace grid/table toggle, the tracking Kanban/list toggle, and the sidebar's collapsed state are all persisted to `localStorage`, so your view survives a page reload.

### Authentication

Login is hand-rolled (not Laravel Breeze's scaffolding, to avoid clobbering this app's Vue/Tailwind/routing setup) and intentionally minimal for a single-user, locally-run app: session-based login/logout at `/login`, route protection on both `web` and `api` groups, and a password-change form under `/profile` → Seguridad. There is no public registration, email verification, or password-reset-by-email — none of that applies when there's exactly one user and no mailer configured. If you forget the password, reset it directly in the database or re-run the seeder after clearing the `password` column.

### Console commands

| Command | Purpose |
|---|---|
| `php artisan jobs:fetch` | Pull new offers from all enabled sources, dedupe by hash. |
| `php artisan jobs:analyze` | Analyze all `fetched` jobs with the configured AI provider. |
| `php artisan jobs:publish` | Publish `analyzed` jobs at or above `MIN_MATCH_TO_PUBLISH` to Notion (others are skipped and reported separately). |
| `php artisan jobs:run` | Run fetch → analyze → publish in sequence and print a summary highlighting matches at or above `MATCH_SCORE_ALERT_THRESHOLD`. |
| `php artisan cv:import {path} [--slug=default]` | Parse a CV file (pdf/txt/md) deterministically — no AI — and store it as a profile. |
| `php artisan profile:sync` | Re-parse a hand-edited `storage/app/perfil.md` back into the active profile's structured fields. |

The Marketplace's "Buscar nuevas" and "Analizar pendientes" buttons, plus the job detail page's "Publicar en Notion" action, call the same logic through the local `/api/jobs*` and `/api/marketplace/*` endpoints (see "API endpoints" below). Unlike `php artisan jobs:analyze`, the "Analizar pendientes" button doesn't call the AI provider synchronously in the web request — it dispatches an `AnalyzeJobListing` queue job per pending offer (job status becomes `analyzing` immediately) and the UI polls until they resolve. This matters if you're serving the app through Valet/nginx+PHP-FPM (or any other daemon-managed web server): with `claude_cli`, macOS denies Keychain access to processes spawned by a daemon rather than an interactive terminal session, so `claude` reports "Not logged in" even when you're logged in everywhere else. Running the queue worker yourself from a normal terminal session sidesteps that:

```bash
php artisan queue:work --queue=analysis,default
```

Both `AnalyzeJobListing` and `TailorProfile` (see below) run on the `analysis` queue, not the default one — a bare `php artisan queue:work` only drains `default` and silently leaves them stuck. `composer dev`/`php artisan dev` don't need this: `AppServiceProvider::configureAnalysisWorkers()` already spawns `JOBHUNTER_ANALYSIS_WORKERS` (default 4) dedicated `queue:work --queue=analysis` processes for you.

Leave it running while you use the Marketplace. If you don't run a worker, queued analyses stay stuck in `analyzing` forever. `php artisan jobs:analyze`/`jobs:run` are unaffected either way — they call the AI provider directly from whatever terminal session you invoke them in, so `claude_cli` works out of the box there.

CV tailoring (job detail page, "Aplicar ajustes de tailoring") follows the same pattern: `ProfileTailorController::preview()` dispatches a `TailorProfile` queue job (also on the `analysis` queue) instead of calling the AI provider inline, and the frontend polls `GET /api/profile/tailor/{request_id}` until it reports `completed` or `failed`. It needs the same worker above to resolve with `claude_cli`.

Every analysis reports how much it actually cost: the job detail page shows the provider/model used, elapsed time, input/output tokens, and cost in USD (or "Gratis / N/A" when a provider — Gemini today — doesn't report a cost). Running "Analizar pendientes" also shows a summary banner with the totals for the whole batch once every job in it finishes.

### CV & profile

Visit `/profile` to manage your profile, organized into four tabs: **Datos personales**, **Mi CV**, **Optimización ATS**, and **Seguridad**. Everything there is backed by a `profiles` table (slug, structured fields, and a rendered `raw_md`) — `storage/app/perfil.md` always mirrors whichever profile is currently active, since that's the file `jobs:analyze` reads.

**Uploading a CV** (Mi CV tab). Upload a PDF, TXT or MD file, or `php artisan cv:import path/to/cv.pdf`. **Parsing never calls the AI layer** — it's all deterministic: `smalot/pdfparser` (falling back to `pdftotext` via Symfony Process if the PDF has no clean text layer, if installed) extracts raw text, then regex/heuristics split it into sections by detecting common Spanish/English CV headers (Resumen, Experiencia, Skills, Educación, Idiomas, Certificaciones) and pull out contact info (email, phone, LinkedIn, GitHub) and the CEFR English level declared under "Idiomas" (e.g. "Inglés B2"). A scanned PDF with no extractable text returns a clear error instead of silently producing nothing — no OCR, no AI fallback. This always creates/overwrites the `default` profile.

**Editing** (Datos personales tab). A form for every structured field (contact, headline, summary, experience/skills/education/certifications as editable lists, languages with an English-level dropdown) — saving regenerates the Markdown. The Mi CV tab also has a raw-Markdown textarea with a "Sincronizar" button: it writes your edits to `storage/app/perfil.md` and re-parses them back into structure by the same `##` headers `cv:import` produces, so hand-editing the file directly (e.g. `vim storage/app/perfil.md && php artisan profile:sync`) works exactly the same way. Only the currently *active* profile can be synced this way. Expected format:

```markdown
# <headline>

## Contacto
- Nombre: ...
- Email: ...

## Resumen
...

## Experiencia
- ...

## Skills
- ...

## Educación
- ...

## Idiomas
- Español nativo
- Inglés B2

## Certificaciones
- ...
```

**Variants.** A variant (e.g. `backend`, `lead`) is a copy of `default` that can reorder or trim fields for a specific kind of role — it can never contain content that isn't already in the real CV, since creating one always clones from `default` first. Only one profile is active at a time (`ACTIVE_PROFILE` seeds the initial slug); switching the active variant immediately changes what `jobs:analyze` matches against.

**English alert & red flags.** Every analysis now also returns `ingles_requerido` and `alerta_ingles` (true when a vacancy asks for more English than the active profile's declared level) and `red_flags` (concrete CV/vacancy mismatches, never generic filler) — shown on the job detail page and synced to Notion.

**AI review (opt-in, unlike parsing, Mi CV tab).** Parsing a CV never calls the AI, but you can explicitly ask the AI to help improve it: the "Revisión con IA" section compares the profile's stored original CV text (`source_text`, saved at import time) against the deterministically-parsed fields, and proposes discrete suggestions — corrections (content the parser dropped, split, or mis-transcribed) and improvements (wording, quantified impact) — each with a rationale. **Nothing is applied automatically**: check the suggestions you approve and click "Aplicar seleccionadas"; only that subset is applied, deterministically, to the profile's fields (`raw_md`/`perfil.md` regenerated), with no further AI call in the loop. Profiles imported before this feature has no stored `source_text`, so re-import the CV once to enable it.

**CV tailoring (opt-in, per job offer).** On a job's detail page, the AI-generated "Tailoring del CV" section lists concrete adjustments (what to reorder, reword, or emphasize) for that specific offer. Check the ones you want, click "Aplicar seleccionados a mi CV", and the AI rewrites just those fields — with a hard rule to only reorder/reformulate/highlight content already in your CV, never invent experience, technologies, or achievements. You get a side-by-side line diff to review before anything is saved; confirming creates a **new profile variant** named after the company and role (e.g. `acme-backend-engineer`) via `ProfileVariantService` — the base profile is never touched, so you can tailor for as many offers as you like without losing your canonical CV.

**ATS optimization (Optimización ATS tab).** Click "Analizar con IA" to score the active profile against standard ATS compatibility criteria (single column, standard headers, no tables/images, consistent dates, keyword coverage, action verbs, quantified achievements, etc.). Returns a 0–100 score, prioritized problems, missing keywords, format recommendations, and a fully rewritten CV you can preview as a diff and save as a new variant — same non-destructive pattern as tailoring. **No CV is bulletproof against every ATS**; this is guidance, not a guarantee of interview callbacks.

**Changing your password (Seguridad tab).** Requires the current password, a new one (min. 8 characters), and confirmation — see "API endpoints" below.

### Optional: daily schedule

`routes/console.php` has a commented-out entry to run `jobs:run` daily at 07:00 (`America/Bogota`). Uncomment it and keep `php artisan schedule:work` running (or a system cron calling `schedule:run` every minute) if you want it to run unattended.

## Data model notes

- Job offers live in a `job_offers` table (not `jobs`), because Laravel's own queue system already owns a table named `jobs` (`QUEUE_CONNECTION=database`).
- `status` (`fetched` → `analyzing` → `analyzed` → `published`, or `failed`) tracks the pipeline stage; `analyzing` only appears while the `AnalyzeJobListing` queue job is in flight (the console commands skip it and go straight to `analyzed`/`failed`). `application_status` (`Nueva`, `CV adaptado`, `Aplicada`, `Entrevista`, `Cerrada`) is a separate, UI-editable field for tracking where you are in the actual application process — it's local-only and isn't synced back to Notion.
- `job_offers` also carries enriched fields populated by the fetchers (falling back to AI-inferred values when a source doesn't provide them): `apply_url` (always non-null — falls back to the listing `url`), `location`, `is_remote`, `work_mode`, `seniority`, `employment_type`, `posted_at`, `expires_at`, `company_logo`, `company_website`, `benefits` (json), `required_skills` (json), `applicants_count`.
- Shortlisting an offer creates a row in `tracked_jobs` (one per job, `status` starting at `sin_aplicar`, plus `priority`, `applied_at`, `cv_version_used`, `next_action`/`next_action_date`) — this is what drives Mis vacantes, entirely separate from the Marketplace's own `job_offers.application_status`. A model observer logs every status change as an automatic entry in `tracked_job_comments` (alongside manual notes/entrevista/seguimiento entries you add yourself) and stamps `applied_at` the moment a job moves to `aplique`.
- The single login user lives in the standard Laravel `users` table, seeded once from `OWNER_*` env vars (see "Setup"). There's no multi-tenancy — every table above is implicitly scoped to that one user.
- The currently selected AI provider/model lives in a single-row `ai_settings` table (not `.env`), since it needs to change at runtime from the UI without a restart.
- Profile variants live in a `profiles` table (slug, structured fields, `raw_md`, `is_active`, `source_text`); `storage/app/perfil.md` is always a mirror of whichever row has `is_active = true`, kept in sync by every import/edit/activate/sync action. `source_text` is the raw text extracted from the uploaded CV at import time (never re-derived from `raw_md`), used only to let the AI review compare the parse against the real original. Tailoring and ATS optimization both save their results as new variants through the same `ProfileVariantService`, never touching `default` or the currently active variant.

## API endpoints

All `/api/*` routes require an authenticated session (redirects to `/login` otherwise). Grouped by module:

| Module | Endpoint | Purpose |
|---|---|---|
| Marketplace | `GET /api/marketplace` | Paginated, filtered offer listing (source, modality, seniority, language, min. match, search, salary-only, hide-tracked, sort). |
| | `POST /api/marketplace/{job}/track` | Shortlist a single offer into Mis vacantes. |
| | `POST /api/marketplace/track-bulk` | Shortlist several offers at once. |
| | `POST /api/marketplace/analyze-pending` | Queue AI analysis for every `fetched` offer. |
| Jobs | `GET /api/jobs`, `GET /api/jobs/sources`, `POST /api/jobs/fetch` | Legacy full listing (used by the table view), source list, fetch pipeline. |
| | `GET /api/jobs/{job}`, `PATCH /api/jobs/{job}` | Single-offer detail and local `application_status` update. |
| | `POST /api/jobs/{job}/analyze`, `POST /api/jobs/{job}/publish` | On-demand AI analysis and Notion publish for one offer. |
| Tracking | `GET /api/tracking`, `GET /api/tracking/{id}` | List/detail of shortlisted offers, including comments and the latest comment preview. |
| | `PATCH /api/tracking/{id}` | Update status, priority, next action/date, or CV version used. |
| | `POST /api/tracking/{id}/comments` | Add a manual note/entrevista/seguimiento entry. |
| | `DELETE /api/tracking/{id}` | Remove a tracked offer and its comments. |
| Profile | `GET /api/profile` | Currently active profile. |
| | `POST /api/profile/import` | Deterministic CV upload/parse (always targets `default`). |
| | `GET/POST /api/profiles` | List variants / create a new one (clones `default`). |
| | `GET /api/profile/{slug}`, `PUT /api/profile/{slug}`, `POST /api/profile/{slug}/activate`, `POST /api/profile/{slug}/sync` | Variant detail/update/activation and Markdown sync. |
| | `POST /api/profile/{slug}/review`, `POST /api/profile/{slug}/suggestions/apply` | AI review suggestions and applying approved ones. |
| | `POST /api/profile/tailor`, `POST /api/profile/tailor/confirm` | CV tailoring preview (diff) and confirm-as-variant. |
| | `POST /api/profile/ats`, `POST /api/profile/ats/confirm` | ATS scoring preview (diff) and confirm-as-variant. |
| | `PUT /api/profile/password` | Change the login password. |
| Nav | `GET /api/nav/badges` | Sidebar badge counts (unreviewed Marketplace offers, in-process tracked offers). |
| Auth | `GET/POST /login`, `POST /logout` | Session login/logout (`web` middleware group, not `/api`). |

## Testing

```bash
php artisan test --compact
```

Covers RSS/JSearch/InfoJobs source parsing (including the `apply_url` fallback to the listing URL), AI JSON response validation (valid, fenced, malformed, English alert, red flags, inferred seniority/modality/skills), each AI provider, Notion payload chunking (including the match-score publish threshold), the AI model catalog/settings endpoints, the full CV pipeline (deterministic PDF/TXT parsing, a scanned-PDF-with-no-text error path, Markdown round-tripping, profile variants), CV tailoring and ATS scoring (including that both only ever write to a new variant, never `default`), tracking (shortlisting, Kanban status transitions and their automatic bitácora entries, comments, destroy), authentication (login/logout, route protection on `web` and `api`), and password changes.

Frontend changes are verified with:

```bash
npm run types:check    # vue-tsc
npm run lint:check     # eslint
npm run format:check   # prettier
npm run build           # production bundle
```

There's no JS test runner in this project (no vitest/jest) — UI behavior is exercised manually in the browser; the commands above catch type errors, style/formatting drift, and build breakage.

### Manual smoke test

```bash
php artisan cv:import tests/Fixtures/sample-resume-full.pdf   # or your own CV
php artisan tinker --execute 'dd(\App\Models\Profile::active()->only(["headline", "skills", "languages"]));'
php artisan jobs:analyze   # any fetched job whose description asks for more English than your CV declares should come back with alerta_ingles=true
```

### Config checklist

- [ ] `OWNER_NAME`/`OWNER_EMAIL`/`OWNER_PASSWORD` set to your real login before running `php artisan db:seed` (the defaults are placeholders)
- [ ] `AI_PROVIDER` + matching credentials (`CLAUDE_CLI_BINARY`/`GEMINI_API_KEY`/`OPENROUTER_API_KEY`)
- [ ] `RAPIDAPI_KEY` (JSearch) — optional, `INFOJOBS_ENABLED`/credentials
- [ ] `NOTION_TOKEN` + `NOTION_DATABASE_ID`, with the properties table above created exactly
- [ ] `MIN_MATCH_TO_PUBLISH` (default 75) and `ACTIVE_PROFILE` (default `default`)
- [ ] A real CV imported via `/profile` → Mi CV or `cv:import` — the starter `perfil.md` is a placeholder
