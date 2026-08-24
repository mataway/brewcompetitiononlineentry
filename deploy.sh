#!/usr/bin/env bash
#
# Deploy californiacidercup.com over SFTP.
#
# Mirrors the working tree to the production docroot with lftp, excluding the
# config file and the runtime upload directories. Dry run by default - nothing
# is written to the server unless you pass --live.
#
#   ./deploy.sh              # dry run: show what would change
#   ./deploy.sh --live       # actually upload
#   ./deploy.sh --live --delete   # also remove remote files deleted locally
#
# See DEPLOY.md for the full picture, including the post-deploy smoke test.

set -euo pipefail

# ---------------------------------------------------------------- connection

REMOTE_HOST="sftp.californiacidercup.com"
REMOTE_PORT="9022"
REMOTE_USER="californiacidercup.com"
REMOTE_ROOT="/home/californiacidercup.com/www"

# Located by pattern so the account-id filename isn't baked into the repo.
# Override with: CCC_DEPLOY_KEY=/path/to/key.pem ./deploy.sh
SSH_KEY="${CCC_DEPLOY_KEY:-$(ls "$HOME"/.ssh/*keys-californiacidercup.com.pem 2>/dev/null | head -1 || true)}"

# ---------------------------------------------------------------- exclusions
#
# Two kinds of entry here, and the first kind is load-bearing:
#
#   NEVER DEPLOY - overwriting or deleting these breaks the live site.
#     site/config.php   live DB credentials; placed on the server by hand
#     user_images/      admin uploads land here, including the contest logo
#     user_docs/        entrant-facing document uploads
#     user_temp/        scratch space the app writes to
#   Git only tracks the placeholder files in those directories, so a mirror
#   without these exclusions would delete every real upload.
#
#   DEV-ONLY - harmless on the server, just dead weight over the wire.

EXCLUDES=(
    # never deploy
    #   The trailing * is load-bearing: it also catches environment copies like
    #   site/config.php.prod. Those hold live credentials and, because .prod is
    #   not a PHP extension, nginx would serve them as PLAIN TEXT.
    "site/config.php*"
    #   Credential-free, but it would be publicly fetchable and only advertises
    #   the install layout. Nothing on the server reads it.
    "site/config.sample.php"
    "user_images/"
    "user_docs/"
    "user_temp/"
    # dev-only
    #   lftp mirrors the working tree, not git - .gitignore does NOT apply here,
    #   so anything ignored but present on disk must be listed explicitly.
    ".DS_Store"
    ".git/"
    ".github/"
    "assets/"
    "sql/"
    "phpstan*"
    "composer.*"
    ".gitignore"
    "deploy.sh"
    "DEPLOY.md"
    "README.*"
)

# ---------------------------------------------------------------- arguments

LIVE=0
DELETE=0

for arg in "$@"; do
    case "$arg" in
        --live)   LIVE=1 ;;
        --delete) DELETE=1 ;;
        -h|--help)
            sed -n '3,14p' "$0" | sed 's/^# \{0,1\}//'
            exit 0
            ;;
        *)
            echo "Unknown option: $arg (try --help)" >&2
            exit 2
            ;;
    esac
done

# ---------------------------------------------------------------- preflight

cd "$(dirname "$0")"

command -v lftp >/dev/null 2>&1 || {
    echo "error: lftp is not installed. brew install lftp" >&2
    exit 1
}

[ -n "$SSH_KEY" ] && [ -f "$SSH_KEY" ] || {
    echo "error: SSH key not found." >&2
    echo "       Looked for ~/.ssh/*keys-californiacidercup.com.pem" >&2
    echo "       Set CCC_DEPLOY_KEY=/path/to/key.pem to override." >&2
    exit 1
}

# ssh refuses group/world-readable private keys.
PERMS="$(stat -f '%Lp' "$SSH_KEY" 2>/dev/null || stat -c '%a' "$SSH_KEY")"
case "$PERMS" in
    600|400) ;;
    *) echo "warning: $SSH_KEY is mode $PERMS; ssh may refuse it (chmod 600)" >&2 ;;
esac

# lftp mirrors the working tree, not HEAD - uncommitted edits go live too.
if [ -n "$(git status --porcelain 2>/dev/null)" ]; then
    echo "warning: working tree has uncommitted changes; they WILL be deployed:" >&2
    git status --short >&2
    echo >&2
fi

# ---------------------------------------------------------------- build + run

# Built as a string because lftp parses its own command line. Patterns are
# single-quoted for *lftp*, not escaped for the shell: printf %q would turn
# "phpstan*" into "phpstan\*", which lftp reads as a literal asterisk, and the
# wildcard exclusions would silently match nothing.
MIRROR_CMD="mirror -R --only-newer --verbose"
[ "$LIVE" -eq 1 ]   || MIRROR_CMD="$MIRROR_CMD --dry-run"
[ "$DELETE" -eq 1 ] && MIRROR_CMD="$MIRROR_CMD --delete"

for pattern in "${EXCLUDES[@]}"; do
    MIRROR_CMD="$MIRROR_CMD -X '$pattern'"
done

MIRROR_CMD="$MIRROR_CMD ./ '$REMOTE_ROOT'"

if [ "$LIVE" -eq 1 ]; then
    echo "About to deploy to ${REMOTE_HOST}:${REMOTE_ROOT}"
    [ "$DELETE" -eq 1 ] && echo "  --delete is ON: remote files missing locally will be removed."
    printf "Continue? [y/N] "
    read -r reply
    case "$reply" in [yY]*) ;; *) echo "Aborted."; exit 0 ;; esac
else
    echo "DRY RUN - nothing will be written. Re-run with --live to deploy."
fi
echo

# lftp appends its own '-s -l USER -p PORT HOST sftp' to connect-program,
# so the key is all that belongs here. Note also that the sftp://user@host
# URL form silently drops the username - it has to come from -u.
lftp -c "
set sftp:connect-program 'ssh -a -x -i \"$SSH_KEY\"';
set net:max-retries 2;
set mirror:parallel-transfer-count 4;
open -u '${REMOTE_USER},' sftp://${REMOTE_HOST}:${REMOTE_PORT};
$MIRROR_CMD;
"

echo
if [ "$LIVE" -eq 1 ]; then
    echo "Done. Smoke test (see DEPLOY.md):"
    echo "  https://californiacidercup.com/                        -> landing page"
    echo "  https://californiacidercup.com/index.php?section=default -> competition page"
    echo "  https://californiacidercup.com/index.php?msg=98          -> competition page WITH alert"
fi
