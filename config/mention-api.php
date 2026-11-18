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
];
