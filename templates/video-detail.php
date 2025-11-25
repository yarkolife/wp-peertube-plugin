<?php
/**
 * Video detail template
 *
 * @package PeerTube_Video_Manager
 * @since 1.0.0
 *
 * Available variables:
 * @var array  $video Video data from PeerTube API
 * @var string $base_url PeerTube instance URL
 * @var PT_API $api API instance
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$video_id          = isset( $video['uuid'] ) ? $video['uuid'] : '';
$video_name        = isset( $video['name'] ) ? $video['name'] : '';
$video_description = isset( $video['description'] ) ? $video['description'] : '';
$video_duration    = isset( $video['duration'] ) ? $video['duration'] : 0;
$video_views       = isset( $video['views'] ) ? $video['views'] : 0;
$video_published   = isset( $video['publishedAt'] ) ? $video['publishedAt'] : '';

// Get category name - PeerTube returns category as object with id and label
$category_name = '';

// Check category field - PeerTube returns it as object: {'id': 102, 'label': 'Trailer'}
if ( isset( $video['category'] ) && $video['category'] !== null ) {
	if ( is_array( $video['category'] ) && isset( $video['category']['label'] ) ) {
		// Category comes with label directly - use it!
		$category_name = $video['category']['label'];
	} elseif ( is_array( $video['category'] ) && isset( $video['category']['id'] ) ) {
		// Category has only ID - try to get name from config
		$video_category_id = (int) $video['category']['id'];
		$category_name = $api->get_category_name( $video_category_id );
	} elseif ( is_numeric( $video['category'] ) ) {
		// Category is just a number - get name from config
		$video_category_id = (int) $video['category'];
		$category_name = $api->get_category_name( $video_category_id );
	}
}

// Also check categoryId field (fallback)
if ( empty( $category_name ) && isset( $video['categoryId'] ) && $video['categoryId'] !== null ) {
	if ( is_numeric( $video['categoryId'] ) ) {
		$video_category_id = (int) $video['categoryId'];
		$category_name = $api->get_category_name( $video_category_id );
	} elseif ( is_array( $video['categoryId'] ) && isset( $video['categoryId']['id'] ) ) {
		$video_category_id = (int) $video['categoryId']['id'];
		$category_name = $api->get_category_name( $video_category_id );
	}
}

// Debug logging (only if WP_DEBUG is enabled)
if ( defined( 'WP_DEBUG' ) && WP_DEBUG && empty( $category_name ) ) {
	error_log( 'PT Video Detail - No category found. Video: ' . print_r( array(
		'category' => isset( $video['category'] ) ? $video['category'] : 'not set',
		'categoryId' => isset( $video['categoryId'] ) ? $video['categoryId'] : 'not set',
		'video_id' => $video_id,
	), true ) );
}

$video_tags        = isset( $video['tags'] ) && is_array( $video['tags'] ) ? $video['tags'] : array();

$embed_url      = PT_Formatter::get_embed_url( $base_url, $video );
$video_url      = PT_Formatter::get_video_url( $base_url, $video );
$duration_str   = PT_Formatter::format_duration( $video_duration );
$views_str      = PT_Formatter::format_views( $video_views );
$relative_time  = PT_Formatter::format_relative_time( $video_published );
// $category_name is already set above from video['category']['label']
$plugin_data    = $api->get_plugin_data( $video );
$description    = PT_Formatter::sanitize_description( $video_description );

$sender_responsible = $plugin_data['senderResponsible'];
$video_number       = $plugin_data['videoNumber'];
?>

<article class="pt-video-detail" data-video-id="<?php echo esc_attr( $video_id ); ?>">
	<?php if ( ! empty( $embed_url ) ) : ?>
		<div class="pt-video-player">
			<div class="pt-video-embed-container">
				<iframe 
					src="<?php echo esc_url( $embed_url ); ?>" 
					frameborder="0" 
					allowfullscreen 
					sandbox="allow-same-origin allow-scripts allow-popups"
					title="<?php echo esc_attr( $video_name ); ?>">
				</iframe>
			</div>
		</div>
	<?php endif; ?>
	
	<div class="pt-video-content">
		<h1 class="pt-video-title"><?php echo esc_html( $video_name ); ?></h1>
		
		<div class="pt-video-meta-full">
			<div class="pt-meta-row">
				<?php if ( ! empty( $sender_responsible ) ) : ?>
					<div class="pt-meta-item">
						<?php echo PT_Formatter::get_svg_icon( 'person', 16 ); ?>
						<span class="pt-meta-label"><?php esc_html_e( 'Sendeverantwortung:', 'peertube-video-manager' ); ?></span>
						<span class="pt-meta-value"><?php echo esc_html( $sender_responsible ); ?></span>
					</div>
				<?php endif; ?>
				
				<?php if ( ! empty( $relative_time ) ) : ?>
					<div class="pt-meta-item">
						<?php echo PT_Formatter::get_svg_icon( 'calendar', 16 ); ?>
						<span class="pt-meta-value"><?php echo esc_html( $relative_time ); ?></span>
					</div>
				<?php endif; ?>
				
				<?php
				$show_views = get_option( 'pt_vm_show_views', false );
				if ( $show_views && $video_views >= 0 ) :
				?>
					<div class="pt-meta-item">
						<?php echo PT_Formatter::get_svg_icon( 'eye', 16 ); ?>
						<span class="pt-meta-value"><?php echo esc_html( $views_str ); ?></span>
					</div>
				<?php endif; ?>
				
				<?php if ( ! empty( $category_name ) ) : ?>
					<div class="pt-meta-item">
						<?php echo PT_Formatter::get_svg_icon( 'folder', 16 ); ?>
						<span class="pt-meta-value"><?php echo esc_html( $category_name ); ?></span>
					</div>
				<?php endif; ?>
				
				<?php if ( $video_duration > 0 ) : ?>
					<div class="pt-meta-item">
						<?php echo PT_Formatter::get_svg_icon( 'clock', 16 ); ?>
						<span class="pt-meta-value"><?php echo esc_html( $duration_str ); ?></span>
					</div>
				<?php endif; ?>
				
				<?php if ( ! empty( $video_tags ) ) : ?>
					<div class="pt-meta-item pt-meta-tags">
						<?php echo PT_Formatter::get_svg_icon( 'tag', 16 ); ?>
						<?php
						$tags_links = array();
						foreach ( $video_tags as $tag ) {
							$search_url = PT_Formatter::get_search_url( $tag );
							$tags_links[] = '<a href="' . esc_url( $search_url ) . '" class="pt-tag-link">' . esc_html( $tag ) . '</a>';
						}
						echo '<span class="pt-meta-value">' . implode( ', ', $tags_links ) . '</span>';
						?>
					</div>
				<?php endif; ?>
			</div>
		</div>
		
		<?php if ( ! empty( $description ) ) : ?>
			<div class="pt-video-description">
				<h2><?php esc_html_e( 'Beschreibung', 'peertube-video-manager' ); ?></h2>
				<div class="pt-description-content">
					<?php echo wp_kses_post( $description ); ?>
				</div>
			</div>
		<?php endif; ?>
		
		<div class="pt-video-actions">
			<?php
			$button_text = get_option( 'pt_vm_peertube_button_text', __( 'Auf PeerTube ansehen', 'peertube-video-manager' ) );
			if ( empty( $button_text ) ) {
				$button_text = __( 'Auf PeerTube ansehen', 'peertube-video-manager' );
			}
			?>
			<a href="<?php echo esc_url( $video_url ); ?>" 
			   class="pt-button pt-button-peertube" 
			   target="_blank" 
			   rel="noopener">
				<?php echo esc_html( $button_text ); ?>
			</a>
		</div>
	</div>
</article>

