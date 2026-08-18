// alle80/agent-devboard — browser side: drag & drop (SortableJS, exposed globally for the Alpine x-init of the
// lists) and live updates via Laravel Echo. Echo is loaded only when a key is configured, either at
// runtime by <x-devboard::assets /> (window.DEVBOARD_ECHO, from config('devboard.echo')) or at build
// time through VITE_REVERB_APP_KEY when the host app bundles this file itself.
// Host app usage:  import '../../vendor/alle80/agent-devboard/resources/js/devboard.js';
import Sortable from 'sortablejs';

window.Sortable = Sortable;

const echoKey = window.DEVBOARD_ECHO?.key || import.meta.env.VITE_REVERB_APP_KEY;

if (echoKey) {
    import('./echo.js');
}
