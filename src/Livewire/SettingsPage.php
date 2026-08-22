<?php

namespace Alle80\Griglia\Livewire;

use Alle80\Griglia\Admin;
use Alle80\Griglia\Agent;
use Alle80\Griglia\Http\Middleware\RememberStyle;
use Alle80\Griglia\Mode;
use Alle80\Griglia\Settings\AgentSettings;
use Alle80\Griglia\Settings\AppSettings;
use Alle80\Griglia\Settings\OptimizationSettings;
use Alle80\Griglia\Support\Locale;
use Alle80\Griglia\Support\QuestionLevel;
use Alle80\Griglia\Themes;
use Alle80\Griglia\ThemeStore;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Pagina /settings: impostazioni (spatie/laravel-settings), due gruppi:
 * «agent» = come lavora Claude (le legge da sviluppo:check), «app» = comportamento della board.
 * Ogni modifica salva subito. La pagina si veste con lo stile della lista da cui si arriva
 * (RememberStyle → Styles::settingsSkin), così «tutto è in stile».
 */
class SettingsPage extends Component
{
    use WithFileUploads;

    /** Theme pack (zip) being uploaded. */
    public $themeZip = null;

    /** Valori correnti: gruppo => [chiave => valore]. */
    public array $values = ['agent' => [], 'optimization' => [], 'app' => []];

    protected function groups(): array
    {
        return ['agent' => AgentSettings::class, 'optimization' => OptimizationSettings::class, 'app' => AppSettings::class];
    }

    /** Defence in depth: admin-only, also on Livewire update requests. */
    public function boot(): void
    {
        abort_unless(Admin::check(), 403, __('griglia::t.errors.admin_only'));
    }

    public function mount(): void
    {
        foreach ($this->groups() as $group => $class) {
            $settings = app($class);
            foreach (array_keys($class::fields()) as $key) {
                $this->values[$group][$key] = $settings->{$key};
            }
        }
    }

    public function toggle(string $group, string $key): void
    {
        [$class, $field] = $this->field($group, $key);
        abort_unless($field[2] === 'bool', 422, __('griglia::t.errors.invalid_request'));

        $settings = app($class);
        $settings->{$key} = ! $settings->{$key};
        $settings->save();

        $this->values[$group][$key] = $settings->{$key};
        $this->dispatch('toast', message: __($settings->{$key} ? 'griglia::t.msg.setting_on' : 'griglia::t.msg.setting_off', ['label' => $field[0]]), type: $settings->{$key} ? 'success' : 'info');
    }

    /** Salvataggio di select/int/text/time (wire:change). */
    public function updatedValues($value, string $path): void
    {
        [$group, $key] = explode('.', $path, 2);
        [$class, $field] = $this->field($group, $key);
        $settings = app($class);

        switch ($field[2]) {
            case 'select':
                if (! array_key_exists((string) $value, $field[3])) {
                    $this->values[$group][$key] = $settings->{$key};

                    return;
                }
                if ($group === 'app' && $key === 'mode' && $value === 'local' && ! Mode::localFromUiAllowed()) {
                    $this->values[$group][$key] = $settings->{$key};
                    $this->dispatch('toast', message: __('griglia::t.msg.local_not_allowed'), type: 'error');

                    return;
                }
                $settings->{$key} = (string) $value;
                break;
            case 'int':
                $n = (int) $value;
                $n = max($field[3]['min'] ?? PHP_INT_MIN, min($field[3]['max'] ?? PHP_INT_MAX, $n));
                $settings->{$key} = $n;
                break;
            case 'time':
                if (! preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', (string) $value)) {
                    $this->values[$group][$key] = $settings->{$key};
                    $this->dispatch('toast', message: __('griglia::t.msg.invalid_time'), type: 'error');

                    return;
                }
                $settings->{$key} = (string) $value;
                break;
            case 'text':
                $settings->{$key} = trim((string) $value);
                break;
            default:
                return; // i bool passano da toggle()
        }

        $settings->save();
        Mode::reset();
        Locale::apply(); // cambiando lingua la pagina si ridisegna già tradotta
        $this->values[$group][$key] = $settings->{$key};
        if ($group === 'agent' && $key === 'autonomy') {
            // The question level also lives in the agent context as a managed block (task 499)
            QuestionLevel::sync();
            $this->dispatch('toast', message: __('griglia::t.question_level.saved'));

            return;
        }
        $this->dispatch('toast', message: __('griglia::t.msg.setting_saved', ['label' => $field[0]]));
    }

    // ----- Theme packs -----

    /** Livewire calls this as soon as the zip has been uploaded. */
    public function updatedThemeZip(): void
    {
        if (! $this->themeZip) {
            return;
        }

        try {
            $this->validate(['themeZip' => ['file', 'max:20480']], ['themeZip.max' => __('griglia::t.themes.err_too_big')]);
            $def = ThemeStore::install($this->themeZip->getRealPath());
            $this->dispatch('toast', message: __('griglia::t.themes.installed_ok', ['label' => $def['label']]));
        } catch (ValidationException $e) {
            $this->dispatch('toast', message: collect($e->errors())->flatten()->first(), type: 'error');
        } catch (\RuntimeException $e) {
            $this->dispatch('toast', message: $e->getMessage(), type: 'error'); // ThemeStore's own, translated messages
        } catch (\Throwable $e) {
            Log::warning('griglia: theme install failed: '.$e->getMessage());
            $this->dispatch('toast', message: __('griglia::t.themes.err_generic'), type: 'error');
        }

        $this->themeZip = null;
    }

    public function uninstallTheme(string $slug): void
    {
        if (ThemeStore::uninstall($slug)) {
            $app = app(AppSettings::class);
            if ($app->default_style === $slug) {
                $app->default_style = '';
                $app->save();
                $this->values['app']['default_style'] = '';
            }
            $this->dispatch('toast', message: __('griglia::t.themes.uninstalled_ok'), type: 'info');
        }
    }

    protected function field(string $group, string $key): array
    {
        $class = $this->groups()[$group] ?? abort(404, __('griglia::t.errors.not_found'));
        $fields = $class::fields();
        abort_unless(isset($fields[$key]), 404, __('griglia::t.errors.not_found'));

        return [$class, $fields[$key]];
    }

    public function render()
    {
        $style = RememberStyle::current();
        $skin = Themes::settingsSkin($style);
        $agentFields = AgentSettings::fields();
        $appFields = AppSettings::fields();
        $notificationKeys = [
            'agent' => ['notify_on_done', 'notify_on_question', 'daily_summary', 'daily_summary_time'],
            'app' => ['notify_in_app', 'notify_webpush', 'notify_mail'],
        ];
        $sectionFields = static function (string $group, array $fields): array {
            $out = [];
            foreach ($fields as $key => $field) {
                $out[$group.'.'.$key] = [...$field, $group, $key];
            }

            return $out;
        };
        $notificationFields = [];
        foreach ($notificationKeys as $group => $keys) {
            $source = $group === 'agent' ? $agentFields : $appFields;
            $notificationFields += $sectionFields($group, array_intersect_key($source, array_flip($keys)));
        }
        $agentFields = array_diff_key($agentFields, array_flip($notificationKeys['agent']));
        $appFields = array_diff_key($appFields, array_flip($notificationKeys['app']));

        return view('griglia::livewire.settings-page', [
            'skin' => $skin,
            'installedThemes' => ThemeStore::installed(),
            'pushSubscriptions' => method_exists(auth()->user() ?? new \stdClass, 'pushSubscriptions') ? auth()->user()->pushSubscriptions()->count() : 0,
            'questionPreviews' => QuestionLevel::previews(), // level => context block (task 499)
            'sections' => [
                'agent' => [__('griglia::t.settings_agent_title', ['agent' => Agent::name()]), __('griglia::t.settings_agent_intro'), $sectionFields('agent', $agentFields)],
                'optimization' => [__('griglia::t.settings_optimization_title'), __('griglia::t.settings_optimization_intro'), $sectionFields('optimization', OptimizationSettings::fields())],
                'app' => [__('griglia::t.settings_app_title'), __('griglia::t.settings_app_intro'), $sectionFields('app', $appFields)],
                'notif' => [__('griglia::t.notif.title'), __('griglia::t.settings_notifications_intro'), $notificationFields],
            ],
        ])->layout($skin['layout'], $skin['layoutData'] + ['title' => 'Impostazioni'])->title(__('griglia::t.settings_title'));
    }
}
