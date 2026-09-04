# Publish ISKO Chat on GitHub — step-by-step guide

Everything you need is already prepared in your workspace:

| Path | Use it for |
|---|---|
| `isko-chat/github/isko-chat-support/` | **Repo contents** — upload these files to GitHub |
| `isko-chat/github/pages-demo/index.html` | Optional: live preview site on GitHub Pages |
| `isko-chat/ISKO-CHAT-RELEASE-v1.1.0/isko-chat-support.zip` | Attach this ZIP to the GitHub **Release** → then install it on WordPress |

> 🔒 The prepared folder contains **no secrets** (verified: no API keys, no
> tokens — the AI key never leaves your WordPress server). It also contains
> **no private files** — the private release manifest and the drive workspace
> are NOT in this folder. Public repo is safe.

---

## Step 1 — Create the repository

1. Go to [github.com](https://github.com) and log in (create a free account
   if you don’t have one).
2. Recommended: use the same organization as your Help Center assets repo —
   **`bu-iskohelpcenter-byte`** (click the **+** → *New repository*).
   Otherwise, use your personal account.
3. Repository name: **`isko-chat-support`** (exactly this — it’s the plugin slug).
4. Choose **Public** (safe, see note above).
5. ❌ Do **NOT** tick “Add a README”, “.gitignore” or “License” — we already
   have those in the folder.
6. Click **Create repository**.

## Step 2 — Upload the files (no terminal needed)

1. On the empty repo page, click **“uploading an existing file”**
   (or **Add file → Upload files**).
2. Open `isko-chat/github/isko-chat-support/` on your computer and **drag the
   whole folder into the upload box** (GitHub keeps the `assets/` subfolder).
3. Type a commit message, e.g. `ISKO Chat v1.1.0 — chat-only support with optional AI assist`.
4. Click **Commit changes**.

Your repo now shows:
```
📁 isko-chat-support/
   ├── README.md
   ├── LICENSE
   ├── .gitignore
   ├── isko-chat-support.php
   ├── readme.txt
   ├── uninstall.php
   ├── docs/GITHUB-SETUP.md
   └── assets/isko-chat.html
```

> **Prefer the terminal?** (from the folder on your own machine)
> ```bash
> git init
> git add .
> git commit -m "ISKO Chat v1.1.0 — chat-only support with optional AI assist"
> git branch -M main
> git remote add origin https://github.com/bu-iskohelpcenter-byte/isko-chat-support.git
> git push -u origin main
> ```
> First push will ask for a token: GitHub → **Settings → Developer settings →
> Personal access tokens → Tokens (classic) → Generate new token** — tick the
> **repo** scope. (Or install [GitHub CLI](https://cli.github.com/) and run
> `gh auth login` once — then `gh release create` works too.)

## Step 3 — Create the Release (this is the ZIP you install on WordPress)

1. On the repo page click **Releases** (right sidebar) → **Create a new release**.
2. **Choose a tag** → type `v1.1.0` → “Create new tag on publish”.
3. Release title: `ISKO Chat v1.1.0`.
4. Description: paste the changelog (see `readme.txt`).
5. **Attach binaries** → drag in
   `isko-chat/ISKO-CHAT-RELEASE-v1.1.0/isko-chat-support.zip`.
6. Click **Publish release**.

Now anyone (including you) can install the plugin by downloading
`isko-chat-support.zip` from **Releases** → WP Admin → Plugins → Add New →
Upload Plugin.

> ⚠️ For WordPress, always use the **Release ZIP**, not “Code → Download ZIP” —
> the Release ZIP has the correct `isko-chat-support/` wrapping folder.

## Step 4 (optional, 2 minutes) — Live preview on GitHub Pages

Want to see/use the chat right away, free, hosted by GitHub?

1. Create a **second** repository named `isko-chat` (same org) — Public.
2. Upload `isko-chat/github/pages-demo/index.html` to its root
   (or rename it `index.html` yourself).
3. **Settings → Pages** → Source: **Deploy from a branch** → branch `main`,
   folder `/ (root)` → **Save**.
4. Wait ~1 minute → your chat is live at
   `https://bu-iskohelpcenter-byte.github.io/isko-chat/`.

> On GitHub Pages the AI assist is **off** by design (it needs the WordPress
> REST proxy + your API key) — the deterministic ISKO answers work 100%.
> The WordPress plugin remains the production version.

## After publishing — checklist

- [ ] Repo shows all 4 plugin files + README/LICENSE/.gitignore/docs
- [ ] Release `v1.1.0` exists with the ZIP attached (sha256
      `5c18cca5e8496d601be4069b5d355de84e3e562e7440037d9eec92bc717e7646`)
- [ ] ZIP installs on WordPress (Plugins → Add New → Upload) → `/isko-chat` loads
- [ ] Optional: Pages preview loaded
- [ ] Optional (later): add a **Topics**/description, and enable
      **Settings → Pages** not needed for the plugin itself

## What NOT to put on GitHub (stays private)

- `ISKO-CHAT-RELEASE-*/PRIVATE-RELEASE-MANIFEST-*.json` (internal metadata)
- The full ISKO workspace ZIP / drive files (52 MB, contains internal docs)
- Your Gemini/Groq API key (never commits!)
