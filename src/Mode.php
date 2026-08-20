<?php

namespace Alle80\Griglia;

use Alle80\Griglia\Settings\AppSettings;

/**
 * Board mode: «server» (default) = authenticated users, each with their own lists, access optionally
 * restricted (see GrigliaAccess); «local» = no authentication at all, one global set of lists (no
 * user), for a board running on the developer's machine. Chosen by config `griglia.mode`
 * (GRIGLIA_MODE) and overridable from /settings (AppSettings::$mode, '' = follow the config).
 */
class Mode
{
    public const LOCAL = 'local';

    public const SERVER = 'server';

    private static ?string $resolved = null;

    public static function current(): string
    {
        if (self::$resolved !== null) {
            return self::$resolved;
        }
        $mode = '';
        try {
            $mode = (string) app(AppSettings::class)->mode;
        } catch (\Throwable) {
            // settings not migrated yet
        }
        if ($mode === self::LOCAL && ! self::localFromUiAllowed()) {
            $mode = ''; // a local override from the UI is only honoured where allowed
        }
        if (! in_array($mode, [self::LOCAL, self::SERVER], true)) {
            $mode = (string) config('griglia.mode', self::SERVER);
        }

        return self::$resolved = ($mode === self::LOCAL ? self::LOCAL : self::SERVER);
    }

    public static function isLocal(): bool
    {
        return self::current() === self::LOCAL;
    }

    /** May the UI switch the board to local mode? Only in the `local` environment or when explicitly allowed. */
    public static function localFromUiAllowed(): bool
    {
        return (bool) config('griglia.allow_local_from_ui', false) || app()->environment('local');
    }

    /** Forget the resolved value (settings changed, tests). */
    public static function reset(): void
    {
        self::$resolved = null;
    }

    /** Broadcast channel: private per user in server mode, a public one in local mode. */
    public static function broadcastChannel(?int $userId = null): string
    {
        if (self::isLocal()) {
            return (string) config('griglia.local_channel', 'griglia.local');
        }

        return str_replace('{id}', (string) ($userId ?? auth()->id() ?? 0), (string) config('griglia.broadcast_channel', 'App.Models.User.{id}'));
    }

    /** Livewire listener key for the live event on the right channel. */
    public static function echoListener(string $event = '.TodoChanged'): string
    {
        return self::isLocal()
            ? 'echo:'.self::broadcastChannel().','.$event
            : 'echo-private:'.str_replace('{id}', '{userId}', (string) config('griglia.broadcast_channel', 'App.Models.User.{id}')).','.$event;
    }
}
