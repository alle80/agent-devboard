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
| ![question](../images/state-question.svg){ width="18" } | question | the agent asked something; answer in the modal and restart |
| ![stopped](../images/state-stop.svg){ width="18" } | stopped | you tapped the working badge — the agent stops at once |
| ![done](../images/state-done.svg){ width="18" } | done | the agent (`--done`) or you (checkbox) |

Tap the dot to move between *waiting* and *open to work* (or to stop the agent). A completed row always
shows the *done* icon, even if it was open to work before completion.

Tap the dot to move between *waiting* and *open to work* (or to stop the agent).

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

Title, **Task** note (Markdown editor, with a microphone for [speech to text](../features/ai.md#speech-to-text)), the agent's answer box, statistics (working time,
tokens, cost), the agent's **skills** accordion, images (upload, camera, paste; AI description when enabled),
sub-tasks (Markdown, sortable), questions/answers, resume-from context. Header: state badge (tap to toggle),
**move to another list**, archive, delete; on a completed task: **resume with changes** (a new linked task).

### Moving between tasks

The modal has ‹ and › next to the state badge, with the position of the task in the list (`3/7`): they open
the previous and the next task without closing the modal — the way to follow a plan from one step to the
next. The **left and right arrow keys** do the same, unless you are typing in a field.

### Copying what is in a note

Notes and agent answers are Markdown: single newlines are displayed as line breaks, and a **code block has a copy button** in its corner (commands, prompts,
snippets), **inline code copies itself with one click**, and links open in a new tab.

## Toolbar

Free-text search (title, notes, comment, sub-tasks, questions, image descriptions), state filters, archive.
On a plan list the **Plan** bar shows progress and the start/pause buttons (see [Plans](../features/plans.md)).

## Mobile

Everything is designed for phones: rows on two levels, full-screen modal, full-width notification panel, Web Push.

The modal header splits in two on a narrow screen: the state badge with ‹ `3/7` › stays on the first line
next to the close button — always reachable, whatever the list holds — and the rest of the commands (agent,
move, archive, delete) sit on a second line, with touch targets big enough for a thumb. Nothing is hidden
behind a menu, and nothing runs off the edge of the screen.

## See also

- [The agent side](../agent/index.md) — what the agent does with what you write here.
- [Plans](../features/plans.md) · [Notifications](../features/notifications.md) · [AI features](../features/ai.md)
- [Feature overview](../features/index.md) — the whole board in one page.
