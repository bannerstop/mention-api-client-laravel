<?php

return [
    /*
     * The address of mention-api, without /v2: https://mention-api.example.com
     */
    'base_url' => env('MENTION_API_URL'),

    /*
     * The API key of your Consumer. Use this or client_credentials.
     */
    'api_key' => env('MENTION_API_KEY'),

    /*
     * Authenticate as a Keycloak client with a service account instead of an API key.
     */
    'client_credentials' => [
        'token_url' => env('MENTION_API_TOKEN_URL'),
        'client_id' => env('MENTION_API_CLIENT_ID'),
        'client_secret' => env('MENTION_API_CLIENT_SECRET'),
        'scope' => env('MENTION_API_SCOPE', 'mention-api'),
    ],

    /*
     * The cache store that keeps the access token of client_credentials: null for the default store, false for none.
     */
    'cache_store' => env('MENTION_API_CACHE_STORE'),

    /*
     * Seconds to wait for an answer, also for the whole call with the default HTTP client.
     */
    'timeout' => env('MENTION_API_TIMEOUT', 30),

    /*
     * The PSR-18 client to send requests with: a class or container binding. null uses Guzzle.
     */
    'http_client' => null,

    /*
     * Defaults of the mention-api:generate command. The directory is relative to the base path of the application.
     */
    'generator' => [
        'namespace' => 'App\\Mention',
        'directory' => 'app/Mention',
    ],
];
