# Using the board

## Lists

The lists menu (top left) switches between your lists, creates new ones, renames or deletes them. A plan is
written on its own page — **New plan…** in the same menu, see [Plans](../features/plans.md). The **agent list** (config `agent_list`) is the channel with the
coding agent; any other list is yours (or a plan).

## Tasks and states

Every row has a state dot:

| Dot | State | Who sets it |
|-----|-------|-------------|
| ![waiting](../images/state-waiting.svg){ width="18" } | waiting | you — the agent must not touch it |
| ![open to work](../images/state-open.svg){ width="18" } | open to work | you — ready for the agent |
| ![working](../images/state-working.svg){ width="18" } | working | the agent (`--take`) — icon animated, progress % and phase next to the title |
| ![paused](../images/state-paused.svg){ width="18" } | paused | the agent (`--pause`) — progress is preserved; the agent worker resumes it automatically |
| ![question](../images/state-question.svg){ width="18" } | question | the agent asked something; answer in the modal and restart |
| ![stopped](../images/state-stop.svg){ width="18" } | stopped | you tapped the working badge — the agent stops at once |
| ![done](../images/state-done.svg){ width="18" } | done | the agent (`--done`) or you (checkbox) |

Tap the dot to move between *waiting* and *open to work*, stop the agent, or reopen a paused task. A completed row always
shows the *done* icon, even if it was open to work before completion.

When the agent has left a result, a very short automatic summary appears under the title. Agents can give a
more precise one with `griglia:check --done --summary="…"`; otherwise Griglia derives it from the closing
comment. It tells apart a sequence of resumed tasks that all share the same title.

At the right end of the title line every row shows the task **id** (`id:510`): the same `id:N` the agent prints
in `griglia:check`, and the number you use with `--take` / `--done` or when you talk about a task. Tap it to
copy the number (the chip says *copied* for a moment, and the modal does not open). The big number at the left
of the row is the position in the list, which changes when you reorder or archive; the id never changes.

### The colour of the row

A row you have not read yet is drawn with a coloured **border around its card**, and the colour says how
much it wants from you:

| Border | Meaning | Where it comes from |
|--------|---------|---------------------|
| green | done, nothing to check | `--done` (no `--outcome`, or `--outcome=ok`) |
| yellow | done, but something needs a look | `--done --outcome=alert` |
| red | something is in the way | `--done --outcome=blocked` |
| violet | the agent is waiting for your answers | `--ask` (open questions) |

The four colours are fixed rather than derived from the theme accent, and a highlighted row keeps them at
full strength: it is exempt from both the fading and the greyscale a theme applies to completed rows,
which would otherwise wash the border out.

Completed rows remain subdued, but their action buttons use a lighter grey (`--tl-done-action`) and stronger
opacity so archive, resume and delete stay legible, including on dark themes and small screens.

The colour is the whole signal: no badge in the row, no chip in the modal. Open the task and the border goes
back to the usual one of the theme; a task you close yourself has no coloured border, because there is no
result to read. A screen reader still gets the meaning, from a hidden label on the row, and the row's tooltip
spells it out.

The row writes the border colour on itself (inline), not only through the `.db-attention` / `.db-att-*`
classes. An app that runs the package views from `vendor/` while its stylesheet is compiled from another
copy of the package can end up with no rule at all for those classes: the highlight would silently
disappear. The stylesheet still adds the pulse and can be re-themed through `--db-att`.

### Carrying on after a task is done

**A closed task stays closed.** The checkbox and the state dot do not reopen it: what the agent answered
stays as it was answered, and nothing it already finished goes back in front of it.

To carry on there is one way: **resume** (the ↻ button on the row or in the modal). It creates a *new* task
right after the old one, with the same title and the old one attached as context — note, answer, sub-tasks
and images stay one click away (the box is closed until you open it: what matters now is what you are
asking today), and `griglia:check` shows them to the agent.

Resuming a resumed task keeps the **whole chain**: the box lists every previous step, from the most recent
one down to the request that started it all (`+2 earlier` next to the title), and the agent receives the same
history — so nothing that was asked or answered along the way is lost. If a step of the chain is deleted, the
task after it is re-linked to the one before, exactly like the chain of a plan.

Nothing else is a one-way door: a task that leaves the board (archived or deleted) hands its chain over to
the task before it, so a plan never waits for something that will never arrive, and a task with open
questions can be taken back without answering — tap its badge in the modal: the questions stay recorded and
the task goes back to waiting.

## The task modal
The board row and modal header prefix every task title with its list name (`List · Task`), so its context stays visible even when a search spans several lists. Renaming still edits only the task title.


Title, **Task** note (Markdown editor, with a microphone for [speech to text](../features/ai.md#speech-to-text)), the agent's answer box, statistics (working time,
tokens, cost), the agent's **skills** accordion, images (upload, camera, paste; AI description when enabled),
sub-tasks (Markdown, sortable), questions/answers, resume-from context.

A task is read-only while it is **working**, so the request cannot change under the agent’s feet. Tap the
working badge to stop it and return it to waiting before editing; it can then be marked open to work again.

The header uses the whole bar: on the left the **state badge**, whose text label stays beside its icon on
every screen, with ‹ `3/7` › and the task id (`id:510`, tap to copy the number) — and on the right the
commands: the agent that owns the task, **move to another list**, archive, delete, close, plus **resume with
changes** (a new linked task) on a completed one.

When several agents are configured, the selected agent is shown on its own line below the task title. The chip expands
to show the complete agent name, up to the available width of the row.

### Saving happens by itself

The title and the note are saved **while you type** — a short pause is enough. There is no «Save» button: what is
written is stored automatically. The small «Saved» flag next to the field says when the
last save happened, and the editor closes with `Enter` (title), `Esc`, or a click outside it.

As soon as the text differs from the one you started from, a **Cancel** button (↩)
appears next to the «Saved» flag: it puts back the title — or the note — as it was when you opened the
editor, and leaves you inside it, so you can keep writing from there. It is one step back, not a history:
close the editor and reopen it and the «previous version» becomes the text you have just left.

The same goes for the inline rename of a row in the list. Sub-tasks are not saved live: they keep their own
✓ and ✕ buttons.

### Moving between tasks

The modal has ‹ and › next to the state badge, with the position of the task in the list (`3/7`): they open
the previous and the next task without closing the modal — the way to follow a plan from one step to the
next. The **left and right arrow keys** do the same, unless you are typing in a field.

### Copying what is in a note

Notes and agent answers are Markdown: single newlines are displayed as line breaks, and a **code block has a copy button** in its corner (commands, prompts,
snippets), **inline code copies itself with one click**, and links open in a new tab.
While editing a note, the browser sizes its field directly from the content, so background autosaves cannot
collapse it or hide the last lines; saving stays silent so it does not interrupt typing.

## Toolbar

On desktop, compact labels, filter controls and the Markdown editor toolbar use a larger, more readable size;
the denser mobile sizing is preserved to leave room for board and modal content.

Free-text search (title, notes, comment, sub-tasks, questions, image descriptions), state and agent filters, archive.
When several agents are configured, the chip with the robot icon (**All agents**) narrows the list to one agent's
tasks; the filter follows the effective assignment (task override, then list default, then the global default),
combines with the search and the state filters, and — like them — disables drag & drop while it is on. Its visual
style stays unchanged after choosing an agent; the selected name is the indication of the active value.
Turn on **All lists** beside the search field to show tasks from every active list you own. The scope applies to the
unfiltered board as well as text, state and agent filters, and each result shows its list name. Archived lists and
other users’ lists stay outside the board.
On a plan list the **Plan** bar shows progress and the start/pause buttons (see [Plans](../features/plans.md)).

## Desktop: the dashboard

On a big screen the board has a second home: **`/dashboard`**, the same list rendered in a wider, roomier
container (`max-w-5xl` instead of `max-w-2xl`), so long titles and notes stop wrapping every few words. It is
the same component and the same data — states, modal, filters, drag & drop all behave exactly as on `/` — only
the width changes. The page wears the current theme when that theme is a generic one; from a dedicated style of
the host app (which has no shared CSS variables) it falls back to the default theme.

The path comes from the config key `dashboard_route` (`GRIGLIA_DASHBOARD_ROUTE`, default `/dashboard`); set it
to `null` and the route disappears, together with the tab below.

### The side tab

Every page carries a **slide-out tab pinned to one edge of the window** — a handle, debugbar style, that opens
a panel with the dashboard inside it. Click the handle and the panel slides out; drag its inner edge to resize
it (from 300px up to 70% of the window); ⤢ opens the full dashboard in the tab you are on; ✕ closes the panel.
Whether it is open and how wide it is are remembered in the browser (`localStorage`), so the panel comes back
the way you left it on the next page. It never appears on the dashboard itself, and it is **desktop only**:
below the `lg` breakpoint it is not rendered at all, on the principle that a phone has no room to spare.

Two settings in `/settings` govern it:

| Setting | What it does |
|---|---|
| **DASHBOARD side tab** (`show_dashboard_tab`) | Shows or hides the tab. Off, the dashboard stays reachable at its own address. |
| **Dashboard tab side** (`tab_side`) | Which edge the tab lives on — `right` (default) or `left`. |

## Mobile

Everything is designed for phones: rows on two levels, full-screen modal, full-width notification panel, Web Push.

The modal header stacks on a narrow screen: the state badge keeps its text label beside the icon and,
with ‹ `3/7` ›, stays on the first line next to the close button — always understandable and reachable, whatever the list
holds — the agent selector takes the line below, aligned left, and the remaining commands (move, archive,
delete) sit on the last line, with touch targets big enough for a thumb. The selector has a line of its
own on every screen: among the icons its label («Default (Claude Code)») ended up clipped. Nothing is
hidden behind a menu, and nothing runs off the edge of the screen.

## See also

- [The agent side](../agent/index.md) — what the agent does with what you write here.
- [Plans](../features/plans.md) · [Notifications](../features/notifications.md) · [AI features](../features/ai.md)
- [Feature overview](../features/index.md) — the whole board in one page.
