<?php

namespace Alle80\Devboard\Livewire;

use Alle80\Devboard\Settings\AgentSettings;
use Alle80\Devboard\Settings\AppSettings;
use Alle80\Devboard\Settings\OptimizationSettings;
use Alle80\Devboard\Http\Middleware\RememberStyle;
use Alle80\Devboard\Themes;
use Alle80\Devboard\ThemeStore;
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
        abort_unless(\Alle80\Devboard\Admin::check(), 403, 'Administrators only.');
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
        abort_unless($field[2] === 'bool', 422);

        $settings = app($class);
        $settings->{$key} = ! $settings->{$key};
        $settings->save();

        $this->values[$group][$key] = $settings->{$key};
        $this->dispatch('toast', message: __($settings->{$key} ? 'devboard::t.msg.setting_on' : 'devboard::t.msg.setting_off', ['label' => $field[0]]), type: $settings->{$key} ? 'success' : 'info');
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
                if ($group === 'app' && $key === 'mode' && $value === 'local' && ! \Alle80\Devboard\Mode::localFromUiAllowed()) {
                    $this->values[$group][$key] = $settings->{$key};
                    $this->dispatch('toast', message: __('devboard::t.msg.local_not_allowed'), type: 'error');
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
                    $this->dispatch('toast', message: __('devboard::t.msg.invalid_time'), type: 'error');
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
        \Alle80\Devboard\Mode::reset();
        $this->values[$group][$key] = $settings->{$key};
        $this->dispatch('toast', message: __('devboard::t.msg.setting_saved', ['label' => $field[0]]));
    }

    // ----- Theme packs -----

    /** Livewire calls this as soon as the zip has been uploaded. */
    public function updatedThemeZip(): void
    {
        if (! $this->themeZip) {
            return;
        }

        try {
            $this->validate(['themeZip' => ['file', 'max:20480']], ['themeZip.max' => __('devboard::t.themes.err_too_big')]);
            $def = ThemeStore::install($this->themeZip->getRealPath());
            $this->dispatch('toast', message: __('devboard::t.themes.installed_ok', ['label' => $def['label']]));
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('toast', message: collect($e->errors())->flatten()->first(), type: 'error');
        } catch (\RuntimeException $e) {
            $this->dispatch('toast', message: $e->getMessage(), type: 'error'); // ThemeStore's own, translated messages
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('devboard: theme install failed: '.$e->getMessage());
            $this->dispatch('toast', message: __('devboard::t.themes.err_generic'), type: 'error');
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
            $this->dispatch('toast', message: __('devboard::t.themes.uninstalled_ok'), type: 'info');
        }
    }

    protected function field(string $group, string $key): array
    {
        $class = $this->groups()[$group] ?? abort(404);
        $fields = $class::fields();
        abort_unless(isset($fields[$key]), 404);

        return [$class, $fields[$key]];
    }

    public function render()
    {
        $style = RememberStyle::current();
        $skin = Themes::settingsSkin($style);

        return view('devboard::livewire.settings-page', [
            'skin' => $skin,
            'installedThemes' => ThemeStore::installed(),
            'pushSubscriptions' => method_exists(auth()->user() ?? new \stdClass, 'pushSubscriptions') ? auth()->user()->pushSubscriptions()->count() : 0,
            'sections' => [
                'agent' => [__('devboard::t.settings_agent_title', ['agent' => \Alle80\Devboard\Agent::name()]), __('devboard::t.settings_agent_intro'), AgentSettings::fields()],
                'optimization' => [__('devboard::t.settings_optimization_title'), __('devboard::t.settings_optimization_intro'), OptimizationSettings::fields()],
                'app' => [__('devboard::t.settings_app_title'), __('devboard::t.settings_app_intro'), AppSettings::fields()],
            ],
        ])->layout($skin['layout'], $skin['layoutData'] + ['title' => 'Impostazioni'])->title(__('devboard::t.settings_title'));
    }
}
