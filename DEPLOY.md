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

**`site/config.php`** holds the live database credentials. Upload it **once**, by
hand, then exclude it from every subsequent sync. This is the single most
important rule here — an accidental overwrite takes the site down, and an
accidental *download-then-commit* leaks the password.

Set the exclusion in your FTP client so you can't forget:

| Client | Where |
|---|---|
| FileZilla | Site Manager → the site → *Filters* → exclude `site/config.php` |
| WinSCP | Options → Transfer → *File mask* → exclude `site/config.php` |
| Cyberduck | Preferences → Transfers → *Filter* |
| `lftp` | `mirror -R --exclude 'site/config.php' . /public_html` |
| `rsync` | `--exclude 'site/config.php'` |

Environment-specific copies (`site/config.php.prod` and friends) are gitignored —
see the entry in `.gitignore`. Keep them locally or in a password manager, never
in the repo.

### Optional exclusions

Not required, just dead weight over FTP: `.git/`, `assets/` (print-resolution
brand art, ~700 KB, not used at runtime), `composer.json` / `composer.lock`,
`phpstan*`, `.github/`, `sql/`.

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
