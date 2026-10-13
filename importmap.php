<?php

/**
 * Returns the importmap for this application.
 *
 * - "path" is a path inside the asset mapper system. Use the
 *     "debug:asset-map" command to see the full list of paths.
 *
 * - "entrypoint" (JavaScript only) set to true for any module that will
 *     be used as an "entrypoint" (and passed to the importmap() Twig function).
 *
 * The "importmap:require" command can be used to add new entries to this file.
 */
return [
    'app' => [
        'path' => './assets/app.js',
        'entrypoint' => true,
    ],
    'user_timesheet' => [
        'path' => './assets/timesheet/js/user/user_timesheet.js',
        'entrypoint' => true,
    ],
    'admin_timesheet_dashboard' => [
        'path' => './assets/timesheet/js/admin/admin_timesheet_dashboard.js',
        'entrypoint' => true,
    ],
    'admin_timesheet_week_detail' => [
        'path' => './assets/timesheet/js/admin/admin_timesheet_week_detail.js',
        'entrypoint' => true,
    ],
    'admin_task_client_filter' => [
        'path' => './assets/timesheet/js/admin/task_client_filter.js',
        'entrypoint' => true,
    ],
    'login' => [
        'path' => './assets/styles/pages/login.css',
        'type' => 'css',
        'entrypoint' => true,
    ],
    'reset_password' => [
        'path' => './assets/styles/pages/reset_password.css',
        'type' => 'css',
        'entrypoint' => true,
    ],
    'check_email' => [
        'path' => './assets/styles/pages/check_email.css',
        'type' => 'css',
        'entrypoint' => true,
    ],
    'new_password' => [
        'path' => './assets/styles/pages/new_password.css',
        'type' => 'css',
        'entrypoint' => true,
    ],
    'export' => [
        'path' => './assets/styles/pages/export.css',
        'type' => 'css',
        'entrypoint' => true,
    ],
    'admin_dashboard_timesheet_css' => [
        'path' => './assets/timesheet/css/admin/admin_dashboard_timesheet.css',
        'type' => 'css',
        'entrypoint' => true,
    ],
    'admin_week_detail_timesheet_css' => [
        'path' => './assets/timesheet/css/admin/admin_week_detail_timesheet.css',
        'type' => 'css',
        'entrypoint' => true,
    ],
        'user_timesheet_css' => [
            'path' => './assets/timesheet/css/user/user_timesheet.css',
            'type' => 'css',
            'entrypoint' => true,
        ],
    '@hotwired/stimulus' => [
        'version' => '3.2.2',
    ],
    '@symfony/stimulus-bundle' => [
        'path' => './vendor/symfony/stimulus-bundle/assets/dist/loader.js',
    ],
    '@hotwired/turbo' => [
        'version' => '7.3.0',
    ],
    'bootstrap' => [
        'version' => '5.3.8',
    ],
    '@popperjs/core' => [
        'version' => '2.11.8',
    ],
    'bootstrap/dist/css/bootstrap.min.css' => [
        'version' => '5.3.8',
        'type' => 'css',
    ],
    'bootstrap_entry' => [
        'path' => './assets/bootstrap.js',
        'entrypoint' => true,
    ],
    '@symfony/ux-live-component' => [
        'path' => './vendor/symfony/ux-live-component/assets/dist/live_controller.js',
    ],
];
