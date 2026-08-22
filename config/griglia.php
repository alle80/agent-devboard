<?php

return [

    // URL prefix of the package pages ('' = site root: /, /settings, /dashboard)
    'route_prefix' => env('GRIGLIA_ROUTE_PREFIX', ''),

    // How the UI calls the coding agent (Claude, Codex, Gemini, …): labels like «Claude's answer», «Claude's skills»
    'agent_name' => env('GRIGLIA_AGENT_NAME', 'Agent'),

    // Several agents at once (key => label), e.g. GRIGLIA_AGENTS="claude:Claude Code,codex:Codex CLI". A list
    // (project) chooses its default agent, a task may override it. Empty = a single agent named `agent_name`.
    'agents' => env('GRIGLIA_AGENTS'),

    // Which of those agents is running here: the key `griglia:check` assumes when `--agent=<key>` is omitted,
    // so that this installation sees only its own tasks. Empty = the agent must pass `--agent=` itself.
    'agent_key' => env('GRIGLIA_AGENT_KEY'),

    // Mode: 'server' (default) = authenticated users with their own lists; 'local' = no authentication,
    // one global set of lists (a board on your own machine). Overridable from /settings (AppSettings mode).
    'mode' => env('GRIGLIA_MODE', 'server'),

    // Server mode: who may open the board. If the user model has `canAccessGriglia(): bool` it decides (the
    // pre-rename `canAccessDevboard()` is still honoured); otherwise this Gate ability (e.g. 'access-griglia')
    // if set; otherwise every authenticated user.
    'access_gate' => env('GRIGLIA_ACCESS_GATE'),

    // Server mode: Gate ability deciding who may ADMINISTER the board (settings, agent context, theme packs).
    // It is consulted after `canManageGriglia(): bool` on the user model (the pre-rename `canManageDevboard()`
    // is still honoured) and before `admins`.
    'admin_gate' => env('GRIGLIA_ADMIN_GATE'),

    // Server mode: the administrators themselves, as ids or e-mails (GRIGLIA_ADMINS="1,alice@example.com").
    // Used when neither `canManageGriglia()` nor `admin_gate` decides. Empty = the first registered user only.
    'admins' => env('GRIGLIA_ADMINS'),

    // Allow switching the board to local mode (no authentication) from /settings. Off by default: the override
    // is accepted from the UI only when the app runs in the `local` environment.
    'allow_local_from_ui' => env('GRIGLIA_ALLOW_LOCAL_FROM_UI', false),

    // Middleware of the package routes. Authentication is enforced by the package itself according to the
    // mode (Alle80\Griglia\Http\Middleware\GrigliaAccess), so 'auth' is not needed here (and is ignored).
    'middleware' => ['web'],

    // Public broadcast channel used for live updates in local mode
    'local_channel' => 'griglia.local',

    // Register the package routes at all (set false to define your own routes with the components)
    'register_routes' => true,

    // Register a home route (route_prefix + '/') showing the theme selected in /settings
    'home_route' => true,

    // Desktop dashboard: a wider, more readable view of the board on its own route.
    // Set to null/false to disable the route and the slide-out board tab.
    'dashboard_route' => env('GRIGLIA_DASHBOARD_ROUTE', '/dashboard'),

    // Generic theme used by the home route and as fallback
    'default_theme' => 'slate',

    // Extra generic themes (slug => definition, same keys as Alle80\Griglia\Themes::builtin())
    'themes' => [],

    // User model owning the lists
    'user_model' => env('GRIGLIA_USER_MODEL', 'App\\Models\\User'),

    // Filesystem disk for image attachments. The private `local` disk is the secure default.
    'attachments_disk' => env('GRIGLIA_ATTACHMENTS_DISK', 'local'),

    // Serve attachments through the authorised, owner-scoped controller. Keep enabled for private disks;
    // disable only when `attachments_disk` deliberately points to a publicly accessible disk.
    'attachments_via_controller' => env('GRIGLIA_ATTACHMENTS_VIA_CONTROLLER', true),

    // Name of the list used as request channel between the user and the coding agent (griglia:check)
    'agent_list' => env('GRIGLIA_AGENT_LIST', 'dev'),

    // Name of the first list created for a new user. Empty = the translated name (griglia::t.default_list)
    'default_list_name' => env('GRIGLIA_DEFAULT_LIST_NAME', ''),

    // Private broadcast channel per user for live updates ({id} = user id); requires a broadcaster
    'broadcast_channel' => 'App.Models.User.{id}',

    // Web Push: hosts a browser subscription endpoint may point to (https only). Wildcards allowed. Empty = any https host.
    'push_allowed_hosts' => ['fcm.googleapis.com', '*.push.apple.com', 'updates.push.services.mozilla.com', '*.notify.windows.com', 'wns2-*.notify.windows.com', '*.push.mozilla.com', 'push.services.mozilla.com', '*.ucweb.com', '*.huawei.com'],

    // Rate limits (Laravel throttle definitions) of the expensive endpoints
    'rate_limits' => [
        'transcribe' => env('GRIGLIA_RATE_TRANSCRIBE', '10,1'),
        'notifications_test' => env('GRIGLIA_RATE_NOTIFICATIONS_TEST', '5,1'),
        'push_subscriptions' => env('GRIGLIA_RATE_PUSH_SUBSCRIPTIONS', '30,1'),
    ],

    // Agents status snapshot (plan + usage windows), written by `griglia:agent-status-import`; shown in /agents
    'agent_status_file' => env('GRIGLIA_AGENT_STATUS_FILE', storage_path('app/griglia/agent-status.json')),

    // Catalogue of the agent's skills (JSON written by `griglia:skills-import`; shown in the task modal)
    'skills_file' => env('GRIGLIA_SKILLS_FILE', storage_path('app/griglia/skills.json')),

    // Vocabulary hint sent with the audio of the speech to text (helps with names and jargon:
    // «l'agente» instead of «la gente»). null = use the translated default, '' = no hint at all.
    'speech_prompt' => env('GRIGLIA_SPEECH_PROMPT', null),

    // Hard limit of a single dictation, in seconds (0 = no limit): when it is reached the recording is
    // closed and transcribed, instead of growing until the upload or the provider refuses it.
    'speech_max_seconds' => (int) env('GRIGLIA_SPEECH_MAX_SECONDS', 300),

    // Front-end assets: 'precompiled' (default) = the CSS/JS built by the package, published in
    // public/vendor/griglia/build — nothing to build in the host app; 'vite' = the host app bundles
    // resources/css/griglia.css + resources/js/griglia.js in its own Vite build (entries below)
    'assets' => env('GRIGLIA_ASSETS', 'precompiled'),

    // With `assets` = 'vite': the entry points of the host app passed to @vite() on the package pages.
    'vite_entries' => ['resources/css/app.css', 'resources/js/app.js'],

    // With `assets` = 'precompiled': public URL where the published build is served from.
    'assets_url' => '/vendor/griglia/build',

    // Runtime configuration of the Echo client (live updates). Empty key = no WebSocket at all.
    'echo' => [
        'key' => env('VITE_REVERB_APP_KEY', env('REVERB_APP_KEY', '')),
        'host' => env('VITE_REVERB_HOST', env('REVERB_HOST', 'localhost')),
        'port' => env('VITE_REVERB_PORT', env('REVERB_PORT', 443)),
        'scheme' => env('VITE_REVERB_SCHEME', env('REVERB_SCHEME', 'https')),
    ],

    // Web fonts of the themes: URL prefix that receives the theme's `fonts` string (bunny.net by default,
    // Google-compatible); '' disables external fonts (self-host them in your CSS instead)
    'fonts_url' => env('GRIGLIA_FONTS_URL', 'https://fonts.bunny.net/css?family='),

];
