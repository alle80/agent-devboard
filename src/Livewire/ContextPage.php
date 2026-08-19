<?php

namespace Alle80\Devboard\Livewire;

use Alle80\Devboard\Http\Middleware\RememberStyle;
use Alle80\Devboard\Models\ContextBlock;
use Alle80\Devboard\Models\ContextGroup;
use Alle80\Devboard\Support\Context;
use Alle80\Devboard\Themes;
use Livewire\Component;

/**
 * /context — the agent's context (instructions file) as groups and blocks: switch a whole group or single
 * blocks, select many blocks and enable/disable them together, edit/add/delete/reorder blocks and groups.
 * What is enabled here is what `devboard:context export` returns (and the host script writes to the file).
 */
class ContextPage extends Component
{
    /** Selected block ids (multi-select). */
    public array $selected = [];

    /** Block being edited + draft, or null. */
    public ?int $editingId = null;

    public string $bodyDraft = '';

    public string $titleDraft = '';

    /** Group being renamed + draft. */
    public ?int $renamingGroupId = null;

    public string $groupDraft = '';

    public string $newGroup = '';

    /** Defence in depth: admin-only, also on Livewire update requests. */
    public function boot(): void
    {
        abort_unless(\Alle80\Devboard\Admin::check(), 403, 'Administrators only.');
    }

    // ----- Groups -----

    public function toggleGroup(int $id): void
    {
        $g = ContextGroup::findOrFail($id);
        $g->update(['enabled' => ! $g->enabled]);
    }

    public function addGroup(): void
    {
        $title = trim($this->newGroup);
        if ($title === '') {
            return;
        }
        ContextGroup::create(['title' => $title, 'order' => ((int) ContextGroup::max('order')) + 1, 'enabled' => true]);
        $this->newGroup = '';
    }

    public function startRenameGroup(int $id): void
    {
        $g = ContextGroup::findOrFail($id);
        $this->renamingGroupId = $g->id;
        $this->groupDraft = $g->title;
    }

    public function saveGroup(): void
    {
        if ($this->renamingGroupId && trim($this->groupDraft) !== '') {
            ContextGroup::whereKey($this->renamingGroupId)->update(['title' => trim($this->groupDraft)]);
        }
        $this->renamingGroupId = null;
    }

    public function deleteGroup(int $id): void
    {
        ContextGroup::whereKey($id)->delete();
        $this->selected = array_values(array_filter($this->selected, fn ($b) => ContextBlock::whereKey($b)->exists()));
    }

    public function reorderGroups(array $ids): void
    {
        foreach (array_values($ids) as $i => $id) {
            ContextGroup::whereKey((int) $id)->update(['order' => $i + 1]);
        }
    }

    // ----- Blocks -----

    public function toggleBlock(int $id): void
    {
        $b = ContextBlock::findOrFail($id);
        $b->update(['enabled' => ! $b->enabled]);
    }

    public function toggleSelect(int $id): void
    {
        $this->selected = in_array($id, $this->selected, true)
            ? array_values(array_diff($this->selected, [$id]))
            : [...$this->selected, $id];
    }

    public function selectGroup(int $groupId, bool $on = true): void
    {
        $ids = ContextBlock::where('group_id', $groupId)->pluck('id')->all();
        $this->selected = $on ? array_values(array_unique([...$this->selected, ...$ids])) : array_values(array_diff($this->selected, $ids));
    }

    public function clearSelection(): void
    {
        $this->selected = [];
    }

    /** Enable/disable every selected block at once. */
    public function setSelected(bool $enabled): void
    {
        if ($this->selected) {
            ContextBlock::whereIn('id', $this->selected)->update(['enabled' => $enabled]);
        }
        $this->selected = [];
    }

    public function startEdit(int $id): void
    {
        $b = ContextBlock::findOrFail($id);
        $this->editingId = $b->id;
        $this->bodyDraft = $b->body;
        $this->titleDraft = (string) $b->title;
    }

    public function cancelEdit(): void
    {
        $this->editingId = null;
    }

    public function saveEdit(): void
    {
        if (! $this->editingId) {
            return;
        }
        $body = rtrim($this->bodyDraft);
        if ($body === '') {
            return;
        }
        $title = trim($this->titleDraft);
        ContextBlock::whereKey($this->editingId)->update(['body' => $body, 'title' => $title !== '' ? $title : Context::titleOf($body)]);
        $this->editingId = null;
    }

    public function addBlock(int $groupId): void
    {
        $g = ContextGroup::findOrFail($groupId);
        $b = $g->blocks()->create(['title' => null, 'body' => '', 'order' => ((int) $g->blocks()->max('order')) + 1, 'enabled' => true]);
        $this->editingId = $b->id;
        $this->bodyDraft = '';
        $this->titleDraft = '';
    }

    public function deleteBlock(int $id): void
    {
        ContextBlock::whereKey($id)->delete();
        $this->selected = array_values(array_diff($this->selected, [$id]));
        if ($this->editingId === $id) {
            $this->editingId = null;
        }
    }

    public function reorderBlocks(int $groupId, array $ids): void
    {
        foreach (array_values($ids) as $i => $id) {
            ContextBlock::whereKey((int) $id)->where('group_id', $groupId)->update(['order' => $i + 1]);
        }
    }

    public function render()
    {
        $style = RememberStyle::current();
        $skin = Themes::settingsSkin($style);
        [$on, $total] = Context::tokens();

        return view('devboard::livewire.context-page', [
            'skin' => $skin,
            'groups' => ContextGroup::with('blocks')->orderBy('order')->orderBy('id')->get(),
            'tokensOn' => $on,
            'tokensTotal' => $total,
        ])->layout($skin['layout'], $skin['layoutData'] + ['title' => 'Context'])->title(__('devboard::t.ctx.title', ['agent' => \Alle80\Devboard\Agent::name()]));
    }
}
