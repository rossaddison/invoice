#!/bin/sh
# Refuses commits that carry real people's data, secrets, or files that should never be in git.
# Shared by .githooks/pre-commit, .githooks/pre-push and the "Sensitive data guard" CI workflow, so the
# same rules apply on a laptop and on GitHub. See docs/SENSITIVE_TEST_DATA_GUARD_NOVEMBER_2026.md.
#
# Usage:
#   sensitive-guard.sh paths [<rev>]        file paths on stdin; <rev> is where file sizes are read
#                                           from ("" = the index, i.e. what is staged)
#   sensitive-guard.sh range <base> <head>  every commit in base..head. A base that is empty or all
#                                           zeros means "commits that are on no remote yet".
#
# Exit status 1 when anything is refused. Nothing is ever printed from inside a file.

ALLOWLIST="$(dirname "$0")/sensitive-allowlist"
MAX_BYTES=5000000

# Prints the reason a path is refused, or nothing when it is fine.
reason_for() {
    p="$1"
    ref="$2"

    case "$p" in
        imports/*|*/imports/*)
            echo "the imports/ folder holds customer run documents"
            return
            ;;
    esac

    base=$(basename "$p")
    case "$base" in
        .env|.env.*)
            echo "environment files hold secrets"
            return
            ;;
    esac

    ext=$(printf '%s' "$base" | sed -n 's/.*\.\([A-Za-z0-9]*\)$/\1/p' | tr 'A-Z' 'a-z')
    case "$ext" in
        docx|doc|xlsx|xls|odt|ods|pst|sqlite|sqlite3|db|zip|7z|rar|tar|gz|tgz)
            echo "documents, databases and archives must not be committed"
            return
            ;;
        csv|tsv)
            case "$p" in
                Tests/*|*/Tests/*|Fixtures/*|fixtures/*|*/Fixtures/*|*/fixtures/*) ;;
                *)
                    echo "CSV/TSV files belong only under Tests/ or a Fixtures/ folder, as virtual data"
                    return
                    ;;
            esac
            ;;
    esac

    size=$(git cat-file -s "${ref}:${p}" 2>/dev/null || echo 0)
    if [ "$size" -gt "$MAX_BYTES" ]; then
        echo "file is $size bytes (the limit is $MAX_BYTES)"
        return
    fi
}

is_allowed() {
    [ -f "$ALLOWLIST" ] && grep -v '^[[:space:]]*#' "$ALLOWLIST" | grep -qxF "$1"
}

check_paths() {
    ref="$1"
    refused=0
    while IFS= read -r p; do
        [ -z "$p" ] && continue
        is_allowed "$p" && continue
        why=$(reason_for "$p" "$ref")
        if [ -n "$why" ]; then
            if [ $refused -eq 0 ]; then
                echo "Sensitive data guard: refused" >&2
            fi
            echo "  $p -- $why" >&2
            refused=1
        fi
    done
    return $refused
}

case "$1" in
    paths)
        check_paths "$2"
        exit $?
        ;;
    range)
        base="$2"
        head="$3"
        case "$base" in
            ""|0000000000000000000000000000000000000000)
                commits=$(git rev-list "$head" --not --remotes 2>/dev/null)
                ;;
            *)
                commits=$(git rev-list "$base..$head" 2>/dev/null)
                ;;
        esac
        status=0
        for c in $commits; do
            git diff-tree --root --no-commit-id --name-only -r --diff-filter=ACMR "$c" | check_paths "$c" || status=1
        done
        exit $status
        ;;
    *)
        echo "Usage: sensitive-guard.sh paths [<rev>] | range <base> <head>" >&2
        exit 2
        ;;
esac
