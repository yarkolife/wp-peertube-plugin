<?php
/**
 * Settings page class
 *
 * @package PeerTube_Video_Manager
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * PT_Settings class for plugin settings page
 *
 * @package PeerTube_Video_Manager
 * @since 1.0.0
 */
class PT_Settings {

	/**
	 * Instance of this class
	 *
	 * @var PT_Settings
	 */
	private static $instance = null;

	/**
	 * Get instance
	 *
	 * @return PT_Settings
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
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_init', array( $this, 'save_checkbox_settings' ) );
		add_action( 'admin_init', array( $this, 'handle_settings_save_redirect' ) );
		add_action( 'admin_post_pt_vm_clear_cache', array( $this, 'handle_clear_cache' ) );
		add_action( 'admin_post_pt_vm_create_search_page', array( $this, 'handle_create_search_page' ) );
		add_action( 'admin_post_pt_vm_create_video_page', array( $this, 'handle_create_video_page' ) );
		add_action( 'wp_ajax_pt_vm_test_connection', array( $this, 'ajax_test_connection' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
	}

	/**
	 * Add settings page to admin menu
	 *
	 * @since 1.0.0
	 */
	public function add_settings_page() {
		add_options_page(
			__( 'PeerTube Video Manager', 'peertube-video-manager' ),
			__( 'PeerTube Videos', 'peertube-video-manager' ),
			'manage_options',
			'pt-video-manager',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register plugin settings
	 *
	 * @since 1.0.0
	 */
	public function register_settings() {
		register_setting( 'pt_vm_settings', 'pt_vm_base_url', array(
			'type'              => 'string',
			'sanitize_callback' => array( $this, 'sanitize_url' ),
			'default'           => 'https://lokalmedial.de',
		) );

		register_setting( 'pt_vm_settings', 'pt_vm_default_channels', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_textarea_field',
			'default'           => '',
		) );

		register_setting( 'pt_vm_settings', 'pt_vm_cache_time_videos', array(
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'default'           => 5,
		) );

		register_setting( 'pt_vm_settings', 'pt_vm_cache_time_config', array(
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'default'           => 24,
		) );

		register_setting( 'pt_vm_settings', 'pt_vm_videos_per_page', array(
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'default'           => 8,
		) );

		register_setting( 'pt_vm_settings', 'pt_vm_show_views', array(
			'type'              => 'boolean',
			'sanitize_callback' => array( $this, 'sanitize_checkbox' ),
			'default'           => false,
		) );

		register_setting( 'pt_vm_settings', 'pt_vm_search_page_id', array(
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'default'           => 0,
		) );

		register_setting( 'pt_vm_settings', 'pt_vm_video_page_id', array(
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'default'           => 0,
		) );

		register_setting( 'pt_vm_settings', 'pt_vm_peertube_button_text', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => __( 'Auf PeerTube ansehen', 'peertube-video-manager' ),
		) );

		register_setting( 'pt_vm_settings', 'pt_vm_button_color', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_hex_color',
			'default'           => '#1e40af',
		) );

		register_setting( 'pt_vm_settings', 'pt_vm_button_hover_color', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_hex_color',
			'default'           => '#f59e0b',
		) );

		register_setting( 'pt_vm_settings', 'pt_vm_button_text_color', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_hex_color',
			'default'           => '#ffffff',
		) );
	}

	/**
	 * Save checkbox settings manually
	 *
	 * @since 1.0.6
	 */
	public function save_checkbox_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( isset( $_POST['option_page'] ) && 'pt_vm_settings' === $_POST['option_page'] ) {
			// Handle checkbox
			if ( isset( $_POST['pt_vm_show_views'] ) && '1' === $_POST['pt_vm_show_views'] ) {
				update_option( 'pt_vm_show_views', true );
			} else {
				update_option( 'pt_vm_show_views', false );
			}
		}
	}

	/**
	 * Handle redirect after settings save
	 *
	 * @since 1.1.3
	 */
	public function handle_settings_save_redirect() {
		// Only process if our settings group is being saved
		if ( ! isset( $_POST['option_page'] ) || 'pt_vm_settings' !== $_POST['option_page'] ) {
			return;
		}

		// Check if this is a settings update request
		if ( ! isset( $_POST['submit'] ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Use filter to modify redirect location
		add_filter( 'wp_redirect', array( $this, 'modify_settings_redirect' ), 10, 2 );
	}

	/**
	 * Modify redirect URL after settings save
	 *
	 * @param string $location Redirect location.
	 * @param int    $status HTTP status code.
	 * @return string Modified redirect location.
	 * @since 1.1.3
	 */
	public function modify_settings_redirect( $location, $status ) {
		// Check if this is a redirect from options.php for our settings
		if ( isset( $_POST['option_page'] ) && 'pt_vm_settings' === $_POST['option_page'] ) {
			// Remove filter to avoid infinite loop
			remove_filter( 'wp_redirect', array( $this, 'modify_settings_redirect' ), 10 );
			
			// Redirect to our settings page instead
			$location = add_query_arg(
				array(
					'page'             => 'pt-video-manager',
					'settings-updated' => 'true',
				),
				admin_url( 'options-general.php' )
			);
		}

		return $location;
	}

	/**
	 * Sanitize checkbox
	 *
	 * @param mixed $value Checkbox value.
	 * @return bool Sanitized boolean value.
	 * @since 1.0.5
	 */
	public function sanitize_checkbox( $value ) {
		return (bool) $value;
	}

	/**
	 * Sanitize URL
	 *
	 * @param string $url URL to sanitize.
	 * @return string Sanitized URL.
	 * @since 1.0.0
	 */
	public function sanitize_url( $url ) {
		$url = esc_url_raw( $url );
		$url = rtrim( $url, '/' );
		
		if ( ! preg_match( '/^https?:\/\/.+/', $url ) ) {
			add_settings_error(
				'pt_vm_base_url',
				'invalid_url',
				__( 'Bitte geben Sie eine gültige URL ein (http:// oder https://).', 'peertube-video-manager' ),
				'error'
			);
			return get_option( 'pt_vm_base_url', 'https://lokalmedial.de' );
		}
		
		return $url;
	}

	/**
	 * Render settings page
	 *
	 * @since 1.0.0
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$base_url          = get_option( 'pt_vm_base_url', 'https://lokalmedial.de' );
		$default_channels  = get_option( 'pt_vm_default_channels', '' );
		$cache_time_videos = get_option( 'pt_vm_cache_time_videos', 5 );
		$cache_time_config = get_option( 'pt_vm_cache_time_config', 24 );
		$videos_per_page   = get_option( 'pt_vm_videos_per_page', 8 );
		$show_views        = get_option( 'pt_vm_show_views', false );
		$search_page_id    = get_option( 'pt_vm_search_page_id', 0 );
		$video_page_id     = get_option( 'pt_vm_video_page_id', 0 );
		$peertube_button_text = get_option( 'pt_vm_peertube_button_text', __( 'Auf PeerTube ansehen', 'peertube-video-manager' ) );
		$button_color      = get_option( 'pt_vm_button_color', '#1e40af' );
		$button_hover_color = get_option( 'pt_vm_button_hover_color', '#f59e0b' );
		$button_text_color = get_option( 'pt_vm_button_text_color', '#ffffff' );
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			
			<?php settings_errors( 'pt_vm_messages' ); ?>
			
			<?php if ( isset( $_GET['settings-updated'] ) ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Einstellungen gespeichert.', 'peertube-video-manager' ); ?></p>
				</div>
			<?php endif; ?>
			
			<form method="post" action="options.php">
				<?php 
				settings_fields( 'pt_vm_settings' );
				// Add hidden field to preserve referer for redirect
				?>
				<input type="hidden" name="_wp_http_referer" value="<?php echo esc_attr( add_query_arg( 'page', 'pt-video-manager', admin_url( 'options-general.php' ) ) ); ?>">
				
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="pt_vm_base_url">
								<?php esc_html_e( 'PeerTube Instanz URL', 'peertube-video-manager' ); ?>
							</label>
						</th>
						<td>
							<input type="url" 
								   id="pt_vm_base_url" 
								   name="pt_vm_base_url" 
								   value="<?php echo esc_attr( $base_url ); ?>" 
								   class="regular-text"
								   required>
							<p class="description">
								<?php esc_html_e( 'Die vollständige URL Ihrer PeerTube-Instanz (z.B. https://lokalmedial.de)', 'peertube-video-manager' ); ?>
							</p>
							<p>
								<button type="button" id="pt-test-connection" class="button">
									<?php esc_html_e( 'Verbindung testen', 'peertube-video-manager' ); ?>
								</button>
								<span id="pt-connection-result"></span>
							</p>
						</td>
					</tr>
					
					<tr>
						<th scope="row">
							<label for="pt_vm_default_channels">
								<?php esc_html_e( 'Standard-Kanäle', 'peertube-video-manager' ); ?>
							</label>
						</th>
						<td>
							<textarea id="pt_vm_default_channels" 
									  name="pt_vm_default_channels" 
									  rows="5" 
									  class="large-text"><?php echo esc_textarea( $default_channels ); ?></textarea>
							<p class="description">
								<?php esc_html_e( 'Liste der Kanal-Handles, einen pro Zeile (z.B. ok_dessau, ok_magdeburg). Wird für [pt-latest-per-channel] verwendet, wenn kein channels-Attribut angegeben ist.', 'peertube-video-manager' ); ?>
							</p>
						</td>
					</tr>
					
					<tr>
						<th scope="row">
							<label for="pt_vm_cache_time_videos">
								<?php esc_html_e( 'Cache-Zeit für Videos (Minuten)', 'peertube-video-manager' ); ?>
							</label>
						</th>
						<td>
							<input type="number" 
								   id="pt_vm_cache_time_videos" 
								   name="pt_vm_cache_time_videos" 
								   value="<?php echo esc_attr( $cache_time_videos ); ?>" 
								   min="1" 
								   max="1440" 
								   class="small-text">
							<p class="description">
								<?php esc_html_e( 'Wie lange Video-Listen zwischengespeichert werden (Standard: 5 Minuten)', 'peertube-video-manager' ); ?>
							</p>
						</td>
					</tr>
					
					<tr>
						<th scope="row">
							<label for="pt_vm_cache_time_config">
								<?php esc_html_e( 'Cache-Zeit für Konfiguration (Stunden)', 'peertube-video-manager' ); ?>
							</label>
						</th>
						<td>
							<input type="number" 
								   id="pt_vm_cache_time_config" 
								   name="pt_vm_cache_time_config" 
								   value="<?php echo esc_attr( $cache_time_config ); ?>" 
								   min="1" 
								   max="168" 
								   class="small-text">
							<p class="description">
								<?php esc_html_e( 'Wie lange Kategorien und Konfiguration zwischengespeichert werden (Standard: 24 Stunden)', 'peertube-video-manager' ); ?>
							</p>
						</td>
					</tr>
					
					<tr>
						<th scope="row">
							<label for="pt_vm_videos_per_page">
								<?php esc_html_e( 'Videos pro Seite', 'peertube-video-manager' ); ?>
							</label>
						</th>
						<td>
							<input type="number" 
								   id="pt_vm_videos_per_page" 
								   name="pt_vm_videos_per_page" 
								   value="<?php echo esc_attr( $videos_per_page ); ?>" 
								   min="1" 
								   max="100" 
								   class="small-text">
							<p class="description">
								<?php esc_html_e( 'Standard-Anzahl von Videos, die angezeigt werden (Standard: 8)', 'peertube-video-manager' ); ?>
							</p>
						</td>
					</tr>
					
					<tr>
						<th scope="row">
							<label for="pt_vm_show_views">
								<?php esc_html_e( 'Aufrufe anzeigen', 'peertube-video-manager' ); ?>
							</label>
						</th>
						<td>
							<label>
								<input type="checkbox" 
									   id="pt_vm_show_views" 
									   name="pt_vm_show_views" 
									   value="1" 
									   <?php checked( $show_views, true ); ?>>
								<?php esc_html_e( 'Anzahl der Aufrufe in Video-Karten und Detailansicht anzeigen', 'peertube-video-manager' ); ?>
							</label>
							<p class="description">
								<?php esc_html_e( 'Wenn aktiviert, wird die Anzahl der Aufrufe bei jedem Video angezeigt.', 'peertube-video-manager' ); ?>
							</p>
						</td>
					</tr>
					
					<tr>
						<th scope="row">
							<label for="pt_vm_search_page_id">
								<?php esc_html_e( 'Seite für Suche', 'peertube-video-manager' ); ?>
							</label>
						</th>
						<td>
							<?php
							wp_dropdown_pages( array(
								'name'             => 'pt_vm_search_page_id',
								'id'               => 'pt_vm_search_page_id',
								'selected'         => $search_page_id,
								'show_option_none' => __( '— Seite auswählen —', 'peertube-video-manager' ),
								'option_none_value' => '0',
							) );
							?>
							<p class="description">
								<?php esc_html_e( 'Wählen Sie die Seite aus, auf der die Suche angezeigt werden soll. Diese Seite sollte die Shortcodes [pt-search] und [pt-search-results] enthalten. Wenn keine Seite ausgewählt ist, wird die aktuelle Seite verwendet.', 'peertube-video-manager' ); ?>
							</p>
							<?php if ( $search_page_id > 0 ) : ?>
								<?php
								$page = get_post( $search_page_id );
								if ( $page && 'publish' === $page->post_status ) :
								?>
									<p>
										<a href="<?php echo esc_url( get_edit_post_link( $search_page_id ) ); ?>" target="_blank">
											<?php esc_html_e( 'Seite bearbeiten', 'peertube-video-manager' ); ?>
										</a> |
										<a href="<?php echo esc_url( get_permalink( $search_page_id ) ); ?>" target="_blank">
											<?php esc_html_e( 'Seite ansehen', 'peertube-video-manager' ); ?>
										</a>
									</p>
								<?php else : ?>
									<p class="description" style="color: #d63638;">
										<?php esc_html_e( 'Die ausgewählte Seite existiert nicht mehr oder ist nicht veröffentlicht.', 'peertube-video-manager' ); ?>
									</p>
								<?php endif; ?>
							<?php else : ?>
								<p class="description">
									<strong><?php esc_html_e( 'Hinweis:', 'peertube-video-manager' ); ?></strong>
									<?php esc_html_e( 'Erstellen Sie eine Seite mit den Shortcodes [pt-search placeholder="Suche..."] und [pt-search-results per_page="12"] und wählen Sie sie hier aus.', 'peertube-video-manager' ); ?>
								</p>
								<p>
									<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display: inline;">
										<?php wp_nonce_field( 'pt_vm_create_search_page', 'pt_vm_create_page_nonce' ); ?>
										<input type="hidden" name="action" value="pt_vm_create_search_page">
										<?php submit_button( __( 'Seite automatisch erstellen', 'peertube-video-manager' ), 'secondary', 'submit', false ); ?>
									</form>
								</p>
							<?php endif; ?>
						</td>
					</tr>
					
					<tr>
						<th scope="row">
							<label for="pt_vm_video_page_id">
								<?php esc_html_e( 'Seite für Video-Ansicht', 'peertube-video-manager' ); ?>
							</label>
						</th>
						<td>
							<?php
							wp_dropdown_pages( array(
								'name'             => 'pt_vm_video_page_id',
								'id'               => 'pt_vm_video_page_id',
								'selected'         => $video_page_id,
								'show_option_none' => __( '— Seite auswählen —', 'peertube-video-manager' ),
								'option_none_value' => '0',
							) );
							?>
							<p class="description">
								<?php esc_html_e( 'Wählen Sie die Seite aus, auf der Videos im Detail angezeigt werden sollen. Diese Seite wird automatisch verwendet, wenn auf ein Video aus den Listen geklickt wird.', 'peertube-video-manager' ); ?>
							</p>
							<?php if ( $video_page_id > 0 ) : ?>
								<?php
								$page = get_post( $video_page_id );
								if ( $page && 'publish' === $page->post_status ) :
								?>
									<p>
										<a href="<?php echo esc_url( get_edit_post_link( $video_page_id ) ); ?>" target="_blank">
											<?php esc_html_e( 'Seite bearbeiten', 'peertube-video-manager' ); ?>
										</a> |
										<a href="<?php echo esc_url( get_permalink( $video_page_id ) ); ?>" target="_blank">
											<?php esc_html_e( 'Seite ansehen', 'peertube-video-manager' ); ?>
										</a>
									</p>
								<?php else : ?>
									<p class="description" style="color: #d63638;">
										<?php esc_html_e( 'Die ausgewählte Seite existiert nicht mehr oder ist nicht veröffentlicht.', 'peertube-video-manager' ); ?>
									</p>
								<?php endif; ?>
							<?php else : ?>
								<p class="description">
									<strong><?php esc_html_e( 'Hinweis:', 'peertube-video-manager' ); ?></strong>
									<?php esc_html_e( 'Wenn beim Aktivieren des Plugins keine Seite gefunden wurde, wird eine neue Seite automatisch erstellt.', 'peertube-video-manager' ); ?>
								</p>
								<p>
									<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display: inline;">
										<?php wp_nonce_field( 'pt_vm_create_video_page', 'pt_vm_create_video_page_nonce' ); ?>
										<input type="hidden" name="action" value="pt_vm_create_video_page">
										<?php submit_button( __( 'Seite automatisch erstellen', 'peertube-video-manager' ), 'secondary', 'submit', false ); ?>
									</form>
								</p>
							<?php endif; ?>
						</td>
					</tr>
					
					<tr>
						<th scope="row">
							<label for="pt_vm_peertube_button_text">
								<?php esc_html_e( 'Text der PeerTube-Button', 'peertube-video-manager' ); ?>
							</label>
						</th>
						<td>
							<input type="text" 
								   id="pt_vm_peertube_button_text" 
								   name="pt_vm_peertube_button_text" 
								   value="<?php echo esc_attr( $peertube_button_text ); ?>" 
								   class="regular-text">
							<p class="description">
								<?php esc_html_e( 'Text, der auf dem Button "Auf PeerTube ansehen" angezeigt wird.', 'peertube-video-manager' ); ?>
							</p>
						</td>
					</tr>
					
					<tr>
						<th scope="row">
							<label for="pt_vm_button_color">
								<?php esc_html_e( 'Farbe der Buttons', 'peertube-video-manager' ); ?>
							</label>
						</th>
						<td>
							<input type="text" 
								   id="pt_vm_button_color" 
								   name="pt_vm_button_color" 
								   value="<?php echo esc_attr( $button_color ); ?>" 
								   class="pt-color-picker"
								   data-default-color="#1e40af">
							<p class="description">
								<?php esc_html_e( 'Hintergrundfarbe der Buttons (Standard: dunkelblau)', 'peertube-video-manager' ); ?>
							</p>
						</td>
					</tr>
					
					<tr>
						<th scope="row">
							<label for="pt_vm_button_hover_color">
								<?php esc_html_e( 'Farbe der Buttons bei Hover', 'peertube-video-manager' ); ?>
							</label>
						</th>
						<td>
							<input type="text" 
								   id="pt_vm_button_hover_color" 
								   name="pt_vm_button_hover_color" 
								   value="<?php echo esc_attr( $button_hover_color ); ?>" 
								   class="pt-color-picker"
								   data-default-color="#f59e0b">
							<p class="description">
								<?php esc_html_e( 'Hintergrundfarbe der Buttons beim Hovern (Standard: orange-gelb)', 'peertube-video-manager' ); ?>
							</p>
						</td>
					</tr>
					
					<tr>
						<th scope="row">
							<label for="pt_vm_button_text_color">
								<?php esc_html_e( 'Textfarbe der Buttons', 'peertube-video-manager' ); ?>
							</label>
						</th>
						<td>
							<input type="text" 
								   id="pt_vm_button_text_color" 
								   name="pt_vm_button_text_color" 
								   value="<?php echo esc_attr( $button_text_color ); ?>" 
								   class="pt-color-picker"
								   data-default-color="#ffffff">
							<p class="description">
								<?php esc_html_e( 'Textfarbe der Buttons (Standard: weiß)', 'peertube-video-manager' ); ?>
							</p>
						</td>
					</tr>
				</table>
				
				<?php submit_button( __( 'Einstellungen speichern', 'peertube-video-manager' ) ); ?>
			</form>
			
			<hr>
			
			<h2><?php esc_html_e( 'Cache-Verwaltung', 'peertube-video-manager' ); ?></h2>
			<p><?php esc_html_e( 'Löschen Sie den Cache, um aktualisierte Daten von PeerTube zu laden.', 'peertube-video-manager' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'pt_vm_clear_cache', 'pt_vm_cache_nonce' ); ?>
				<input type="hidden" name="action" value="pt_vm_clear_cache">
				<?php submit_button( __( 'Cache löschen', 'peertube-video-manager' ), 'secondary', 'submit', false ); ?>
			</form>
			
			<hr>
			
			<h2><?php esc_html_e( 'Shortcode-Beispiele', 'peertube-video-manager' ); ?></h2>
			<ul>
				<li><code>[pt-last-videos count="8"]</code> - <?php esc_html_e( 'Letzte Videos der Instanz', 'peertube-video-manager' ); ?></li>
				<li><code>[pt-last-videos count="8" columns="2"]</code> - <?php esc_html_e( 'Letzte Videos in 2 Spalten', 'peertube-video-manager' ); ?></li>
				<li><code>[pt-last-videos count="8" columns="1"]</code> - <?php esc_html_e( 'Letzte Videos in einer Spalte', 'peertube-video-manager' ); ?></li>
				<li><code>[pt-latest-per-channel channels="ok_dessau,ok_magdeburg"]</code> - <?php esc_html_e( 'Je ein Video pro Kanal', 'peertube-video-manager' ); ?></li>
				<li><code>[pt-latest-per-channel channels="ok_dessau,ok_magdeburg" columns="3"]</code> - <?php esc_html_e( 'Je ein Video pro Kanal in 3 Spalten', 'peertube-video-manager' ); ?></li>
				<li><code>[pt-channel-videos channel="okmq" count="6"]</code> - <?php esc_html_e( 'Videos eines Kanals', 'peertube-video-manager' ); ?></li>
				<li><code>[pt-channel-videos channel="okmq" count="6" columns="2"]</code> - <?php esc_html_e( 'Videos eines Kanals in 2 Spalten', 'peertube-video-manager' ); ?></li>
				<li><code>[pt-video id="UUID"]</code> - <?php esc_html_e( 'Einzelnes Video mit Details', 'peertube-video-manager' ); ?></li>
				<li><code>[pt-video number="123"]</code> - <?php esc_html_e( 'Video per Video-Nummer', 'peertube-video-manager' ); ?></li>
				<li><code>[pt-search placeholder="Suche..."]</code> - <?php esc_html_e( 'Suchformular', 'peertube-video-manager' ); ?></li>
				<li><code>[pt-search-results per_page="12"]</code> - <?php esc_html_e( 'Suchergebnisse', 'peertube-video-manager' ); ?></li>
			</ul>
			<p class="description">
				<strong><?php esc_html_e( 'Hinweis:', 'peertube-video-manager' ); ?></strong>
				<?php esc_html_e( 'Der Parameter "columns" kann Werte von 1 bis 6 haben oder "auto" (Standard, responsive). Bei "auto" passt sich die Anzahl der Spalten automatisch der Bildschirmgröße an.', 'peertube-video-manager' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Handle cache clearing
	 *
	 * @since 1.0.0
	 */
	public function handle_clear_cache() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Sie haben keine Berechtigung für diese Aktion.', 'peertube-video-manager' ) );
		}

		check_admin_referer( 'pt_vm_clear_cache', 'pt_vm_cache_nonce' );

		$cache = new PT_Cache();
		$count = $cache->flush_all();

		add_settings_error(
			'pt_vm_messages',
			'cache_cleared',
			sprintf(
				/* translators: %d: number of cache entries cleared */
				__( 'Cache geleert! %d Einträge wurden gelöscht.', 'peertube-video-manager' ),
				$count
			),
			'success'
		);

		set_transient( 'pt_vm_cache_cleared', true, 30 );

		wp_safe_redirect( add_query_arg( 'page', 'pt-video-manager', admin_url( 'options-general.php' ) ) );
		exit;
	}

	/**
	 * Handle search page creation
	 *
	 * @since 1.0.8
	 */
	public function handle_create_search_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Sie haben keine Berechtigung für diese Aktion.', 'peertube-video-manager' ) );
		}

		check_admin_referer( 'pt_vm_create_search_page', 'pt_vm_create_page_nonce' );

		// Create search page
		$search_page_content = __( 'Suche in der PeerTube Mediathek', 'peertube-video-manager' ) . "\n\n" .
			'[pt-search placeholder="' . __( 'Suche...', 'peertube-video-manager' ) . '"]' . "\n\n" .
			'[pt-search-results per_page="12"]';

		$page_data = array(
			'post_title'   => __( 'PeerTube Suche', 'peertube-video-manager' ),
			'post_content' => $search_page_content,
			'post_status'  => 'publish',
			'post_type'    => 'page',
			'post_name'    => 'peertube-suche',
		);

		$search_page_id = wp_insert_post( $page_data );
		if ( ! is_wp_error( $search_page_id ) && $search_page_id > 0 ) {
			update_option( 'pt_vm_search_page_id', $search_page_id );

			add_settings_error(
				'pt_vm_messages',
				'search_page_created',
				__( 'Suchseite wurde erfolgreich erstellt!', 'peertube-video-manager' ),
				'success'
			);
		} else {
			add_settings_error(
				'pt_vm_messages',
				'search_page_error',
				__( 'Fehler beim Erstellen der Suchseite.', 'peertube-video-manager' ),
				'error'
			);
		}

		set_transient( 'pt_vm_settings_errors', get_settings_errors( 'pt_vm_messages' ), 30 );

		wp_safe_redirect( add_query_arg( 'page', 'pt-video-manager', admin_url( 'options-general.php' ) ) );
		exit;
	}

	/**
	 * Handle video page creation
	 *
	 * @since 1.1.2
	 */
	public function handle_create_video_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Sie haben keine Berechtigung für diese Aktion.', 'peertube-video-manager' ) );
		}

		check_admin_referer( 'pt_vm_create_video_page', 'pt_vm_create_video_page_nonce' );

		// Create video page - the shortcode will be added automatically via auto_display_video filter
		$video_page_content = __( 'PeerTube Video', 'peertube-video-manager' );

		$page_data = array(
			'post_title'   => __( 'PeerTube Video', 'peertube-video-manager' ),
			'post_content' => $video_page_content,
			'post_status'  => 'publish',
			'post_type'    => 'page',
			'post_name'    => 'peertube-video',
		);

		$video_page_id = wp_insert_post( $page_data );
		if ( ! is_wp_error( $video_page_id ) && $video_page_id > 0 ) {
			update_option( 'pt_vm_video_page_id', $video_page_id );

			add_settings_error(
				'pt_vm_messages',
				'video_page_created',
				__( 'Videoseite wurde erfolgreich erstellt!', 'peertube-video-manager' ),
				'success'
			);
		} else {
			add_settings_error(
				'pt_vm_messages',
				'video_page_error',
				__( 'Fehler beim Erstellen der Videoseite.', 'peertube-video-manager' ),
				'error'
			);
		}

		set_transient( 'pt_vm_settings_errors', get_settings_errors( 'pt_vm_messages' ), 30 );

		wp_safe_redirect( add_query_arg( 'page', 'pt-video-manager', admin_url( 'options-general.php' ) ) );
		exit;
	}

	/**
	 * AJAX handler for testing connection
	 *
	 * @since 1.0.0
	 */
	public function ajax_test_connection() {
		check_ajax_referer( 'pt_vm_test_connection', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array(
				'message' => __( 'Keine Berechtigung.', 'peertube-video-manager' ),
			) );
		}

		$url = isset( $_POST['url'] ) ? esc_url_raw( $_POST['url'] ) : '';
		
		if ( empty( $url ) ) {
			wp_send_json_error( array(
				'message' => __( 'Keine URL angegeben.', 'peertube-video-manager' ),
			) );
		}

		// Temporarily set the URL for testing
		$old_url = get_option( 'pt_vm_base_url' );
		update_option( 'pt_vm_base_url', $url );

		$api    = new PT_API();
		$result = $api->test_connection();

		// Restore old URL
		update_option( 'pt_vm_base_url', $old_url );

		if ( $result['success'] ) {
			wp_send_json_success( $result );
		} else {
			wp_send_json_error( $result );
		}
	}

	/**
	 * Enqueue admin scripts
	 *
	 * @param string $hook Current admin page hook.
	 * @since 1.0.0
	 */
	public function enqueue_admin_scripts( $hook ) {
		if ( 'settings_page_pt-video-manager' !== $hook ) {
			return;
		}

		// Enqueue color picker
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );

		wp_enqueue_script(
			'pt-admin-scripts',
			PT_VM_PLUGIN_URL . 'assets/js/pt-admin.js',
			array( 'jquery', 'wp-color-picker' ),
			PT_VM_VERSION,
			true
		);

		wp_localize_script( 'pt-admin-scripts', 'ptAdmin', array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'pt_vm_test_connection' ),
		) );
	}
}

