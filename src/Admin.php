<?php

namespace Alle80\Griglia;

use Illuminate\Support\Facades\Gate;

/**
 * Who may administer the board (global settings, agent context, theme packs): in local mode everybody;
 * in server mode, in this order, `canManageGriglia(): bool` on the user model if defined (the pre-rename
 * `canManageDevboard()` is still honoured), else the Gate ability `griglia.admin_gate` if set, else
 * membership in `griglia.admins` (ids or e-mails, GRIGLIA_ADMINS) if set, else the first registered user
 * (lowest id) only.
 */
class Admin
{
    public static function allows(?object $user): bool
    {
        if (Mode::isLocal()) {
            return true;
        }
        if (! $user) {
            return false;
        }
        if (method_exists($user, 'canManageGriglia')) {
            return (bool) $user->canManageGriglia();
        }
        if (method_exists($user, 'canManageDevboard')) {   // pre-rename hook, still honoured
            return (bool) $user->canManageDevboard();
        }
        if ($gate = config('griglia.admin_gate')) {
            return Gate::forUser($user)->allows($gate);
        }
        $admins = config('griglia.admins');
        if (is_string($admins)) {
            $admins = array_values(array_filter(array_map('trim', explode(',', $admins))));
        }
        if (is_array($admins) && $admins !== []) {
            return in_array((string) $user->getKey(), array_map('strval', $admins), true)
                || (isset($user->email) && in_array(strtolower((string) $user->email), array_map('strtolower', $admins), true));
        }
        $model = get_class($user);

        return (string) $model::query()->orderBy($user->getKeyName())->value($user->getKeyName()) === (string) $user->getKey();
    }

    public static function check(): bool
    {
        return self::allows(auth()->user());
    }
}
