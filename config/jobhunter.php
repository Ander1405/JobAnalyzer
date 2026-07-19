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

    'min_match_to_publish' => (int) env('MIN_MATCH_TO_PUBLISH', 75),

    'active_profile' => env('ACTIVE_PROFILE', 'default'),

    'pdftotext_binary' => env('PDFTOTEXT_BINARY', 'pdftotext'),

];
