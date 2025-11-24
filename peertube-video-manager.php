<?php
/**
 * Plugin Name: PeerTube Video Manager
 * Description: Integrate PeerTube videos into WordPress with shortcodes for displaying videos from PeerTube instances. Supports custom metadata including Sendeverantwortung.
 * Version: 1.1.7
 * Author: Pavlo Kozakov
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: peertube-video-manager
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 *
 * @package PeerTube_Video_Manager
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants
define( 'PT_VM_VERSION', '1.1.7' );
define( 'PT_VM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'PT_VM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'PT_VM_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Main PeerTube Video Manager class
 *
 * @package PeerTube_Video_Manager
 * @since 1.0.0
 */
class PeerTube_Video_Manager {

	/**
	 * Instance of this class
	 *
	 * @var PeerTube_Video_Manager
	 */
	private static $instance = null;

	/**
	 * Get instance of this class
	 *
	 * @return PeerTube_Video_Manager
	 * @since 1.0.0
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		$this->load_dependencies();
		$this->init_hooks();
	}

	/**
	 * Load required dependencies
	 *
	 * @since 1.0.0
	 */
	private function load_dependencies() {
		// Load core classes
		require_once PT_VM_PLUGIN_DIR . 'includes/class-pt-cache.php';
		require_once PT_VM_PLUGIN_DIR . 'includes/class-pt-api.php';
		require_once PT_VM_PLUGIN_DIR . 'includes/class-pt-formatter.php';
		require_once PT_VM_PLUGIN_DIR . 'includes/class-pt-settings.php';

		// Load shortcode classes
		require_once PT_VM_PLUGIN_DIR . 'shortcodes/class-pt-last-videos.php';
		require_once PT_VM_PLUGIN_DIR . 'shortcodes/class-pt-latest-per-channel.php';
		require_once PT_VM_PLUGIN_DIR . 'shortcodes/class-pt-channel-videos.php';
		require_once PT_VM_PLUGIN_DIR . 'shortcodes/class-pt-channels-ordered.php';
		require_once PT_VM_PLUGIN_DIR . 'shortcodes/class-pt-video-detail.php';
		require_once PT_VM_PLUGIN_DIR . 'shortcodes/class-pt-search.php';
		require_once PT_VM_PLUGIN_DIR . 'shortcodes/class-pt-peertube-search.php';
	}

	/**
	 * Initialize WordPress hooks
	 *
	 * @since 1.0.0
	 */
	private function init_hooks() {
		// Load plugin text domain
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );

		// Enqueue styles and scripts
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );

		// Add critical inline styles in head for search pages (high priority)
		add_action( 'wp_head', array( $this, 'add_search_page_critical_styles' ), 999 );

		// Initialize settings page (must be before admin_menu hook)
		add_action( 'init', array( $this, 'init_admin' ), 5 );

		// Register shortcodes
		add_action( 'init', array( $this, 'register_shortcodes' ) );

		// Auto-display video if URL parameter is present
		add_filter( 'the_content', array( $this, 'auto_display_video' ) );

		// Modify WordPress search form to add PeerTube search option
		add_filter( 'get_search_form', array( $this, 'modify_search_form' ) );

		// Handle search form submission - redirect to appropriate search page
		add_action( 'template_redirect', array( $this, 'handle_search_redirect' ) );

		// Add JavaScript to modify search forms dynamically
		add_action( 'wp_footer', array( $this, 'add_search_form_modification_script' ) );
	}

	/**
	 * Initialize admin functionality
	 *
	 * @since 1.0.0
	 */
	public function init_admin() {
		// Only initialize in admin area
		if ( is_admin() ) {
			PT_Settings::get_instance();
		}
	}

	/**
	 * Load plugin text domain for translations
	 *
	 * @since 1.0.0
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'peertube-video-manager',
			false,
			dirname( PT_VM_PLUGIN_BASENAME ) . '/languages'
		);
	}

	/**
	 * Enqueue frontend styles and scripts
	 *
	 * @since 1.0.0
	 */
	public function enqueue_assets() {
		// Enqueue styles
		wp_enqueue_style(
			'pt-video-manager-styles',
			PT_VM_PLUGIN_URL . 'assets/css/pt-styles.css',
			array(),
			PT_VM_VERSION
		);

		// Add inline styles for button colors from settings
		$button_color = get_option( 'pt_vm_button_color', '#1e40af' );
		$button_hover_color = get_option( 'pt_vm_button_hover_color', '#f59e0b' );
		$button_text_color = get_option( 'pt_vm_button_text_color', '#ffffff' );
		
		$custom_css = "
			:root {
				--pt-button-color: {$button_color};
				--pt-button-hover-color: {$button_hover_color};
				--pt-button-text-color: {$button_text_color};
			}
			/* Additional styles for search page to prevent layout issues */
			body.search .pt-wp-search-results-wrapper,
			body.search .pt-wp-search-separator {
				display: block !important;
				width: 100% !important;
				margin-left: 0 !important;
				margin-right: 0 !important;
				float: none !important;
				clear: both !important;
				position: relative !important;
				overflow: visible !important;
				box-sizing: border-box !important;
			}
			body.search .pt-wp-search-results-content .pt-video-grid {
				display: grid !important;
				grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)) !important;
				width: 100% !important;
				max-width: 100% !important;
				margin: 2rem 0 !important;
				padding: 0 !important;
				gap: 1.5rem !important;
				clear: both !important;
				float: none !important;
				position: relative !important;
				box-sizing: border-box !important;
			}
		body.search .pt-wp-search-results-content .pt-video-card {
			max-width: 100% !important;
			width: 100% !important;
			min-width: 0 !important;
			box-sizing: border-box !important;
			position: relative !important;
			float: none !important;
			display: flex !important;
			flex-direction: column !important;
			overflow: hidden !important;
			word-wrap: break-word !important;
		}
		body.search .pt-wp-search-results-content .pt-video-grid > * {
			min-width: 0 !important;
			max-width: 100% !important;
			overflow: hidden !important;
		}
		body.search .pt-wp-search-results-content .pt-video-card * {
			max-width: 100% !important;
			word-wrap: break-word !important;
			overflow-wrap: break-word !important;
		}
		";
		
		wp_add_inline_style( 'pt-video-manager-styles', $custom_css );

		// Enqueue scripts (if needed)
		wp_enqueue_script(
			'pt-video-manager-scripts',
			PT_VM_PLUGIN_URL . 'assets/js/pt-frontend.js',
			array( 'jquery' ),
			PT_VM_VERSION,
			true
		);
	}

	/**
	 * Register all shortcodes
	 *
	 * @since 1.0.0
	 */
	public function register_shortcodes() {
		PT_Last_Videos::register();
		PT_Latest_Per_Channel::register();
		PT_Channel_Videos::register();
		PT_Channels_Ordered::register();
		PT_Video_Detail::register();
		PT_Search::register();
		PT_PeerTube_Search::register();
	}

	/**
	 * Auto-display video if URL parameter is present
	 *
	 * @param string $content Post content.
	 * @return string Modified content.
	 * @since 1.0.0
	 */
	public function auto_display_video( $content ) {
		// Only on singular posts/pages and not in admin
		if ( ! is_singular() || is_admin() ) {
			return $content;
		}

		// Check for video parameters first - if they exist, we should display video
		$has_video_param = false;
		$video_shortcode = '';

		// Check for video number parameter
		if ( isset( $_GET['pt_video'] ) && ! empty( $_GET['pt_video'] ) ) {
			$video_number = sanitize_text_field( $_GET['pt_video'] );
			$video_shortcode = '[pt-video number="' . esc_attr( $video_number ) . '"]';
			$has_video_param = true;
		}
		// Check for video ID parameter
		elseif ( isset( $_GET['pt_video_id'] ) && ! empty( $_GET['pt_video_id'] ) ) {
			$video_id = sanitize_text_field( $_GET['pt_video_id'] );
			$video_shortcode = '[pt-video id="' . esc_attr( $video_id ) . '"]';
			$has_video_param = true;
		}

		// If no video parameters, don't display anything
		if ( ! $has_video_param || empty( $video_shortcode ) ) {
			return $content;
		}

		// Get video page ID from settings
		$video_page_id = get_option( 'pt_vm_video_page_id', 0 );
		
		// If video page is configured, verify we're on the correct page
		if ( $video_page_id > 0 ) {
			$current_page_id = get_the_ID();
			$video_page_url = get_permalink( $video_page_id );
			
			// Check both by ID and by URL to be more reliable
			$is_correct_page = false;
			
			// Check by page ID
			if ( $current_page_id == $video_page_id ) {
				$is_correct_page = true;
			}
			// Also check by URL (remove query params for comparison)
			elseif ( $video_page_url ) {
				global $wp;
				$current_url = home_url( $wp->request );
				// Remove query parameters for comparison
				$current_url_clean = strtok( $current_url, '?' );
				$video_page_url_clean = strtok( $video_page_url, '?' );
				
				if ( $current_url_clean === $video_page_url_clean ) {
					$is_correct_page = true;
				}
			}
			
			if ( ! $is_correct_page ) {
				// Not on the configured video page, don't auto-display
				// This ensures videos only show on the dedicated video page
				return $content;
			}
		}

		// Prepend video shortcode to content if parameter is present
		// This will display the video player and details above any existing content
		if ( ! empty( $video_shortcode ) ) {
			$rendered_video = do_shortcode( $video_shortcode );
			// Only prepend if shortcode actually rendered something
			if ( ! empty( $rendered_video ) ) {
				$content = $rendered_video . $content;
			}
		}

		return $content;
	}

	/**
	 * Add critical inline styles in head for search pages
	 *
	 * @since 1.1.7
	 */
	public function add_search_page_critical_styles() {
		// Only on search pages
		if ( ! is_search() ) {
			return;
		}

		// Only if option is enabled
		$redirect_wp_search = get_option( 'pt_vm_redirect_wp_search', false );
		if ( ! $redirect_wp_search ) {
			return;
		}

		?>
		<style id="pt-vm-search-critical-styles" type="text/css">
		/* Critical styles to prevent layout issues on search pages */
		body.search .pt-wp-search-results-wrapper,
		body.search .pt-wp-search-separator {
			display: block !important;
			width: 100% !important;
			margin-left: 0 !important;
			margin-right: 0 !important;
			float: none !important;
			clear: both !important;
			position: relative !important;
			overflow: visible !important;
			box-sizing: border-box !important;
		}
		body.search .pt-wp-search-results-wrapper {
			margin-bottom: 3em !important;
			padding: 0 !important;
		}
		body.search .pt-wp-search-section-title {
			display: block !important;
			width: 100% !important;
			margin: 0 0 1.5em 0 !important;
			padding: 0 !important;
			clear: both !important;
			float: none !important;
		}
		body.search .pt-wp-search-results-content {
			display: block !important;
			width: 100% !important;
			margin: 0 !important;
			padding: 0 !important;
			clear: both !important;
			float: none !important;
			position: relative !important;
			overflow: visible !important;
			box-sizing: border-box !important;
		}
		body.search .pt-wp-search-results-content .pt-video-grid {
			display: grid !important;
			grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)) !important;
			width: 100% !important;
			max-width: 100% !important;
			margin: 2rem 0 !important;
			padding: 0 !important;
			gap: 1.5rem !important;
			clear: both !important;
			float: none !important;
			position: relative !important;
			box-sizing: border-box !important;
			overflow: visible !important;
		}
		body.search .pt-wp-search-results-content .pt-video-card {
			max-width: 100% !important;
			width: 100% !important;
			min-width: 0 !important;
			box-sizing: border-box !important;
			position: relative !important;
			float: none !important;
			display: flex !important;
			flex-direction: column !important;
			overflow: hidden !important;
			word-wrap: break-word !important;
		}
		body.search .pt-wp-search-results-content .pt-video-grid > * {
			min-width: 0 !important;
			max-width: 100% !important;
			overflow: hidden !important;
		}
		body.search .pt-wp-search-results-content .pt-video-card * {
			max-width: 100% !important;
			word-wrap: break-word !important;
			overflow-wrap: break-word !important;
		}
		body.search .pt-wp-search-results-content .pt-video-card h3,
		body.search .pt-wp-search-results-content .pt-video-card .pt-video-title {
			max-width: 100% !important;
			width: 100% !important;
			min-width: 0 !important;
			overflow: hidden !important;
			text-overflow: ellipsis !important;
			word-wrap: break-word !important;
		}
		</style>
		<?php
	}

	/**
	 * Modify WordPress search form to add PeerTube search option
	 *
	 * @param string $form Search form HTML.
	 * @return string Modified search form.
	 * @since 1.1.7
	 */
	public function modify_search_form( $form ) {
		// Only if option is enabled
		$redirect_wp_search = get_option( 'pt_vm_redirect_wp_search', false );
		if ( ! $redirect_wp_search ) {
			return $form;
		}

		// Get search page ID from settings
		$search_page_id = get_option( 'pt_vm_search_page_id', 0 );
		if ( $search_page_id <= 0 ) {
			return $form;
		}

		// Check which search type was selected in previous search
		$search_type = isset( $_GET['pt_search_type'] ) ? sanitize_text_field( $_GET['pt_search_type'] ) : 'wp';
		if ( $search_type !== 'peertube' && $search_type !== 'wp' ) {
			$search_type = 'wp';
		}

		// Add dropdown selector after the search input field (PeerTube-style)
		$selector_html = '<div class="pt-search-type-selector" style="display: inline-flex; align-items: stretch; margin-left: 0.25em; position: relative; vertical-align: middle;">';
		$selector_html .= '<select name="pt_search_type" class="pt-search-type-select" style="padding: 0.5em 2.2em 0.5em 0.75em; border: 1px solid rgba(0,0,0,0.2); border-left: none; border-radius: 0 4px 4px 0; background: #f8f9fa; font-size: 0.875em; cursor: pointer; appearance: none; background-image: url(\'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 10 10"><path fill="%23666" d="M5 8L1 3h8z"/></svg>\'); background-repeat: no-repeat; background-position: right 0.6em center; padding-right: 2em; color: #333; height: 100%; min-height: 2.5em;">';
		// Get option texts from settings
		$wp_option_text_fallback = get_option( 'pt_vm_wp_search_wp_option_text', __( 'Auf der Webseite suchen', 'peertube-video-manager' ) );
		$peertube_option_text_fallback = get_option( 'pt_vm_wp_search_peertube_option_text', __( 'In Mediathek LokalMedial.de suchen', 'peertube-video-manager' ) );
		
		$selector_html .= '<option value="wp" ' . selected( $search_type, 'wp', false ) . '>' . esc_html( $wp_option_text_fallback ) . '</option>';
		$selector_html .= '<option value="peertube" ' . selected( $search_type, 'peertube', false ) . '>' . esc_html( $peertube_option_text_fallback ) . '</option>';
		$selector_html .= '</select>';
		$selector_html .= '</div>';

		// Try multiple patterns to insert checkbox
		$patterns = array(
			// Pattern 1: After search input field
			'/(<input[^>]*type=["\']search["\'][^>]*>)/i',
			// Pattern 2: After search input with name="s"
			'/(<input[^>]*name=["\']s["\'][^>]*>)/i',
			// Pattern 3: After label containing "search"
			'/(<label[^>]*>.*?<\/label>\s*<input[^>]*>)/is',
			// Pattern 4: Before submit button
			'/(<button[^>]*type=["\']submit["\'][^>]*>)/i',
			// Pattern 5: Before closing form tag (fallback)
			'/(<\/form>)/i',
		);

		foreach ( $patterns as $pattern ) {
			if ( preg_match( $pattern, $form, $matches ) ) {
				// Insert selector after the match
				$pos = strpos( $form, $matches[0] );
				if ( $pos !== false ) {
					$insert_pos = $pos + strlen( $matches[0] );
					// For input fields, insert inline (on the same line)
					if ( strpos( $matches[0], '<input' ) !== false ) {
						$form = substr_replace( $form, $selector_html, $insert_pos, 0 );
					} else {
						$form = substr_replace( $form, "\n" . $selector_html, $insert_pos, 0 );
					}
					return $form;
				}
			}
		}

		// Last resort: append before closing form tag
		$form = str_replace( '</form>', $selector_html . '</form>', $form );

		return $form;
	}

	/**
	 * Handle search form submission - redirect to appropriate search page
	 *
	 * @since 1.1.7
	 */
	public function handle_search_redirect() {
		// Only if option is enabled
		$redirect_wp_search = get_option( 'pt_vm_redirect_wp_search', false );
		if ( ! $redirect_wp_search ) {
			return;
		}

		// Only on search results page with 's' parameter
		if ( ! is_search() || ! isset( $_GET['s'] ) || empty( $_GET['s'] ) ) {
			return;
		}

		// Check if PeerTube search was selected
		$search_type = isset( $_GET['pt_search_type'] ) ? sanitize_text_field( $_GET['pt_search_type'] ) : 'wp';
		if ( $search_type === 'peertube' ) {
			// Get search query
			$query = sanitize_text_field( $_GET['s'] );
			if ( empty( $query ) ) {
				return;
			}

			// Get search page ID from settings
			$search_page_id = get_option( 'pt_vm_search_page_id', 0 );
			if ( $search_page_id <= 0 ) {
				return;
			}

			// Build redirect URL with pt_search parameter
			$redirect_url = add_query_arg( 'pt_search', urlencode( $query ), get_permalink( $search_page_id ) );

			// Redirect to PeerTube search page
			wp_safe_redirect( $redirect_url, 302 );
			exit;
		}

		// If PeerTube search not selected, let WordPress handle the search normally
		// (no redirect, standard WordPress search results will be shown)
	}

	/**
	 * Add JavaScript to modify search forms dynamically
	 *
	 * @since 1.1.7
	 */
	public function add_search_form_modification_script() {
		// Only if option is enabled
		$redirect_wp_search = get_option( 'pt_vm_redirect_wp_search', false );
		if ( ! $redirect_wp_search ) {
			return;
		}

		// Get search page ID from settings
		$search_page_id = get_option( 'pt_vm_search_page_id', 0 );
		if ( $search_page_id <= 0 ) {
			return;
		}

		// Check which search type was selected in previous search
		$search_type = isset( $_GET['pt_search_type'] ) ? sanitize_text_field( $_GET['pt_search_type'] ) : 'wp';
		if ( $search_type !== 'peertube' && $search_type !== 'wp' ) {
			$search_type = 'wp';
		}

		$search_page_url = get_permalink( $search_page_id );
		if ( ! $search_page_url ) {
			return;
		}

		// Get option texts with proper defaults
		$wp_option_text = get_option( 'pt_vm_wp_search_wp_option_text' );
		$peertube_option_text = get_option( 'pt_vm_wp_search_peertube_option_text' );

		// Remove slashes that WordPress might add
		if ( $wp_option_text !== false ) {
			$wp_option_text = wp_unslash( $wp_option_text );
		}
		if ( $peertube_option_text !== false ) {
			$peertube_option_text = wp_unslash( $peertube_option_text );
		}

		// Ensure values are not empty - use defaults if empty or false
		if ( $wp_option_text === false || empty( trim( $wp_option_text ) ) ) {
			$wp_option_text = __( 'Auf der Webseite suchen', 'peertube-video-manager' );
		}
		if ( $peertube_option_text === false || empty( trim( $peertube_option_text ) ) ) {
			$peertube_option_text = __( 'In Mediathek LokalMedial.de suchen', 'peertube-video-manager' );
		}

		// Trim and sanitize for JavaScript
		$wp_option_text = trim( $wp_option_text );
		$peertube_option_text = trim( $peertube_option_text );
		$wp_option_text = esc_js( $wp_option_text );
		$peertube_option_text = esc_js( $peertube_option_text );

		// Add version to prevent caching
		$script_version = '1.1.7-' . time();
		?>
		<script type="text/javascript">
		/* PeerTube Search Form Modification v<?php echo esc_js( $script_version ); ?> */
		(function() {
			'use strict';
			
			// Get option texts from PHP
			var ptSearchWpOptionText = <?php echo wp_json_encode( $wp_option_text, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE ); ?>;
			var ptSearchPeertubeOptionText = <?php echo wp_json_encode( $peertube_option_text, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE ); ?>;
			
			try {
				function getUrlParam(name, defaultValue) {
					try {
						if (window.URLSearchParams) {
							var urlParams = new URLSearchParams(window.location.search);
							return urlParams.get(name) || defaultValue;
						}
					} catch(e) {
						// Fallback for older browsers
					}
					
					// Fallback: parse URL manually
					var match = window.location.search.match(new RegExp('[?&]' + name + '=([^&]*)'));
					return match ? decodeURIComponent(match[1]) : defaultValue;
				}
				
				function modifySearchForms() {
					try {
						// Find all search forms
						var searchForms = document.querySelectorAll('form[role="search"], form.search-form, form[class*="search"]');
						
						if (!searchForms || searchForms.length === 0) {
							return;
						}
						
						for (var i = 0; i < searchForms.length; i++) {
							var form = searchForms[i];
							
							// Skip if already modified
							if (form.querySelector('.pt-search-type-selector')) {
								continue;
							}
							
							// Find search input
							var searchInput = form.querySelector('input[type="search"], input[name="s"]');
							if (!searchInput || !searchInput.parentNode) {
								continue;
							}
							
							// Get current search type from URL
							var currentSearchType = getUrlParam('pt_search_type', '<?php echo esc_js( $search_type ); ?>');
							
							// Create selector wrapper
							var selectorWrapper = document.createElement('div');
							selectorWrapper.className = 'pt-search-type-selector';
							selectorWrapper.style.cssText = 'display: inline-flex; align-items: stretch; margin-left: 0.25em; position: relative; vertical-align: middle;';
							
							// Create select element
							var select = document.createElement('select');
							select.name = 'pt_search_type';
							select.className = 'pt-search-type-select';
							// Set styles separately to avoid issues with SVG in CSS
							select.style.padding = '0.5em 2.2em 0.5em 0.75em';
							select.style.border = '1px solid rgba(0,0,0,0.2)';
							select.style.borderLeft = 'none';
							select.style.borderRadius = '0 4px 4px 0';
							select.style.background = '#f8f9fa';
							select.style.fontSize = '0.875em';
							select.style.cursor = 'pointer';
							select.style.appearance = 'none';
							select.style.webkitAppearance = 'none';
							select.style.mozAppearance = 'none';
							select.style.backgroundImage = 'url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'10\' height=\'10\' viewBox=\'0 0 10 10\'%3E%3Cpath fill=\'%23666\' d=\'M5 8L1 3h8z\'/%3E%3C/svg%3E")';
							select.style.backgroundRepeat = 'no-repeat';
							select.style.backgroundPosition = 'right 0.6em center';
							select.style.paddingRight = '2em';
							select.style.color = '#333';
							select.style.height = '100%';
							select.style.minHeight = '2.5em';
							
							// Add options
							var option1 = document.createElement('option');
							option1.value = 'wp';
							option1.textContent = ptSearchWpOptionText;
							if (currentSearchType === 'wp') {
								option1.selected = true;
							}
							select.appendChild(option1);
							
							var option2 = document.createElement('option');
							option2.value = 'peertube';
							option2.textContent = ptSearchPeertubeOptionText;
							if (currentSearchType === 'peertube') {
								option2.selected = true;
							}
							select.appendChild(option2);
							
							selectorWrapper.appendChild(select);
							
							// Insert after search input
							try {
								searchInput.parentNode.insertBefore(selectorWrapper, searchInput.nextSibling);
								
								// Modify search input border
								searchInput.style.borderRadius = '4px 0 0 4px';
								searchInput.style.borderRight = 'none';
								
								// Handle form submission
								form.addEventListener('submit', function(e) {
									try {
										var selectedType = select.value;
										if (selectedType === 'peertube') {
											e.preventDefault();
											var query = searchInput.value;
											if (query) {
												var searchUrl = '<?php echo esc_js( $search_page_url ); ?>';
												searchUrl += (searchUrl.indexOf('?') > -1 ? '&' : '?') + 'pt_search=' + encodeURIComponent(query);
												window.location.href = searchUrl;
											}
										}
									} catch(err) {
										console.error('PT Search form submit error:', err);
									}
								});
							} catch(err) {
								console.error('PT Search form modification error:', err);
								continue;
							}
						}
					} catch(err) {
						console.error('PT Search forms modification error:', err);
					}
				}
				
				// Run on DOM ready
				if (document.readyState === 'loading') {
					document.addEventListener('DOMContentLoaded', modifySearchForms);
				} else {
					modifySearchForms();
				}
				
				// Also run after a short delay to catch dynamically loaded forms
				setTimeout(modifySearchForms, 500);
			} catch(err) {
				console.error('PT Search script error:', err);
			}
		})();
		</script>
		<?php
	}
}

/**
 * Activation hook
 *
 * @since 1.0.0
 */
function pt_vm_activate() {
	// Set default options if not exist
	if ( ! get_option( 'pt_vm_base_url' ) ) {
		add_option( 'pt_vm_base_url', 'https://lokalmedial.de' );
	}
	if ( ! get_option( 'pt_vm_cache_time_videos' ) ) {
		add_option( 'pt_vm_cache_time_videos', 5 );
	}
	if ( ! get_option( 'pt_vm_cache_time_config' ) ) {
		add_option( 'pt_vm_cache_time_config', 24 );
	}
	if ( ! get_option( 'pt_vm_videos_per_page' ) ) {
		add_option( 'pt_vm_videos_per_page', 8 );
	}
	if ( ! get_option( 'pt_vm_default_channels' ) ) {
		add_option( 'pt_vm_default_channels', '' );
	}
	if ( ! get_option( 'pt_vm_show_views' ) ) {
		add_option( 'pt_vm_show_views', false );
	}
	if ( ! get_option( 'pt_vm_peertube_button_text' ) ) {
		add_option( 'pt_vm_peertube_button_text', __( 'Auf PeerTube ansehen', 'peertube-video-manager' ) );
	}
	if ( ! get_option( 'pt_vm_button_color' ) ) {
		add_option( 'pt_vm_button_color', '#1e40af' );
	}
	if ( ! get_option( 'pt_vm_button_hover_color' ) ) {
		add_option( 'pt_vm_button_hover_color', '#f59e0b' );
	}
	if ( ! get_option( 'pt_vm_button_text_color' ) ) {
		add_option( 'pt_vm_button_text_color', '#ffffff' );
	}
	if ( ! get_option( 'pt_vm_wp_search_section_title' ) ) {
		add_option( 'pt_vm_wp_search_section_title', __( 'PeerTube Videos', 'peertube-video-manager' ) );
	}
	if ( ! get_option( 'pt_vm_wp_search_wp_option_text' ) ) {
		add_option( 'pt_vm_wp_search_wp_option_text', __( 'Auf der Webseite suchen', 'peertube-video-manager' ) );
	}
	if ( ! get_option( 'pt_vm_wp_search_peertube_option_text' ) ) {
		add_option( 'pt_vm_wp_search_peertube_option_text', __( 'In Mediathek LokalMedial.de suchen', 'peertube-video-manager' ) );
	}

	// Create search page if it doesn't exist
	$search_page_id = get_option( 'pt_vm_search_page_id', 0 );
	
	// Check if the stored page still exists
	if ( $search_page_id > 0 ) {
		$page = get_post( $search_page_id );
		if ( ! $page || 'page' !== $page->post_type || 'publish' !== $page->post_status ) {
			$search_page_id = 0;
			delete_option( 'pt_vm_search_page_id' );
		}
	}
	
	// Create search page if needed
	if ( 0 === $search_page_id ) {
		global $wpdb;
		
		// Check if a page with our shortcode already exists
		$existing_page_id = $wpdb->get_var(
			"SELECT ID FROM {$wpdb->posts} 
			WHERE post_type = 'page' 
			AND post_status = 'publish' 
			AND (post_content LIKE '%[pt-search%' OR post_content LIKE '%[pt-search-results%')
			LIMIT 1"
		);

		if ( $existing_page_id ) {
			// Use existing page
			update_option( 'pt_vm_search_page_id', $existing_page_id );
		} else {
			// Create new search page with simple content
			$search_page_content = __( 'Suche in der PeerTube Mediathek', 'peertube-video-manager' ) . "\n\n" .
				'[pt-search placeholder="' . __( 'Suche...', 'peertube-video-manager' ) . '"]' . "\n\n" .
				'[pt-search-results per_page="12"]';

			$page_data = array(
				'post_title'    => __( 'PeerTube Suche', 'peertube-video-manager' ),
				'post_content'  => $search_page_content,
				'post_status'   => 'publish',
				'post_type'     => 'page',
				'post_name'     => 'peertube-suche',
			);

			$search_page_id = wp_insert_post( $page_data );
			if ( ! is_wp_error( $search_page_id ) && $search_page_id > 0 ) {
				update_option( 'pt_vm_search_page_id', $search_page_id );
			}
		}
	}

	// Create video page if it doesn't exist
	$video_page_id = get_option( 'pt_vm_video_page_id', 0 );
	
	// Check if the stored page still exists
	if ( $video_page_id > 0 ) {
		$page = get_post( $video_page_id );
		if ( ! $page || 'page' !== $page->post_type || 'publish' !== $page->post_status ) {
			$video_page_id = 0;
			delete_option( 'pt_vm_video_page_id' );
		}
	}
	
	// Create video page if needed
	if ( 0 === $video_page_id ) {
		global $wpdb;
		
		// Check if a page with pt-video shortcode already exists
		$existing_page_id = $wpdb->get_var(
			"SELECT ID FROM {$wpdb->posts} 
			WHERE post_type = 'page' 
			AND post_status = 'publish' 
			AND post_content LIKE '%[pt-video%'
			LIMIT 1"
		);

		if ( $existing_page_id ) {
			// Use existing page
			update_option( 'pt_vm_video_page_id', $existing_page_id );
		} else {
			// Create new video page - the shortcode will be added automatically via auto_display_video filter
			$video_page_content = __( 'PeerTube Video', 'peertube-video-manager' );

			$page_data = array(
				'post_title'    => __( 'PeerTube Video', 'peertube-video-manager' ),
				'post_content'  => $video_page_content,
				'post_status'   => 'publish',
				'post_type'     => 'page',
				'post_name'     => 'peertube-video',
			);

			$video_page_id = wp_insert_post( $page_data );
			if ( ! is_wp_error( $video_page_id ) && $video_page_id > 0 ) {
				update_option( 'pt_vm_video_page_id', $video_page_id );
			}
		}
	}

	// Flush rewrite rules
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'pt_vm_activate' );

/**
 * Deactivation hook
 *
 * @since 1.0.0
 */
function pt_vm_deactivate() {
	// Flush rewrite rules
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'pt_vm_deactivate' );

/**
 * Initialize the plugin
 *
 * @since 1.0.0
 */
function pt_vm_init() {
	return PeerTube_Video_Manager::get_instance();
}

// Start the plugin
pt_vm_init();

