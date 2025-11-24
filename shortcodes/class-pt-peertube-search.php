<?php
/**
 * PeerTube-only search shortcodes
 *
 * @package PeerTube_Video_Manager
 * @since 1.1.7
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * PT_PeerTube_Search shortcode class
 *
 * @package PeerTube_Video_Manager
 * @since 1.1.7
 */
class PT_PeerTube_Search {

	/**
	 * Register shortcodes
	 *
	 * @since 1.1.7
	 */
	public static function register() {
		add_shortcode( 'pt-peertube-search', array( __CLASS__, 'render_form' ) );
		add_shortcode( 'pt-peertube-search-results', array( __CLASS__, 'render_results' ) );
	}

	/**
	 * Render search form
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string Rendered output.
	 * @since 1.1.7
	 */
	public static function render_form( $atts ) {
		$atts = shortcode_atts( array(
			'placeholder' => __( 'Suche in der Mediathek', 'peertube-video-manager' ),
			'action'      => '',
		), $atts, 'pt-peertube-search' );

		$placeholder = esc_attr( $atts['placeholder'] );
		$action      = ! empty( $atts['action'] ) ? esc_url( $atts['action'] ) : '';

		// If no action specified, use search page from settings or current page
		if ( empty( $action ) ) {
			$search_page_id = get_option( 'pt_vm_search_page_id', 0 );
			if ( $search_page_id > 0 ) {
				$action = get_permalink( $search_page_id );
			} else {
				$action = get_permalink();
				if ( ! $action ) {
					$action = home_url( '/' );
				}
			}
		}

		ob_start();
		$template_path = PT_VM_PLUGIN_DIR . 'templates/search-form.php';
		
		if ( file_exists( $template_path ) ) {
			include $template_path;
		} else {
			// Fallback inline form
			?>
			<form method="get" action="<?php echo esc_url( $action ); ?>" class="pt-search-form">
				<input type="search" 
					   name="pt_search" 
					   value="<?php echo isset( $_GET['pt_search'] ) ? esc_attr( $_GET['pt_search'] ) : ''; ?>" 
					   placeholder="<?php echo esc_attr( $placeholder ); ?>" 
					   required>
				<button type="submit"><?php esc_html_e( 'Suchen', 'peertube-video-manager' ); ?></button>
			</form>
			<?php
		}

		return ob_get_clean();
	}

	/**
	 * Render search results (PeerTube only, no WordPress search support)
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string Rendered output.
	 * @since 1.1.7
	 */
	public static function render_results( $atts ) {
		$atts = shortcode_atts( array(
			'per_page' => 12,
		), $atts, 'pt-peertube-search-results' );

		$per_page = absint( $atts['per_page'] );
		
		// Get search query - ONLY from pt_search parameter (no WordPress 's' support)
		$query = '';
		if ( isset( $_GET['pt_search'] ) && ! empty( $_GET['pt_search'] ) ) {
			$query = sanitize_text_field( $_GET['pt_search'] );
		}
		
		// Show "enter search term" message only on first visit (when search parameter is not in URL)
		if ( empty( $query ) && ! isset( $_GET['pt_search'] ) ) {
			return '<p class="pt-no-results">' . esc_html__( 'Bitte geben Sie einen Suchbegriff ein.', 'peertube-video-manager' ) . '</p>';
		}
		
		// If search parameter exists in URL (even if empty), don't show the initial message
		// This handles cases where form was submitted with empty value
		if ( empty( $query ) && isset( $_GET['pt_search'] ) ) {
			// Return empty or show "no results" message instead
			return '';
		}

		// Get page number for pagination
		$paged = isset( $_GET['pt_page'] ) ? absint( $_GET['pt_page'] ) : 1;
		$start = ( $paged - 1 ) * $per_page;

		$api    = new PT_API();
		$result = $api->search_videos( $query, array(
			'count' => $per_page,
			'start' => $start,
		) );

		if ( empty( $result['data'] ) ) {
			return '<p class="pt-no-results">' . sprintf(
				/* translators: %s: search query */
				esc_html__( 'Keine Ergebnisse für "%s" gefunden.', 'peertube-video-manager' ),
				esc_html( $query )
			) . '</p>';
		}

		$base_url = get_option( 'pt_vm_base_url', 'https://lokalmedial.de' );
		$total    = isset( $result['total'] ) ? $result['total'] : count( $result['data'] );

		ob_start();
		
		echo '<div class="pt-search-results-header">';
		echo '<p>' . sprintf(
			/* translators: 1: number of results, 2: search query */
			esc_html__( '%1$d Ergebnisse für "%2$s"', 'peertube-video-manager' ),
			esc_html( number_format_i18n( $total ) ),
			'<strong>' . esc_html( $query ) . '</strong>'
		) . '</p>';
		echo '</div>';

		echo '<div class="pt-video-grid">';

		foreach ( $result['data'] as $video ) {
			// Get full video data only if pluginData is missing
			if ( ! isset( $video['pluginData'] ) || empty( $video['pluginData'] ) ) {
				$full_video = $api->get_video( $video['uuid'] );
				if ( $full_video ) {
					$video = $full_video;
				}
			}
			self::render_video_card( $video, $base_url, $api );
		}

		echo '</div>';

		// Pagination
		if ( $total > $per_page ) {
			$total_pages = ceil( $total / $per_page );
			$links       = paginate_links( array(
				'base'      => add_query_arg( 'pt_page', '%#%' ),
				'format'    => '',
				'current'   => $paged,
				'total'     => $total_pages,
				'type'      => 'array',
				'prev_text' => __( '&laquo; Zurück', 'peertube-video-manager' ),
				'next_text' => __( 'Weiter &raquo;', 'peertube-video-manager' ),
			) );

			if ( ! empty( $links ) ) {
				echo '<nav class="pt-pagination" aria-label="' . esc_attr__( 'PeerTube Video Pagination', 'peertube-video-manager' ) . '">';
				echo '<ul class="pt-pagination-list">';

				foreach ( $links as $link ) {
					echo '<li class="pt-pagination-item">' . $link . '</li>';
				}

				echo '</ul>';
				echo '</nav>';
			}
		}

		return ob_get_clean();
	}

	/**
	 * Render video card
	 *
	 * @param array  $video Video data.
	 * @param string $base_url Base URL.
	 * @param PT_API $api API instance.
	 * @since 1.1.7
	 */
	private static function render_video_card( $video, $base_url, $api ) {
		$template_path = PT_VM_PLUGIN_DIR . 'templates/video-card.php';
		
		if ( file_exists( $template_path ) ) {
			include $template_path;
		}
	}
}

