<?php
/**
 * Cache management class
 *
 * @package PeerTube_Video_Manager
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * PT_Cache class for managing transient cache
 *
 * @package PeerTube_Video_Manager
 * @since 1.0.0
 */
class PT_Cache {

	/**
	 * Cache prefix
	 *
	 * @var string
	 */
	const PREFIX = 'pt_vm_cache_';

	/**
	 * Get cached value
	 *
	 * @param string $key Cache key.
	 * @return mixed|false Cached value or false if not found.
	 * @since 1.0.0
	 */
	public function get( $key ) {
		$cache_key = self::PREFIX . $key;
		return get_transient( $cache_key );
	}

	/**
	 * Set cached value
	 *
	 * @param string $key Cache key.
	 * @param mixed  $value Value to cache.
	 * @param int    $expiration Expiration time in seconds (optional).
	 * @return bool True on success, false on failure.
	 * @since 1.0.0
	 */
	public function set( $key, $value, $expiration = null ) {
		$cache_key = self::PREFIX . $key;
		
		// Default expiration: 5 minutes for videos
		if ( null === $expiration ) {
			$expiration = get_option( 'pt_vm_cache_time_videos', 5 ) * MINUTE_IN_SECONDS;
		}
		
		return set_transient( $cache_key, $value, $expiration );
	}

	/**
	 * Delete cached value
	 *
	 * @param string $key Cache key.
	 * @return bool True on success, false on failure.
	 * @since 1.0.0
	 */
	public function delete( $key ) {
		$cache_key = self::PREFIX . $key;
		return delete_transient( $cache_key );
	}

	/**
	 * Flush all plugin cache
	 *
	 * @return int Number of transients deleted.
	 * @since 1.0.0
	 */
	public function flush_all() {
		global $wpdb;
		
		$count = 0;
		
		// Delete transients from options table
		$result = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} 
				WHERE option_name LIKE %s 
				OR option_name LIKE %s",
				'_transient_' . self::PREFIX . '%',
				'_transient_timeout_' . self::PREFIX . '%'
			)
		);
		
		if ( false !== $result ) {
			$count = $result;
		}
		
		return $count;
	}

	/**
	 * Generate cache key from parts
	 *
	 * @param array $parts Parts to generate key from.
	 * @return string Generated cache key.
	 * @since 1.0.0
	 */
	public function generate_key( $parts ) {
		if ( ! is_array( $parts ) ) {
			$parts = array( $parts );
		}
		
		// Serialize and hash the parts
		$serialized = serialize( $parts );
		return md5( $serialized );
	}
}

