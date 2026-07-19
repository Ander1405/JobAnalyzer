# JobHunter

A personal, local-only job search automation tool. It fetches job offers (JSearch/RapidAPI, LaraJobs RSS, optionally InfoJobs), analyzes each one against your profile with an AI provider of your choice, shows everything in a local Vue table, and backs up qualifying offers to Notion.

Everything runs on your machine — there is no cloud deployment, no queue workers, no scraping of LinkedIn/Indeed. The default AI provider (`claude_cli`) shells out to your local `claude` CLI session, so analysis costs $0 beyond your existing Claude subscription.

## Setup

### 1. Install dependencies

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
```

`storage/app/perfil.md` already ships with a starter profile — edit it to match your own background; it's the single source of truth the AI uses for matching.

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
   | `Estado` | Select — options: `Nueva`, `CV adaptado`, `Aplicada`, `Entrevista`, `Cerrada` |
   | `Fecha detectada` | Date |

   New pages are always created with `Estado = Nueva`; JobHunter never updates a page after creating it, so anything beyond that is tracked manually in Notion.

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
| `php artisan jobs:publish` | Publish all `analyzed` jobs to Notion. |
| `php artisan jobs:run` | Run fetch → analyze → publish in sequence and print a summary highlighting matches at or above `MATCH_SCORE_ALERT_THRESHOLD`. |

The UI's "Buscar nuevas" and "Analizar pendientes" buttons, plus the per-row "Publicar en Notion" action, call the same logic through the local `/api/jobs*` endpoints.

Every analysis reports how much it actually cost: the job detail drawer shows the provider/model used, elapsed time, input/output tokens, and cost in USD (or "Gratis / N/A" when a provider — Gemini today — doesn't report a cost). Running "Analizar pendientes" also shows a summary banner with the totals for the whole batch.

### Optional: daily schedule

`routes/console.php` has a commented-out entry to run `jobs:run` daily at 07:00 (`America/Bogota`). Uncomment it and keep `php artisan schedule:work` running (or a system cron calling `schedule:run` every minute) if you want it to run unattended.

## Data model notes

- Job offers live in a `job_offers` table (not `jobs`), because Laravel's own queue system already owns a table named `jobs` (`QUEUE_CONNECTION=database`).
- `status` (`fetched` → `analyzed` → `published` → `failed`) tracks the pipeline stage. `application_status` (`Nueva`, `CV adaptado`, `Aplicada`, `Entrevista`, `Cerrada`) is a separate, UI-editable field for tracking where you are in the actual application process — it's local-only and isn't synced back to Notion.
- The currently selected AI provider/model lives in a single-row `ai_settings` table (not `.env`), since it needs to change at runtime from the UI without a restart.

## Testing

```bash
php artisan test --compact
```

Covers RSS/JSearch source parsing, AI JSON response validation (valid, fenced, malformed), each AI provider, and Notion payload chunking.
