<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Two-factor enforcement
    |--------------------------------------------------------------------------
    |
    | Permissions and roles flagged `requires_two_factor`, and workspaces with a
    | strict two-factor policy, send users without 2FA to the security settings.
    |
    */

    'two_factor' => [
        'enforce' => (bool) env('SBBLOG_ENFORCE_TWO_FACTOR', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Invitations
    |--------------------------------------------------------------------------
    */

    'invitations' => [
        'expire_days' => 7,
    ],

    /*
    |--------------------------------------------------------------------------
    | Media
    |--------------------------------------------------------------------------
    |
    | Where uploads go: local (the public disk), s3 or r2. Size in kilobytes.
    |
    */

    'media' => [
        'disk' => env('SBBLOG_MEDIA_DISK', 'local'),
        'max_kilobytes' => 20 * 1024,
        'mimes' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Publishing
    |--------------------------------------------------------------------------
    |
    | Events older than the retention are pruned once a snapshot covers them.
    |
    */

    'publishing' => [
        'event_retention_days' => (int) env('SBBLOG_EVENT_RETENTION_DAYS', 30),

        // Webhooks to private or loopback addresses are refused (SSRF). Allow them only in
        // local development.
        'allow_private_webhooks' => (bool) env('SBBLOG_ALLOW_PRIVATE_WEBHOOKS', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Reserved slugs
    |--------------------------------------------------------------------------
    |
    | Workspace slugs are the first URL segment, so they can't take the name of
    | a top-level route.
    |
    */

    'reserved_slugs' => [
        'account', 'admin', 'api', 'app', 'assets', 'build', 'confirm-password', 'dashboard',
        'email', 'forgot-password', 'help', 'invitations', 'locale', 'login', 'logout', 'oauth',
        'onboarding', 'register', 'reset-password', 'sanctum', 'settings', 'storage',
        'support', 'sync', 'up', 'verify-email', 'www',
    ],

];
