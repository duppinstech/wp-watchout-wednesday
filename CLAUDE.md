@AGENTS.md

# Claude

Commands, conventions, and do-not-touch rules are in AGENTS.md. Do not copy them here.

- Put machine-specific notes in `CLAUDE.local.md` (gitignored). Start from `CLAUDE.local.md.example`.
- Put local permission overrides in `.claude/settings.local.json` (gitignored). Start from `.claude/settings.local.json.example`.
- `.claude/settings.json` denies env reads and destructive git. Do not weaken those denies in the shared file.
