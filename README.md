# ISKO Chat — Chat-Only Support

Official ISKO chat support for **Bicol University** as a standalone,
**conversation-only website**. A single fullscreen chat — no search page, no
FAQ listings, nothing else. Answers come **only** from the bundled official
ISKO FAQ knowledge base (590 Q&As from ISKO Help Center release v3.64.2),
plus an **optional free-tier AI assist** that explains using those same
official FAQs.

> ISKO = **I**mpormasyon, **S**erbisyo, **K**aalaman, at **O**portunidad.

## Features

- 💬 **Chat only** — visitors talk with ISKO in English, Filipino, or Bikol
- 📚 **Ground truth** — every answer is verbatim official FAQ text with a
  “Source: official ISKO FAQ” note; 590 Q&As bundled inside the plugin
- 🧠 **No AI required** — deterministic retrieval engine (bilingual synonym
  expansion, typo-tolerant matching, relevance scoring). Zero network calls
  when AI is off.
- ✨ **Optional free AI assist (v1.1.0)** — grounded enhancement only: the
  model explains using the official FAQ excerpts the engine retrieved, never
  answers on its own. Modes: **Off** / **Button** (visitor taps ✨) /
  **Full** (✨ + auto-assist on no-match). Providers: Google Gemini
  (`gemini-2.5-flash`) or Groq (`llama-3.1-8b-instant`). The provider API key
  is stored **only on the WordPress server** — never in the page source.
- 🔒 **Private by default** — chat history, name, and ratings stay in the
  visitor’s own browser (localStorage). No tracking, no accounts.
- 🎨 Conversation UX — name/role ask (skippable), tap-to-ask topic chips,
  related-question suggestions, “did you mean”, helpful 👍/👎, 5-star rating,
  dark mode, EN/FIL UI, fully responsive, works offline after first load.

## Install on WordPress (plugin ZIP only)

1. Download the latest ZIP from **Releases** on the right →
   `isko-chat-support-1.x.x.zip`.
2. WP Admin → **Plugins → Add New → Upload Plugin** → choose the ZIP →
   **Install Now → Activate**.
3. Open `https://your-site.edu/isko-chat/`.
   (404? → **Settings → Permalinks → Save Changes** once.)

### Make it the whole site (conversation-only website)
**Settings → Reading** → “Your homepage displays” → **A static page** →
select the **ISKO Chat** page → Save. The chat page bypasses the theme and
fills the screen.

### Embed on other pages
Add the shortcode anywhere: `[isko_chat]` (floating button + fullscreen
overlay), or `[isko_chat close="no"]`.

### Optional: turn on the free AI assist
1. Get a free key (no credit card): **Google AI Studio** (ai.google.dev) or
   **Groq** (console.groq.com).
2. **Settings → ISKO Chat Support → Free AI assist** → mode *Button*
   (recommended), provider, model, key, daily cap → Save.
3. ✨ “Ask AI to explain” chips appear under answers. When AI is over quota,
   ISKO falls back to the deterministic official answer — always.

> Honest note: no hosted free tier is truly unlimited (Gemini ≈ 1,500
> requests/day; Groq up to ≈ 14,400/day; both no card). The deterministic
> engine answers ordinary questions with zero AI calls, so school-day traffic
> stays well inside the free quota.

## Development

```bash
node tests/engine_test.js   # 67 assertions — retrieval + AI layer + boot smoke
python3 build/build.py      # rebuild isko-chat.html from the template
python3 build/package.py    # rebuild the release ZIP + checksums
```

## Test results

| Suite | Result |
|---|---|
| Retrieval (20 official queries → correct FAQ) | ✅ 20/20 |
| Typo tolerance / did-you-mean | ✅ |
| Ambiguity → related chips | ✅ |
| No-match protection (never answers off-base) | ✅ |
| Intents (EN + FIL) | ✅ |
| Boot smoke test (stubbed DOM) | ✅ |
| AI layer (off = 0 calls; ✨ = 1 grounded call; fallback) | ✅ |
| **Total** | **67/67 passed** |

## Security & privacy

- AI off → **zero network calls**; nothing leaves the browser.
- AI on → only the question + the selected **official FAQ excerpts** are sent
  to the configured provider, via a WordPress REST proxy
  (`/wp-json/isko-chat/v1/ai`) that checks: site token (random, generated on
  activation), same-origin, and a daily cap. The provider key never leaves the
  server.
- No tracking, no cookies, no accounts.

## License

GPL-2.0-or-later. Built by the BU Communication & Public Relations Office
(CPRO).

## Related

- ISKO Help Center assets CDN: `bu-iskohelpcenter-byte/isko-helpdesk-assets`
- Official site: <https://bicol-u.edu.ph/>
