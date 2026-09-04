<?php
/**
 * Plugin Name:       ISKO Chat — Chat-Only Support
 * Plugin URI:        https://bicol-u.edu.ph/
 * Description:       Official ISKO chat support as a standalone, conversation-only website. Deterministic answers ONLY from the bundled official ISKO FAQ knowledge base — with an optional free-tier AI assist layer (grounded on the same official FAQs; provider key stays server-side).
 * Version:           1.1.0
 * Requires at least: 5.0
 * Requires PHP:      7.0
 * Author:            BU Communication & Public Relations Office (CPRO)
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       isko-chat-support
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access.
}

final class Isko_Chat_Support {

	const VERSION      = '1.1.0';
	const QUERY_VAR    = 'isko_chat';
	const DEFAULT_SLUG = 'isko-chat';
	const HTML_FILE    = 'assets/isko-chat.html';

	/* Feature: optional AI assist. mode: off | chip | full. */
	const AI_SLUG  = 'isko-chat/v1/ai';
	const AI_DAILY_LIMIT_DEFAULT = 1500;

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_rewrite' ) );
		add_filter( 'query_vars', array( __CLASS__, 'register_query_var' ) );
		add_action( 'template_redirect', array( __CLASS__, 'maybe_serve_fullscreen' ), 0 );
		add_shortcode( 'isko_chat', array( __CLASS__, 'shortcode' ) );
		add_action( 'admin_menu', array( __CLASS__, 'settings_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'settings_register' ) );
		add_action( 'rest_api_init', array( __CLASS__, 'register_rest' ) );

		register_activation_hook( __FILE__, array( __CLASS__, 'activate' ) );
		register_deactivation_hook( __FILE__, array( __CLASS__, 'deactivate' ) );

		add_filter(
			'plugin_action_links_' . plugin_basename( __FILE__ ),
			array( __CLASS__, 'action_links' )
		);
	}

	/* ------------------------------------------------------------------ *
	 * Lifecycle
	 * ------------------------------------------------------------------ */

	public static function activate() {
		self::register_rewrite();

		$slug   = self::slug();
		$pid    = (int) get_option( 'isko_chat_page_id', 0 );
		$post   = $pid ? get_post( $pid ) : null;
		if ( ! $post || 'page' !== $post->post_type || 'trash' === $post->post_status ) {
			$existing = get_page_by_path( $slug );
			if ( $existing && 'page' === $existing->post_type && 'trash' !== $existing->post_status ) {
				$pid = $existing->ID;
			} else {
				$pid = wp_insert_post( array(
					'post_title'   => 'ISKO Chat',
					'post_name'    => $slug,
					'post_status'  => 'publish',
					'post_type'    => 'page',
					'post_content' => '<!-- ISKO Chat page: the plugin serves the fullscreen chat build at this address. -->',
				) );
			}
			if ( $pid && ! is_wp_error( $pid ) ) {
				update_option( 'isko_chat_page_id', (int) $pid, false );
			}
		}

		/* Site token: random, never equals the provider API key. It is the
		   only secret embedded in the served page. */
		if ( ! get_option( 'isko_chat_ai_token' ) ) {
			update_option( 'isko_chat_ai_token', wp_generate_password( 32, false ), false );
		}

		flush_rewrite_rules();
	}

	public static function deactivate() {
		flush_rewrite_rules();
	}

	public static function action_links( $links ) {
		$open = sprintf(
			'<a href="%s" target="_blank" rel="noopener">%s</a>',
			esc_url( self::chat_url() ),
			esc_html__( 'Open ISKO Chat', 'isko-chat-support' )
		);
		$ai = sprintf(
			'<a href="%s" target="_blank" rel="noopener">%s</a>',
			admin_url( 'options-general.php?page=isko-chat-support' ),
			esc_html__( 'AI settings', 'isko-chat-support' )
		);
		array_unshift( $links, $ai, $open );
		return $links;
	}

	/* ------------------------------------------------------------------ *
	 * URL handling
	 * ------------------------------------------------------------------ */

	public static function slug() {
		$slug = (string) get_option( 'isko_chat_slug', self::DEFAULT_SLUG );
		$slug = apply_filters( 'isko_chat_slug', $slug );
		$slug = sanitize_title( $slug );
		return $slug ? $slug : self::DEFAULT_SLUG;
	}

	public static function chat_url() {
		return home_url( user_trailingslashit( self::slug() ) );
	}

	public static function register_rewrite() {
		add_rewrite_rule(
			'^' . preg_quote( self::slug(), '/' ) . '/?$',
			'index.php?' . self::QUERY_VAR . '=1',
			'top'
		);
	}

	public static function register_query_var( $vars ) {
		$vars[] = self::QUERY_VAR;
		return $vars;
	}

	/* ------------------------------------------------------------------ *
	 * Fullscreen serving (bypasses the theme entirely)
	 * ------------------------------------------------------------------ */

	public static function maybe_serve_fullscreen() {
		if ( ! get_query_var( self::QUERY_VAR ) ) {
			return;
		}

		$ver = isset( $_GET['v'] ) ? sanitize_text_field( wp_unslash( $_GET['v'] ) ) : '';
		if ( $ver !== self::VERSION ) {
			$clean = home_url( add_query_arg( 'v', self::VERSION, $_SERVER['REQUEST_URI'] ) );
			wp_redirect( $clean, 302 );
			exit;
		}

		self::serve_file( self::HTML_FILE, 'noindex, nofollow, noarchive',
			'ISKO Chat asset is missing. Please reinstall the plugin.' );
	}

	private static function serve_file( $file, $robots, $missing_msg ) {
		$path = plugin_dir_path( __FILE__ ) . $file;
		if ( ! file_exists( $path ) ) {
			status_header( 404 );
			wp_die( esc_html( $missing_msg ) );
		}

		/* Inject the optional AI configuration (mode + site token only —
		   the provider API key never leaves the server). */
		if ( self::HTML_FILE === $file ) {
			$mode = (string) get_option( 'isko_chat_ai_mode', 'chip' );
			if ( ! in_array( $mode, array( 'off', 'chip', 'full' ), true ) ) {
				$mode = 'chip';
			}
			$tok  = (string) get_option( 'isko_chat_ai_token', '' );
			$html = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions
			if ( false !== $html ) {
				if ( 1 === substr_count( $html, "mode: 'off'" ) ) {
					$html = str_replace( "mode: 'off'", 'mode: ' . wp_json_encode( $mode ), $html );
				}
				if ( 1 === substr_count( $html, "token: ''" ) ) {
					$html = str_replace( "token: ''", 'token: ' . wp_json_encode( $tok ), $html );
				}
			} else {
				$html = '';
			}
			status_header( 200 );
			header( 'Content-Type: text/html; charset=utf-8' );
			header( 'X-Robots-Tag: ' . $robots, true );
			header( 'Cache-Control: no-store, max-age=0' );
			header( 'X-Content-Type-Options: nosniff' );
			echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			exit;
		}

		status_header( 200 );
		header( 'Content-Type: text/html; charset=utf-8' );
		header( 'X-Robots-Tag: ' . $robots, true );
		header( 'Cache-Control: public, max-age=3600, s-maxage=3600, stale-while-revalidate=86400' );
		header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s', filemtime( $path ) ) . ' GMT' );
		header( 'X-Content-Type-Options: nosniff' );
		readfile( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		exit;
	}

	/* ------------------------------------------------------------------ *
	 * Shortcode — embed the chat as a fullscreen overlay on any page
	 * ------------------------------------------------------------------ */

	public static function shortcode( $atts ) {
		$atts = shortcode_atts( array( 'close' => 'yes' ), $atts, 'isko_chat' );

		ob_start();
		?>
		<div id="isko-chat-launcher" style="position:fixed;right:18px;bottom:18px;z-index:99999;">
			<button type="button" id="isko-chat-open"
				style="display:inline-flex;align-items:center;gap:8px;border:none;cursor:pointer;
					padding:12px 18px;border-radius:999px;font-size:15px;font-weight:600;
					color:#fff;background:linear-gradient(135deg,#0087c9,#005f9e);
					box-shadow:0 6px 18px rgba(0,90,160,.4);font-family:inherit;">
				💬&nbsp;ISKO Chat
			</button>
		</div>
		<div id="isko-chat-frame" style="display:none;position:fixed;inset:0;z-index:100000;background:rgba(8,26,44,.55);">
			<div style="position:relative;width:100%;height:100%;">
				<iframe id="isko-chat-if"
					src="<?php echo esc_url( add_query_arg( 'v', self::VERSION, self::chat_url() ) ); ?>"
					style="width:100%;height:100%;border:0;background:#fff;"
					title="ISKO Chat Support" loading="lazy"></iframe>
				<?php if ( 'no' !== strtolower( (string) $atts['close'] ) ) : ?>
				<button type="button" id="isko-chat-close" aria-label="Close ISKO Chat"
					style="position:absolute;top:12px;right:14px;z-index:2;border:none;cursor:pointer;
						width:38px;height:38px;border-radius:50%;font-size:18px;line-height:1;
						color:#fff;background:rgba(0,0,0,.45);">✕</button>
				<?php endif; ?>
			</div>
		</div>
		<script>
		(function () {
			var open = document.getElementById('isko-chat-open');
			var frame = document.getElementById('isko-chat-frame');
			var close = document.getElementById('isko-chat-close');
			function show() {
				if (frame) { frame.style.display = 'block'; }
				document.body.style.overflow = 'hidden';
			}
			function hide() {
				if (frame) { frame.style.display = 'none'; }
				document.body.style.overflow = '';
				var openBtn = document.getElementById('isko-chat-open');
				if (openBtn) { setTimeout(function () { openBtn.focus(); }, 50); }
			}
			if (open) { open.addEventListener('click', show); }
			if (close) { close.addEventListener('click', hide); }
		})();
		</script>
		<?php
		return ob_get_clean();
	}

	/* ------------------------------------------------------------------ *
	 * Optional AI assist — server-side proxy (key never leaves the server)
	 * ------------------------------------------------------------------ */

	public static function register_rest() {
		register_rest_route(
			'isko-chat/v1',
			'/ai',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'permission_callback' => '__return_true',
				'callback'            => array( __CLASS__, 'ai_endpoint' ),
			)
		);
	}

	private static function ai_usage_ok() {
		$limit = (int) get_option( 'isko_chat_ai_daily_limit', self::AI_DAILY_LIMIT_DEFAULT );
		if ( $limit <= 0 ) {
			return true;
		}
		$day = gmdate( 'Y-m-d' );
		$u   = get_option( 'isko_chat_ai_usage', array() );
		if ( ! is_array( $u ) || ( isset( $u['d'] ) && $u['d'] !== $day ) ) {
			$u = array( 'd' => $day, 'n' => 0 );
		}
		$n = isset( $u['n'] ) ? (int) $u['n'] : 0;
		if ( $n >= $limit ) {
			return false;
		}
		$u['n'] = $n + 1;
		update_option( 'isko_chat_ai_usage', $u, false );
		return true;
	}

	private static function ai_lang_name( $lang ) {
		return ( 'fil' === $lang ) ? 'Filipino/Tagalog (po/opo politeness when natural)' : 'English';
	}

	private static function ai_build_prompt( $q, $faqs, $lang ) {
		$lang_name = self::ai_lang_name( $lang );
		$sys  = "You are ISKO, the official chat assistant of Bicol University (BU) — "
			. "Impormasyon, Serbisyo, Kaalaman, at Oportunidad.\n"
			. "STRICT RULES:\n"
			. "1. Answer ONLY from the OFFICIAL FAQ excerpts provided below. "
			. "Never invent fees, dates, requirements, policies, or contacts.\n"
			. "2. If the excerpts do not address the question, say briefly that you can only "
			. "answer from official ISKO information and suggest an official topic.\n"
			. "3. Be warm, clear, and concise — under 120 words. Welcome the reader, then answer.\n"
			. "4. Respond in " . $lang_name . ".\n"
			. "5. Plain text only: **bold** for key terms, short paragraphs, simple bullets (-). "
			. "No Markdown headings, no tables, no emoji spam.\n"
			. "6. If the reader is asking about a person's specific case, gently say official "
			. "evaluation is needed and give the closest official facts.";

		$user = "Question: " . $q . "\n\n"
			. "OFFICIAL FAQ EXCERPTS (source of truth):\n";
		$i = 1;
		foreach ( $faqs as $f ) {
			$user .= $i . '. Q: ' . $f['q'] . "\n   A: " . $f['a'] . "\n";
			$i++;
		}
		$user .= "\nAnswer the question using ONLY those excerpts.";
		return array( $sys, $user );
	}

	public static function ai_endpoint( WP_REST_Request $req ) {
		$out = function ( $ok, $extra = array() ) {
			$data = array_merge( array( 'ok' => $ok ), $extra );
			return new WP_REST_Response( $data, 200 );
		};

		$mode = (string) get_option( 'isko_chat_ai_mode', 'chip' );
		if ( 'off' === $mode ) {
			return $out( false, array( 'error' => 'disabled' ) );
		}

		$tok = (string) get_option( 'isko_chat_ai_token', '' );
		$hdr = (string) $req->get_header( 'X-Isko-Token' );
		if ( ! $tok || ! hash_equals( $tok, $hdr ) ) {
			return $out( false, array( 'error' => 'unauthorized' ) );
		}

		/* Same-site check: Origin host must match the site host (defense in depth). */
		$origin = wp_parse_url( (string) $req->get_header( 'Origin' ), PHP_URL_HOST );
		$site   = wp_parse_url( home_url(), PHP_URL_HOST );
		if ( $origin && $site && strtolower( $origin ) !== strtolower( $site ) ) {
			return $out( false, array( 'error' => 'origin' ) );
		}

		$key = (string) get_option( 'isko_chat_ai_key', '' );
		if ( ! $key ) {
			return $out( false, array( 'error' => 'no-key' ) );
		}
		if ( ! self::ai_usage_ok() ) {
			return $out( false, array( 'error' => 'daily-limit' ) );
		}

		$body  = $req->get_json_params();
		if ( ! is_array( $body ) ) {
			return $out( false, array( 'error' => 'bad-body' ) );
		}
		$q    = isset( $body['q'] ) ? sanitize_text_field( wp_unslash( (string) $body['q'] ) ) : '';
		$lang = ( 'fil' === ( isset( $body['lang'] ) ? $body['lang'] : '' ) ) ? 'fil' : 'en';
		if ( '' === $q || mb_strlen( $q ) > 400 ) {
			return $out( false, array( 'error' => 'bad-q' ) );
		}
		$faqs = array();
		if ( isset( $body['faqs'] ) && is_array( $body['faqs'] ) ) {
			foreach ( array_slice( $body['faqs'], 0, 3 ) as $f ) {
				if ( is_array( $f ) ) {
					$faqs[] = array(
						'q' => mb_substr( (string) ( isset( $f['q'] ) ? $f['q'] : '' ), 0, 220 ),
						'a' => mb_substr( wp_strip_all_tags( (string) ( isset( $f['a'] ) ? $f['a'] : '' ) ), 0, 1400 ),
					);
				}
			}
		}
		if ( ! $faqs ) {
			return $out( false, array( 'error' => 'no-context' ) );
		}

		list( $sys, $user ) = self::ai_build_prompt( $q, $faqs, $lang );

		$provider = (string) get_option( 'isko_chat_ai_provider', 'gemini' );
		$model    = (string) get_option( 'isko_chat_ai_model', ( 'gemini' === $provider ? 'gemini-2.5-flash' : 'llama-3.3-70b-versatile' ) );

		if ( 'groq' === $provider ) {
			$result = self::ai_call_groq( $key, $model, $sys, $user );
		} else {
			$result = self::ai_call_gemini( $key, $model, $sys, $user );
		}

		if ( is_wp_error( $result ) ) {
			return $out( false, array( 'error' => 'provider', 'message' => $result->get_error_message() ) );
		}
		return $out( true, array( 'text' => $result['text'], 'model' => $result['model'] ) );
	}

	private static function ai_call_gemini( $key, $model, $sys, $user ) {
		$url = 'https://generativelanguage.googleapis.com/v1beta/models/'
			. rawurlencode( $model ) . ':generateContent?key=' . rawurlencode( $key );
		$resp = wp_remote_post( $url, array(
			'timeout' => 18,
			'headers' => array( 'Content-Type' => 'application/json' ),
			'body'    => wp_json_encode( array(
				'system_instruction' => array( 'parts' => array( array( 'text' => $sys ) ) ),
				'contents'           => array( array( 'role' => 'user', 'parts' => array( array( 'text' => $user ) ) ) ),
				'generationConfig'   => array( 'temperature' => 0.3, 'maxOutputTokens' => 800 ),
			) ),
		) );
		if ( is_wp_error( $resp ) ) {
			return $resp;
		}
		$code = wp_remote_retrieve_response_code( $resp );
		$data = json_decode( wp_remote_retrieve_body( $resp ), true );
		if ( $code >= 400 || ! is_array( $data ) ) {
			return new WP_Error( 'gemini', 'HTTP ' . $code . ' ' . wp_remote_retrieve_body( $resp ) );
		}
		$text = isset( $data['candidates'][0]['content']['parts'][0]['text'] )
			? (string) $data['candidates'][0]['content']['parts'][0]['text'] : '';
		if ( '' === trim( $text ) ) {
			return new WP_Error( 'gemini', 'empty response' );
		}
		return array( 'text' => trim( $text ), 'model' => $model );
	}

	private static function ai_call_groq( $key, $model, $sys, $user ) {
		$resp = wp_remote_post( 'https://api.groq.com/openai/v1/chat/completions', array(
			'timeout' => 18,
			'headers' => array(
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . $key,
			),
			'body'    => wp_json_encode( array(
				'model'       => $model,
				'messages'    => array(
					array( 'role' => 'system', 'content' => $sys ),
					array( 'role' => 'user', 'content' => $user ),
				),
				'temperature' => 0.3,
				'max_tokens'  => 800,
			) ),
		) );
		if ( is_wp_error( $resp ) ) {
			return $resp;
		}
		$code = wp_remote_retrieve_response_code( $resp );
		$data = json_decode( wp_remote_retrieve_body( $resp ), true );
		if ( $code >= 400 || ! is_array( $data ) ) {
			return new WP_Error( 'groq', 'HTTP ' . $code . ' ' . wp_remote_retrieve_body( $resp ) );
		}
		$text = isset( $data['choices'][0]['message']['content'] )
			? (string) $data['choices'][0]['message']['content'] : '';
		if ( '' === trim( $text ) ) {
			return new WP_Error( 'groq', 'empty response' );
		}
		return array( 'text' => trim( $text ), 'model' => $data['model'] ? $data['model'] : $model );
	}

	/* ------------------------------------------------------------------ *
	 * Settings
	 * ------------------------------------------------------------------ */

	public static function settings_menu() {
		add_options_page(
			__( 'ISKO Chat Support', 'isko-chat-support' ),
			__( 'ISKO Chat Support', 'isko-chat-support' ),
			'manage_options',
			'isko-chat-support',
			array( __CLASS__, 'settings_page' )
		);
	}

	public static function settings_register() {
		register_setting( 'isko_chat_settings', 'isko_chat_slug', array(
			'sanitize_callback' => function ( $v ) {
				$s = sanitize_title( (string) $v );
				return $s ? $s : Isko_Chat_Support::DEFAULT_SLUG;
			},
		) );
		register_setting( 'isko_chat_settings', 'isko_chat_ai_mode', array(
			'sanitize_callback' => function ( $v ) {
				$v = (string) $v;
				return in_array( $v, array( 'off', 'chip', 'full' ), true ) ? $v : 'chip';
			},
		) );
		register_setting( 'isko_chat_settings', 'isko_chat_ai_provider', array(
			'sanitize_callback' => function ( $v ) {
				return ( 'groq' === (string) $v ) ? 'groq' : 'gemini';
			},
		) );
		register_setting( 'isko_chat_settings', 'isko_chat_ai_model', array(
			'sanitize_callback' => function ( $v ) {
				return sanitize_text_field( (string) $v );
			},
		) );
		register_setting( 'isko_chat_settings', 'isko_chat_ai_key', array(
			'sanitize_callback' => function ( $v ) {
				return sanitize_text_field( (string) $v );
			},
		) );
		register_setting( 'isko_chat_settings', 'isko_chat_ai_daily_limit', array(
			'sanitize_callback' => function ( $v ) {
				$n = (int) $v;
				return $n > 0 ? $n : Isko_Chat_Support::AI_DAILY_LIMIT_DEFAULT;
			},
		) );
	}

	public static function settings_page() {
		$url = self::chat_url();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'ISKO Chat — Chat-Only Support', 'isko-chat-support' ); ?></h1>
			<p><?php esc_html_e( 'A standalone, conversation-only ISKO chat page. Answers come from the bundled official ISKO FAQ knowledge base — no AI is required. The optional AI layer only rephrases/explains using those same official FAQs (it never answers on its own), and the provider key stays on this server.', 'isko-chat-support' ); ?></p>

			<h2><?php esc_html_e( 'Page', 'isko-chat-support' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Chat page URL', 'isko-chat-support' ); ?></th>
					<td><a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $url ); ?></a></td>
				</tr>
				<tr>
					<th scope="row"><label for="isko_chat_slug"><?php esc_html_e( 'URL slug', 'isko-chat-support' ); ?></label></th>
					<td>
						<input type="text" id="isko_chat_slug" name="isko_chat_slug" value="<?php echo esc_attr( self::slug() ); ?>" class="regular-text">
						<p class="description"><?php esc_html_e( 'Example: isko-chat ➜ yoursite.com/isko-chat/. Save Permalinks after changing.', 'isko-chat-support' ); ?></p>
					</td>
				</tr>
			</table>

			<h2><?php esc_html_e( 'Free AI assist (optional)', 'isko-chat-support' ); ?></h2>
			<p>
				<?php esc_html_e( 'Free tiers (no credit card): Google Gemini Flash ≈ 1,500 requests/day, 15/min · Groq ≈ 14,400 requests/day (llama-3.1-8b), 30/min. “No limits” does not exist on hosted free tiers, so ISKO uses AI sparingly by design:', 'isko-chat-support' ); ?>
				<strong><?php esc_html_e( 'Off', 'isko-chat-support' ); ?></strong> — <?php esc_html_e( 'deterministic answers only (zero AI calls).', 'isko-chat-support' ); ?>
				<strong><?php esc_html_e( 'Button', 'isko-chat-support' ); ?></strong> — <?php esc_html_e( 'a ✨ “Ask AI to explain” chip appears after answers; the visitor taps it deliberately (recommended default).', 'isko-chat-support' ); ?>
				<strong><?php esc_html_e( 'Full', 'isko-chat-support' ); ?></strong> — <?php esc_html_e( 'the ✨ chip plus an automatic AI assist when no confident FAQ matches.', 'isko-chat-support' ); ?>
			</p>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="isko_chat_ai_mode"><?php esc_html_e( 'AI mode', 'isko-chat-support' ); ?></label></th>
					<td>
						<select id="isko_chat_ai_mode" name="isko_chat_ai_mode">
							<option value="off" <?php selected( get_option( 'isko_chat_ai_mode', 'chip' ), 'off' ); ?>><?php esc_html_e( 'Off — deterministic only', 'isko-chat-support' ); ?></option>
							<option value="chip" <?php selected( get_option( 'isko_chat_ai_mode', 'chip' ), 'chip' ); ?>><?php esc_html_e( 'Button — visitor taps ✨ (recommended)', 'isko-chat-support' ); ?></option>
							<option value="full" <?php selected( get_option( 'isko_chat_ai_mode', 'chip' ), 'full' ); ?>><?php esc_html_e( 'Full — button + auto-assist on no-match', 'isko-chat-support' ); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="isko_chat_ai_provider"><?php esc_html_e( 'Provider', 'isko-chat-support' ); ?></label></th>
					<td>
						<select id="isko_chat_ai_provider" name="isko_chat_ai_provider">
							<option value="gemini" <?php selected( get_option( 'isko_chat_ai_provider', 'gemini' ), 'gemini' ); ?>>Google Gemini (AI Studio — free, no card)</option>
							<option value="groq" <?php selected( get_option( 'isko_chat_ai_provider', 'gemini' ), 'groq' ); ?>>Groq (free, no card — very fast)</option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="isko_chat_ai_model"><?php esc_html_e( 'Model', 'isko-chat-support' ); ?></label></th>
					<td>
						<input type="text" id="isko_chat_ai_model" name="isko_chat_ai_model" value="<?php echo esc_attr( get_option( 'isko_chat_ai_model', 'gemini-2.5-flash' ) ); ?>" class="regular-text">
						<p class="description"><?php esc_html_e( 'Gemini: gemini-2.5-flash (best free). Groq: llama-3.3-70b-versatile, llama-3.1-8b-instant (highest free quota), gpt-oss-20b.', 'isko-chat-support' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="isko_chat_ai_key"><?php esc_html_e( 'API key', 'isko-chat-support' ); ?></label></th>
					<td>
						<input type="password" id="isko_chat_ai_key" name="isko_chat_ai_key" value="<?php echo esc_attr( get_option( 'isko_chat_ai_key', '' ) ); ?>" class="regular-text" autocomplete="off">
						<p class="description"><?php esc_html_e( 'Stored only on this server (never printed into the page). Get one free at ai.google.dev (Gemini) or console.groq.com.', 'isko-chat-support' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="isko_chat_ai_daily_limit"><?php esc_html_e( 'Daily request cap', 'isko-chat-support' ); ?></label></th>
					<td>
						<input type="number" id="isko_chat_ai_daily_limit" name="isko_chat_ai_daily_limit" value="<?php echo esc_attr( get_option( 'isko_chat_ai_daily_limit', self::AI_DAILY_LIMIT_DEFAULT ) ); ?>" min="1" max="14400" class="small-text">
						<p class="description"><?php esc_html_e( 'Keeps free-tier usage safely under the provider limit. After the cap, ISKO falls back to deterministic answers.', 'isko-chat-support' ); ?></p>
					</td>
				</tr>
			</table>
			<?php submit_button(); ?>

			<h2><?php esc_html_e( 'Embed it anywhere', 'isko-chat-support' ); ?></h2>
			<p><code>[isko_chat]</code> — <?php esc_html_e( 'floating ISKO Chat button with fullscreen overlay.', 'isko-chat-support' ); ?><br>
			<code>[isko_chat close="no"]</code> — <?php esc_html_e( 'without the floating close button.', 'isko-chat-support' ); ?></p>

			<h2><?php esc_html_e( 'Make it the whole website', 'isko-chat-support' ); ?></h2>
			<p><?php esc_html_e( 'Settings → Reading → static page → “ISKO Chat”. The chat page serves fullscreen without your theme.', 'isko-chat-support' ); ?></p>
		</div>
		<?php
	}
}

Isko_Chat_Support::init();
