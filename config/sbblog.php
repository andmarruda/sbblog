<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Two-factor enforcement
    |--------------------------------------------------------------------------
    |
    | Permissions and roles flagged `requires_two_factor`, and workspaces with a
    | strict two-factor policy, deny access to users without 2FA. Off until the
    | 2FA setup screens ship (console plan, phase 10).
    |
    */

    'two_factor' => [
        'enforce' => (bool) env('SBBLOG_ENFORCE_TWO_FACTOR', false),
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
    | Reserved slugs
    |--------------------------------------------------------------------------
    |
    | Workspace slugs are the first URL segment, so they can't take the name of
    | a top-level route.
    |
    */

    'reserved_slugs' => [
        'account', 'admin', 'api', 'app', 'assets', 'build', 'confirm-password', 'dashboard',
        'email', 'forgot-password', 'help', 'invitations', 'locale', 'login', 'logout',
        'onboarding', 'register', 'reset-password', 'sanctum', 'settings', 'storage',
        'support', 'sync', 'up', 'verify-email', 'www',
    ],

];
