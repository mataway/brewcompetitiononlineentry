# Deploying californiacidercup.com

FTP deploy notes for this installation. Read the **Never overwrite** section before
your first upload.

---

## Host facts worth knowing

These were verified against the live server and they change how things behave:

| | |
|---|---|
| Web server | **nginx (openresty)**, not Apache |
| `.htaccess` | **Inert.** The rewrite rules, bad-bot blocks, and `ErrorDocument` lines in the repo's `.htaccess` do nothing here. `/rules` returns an nginx 404, and the HTTPS redirect comes from nginx, not from the file. |
| SEF URLs | **Off** (`prefsSEF = N`). All links are `index.php?section=…`. Turning the preference on will produce broken links unless nginx rewrite rules are added first. |
| PHP config | `site/config.php` is a bootstrap script, not a key-value file. It sets `$prefix`, `$installation_id`, `$base_url`, session lifetime, and opens `$connection` — `paths.php` uses it immediately. |

---

## Never overwrite

Four paths must survive every deploy. `site/config.php` is the obvious one;
the other three are less obvious and would cause real damage:

| Path | Why |
|---|---|
| `site/config.php` | Live DB credentials. Placed on the server by hand, once. |
| `user_images/` | Admin uploads land here — **including the contest logo**. |
| `user_docs/` | Entrant-facing document uploads. |
| `user_temp/` | Scratch space the app writes to. |

The config exclusion is `site/config.php*`, with the trailing `*`. Without it,
environment copies like `site/config.php.prod` are uploaded into the public
docroot — and since `.prod` is not a PHP extension, nginx serves them as **plain
text**. That is a full credential disclosure over HTTP, and a dry run is how you
catch it.

**`.gitignore` does not apply to lftp.** It mirrors the working tree, so anything
ignored-but-present on disk (`.DS_Store`, `site/config.php.prod`) uploads unless
it is in the script's own exclusion list. Check with:

```bash
git status --ignored --porcelain | grep -E '^(\?\?|!!)'
```

Every path that prints must either be excluded in `deploy.sh` or be something you
genuinely want on the server.

Git tracks only the placeholder files in those three directories
(`sample_logo.png` and two `readme.txt`), so a mirror without these exclusions
would delete every real upload.

**`site/config.php`** holds the live database credentials. Upload it **once**, by
hand, then exclude it from every subsequent sync. This is the single most
important rule here — an accidental overwrite takes the site down, and an
accidental *download-then-commit* leaks the password.

`./deploy.sh` already excludes it (see below). If you deploy by hand instead,
set the exclusion in your client so you can't forget:

**SFTP note:** SFTP is a protocol, not a tool — there is no "SFTP exclude
syntax". Exclusion is a feature of whatever client does the mirroring, and every
GUI client below filters identically whether it's connected over FTP or SFTP.

| Client | Exclude syntax |
|---|---|
| FileZilla | Site Manager → the site → *Filters* → exclude `site/config.php` |
| WinSCP | Options → Transfer → *File mask* → `| site/config.php` (leading pipe = exclude) |
| Cyberduck | Preferences → Transfers → *Filter* |
| Sublime Text SFTP | `"ignore_regexes": ["site/config\\.php$"]` in `sftp-config.json` |
| `lftp` (`sftp://`) | `mirror -R --exclude-glob 'site/config.php' . /public_html` |
| `rsync -e ssh` | `--exclude 'site/config.php'` |
| `rclone` (sftp remote) | `--exclude 'site/config.php'` |
| OpenSSH `sftp` / `scp` | **No exclude support at all.** `put -r` uploads everything, every time. Do not deploy with these. |

`lftp` gotcha: `--exclude` takes a **regular expression**, `--exclude-glob` takes
a **glob**. `--exclude 'site/config.php'` works only by accident (the `.` matches
any character). Use `--exclude-glob`, or anchor the regex as
`--exclude 'site/config\.php$'`.

### The deploy script

`./deploy.sh` wraps all of this. It is a **dry run by default** — nothing reaches
the server without `--live`:

```bash
./deploy.sh                    # show what would change
./deploy.sh --live             # upload
./deploy.sh --live --delete    # also remove remote files deleted locally
```

Connection settings live at the top of the script. The SSH key is found by
pattern (`~/.ssh/*keys-californiacidercup.com.pem`) so the account-id filename
isn't baked into the repo; override with `CCC_DEPLOY_KEY=/path/to/key.pem`.

Before running it warns you if the working tree is dirty — **lftp mirrors the
working tree, not `HEAD`**, so uncommitted edits are deployed too.

**Two lftp details that cost real debugging time**, both verified against 4.9.3:

- lftp appends its own `-s -l USER -p PORT HOST sftp` to `sftp:connect-program`,
  so that setting should carry *only* the key. And the `sftp://user@host` URL
  form **silently drops the username** — ssh then falls back to your local login
  name. The user has to come from `-u 'name,'` (trailing comma = empty password).
- Exclusion patterns must be quoted for **lftp's** parser, not escaped for the
  shell. Passing them through `printf %q` turns `phpstan*` into `phpstan\*`,
  which lftp reads as a literal asterisk — the wildcard exclusions then match
  nothing and upload silently. The script single-quotes them instead.

### Alternative: `git-ftp`

`git-ftp` deploys **commits** rather than the working tree, and uploads only
git-tracked files — so a gitignored `site/config.php` can never go up, no
exclusion list required. The catch on macOS: it shells out to `curl`, and the
system `/usr/bin/curl` has no SFTP support. You'd need Homebrew's curl (keg-only)
ahead of it on `PATH`:

```bash
export PATH="/opt/homebrew/opt/curl/bin:$PATH"
curl -V | grep -o sftp        # must print sftp

git config git-ftp.url  "sftp://sftp.californiacidercup.com:9022/home/californiacidercup.com/www"
git config git-ftp.user "californiacidercup.com"
git config git-ftp.key  "$HOME/.ssh/…keys-californiacidercup.com.pem"

git ftp init -D && git ftp init     # first deploy
git ftp push -D && git ftp push     # subsequent
```

Use `.git-ftp-ignore` (glob patterns, one per line) to skip `assets/`, `sql/`
and friends.

---

## What the landing-page work added

**Modified**
- `index.php` — routes the site root to the landing page; loads the brand stylesheet
- `pub/nav.pub.php` — home icon and section anchors repointed
- `.gitignore`

**New**
- `index.landing.php` — the document served at `/`
- `pub/landing.pub.php` — About / Join Us sections
- `css/ccc.css` — brand layer (landing page + competition-page skin)
- `images/ccc/` — logo, mark, photos, favicons, social card
- `images/cider-ccc-apples_1800x300.webp` — hero image for the competition pages

All of these must be uploaded together. `index.php` will fatal if
`index.landing.php` is missing.

---

## Post-deploy: admin steps (no code)

These are configuration, done through the admin UI after the files are up:

1. **Contest logo** — Admin → Preferences. Replace the shipped `sample_logo.png`
   placeholder. Source art is in `assets/`.
2. **Hero image** — Admin → Hero Images. Enable `cider-ccc-apples_1800x300.webp`
   and disable the beer/misc images, otherwise the competition pages keep showing
   generic beer photography. Any file dropped in `/images` with a `cider-` prefix
   is auto-discovered.
3. **Contest Host / Host Website** — Admin → Preferences, so the salutation line
   on the competition page reads correctly.

---

## Post-deploy: smoke test

Routing is the part most likely to break, because the root now serves a different
page than it used to. Check all five:

| URL | Expected |
|---|---|
| `/` | Brand landing page |
| `/index.php` | Brand landing page |
| `/index.php?section=default` | Competition page (rules, dates, entry info) |
| `/index.php?msg=98` | Competition page **with its status alert visible** |
| `/index.php?section=admin` | Admin, unstyled by the brand layer |

That fourth row is the important one: roughly fifteen files under
`includes/process/` redirect to the bare root with a `?msg=` code after a save.
If the alert doesn't appear, the root-routing guard in `index.php` is catching
requests it shouldn't.

---

## Known gaps

- **Hero apple photo is low-resolution** (799px wide, displayed at ~1270px). It
  came from the Canva export; the original would fix the softness on the landing
  page and allow a sharper `cider-ccc-apples_1800x300.webp`.
- **Brand fonts are substitutes.** Futura Bold and Lota Grotesque are commercial
  with no free webfont, so `css/ccc.css` uses Jost and Hanken Grotesk. If a
  licensed webfont kit is obtained, `@font-face` it and put the real family first
  in `--ccc-font-display` / `--ccc-font-body`; nothing else changes.
- **Hero band orange** (`#FF9322`) is from the approved design and is not one of
  the four brand colours. Set `--ccc-orange` to `var(--ccc-orange-deep)` in
  `css/ccc.css` to normalise it.
