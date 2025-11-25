<?php
/**
 * Search form template
 *
 * @package PeerTube_Video_Manager
 * @since 1.0.0
 *
 * Available variables:
 * @var string $placeholder Search placeholder text
 * @var string $action Form action URL
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<form method="get" action="<?php echo esc_url( $action ); ?>" class="pt-search-form">
	<div class="pt-search-input-wrapper">
		<input type="search" 
			   name="pt_search" 
			   value="<?php echo isset( $_GET['pt_search'] ) ? esc_attr( $_GET['pt_search'] ) : ''; ?>" 
			   placeholder="<?php echo esc_attr( $placeholder ); ?>" 
			   class="pt-search-input"
			   required>
		<button type="submit" class="pt-search-button">
			<span class="pt-search-icon">🔍</span>
			<span class="pt-search-text"><?php esc_html_e( 'Suchen', 'peertube-video-manager' ); ?></span>
		</button>
	</div>
</form>

