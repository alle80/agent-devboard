# Using the board

## Lists

The lists menu (top left) switches between your lists, creates new ones (optionally **as a plan**, see
[Plans](../features/plans.md)), renames or deletes them. The **agent list** (config `agent_list`) is the channel with the
coding agent; any other list is yours (or a plan).

## Tasks and states

Every row has a state dot:

| Dot | State | Who sets it |
|-----|-------|-------------|
| ⚪ | waiting | you — the agent must not touch it |
| 🟢 | open to work | you — ready for the agent |
| 🔧 | working | the agent (`--take`) — icon animated, progress % and phase next to the title |
| ❓ | question | the agent asked something; answer in the modal and restart |
| ⏹ | stopped | you tapped 🔧 — the agent stops at once |
| ✔ | done | the agent (`--done`) or you (checkbox) |

Tap the dot to toggle ⚪ ⇄ 🟢 (or to stop the agent). Tasks the agent completed and you have not opened yet are
highlighted until you open them.

## The task modal

Title, **Task** note (Markdown editor with 🎤 speech to text), the agent's answer box, statistics (working time,
tokens, cost), the agent's **skills** accordion, images (upload, camera, paste; AI description when enabled),
sub-tasks (Markdown, sortable), questions/answers, resume-from context. Header: state badge (tap to toggle),
**move to another list**, archive, delete; on a completed task: **resume with changes** (a new linked task).

## Toolbar

Free-text search (title, notes, comment, sub-tasks, questions, image descriptions), state filters, archive.
On a plan list the **Plan** bar shows progress and ▶ / ⏸ (see [Plans](../features/plans.md)).

## Mobile

Everything is designed for phones: rows on two levels, full-screen modal, full-width notification panel, Web Push.
