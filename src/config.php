<?php

/*
 |--------------------------------------------------------------------------
 | Clumsy Aleph settings
 |--------------------------------------------------------------------------
 |
 |
 */

return [

    /*
     |--------------------------------------------------------------------------
     | Endpoint
     |--------------------------------------------------------------------------
     |
     | An optional URL where information about the log entry will be posted.
     |
     */

    'endpoint' => '',

    /*
     |--------------------------------------------------------------------------
     | Enforce endpoint HTTPS
     |--------------------------------------------------------------------------
     |
     | Whether of not to allow endpoints without HTTPS. It is enabled by default
     | and disabling this is discouraged because of the sensitive nature of
     | the information contained within the logs, which can reveal your apps
     | database credentials or your users personal information.
     |
     */

    'enforce-endpoint-https' => true,

    /*
     |--------------------------------------------------------------------------
     | Log endpoint response
     |--------------------------------------------------------------------------
     |
     | When using an endpoint, should its response be logged instead of the
     | additional information provided by Aleph? The logic being that if you're
     | storing the log information remotely, you might only want a reference
     | given by your own endpoint, or just avoid unnecessary repetition of
     | sensitive data.
     |
     */

    'log-endpoint-response' => true,

    /*
     |--------------------------------------------------------------------------
     | Sensitive keywords
     |--------------------------------------------------------------------------
     |
     | When processing data to be logged, certain keywords should not be stored
     | in plain text, like passwords or credit card information. Keys matching
     | the entries below will be redacted. Some sensible defaults are given, but
     | they should vary from app to app, so be sure to add your own. These will
     | be parsed with case-insensitive regular expressions, so any partial match
     | will be redacted as well.
     |
     */

    'sensitive-keywords' => [
        'token',
        'password',
        'cc',
        'credit',
        'cvv',
        'csc',
    ],

    /*
     |--------------------------------------------------------------------------
     | Attribute whitelist
     |--------------------------------------------------------------------------
     |
     | Attributes which are matched by the sensitive keywords configured can
     | be whitelisted here (i.e. they are safe to be logged in plain text)
     |
     */

    'attribute-whitelist' => [
        '_token',
    ],
];
