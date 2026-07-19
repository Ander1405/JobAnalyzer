# JobHunter

A personal, local-only job search automation tool. It fetches job offers (JSearch/RapidAPI, LaraJobs RSS, optionally InfoJobs), analyzes each one against your profile with an AI provider of your choice, shows everything in a local Vue table, and backs up qualifying offers to Notion.

Everything runs on your machine — there is no cloud deployment, no scraping of LinkedIn/Indeed. The default AI provider (`claude_cli`) shells out to your local `claude` CLI session, so analysis costs $0 beyond your existing Claude subscription.

## Setup

### 1. Install dependencies

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
```

`storage/app/perfil.md` already ships with a starter profile so the app works out of the box — replace it with your real background by uploading your CV from the `/profile` page (see "CV & profile" below). Whichever profile variant is active is the single source of truth the AI uses for matching.

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

The `.env` values above only seed the *first* run. Provider and model can be switched live from the UI (top of the jobs page) — that selection is persisted to the database and takes effect on the next analysis, no restart needed. The model dropdown is populated from each provider's real model list (a curated static list for Claude CLI, live API lookups for Gemini/OpenRouter), with a filter box and free/pricing badges.

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

Then open `http://127.0.0.1:8000`.

### Console commands

| Command | Purpose |
|---|---|
| `php artisan jobs:fetch` | Pull new offers from all enabled sources, dedupe by hash. |
| `php artisan jobs:analyze` | Analyze all `fetched` jobs with the configured AI provider. |
| `php artisan jobs:publish` | Publish `analyzed` jobs at or above `MIN_MATCH_TO_PUBLISH` to Notion (others are skipped and reported separately). |
| `php artisan jobs:run` | Run fetch → analyze → publish in sequence and print a summary highlighting matches at or above `MATCH_SCORE_ALERT_THRESHOLD`. |
| `php artisan cv:import {path} [--slug=default]` | Parse a CV file (pdf/txt/md) deterministically — no AI — and store it as a profile. |
| `php artisan profile:sync` | Re-parse a hand-edited `storage/app/perfil.md` back into the active profile's structured fields. |

The UI's "Buscar nuevas" and "Analizar pendientes" buttons, plus the per-row "Publicar en Notion" action, call the same logic through the local `/api/jobs*` endpoints. Unlike `php artisan jobs:analyze`, the "Analizar pendientes" button doesn't call the AI provider synchronously in the web request — it dispatches an `AnalyzeJobListing` queue job per pending offer (job status becomes `analyzing` immediately) and the UI polls until they resolve. This matters if you're serving the app through Valet/nginx+PHP-FPM (or any other daemon-managed web server): with `claude_cli`, macOS denies Keychain access to processes spawned by a daemon rather than an interactive terminal session, so `claude` reports "Not logged in" even when you're logged in everywhere else. Running the queue worker yourself from a normal terminal session sidesteps that:

```bash
php artisan queue:work
```

Leave it running while you use the jobs page. If you don't run a worker, queued analyses stay stuck in `analyzing` forever. `php artisan jobs:analyze`/`jobs:run` are unaffected either way — they call the AI provider directly from whatever terminal session you invoke them in, so `claude_cli` works out of the box there.

Every analysis reports how much it actually cost: the job detail drawer shows the provider/model used, elapsed time, input/output tokens, and cost in USD (or "Gratis / N/A" when a provider — Gemini today — doesn't report a cost). Running "Analizar pendientes" also shows a summary banner with the totals for the whole batch once every job in it finishes.

### CV & profile

Visit `/profile` (linked from the top of the jobs page) to manage your profile. Everything there is backed by a `profiles` table (slug, structured fields, and a rendered `raw_md`) — `storage/app/perfil.md` always mirrors whichever profile is currently active, since that's the file `jobs:analyze` reads.

**Uploading a CV.** Upload a PDF, TXT or MD file (drag-and-drop zone at the top of `/profile`, or `php artisan cv:import path/to/cv.pdf`). **Parsing never calls the AI layer** — it's all deterministic: `smalot/pdfparser` (falling back to `pdftotext` via Symfony Process if the PDF has no clean text layer, if installed) extracts raw text, then regex/heuristics split it into sections by detecting common Spanish/English CV headers (Resumen, Experiencia, Skills, Educación, Idiomas, Certificaciones) and pull out contact info (email, phone, LinkedIn, GitHub) and the CEFR English level declared under "Idiomas" (e.g. "Inglés B2"). A scanned PDF with no extractable text returns a clear error instead of silently producing nothing — no OCR, no AI fallback. This always creates/overwrites the `default` profile.

**Editing.** The `/profile` page has a form for every structured field (contact, headline, summary, experience/skills/education/certifications as editable lists, languages with an English-level dropdown) — saving regenerates the Markdown. There's also a raw-Markdown textarea with a "Sincronizar" button: it writes your edits to `storage/app/perfil.md` and re-parses them back into structure by the same `##` headers `cv:import` produces, so hand-editing the file directly (e.g. `vim storage/app/perfil.md && php artisan profile:sync`) works exactly the same way. Only the currently *active* profile can be synced this way. Expected format:

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

**English alert & red flags.** Every analysis now also returns `ingles_requerido` and `alerta_ingles` (true when a vacancy asks for more English than the active profile's declared level) and `red_flags` (concrete CV/vacancy mismatches, never generic filler) — shown in the job detail drawer and synced to Notion.

**AI review (opt-in, unlike parsing).** Parsing a CV never calls the AI, but you can explicitly ask the AI to help improve it: the "Revisión con IA" section on `/profile` compares the profile's stored original CV text (`source_text`, saved at import time) against the deterministically-parsed fields, and proposes discrete suggestions — corrections (content the parser dropped, split, or mis-transcribed) and improvements (wording, quantified impact) — each with a rationale. **Nothing is applied automatically**: check the suggestions you approve and click "Aplicar seleccionadas"; only that subset is applied, deterministically, to the profile's fields (`raw_md`/`perfil.md` regenerated), with no further AI call in the loop. Profiles imported before this feature has no stored `source_text`, so re-import the CV once to enable it.

### Optional: daily schedule

`routes/console.php` has a commented-out entry to run `jobs:run` daily at 07:00 (`America/Bogota`). Uncomment it and keep `php artisan schedule:work` running (or a system cron calling `schedule:run` every minute) if you want it to run unattended.

## Data model notes

- Job offers live in a `job_offers` table (not `jobs`), because Laravel's own queue system already owns a table named `jobs` (`QUEUE_CONNECTION=database`).
- `status` (`fetched` → `analyzing` → `analyzed` → `published`, or `failed`) tracks the pipeline stage; `analyzing` only appears while the `AnalyzeJobListing` queue job is in flight (the console commands skip it and go straight to `analyzed`/`failed`). `application_status` (`Nueva`, `CV adaptado`, `Aplicada`, `Entrevista`, `Cerrada`) is a separate, UI-editable field for tracking where you are in the actual application process — it's local-only and isn't synced back to Notion.
- The currently selected AI provider/model lives in a single-row `ai_settings` table (not `.env`), since it needs to change at runtime from the UI without a restart.
- Profile variants live in a `profiles` table (slug, structured fields, `raw_md`, `is_active`, `source_text`); `storage/app/perfil.md` is always a mirror of whichever row has `is_active = true`, kept in sync by every import/edit/activate/sync action. `source_text` is the raw text extracted from the uploaded CV at import time (never re-derived from `raw_md`), used only to let the AI review compare the parse against the real original.

## Testing

```bash
php artisan test --compact
```

Covers RSS/JSearch source parsing, AI JSON response validation (valid, fenced, malformed, English alert, red flags), each AI provider, Notion payload chunking (including the match-score publish threshold), the AI model catalog/settings endpoints, and the full CV pipeline: deterministic PDF/TXT parsing (contact info, skills, declared English level), a scanned-PDF-with-no-text error path, Markdown round-tripping, profile variants (create/activate/sync), and the `/profile` API endpoints.

### Manual smoke test

```bash
php artisan cv:import tests/Fixtures/sample-resume-full.pdf   # or your own CV
php artisan tinker --execute 'dd(\App\Models\Profile::active()->only(["headline", "skills", "languages"]));'
php artisan jobs:analyze   # any fetched job whose description asks for more English than your CV declares should come back with alerta_ingles=true
```

### Config checklist

- [ ] `AI_PROVIDER` + matching credentials (`CLAUDE_CLI_BINARY`/`GEMINI_API_KEY`/`OPENROUTER_API_KEY`)
- [ ] `RAPIDAPI_KEY` (JSearch) — optional, `INFOJOBS_ENABLED`/credentials
- [ ] `NOTION_TOKEN` + `NOTION_DATABASE_ID`, with the properties table above created exactly
- [ ] `MIN_MATCH_TO_PUBLISH` (default 75) and `ACTIVE_PROFILE` (default `default`)
- [ ] A real CV imported via `/profile` or `cv:import` — the starter `perfil.md` is a placeholder
