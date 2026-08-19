# Plans

A **plan** is a list built from a prompt. In the lists menu tick **Create as a plan**, describe the goal (🎤
available) and press +: the AI SDK agent `PlanBuilder` splits it into ordered tasks with notes and sub-tasks,
**chained** (`depends_on_id`). Without an AI provider the list gets a single «Build the plan» task for the agent.

- **Start the plan** (▶ in the Plan bar or in the lists menu): the first not-started task becomes 🟢; when it is
  completed the next one opens automatically.
- **Pause** (⏸): open tasks go back to ⚪ and the chain stops; **Resume** clears the pause and opens the next one.
- New tasks added to a plan list join the chain automatically; after completion you can add tasks and resume.
- `devboard:check` / `devboard:watch` cover the started plans too (after the agent list).
