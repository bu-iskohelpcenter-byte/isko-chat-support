=== ISKO Chat — Chat-Only Support ===
Contributors: bucpro
Donate link: https://bicol-u.edu.ph/
Tags: chat, isko, bicol university, support, faq, no-ai, wordpress
Requires at least: 5.0
Tested up to: 6.8
Requires PHP: 7.0
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Official ISKO chat support as a standalone, conversation-only website. Answers ONLY from the bundled official ISKO FAQ knowledge base — no AI, no external APIs.

== Description ==

ISKO (Impormasyon, Serbisyo, Kaalaman, at Oportunidad) Chat Support is the
conversation-only companion of the BU ISKO Help Center.

This plugin is a **chat website**: there is no search page, no FAQ listings,
no other information — just a fullscreen chat where visitors talk with ISKO.

* **Questions only.** The visitor types (or taps) a question in English,
  Filipino, or Bikol. ISKO answers **only from the bundled official ISKO
  knowledge base** (590 Q&As covering BUCET admissions, academic policies,
  student services, records, fees, calendar, and more).
* **No AI required.** Answers are found by a deterministic retrieval engine
  (bilingual synonym expansion, typo-tolerant matching, relevance scoring).
  With AI off, nothing the visitor types leaves the browser.
* **Optional free-tier AI assist (v1.1.0)** — a grounded enhancement layer:
  it never answers on its own, it only rephrases/explains using the official
  FAQ excerpts the engine already retrieved. Modes: Off / Button (visitor taps
  ✨) / Full (button + auto on no-match). Supports Google Gemini (free,
  ~1,500 req/day, no card) or Groq (free, up to ~14,400 req/day, no card).
  The provider API key is stored only on the WordPress server — it never
  appears in the page source. Daily cap + graceful fallback to deterministic
  answers keep the free tier safe.
* **Self-contained.** The whole app — styles, script, logo, and the FAQ
  database — ships inside this plugin. No CDN, no fonts, no external calls.
* **Fullscreen page** at `yoursite.com/isko-chat` (bypasses your theme).
* **Shortcode overlay** `[isko_chat]` — floating ISKO Chat button on any
  page, opens the chat fullscreen on that page.
* **Conversation features** — greeting, optional name + role, tap-to-ask
  topic chips, related-question suggestions, "did you mean", no-match
  fallback that only points to official topics, helpful Yes/No feedback,
  5-star rating (stored on the device only), dark/light mode, EN/FIL UI.

== Installation ==

1. In WP Admin go to **Plugins → Add New → Upload Plugin**.
2. Choose `isko-chat-support.zip` and click **Install Now**, then **Activate**.
3. Visit `yoursite.com/isko-chat` — the ISKO chat opens fullscreen.
4. If you get a 404, go to **Settings → Permalinks** and click **Save Changes**.

Optional:
* Add "ISKO Chat" to your menu (Appearance → Menus).
* Set it as the homepage: Settings → Reading → static page → "ISKO Chat".
* Embed on any page with the `[isko_chat]` shortcode.
* Change the URL slug in **Settings → ISKO Chat Support**.

== Frequently Asked Questions ==

= Does it use AI? =
Not by default. It is a deterministic FAQ retrieval engine (the same scoring
and typo-tolerant synonym matching used by the ISKO Help Center chat); answers
come verbatim from the bundled official FAQ database. You can optionally turn
on the free AI assist (Settings → ISKO Chat Support → AI mode) — it only
explains/rephrases using the same official FAQs and falls back gracefully.

= Which free AI provider should I use? =
Gemini Flash (Google AI Studio, free, no card, ~1,500 requests/day) or Groq
(free, no card, up to ~14,400 requests/day on llama-3.1-8b-instant, very fast).
Both are one API key, configured in Settings. Nothing hosted is truly
unlimited, so ISKO spends AI calls frugally (deterministic engine first).

= Where does the visitor's chat history go? =
Only to that visitor's own browser (localStorage) — name, preferences,
rating, and the on-screen conversation. Nothing is transmitted anywhere.

= Can it be the entire website? =
Yes. Set the "ISKO Chat" page as your static front page. The chat page
serves fullscreen without your theme, so the site is conversation-only.

= Does it conflict with the main ISKO Help Center plugin? =
No. It is standalone and does not touch the Help Center or Admin Console.

= Is it updated for AY 2026–2027 / BUCET 2027–2028? =
Content ships from the ISKO release v3.64.2 FAQ library (as of
August 10, 2026). Update the plugin when a new ISKO FAQ library ships.

== Changelog ==

= 1.1.0 =
* Optional free-tier AI assist layer (Gemini or Groq), grounded strictly on
  the official FAQ excerpts; provider key stays server-side (REST proxy at
  /wp-json/isko-chat/v1/ai), daily cap + graceful fallback.
* AI modes: Off / Button (✨ chip) / Full (✨ + auto on no-match). EN/FIL strings.
* Settings: provider, model, API key, daily limit.

= 1.0.0 =
* Initial release. Chat-only ISKO support website.
* Bundled official ISKO knowledge base (590 FAQs, v3.64.2).
* No-AI deterministic retrieval engine (bilingual EN/FIL/Bikol synonyms,
  79 topic groups, Levenshtein typo tolerance, relevance scoring).
* Fullscreen page + [isko_chat] shortcode overlay + settings page.
* Fully self-contained single-file app (logo embedded, zero external calls).
