<?php
/**
 * Video card template
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
$video_short_id    = isset( $video['shortUUID'] ) ? $video['shortUUID'] : '';
$video_name        = isset( $video['name'] ) ? $video['name'] : '';
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
	error_log( 'PT Video Card - No category found. Video: ' . print_r( array(
		'category' => isset( $video['category'] ) ? $video['category'] : 'not set',
		'categoryId' => isset( $video['categoryId'] ) ? $video['categoryId'] : 'not set',
		'video_id' => $video_id,
	), true ) );
}

$video_tags        = isset( $video['tags'] ) && is_array( $video['tags'] ) ? $video['tags'] : array();

$thumbnail_url  = PT_Formatter::get_thumbnail_url( $base_url, $video );
$wp_video_url   = PT_Formatter::get_wp_video_url( $video, $api );
$peertube_url   = PT_Formatter::get_video_url( $base_url, $video );
$duration_str   = PT_Formatter::format_duration( $video_duration );
$views_str      = PT_Formatter::format_views( $video_views );
$relative_time  = PT_Formatter::format_relative_time( $video_published );
// $category_name is already set above from video['category']['label']
$plugin_data    = $api->get_plugin_data( $video );

$sender_responsible = $plugin_data['senderResponsible'];
$video_number       = $plugin_data['videoNumber'];

// Use WordPress URL if available, fallback to PeerTube URL
$video_link_url = ! empty( $wp_video_url ) ? $wp_video_url : $peertube_url;
?>

<article class="pt-video-card" data-video-id="<?php echo esc_attr( $video_id ); ?>" data-video-embed="<?php echo esc_attr( PT_Formatter::get_embed_url( $base_url, $video ) ); ?>">
	<div class="pt-video-thumbnail">
		<a href="#" class="pt-video-play" data-video-id="<?php echo esc_attr( $video_id ); ?>" data-video-number="<?php echo esc_attr( $video_number ); ?>">
			<?php if ( ! empty( $thumbnail_url ) ) : ?>
				<?php
				$thumbnail_srcset = PT_Formatter::get_thumbnail_srcset( $base_url, $video );
				?>
				<img src="<?php echo esc_url( $thumbnail_url ); ?>" 
					 <?php if ( ! empty( $thumbnail_srcset ) ) : ?>
					 srcset="<?php echo esc_attr( $thumbnail_srcset ); ?>"
					 sizes="(max-width: 768px) 100vw, (max-width: 1024px) 50vw, 33vw"
					 <?php endif; ?>
					 alt="<?php echo esc_attr( $video_name ); ?>"
					 loading="lazy"
					 decoding="async">
			<?php endif; ?>
			<?php if ( $video_duration > 0 ) : ?>
				<span class="pt-duration"><?php echo esc_html( $duration_str ); ?></span>
			<?php endif; ?>
			<span class="pt-play-button"></span>
		</a>
	</div>
	
	<div class="pt-video-info">
		<h3 class="pt-video-title">
			<a href="<?php echo esc_url( $video_link_url ); ?>"<?php echo ( empty( $wp_video_url ) ) ? ' target="_blank" rel="noopener"' : ''; ?>>
				<?php echo esc_html( $video_name ); ?>
			</a>
		</h3>
		
		<div class="pt-video-meta">
			<span class="pt-meta-line">
				<?php if ( ! empty( $sender_responsible ) ) : ?>
					<span class="pt-meta-item pt-meta-sender" title="<?php esc_attr_e( 'Sendeverantwortung', 'peertube-video-manager' ); ?>">
						<?php echo PT_Formatter::get_svg_icon( 'person', 16 ); ?>
						<span class="pt-meta-label"><?php esc_html_e( 'Sendeverantwortung:', 'peertube-video-manager' ); ?></span>
						<?php echo esc_html( $sender_responsible ); ?>
					</span>
				<?php endif; ?>
			</span>
			
			<span class="pt-meta-line">
				<?php if ( $video_duration > 0 ) : ?>
					<span class="pt-meta-item pt-meta-duration" title="<?php esc_attr_e( 'Länge', 'peertube-video-manager' ); ?>">
						<?php echo PT_Formatter::get_svg_icon( 'clock', 16 ); ?>
						<?php echo esc_html( $duration_str ); ?>
					</span>
				<?php endif; ?>
				
				<?php if ( ! empty( $relative_time ) ) : ?>
					<span class="pt-meta-item pt-meta-date" title="<?php esc_attr_e( 'Veröffentlicht', 'peertube-video-manager' ); ?>">
						<?php echo PT_Formatter::get_svg_icon( 'calendar', 16 ); ?>
						<?php echo esc_html( $relative_time ); ?>
					</span>
				<?php endif; ?>
				
				<?php if ( ! empty( $category_name ) ) : ?>
					<span class="pt-meta-item pt-meta-category" title="<?php esc_attr_e( 'Kategorie', 'peertube-video-manager' ); ?>">
						<?php echo PT_Formatter::get_svg_icon( 'folder', 16 ); ?>
						<?php echo esc_html( $category_name ); ?>
					</span>
				<?php endif; ?>
				
				<?php
				$show_views = get_option( 'pt_vm_show_views', false );
				if ( $show_views && $video_views >= 0 ) :
				?>
					<span class="pt-meta-item pt-meta-views" title="<?php esc_attr_e( 'Aufrufe', 'peertube-video-manager' ); ?>">
						<?php echo PT_Formatter::get_svg_icon( 'eye', 16 ); ?>
						<?php echo esc_html( $views_str ); ?>
					</span>
				<?php endif; ?>
			</span>
			
			<?php if ( ! empty( $video_tags ) ) : ?>
				<span class="pt-meta-line">
					<div class="pt-video-tags">
						<?php echo PT_Formatter::get_svg_icon( 'tag', 16 ); ?>
						<?php foreach ( array_slice( $video_tags, 0, 5 ) as $tag ) : ?>
							<?php $search_url = PT_Formatter::get_search_url( $tag ); ?>
							<a href="<?php echo esc_url( $search_url ); ?>" class="pt-tag">
								#<?php echo esc_html( $tag ); ?>
							</a>
						<?php endforeach; ?>
					</div>
				</span>
			<?php endif; ?>
		</div>
	</div>
</article>

