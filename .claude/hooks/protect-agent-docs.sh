#!/usr/bin/env bash
#
# Enforces two rules from AGENTS.md against agent file writes:
#
#   Rule zero   AGENTS.md and CLAUDE.md may only be modified when the git signing
#               key configured in this working copy is the key that signed commit
#               81dd558c.
#   Section 1   No agent may create or modify anything under src/ or .patches/.
#               Unconditional — there is no key that unlocks these.
#
# PreToolUse hook on Edit|Write. Reads the tool call as JSON on stdin.
# Exit 0 = allow. Exit 2 = block, with the reason on stderr handed to the agent.
#
set -uo pipefail

GUARDED_COMMIT=81dd558c

input=$(cat)

command -v jq >/dev/null 2>&1 || exit 0
path=$(printf '%s' "$input" | jq -r '.tool_input.file_path // empty' 2>/dev/null)
[ -n "$path" ] || exit 0

repo="${CLAUDE_PROJECT_DIR:-$(git rev-parse --show-toplevel 2>/dev/null)}"
[ -n "$repo" ] || exit 0

case "$path" in
    /*) ;;
    *) path="$repo/$path" ;;
esac

# Resolve "..", "." and symlinks so a guarded file cannot be reached by an
# alternate spelling of its path. -m tolerates paths that do not exist yet.
if command -v realpath >/dev/null 2>&1; then
    repo=$(realpath -m -- "$repo" 2>/dev/null || printf '%s' "$repo")
    path=$(realpath -m -- "$path" 2>/dev/null || printf '%s' "$path")
fi

# --- Section 1: src/ and .patches/ are closed to agents, always. ------------
case "$path" in
    "$repo/src" | "$repo/src"/* | "$repo/.patches" | "$repo/.patches"/*)
        echo "Blocked by section 1 of AGENTS.md: agents may not create or modify anything under src/ or .patches/ (attempted: ${path#"$repo/"}). This is unconditional. There is no diff fallback for these paths — do not hand the user a patch, a full-file listing or copy-paste-ready code for them, and do not reach them by another route. Complete the parts of the task that are allowed and state plainly what was left out and why." >&2
        exit 2
        ;;
esac

# --- Rule zero: AGENTS.md / CLAUDE.md require the project signing key. ------
case "$path" in
    "$repo/AGENTS.md" | "$repo/CLAUDE.md") ;;
    *) exit 0 ;;
esac

key=$(git -C "$repo" config --get user.signingkey 2>/dev/null | tr -d ' ' | tr 'a-f' 'A-F')
fpr=$(git -C "$repo" log -1 --format=%GF "$GUARDED_COMMIT" 2>/dev/null | tr -d ' ' | tr 'a-f' 'A-F')

if [ -n "$key" ] && [ -n "$fpr" ] && [ "${fpr%"$key"}" != "$fpr" ]; then
    exit 0
fi

echo "Blocked by rule zero in AGENTS.md: $(basename "$path") may only be edited when this working copy's git signing key matches the key that signed commit ${GUARDED_COMMIT}. Do not retry — show the user the proposed change as a diff instead." >&2
exit 2
