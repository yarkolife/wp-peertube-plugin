<?php
/**
 * Plugin Name: PeerTube Video Manager
 * Description: Integrate PeerTube videos into WordPress with shortcodes for displaying videos from PeerTube instances. Supports custom metadata including Sendeverantwortung.
 * Version: 1.1.5
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
define( 'PT_VM_VERSION', '1.1.5' );
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
		require_once PT_VM_PLUGIN_DIR . 'shortcodes/class-pt-video-detail.php';
		require_once PT_VM_PLUGIN_DIR . 'shortcodes/class-pt-search.php';
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

		// Initialize settings page (must be before admin_menu hook)
		add_action( 'init', array( $this, 'init_admin' ), 5 );

		// Register shortcodes
		add_action( 'init', array( $this, 'register_shortcodes' ) );

		// Auto-display video if URL parameter is present
		add_filter( 'the_content', array( $this, 'auto_display_video' ) );
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
		PT_Video_Detail::register();
		PT_Search::register();
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

