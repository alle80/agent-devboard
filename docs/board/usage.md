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

Tap the dot to move between *waiting* and *open to work* (or to stop the agent). Tasks the agent completed and you have not opened yet are
highlighted until you open them.

### Carrying on after a task is done

**A closed task stays closed.** The checkbox and the state dot do not reopen it: what the agent answered
stays as it was answered, and nothing it already finished goes back in front of it.

To carry on there is one way: **resume** (the ↻ button on the row or in the modal). It creates a *new* task
right after the old one, with the same title and the old one attached as context — note, answer, sub-tasks
and images stay one click away (the box is closed until you open it: what matters now is what you are
asking today), and `griglia:check` shows them to the agent.

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

## Toolbar

Free-text search (title, notes, comment, sub-tasks, questions, image descriptions), state filters, archive.
On a plan list the **Plan** bar shows progress and the start/pause buttons (see [Plans](../features/plans.md)).

## Mobile

Everything is designed for phones: rows on two levels, full-screen modal, full-width notification panel, Web Push.

## See also

- [The agent side](../agent/index.md) — what the agent does with what you write here.
- [Plans](../features/plans.md) · [Notifications](../features/notifications.md) · [AI features](../features/ai.md)
- [Feature overview](../features/index.md) — the whole board in one page.
