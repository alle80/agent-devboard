<?php

return [

    // URL prefix of the package pages ('' = site root: /, /<theme>, /settings)
    'route_prefix' => env('DEVBOARD_ROUTE_PREFIX', ''),

    // Middleware of the package routes (authentication is required: lists belong to users)
    'middleware' => ['web', 'auth'],

    // Register the package routes at all (set false to define your own routes with the components)
    'register_routes' => true,

    // Register a home route (route_prefix + '/') showing the default theme
    'home_route' => true,

    // Generic theme used by the home route and as fallback
    'default_theme' => 'linux',

    // Extra generic themes (slug => definition, same keys as Alle80\Devboard\Themes::builtin())
    'themes' => [],

    // User model owning the lists
    'user_model' => env('DEVBOARD_USER_MODEL', 'App\\Models\\User'),

    // Filesystem disk for image attachments (must be publicly reachable, e.g. the "public" disk with storage:link)
    'attachments_disk' => env('DEVBOARD_ATTACHMENTS_DISK', 'public'),

    // Name of the list used as request channel between the user and the coding agent (devboard:check)
    'agent_list' => env('DEVBOARD_AGENT_LIST', 'dev'),

    // Name of the default list created for a new user
    'default_list_name' => 'My list',

    // Private broadcast channel per user for live updates ({id} = user id); requires a broadcaster
    'broadcast_channel' => 'App.Models.User.{id}',

    // Front-end assets: 'vite' = the host app bundles resources/css/devboard.css + resources/js/devboard.js
    // in its own Vite build (entries below); 'precompiled' = use the files built by the package and
    // published with `vendor:publish --tag=devboard-assets` (public/vendor/devboard/build)
    'assets' => env('DEVBOARD_ASSETS', 'vite'),
    'vite_entries' => ['resources/css/app.css', 'resources/js/app.js'],
    'assets_url' => '/vendor/devboard/build',

    // Runtime configuration of the Echo client (live updates). Empty key = no WebSocket at all.
    'echo' => [
        'key' => env('VITE_REVERB_APP_KEY', env('REVERB_APP_KEY', '')),
        'host' => env('VITE_REVERB_HOST', env('REVERB_HOST', 'localhost')),
        'port' => env('VITE_REVERB_PORT', env('REVERB_PORT', 443)),
        'scheme' => env('VITE_REVERB_SCHEME', env('REVERB_SCHEME', 'https')),
    ],

    // Web fonts of the themes: URL prefix that receives the theme's `fonts` string (bunny.net by default,
    // Google-compatible); '' disables external fonts (self-host them in your CSS instead)
    'fonts_url' => env('DEVBOARD_FONTS_URL', 'https://fonts.bunny.net/css?family='),

];
