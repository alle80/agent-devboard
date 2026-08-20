// alle80/griglia — browser side: drag & drop (SortableJS, exposed globally for the Alpine x-init of the
// lists) and live updates via Laravel Echo. Echo is set up SYNCHRONOUSLY (static import) so window.Echo exists
// before Livewire wires the #[On('echo-private:...')] listeners — a dynamic import() resolves too late
// (especially on mobile), Livewire wins the race, and the private channel is never subscribed.
// echo.js is a no-op when no broadcaster key is configured.
// Host app usage:  import '../../vendor/alle80/griglia/resources/js/griglia.js';
import Sortable from 'sortablejs';
import './echo.js';
import './push.js';
import './dictate.js';

window.Sortable = Sortable;

// Mobile keyboard vs modal (task 303): when a field inside the scrollable modal body gets the focus,
// bring it above the virtual keyboard once the keyboard has settled. The viewport meta
// (interactive-widget=resizes-content) does the heavy lifting on Android; this is the safety net
// (iOS, browsers that ignore the meta, fields near the bottom edge).
document.addEventListener('focusin', (e) => {
    const field = e.target.closest('.modal-body input, .modal-body textarea, .modal-body [contenteditable]');
    if (! field) return;
    setTimeout(() => {
        if (document.activeElement === e.target) {
            field.scrollIntoView({ block: 'center', behavior: 'smooth' });
        }
    }, 300); // keyboard open animation
});
