<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | AI Provider Selection
    |--------------------------------------------------------------------------
    |
    | Which AIProvider implementation JobAnalyzer resolves through the
    | AIProviderFactory. Supported values: "claude_cli", "gemini", "openrouter".
    |
    */

    'ai_provider' => env('AI_PROVIDER', 'claude_cli'),

    'claude_cli' => [
        'binary' => env('CLAUDE_CLI_BINARY', 'claude'),
        'model' => env('CLAUDE_CLI_MODEL'),

        /*
        | On macOS the Claude CLI keeps its session in the login Keychain, which is
        | only reachable from processes inside the user's GUI session. PHP-FPM is
        | launched by a root LaunchDaemon (session 0), so `claude -p` there always
        | answers "Not logged in · Please run /login" no matter who is logged in.
        | A long-lived token (`claude setup-token`) is session-independent and is
        | what makes the web requests work; the queue worker, started from your
        | terminal, would work either way.
        */
        'oauth_token' => env('CLAUDE_CODE_OAUTH_TOKEN'),
        'api_key' => env('ANTHROPIC_API_KEY'),

        /*
        | Extended thinking burns output tokens (and wall-clock time) on reasoning
        | that never reaches the "result" field we parse — see the analysis behind
        | this default. 0 disables it; leave unset (null) to use the CLI's default.
        */
        'max_thinking_tokens' => env('CLAUDE_CLI_MAX_THINKING_TOKENS', 0),
    ],

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-flash-latest'),
    ],

    'openrouter' => [
        'api_key' => env('OPENROUTER_API_KEY'),
        'model' => env('OPENROUTER_MODEL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Job Sources
    |--------------------------------------------------------------------------
    */

    'rapidapi_key' => env('RAPIDAPI_KEY'),

    'infojobs' => [
        'enabled' => env('INFOJOBS_ENABLED', false),
        'client_id' => env('INFOJOBS_CLIENT_ID'),
        'client_secret' => env('INFOJOBS_CLIENT_SECRET'),
    ],

    'job_search_queries' => array_filter(array_map(
        'trim',
        explode(',', (string) env('JOB_SEARCH_QUERIES', ''))
    )),

    'job_search_country' => env('JOB_SEARCH_COUNTRY', 'co'),

    /*
    |--------------------------------------------------------------------------
    | Queue
    |--------------------------------------------------------------------------
    |
    | How many `queue:work` processes `composer dev` starts for the "analysis"
    | queue (see AppServiceProvider). Each AI call is ~10-50s of wall-clock spent
    | waiting on the provider, not CPU, so running several at once shortens a
    | fetch's total analysis time roughly by this factor.
    */

    'analysis_workers' => (int) env('JOBHUNTER_ANALYSIS_WORKERS', 4),

    /*
    |--------------------------------------------------------------------------
    | Notion Backup
    |--------------------------------------------------------------------------
    */

    'notion' => [
        'token' => env('NOTION_TOKEN'),
        'database_id' => env('NOTION_DATABASE_ID'),
    ],

    /*
    |--------------------------------------------------------------------------
    | General
    |--------------------------------------------------------------------------
    */

    'match_score_alert_threshold' => (int) env('MATCH_SCORE_ALERT_THRESHOLD', 80),

    /*
    |--------------------------------------------------------------------------
    | CV / Profile
    |--------------------------------------------------------------------------
    */

    'min_match_to_publish' => (int) env('MIN_MATCH_TO_PUBLISH', 65),

    'active_profile' => env('ACTIVE_PROFILE', 'default'),

    'pdftotext_binary' => env('PDFTOTEXT_BINARY', 'pdftotext'),

    /*
    |--------------------------------------------------------------------------
    | Owner (single-user authentication)
    |--------------------------------------------------------------------------
    */

    'owner' => [
        'name' => env('OWNER_NAME', 'Owner'),
        'email' => env('OWNER_EMAIL', 'owner@example.com'),
        'password' => env('OWNER_PASSWORD', 'password'),
    ],

];
