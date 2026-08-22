# Plans

A **plan** is a list built from a prompt. From the lists menu, **Plans** opens `/plans`, a dedicated overview
where every plan can be opened, edited, started, paused or resumed. **New plan** opens `/plans/new` —
because describing a goal takes a paragraph, not a line:

- **the goal** is the field that matters: a large box, with the microphone for dictation and a character
  count. Ctrl/⌘+Enter builds the plan without leaving the keyboard;
- **the name of the list** is optional: leave it empty and the first words of the goal become the name;
- **the agent** of the plan can be chosen here when the install declares more than one.

What you type is kept as a draft, so leaving the page and coming back finds it where you left it, and
cancelling asks before throwing it away. Pressing *Build the plan* hands the goal to the AI SDK agent
`PlanBuilder`, which splits it into ordered tasks with notes and sub-tasks, **chained** (`depends_on_id`),
and takes you back to the board. For each task it may also select pertinent, genuinely useful skills from the
ones installed for the plan's default agent; unavailable skills are never assigned. Without an AI provider the list gets a single «Build the plan» task for the
agent; if the AI fails, no half-created list is left behind.

## Changing a plan

The **Plan** bar has an *Edit the plan* link — `/plans/{list}/edit` — with the original goal in it. From
there you can:

- **save the goal** (and the name or agent of the list): the tasks stay exactly as they are;
- **rebuild the tasks**: only the ones nobody has started are replaced. Tasks already done, taken by the
  agent or waiting for an answer are never touched, and the confirmation says how many will be replaced.

## Running a plan

- **Start the plan** (the start button in the Plan bar or on the Plans page): the first not-started task becomes *open to work*; when it is
  completed the next one opens automatically.
- **Pause**: open tasks go back to *waiting* and the chain stops; **Resume** clears the pause and opens the next one.
- New tasks added to a plan list join the chain automatically; after completion you can add tasks and resume.
- `griglia:check` / `griglia:watch` cover the started plans too (after the agent list).
- If a plan still has work but nothing is open to work, `griglia:check` says so: it is the one state where
  you could be waiting for an agent that is waiting for you.

## See also

- [The agent side](../agent/index.md) — how the agent walks the chain.
- [AI features](ai.md) — the model that builds the plan.
