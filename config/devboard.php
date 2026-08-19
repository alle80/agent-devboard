<?php

return [

    // URL prefix of the package pages ('' = site root: /, /<theme>, /settings)
    'route_prefix' => env('DEVBOARD_ROUTE_PREFIX', ''),

    // How the UI calls the coding agent (Claude, Codex, Gemini, …): labels like «🤖 Claude», «Claude's skills»
    'agent_name' => env('DEVBOARD_AGENT_NAME', 'Agent'),

    // Mode: 'server' (default) = authenticated users with their own lists; 'local' = no authentication,
    // one global set of lists (a board on your own machine). Overridable from /settings (AppSettings mode).
    'mode' => env('DEVBOARD_MODE', 'server'),

    // Server mode: who may open the board. If the user model has `canAccessDevboard(): bool` it decides;
    // otherwise this Gate ability (e.g. 'access-devboard') if set; otherwise every authenticated user.
    'access_gate' => env('DEVBOARD_ACCESS_GATE'),

    // Middleware of the package routes. Authentication is enforced by the package itself according to the
    // mode (Alle80\Devboard\Http\Middleware\DevboardAccess), so 'auth' is not needed here (and is ignored).
    'middleware' => ['web'],

    // Public broadcast channel used for live updates in local mode
    'local_channel' => 'devboard.local',

    // Register the package routes at all (set false to define your own routes with the components)
    'register_routes' => true,

    // Register a home route (route_prefix + '/') showing the default theme
    'home_route' => true,

    // Desktop dashboard: a wider, more readable view of the board on its own route.
    // Set to null/false to disable the route and the slide-out board tab.
    'dashboard_route' => env('DEVBOARD_DASHBOARD_ROUTE', '/dashboard'),

    // Generic theme used by the home route and as fallback
    'default_theme' => 'slate',

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

    // Agents status snapshot (plan + usage windows), written by `devboard:agent-status-import`; shown in /agents
    'agent_status_file' => env('DEVBOARD_AGENT_STATUS_FILE', storage_path('app/devboard/agent-status.json')),

    // Catalogue of the agent's skills (JSON written by `devboard:skills-import`; shown in the task modal)
    'skills_file' => env('DEVBOARD_SKILLS_FILE', storage_path('app/devboard/skills.json')),

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
