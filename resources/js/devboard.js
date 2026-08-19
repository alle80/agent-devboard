// alle80/agent-devboard — browser side: drag & drop (SortableJS, exposed globally for the Alpine x-init of the
// lists) and live updates via Laravel Echo. Echo is set up SYNCHRONOUSLY (static import) so window.Echo exists
// before Livewire wires the #[On('echo-private:...')] listeners — a dynamic import() resolves too late
// (especially on mobile), Livewire wins the race, and the private channel is never subscribed.
// echo.js is a no-op when no broadcaster key is configured.
// Host app usage:  import '../../vendor/alle80/agent-devboard/resources/js/devboard.js';
import Sortable from 'sortablejs';
import './echo.js';

window.Sortable = Sortable;
