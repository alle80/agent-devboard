# Using the board

## Lists

The lists menu (top left) switches between your lists, creates new ones (optionally **as a plan**, see
[Plans](../features/plans.md)), renames or deletes them. The **agent list** (config `agent_list`) is the channel with the
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

## The task modal

Title, **Task** note (Markdown editor, with a microphone for [speech to text](../features/ai.md#speech-to-text)), the agent's answer box, statistics (working time,
tokens, cost), the agent's **skills** accordion, images (upload, camera, paste; AI description when enabled),
sub-tasks (Markdown, sortable), questions/answers, resume-from context. Header: state badge (tap to toggle),
**move to another list**, archive, delete; on a completed task: **resume with changes** (a new linked task).

## Toolbar

Free-text search (title, notes, comment, sub-tasks, questions, image descriptions), state filters, archive.
On a plan list the **Plan** bar shows progress and the start/pause buttons (see [Plans](../features/plans.md)).

## Mobile

Everything is designed for phones: rows on two levels, full-screen modal, full-width notification panel, Web Push.

## See also

- [The agent side](../agent/index.md) — what the agent does with what you write here.
- [Plans](../features/plans.md) · [Notifications](../features/notifications.md) · [AI features](../features/ai.md)
- [Feature overview](../features/index.md) — the whole board in one page.
