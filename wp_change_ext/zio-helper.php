<?php
class zioHelper {
	var $tmp_path    = false;
	var $force_retry = false;

	function __construct() {
		global $wpdb;

		$this->wpdb = $wpdb;
	}

	/**
	 * WP CLI "replace" command for replace old image with new.
	 * 
	 * @param array $args       Command arguments
	 * @param array $assoc_args Command assoc arguments
	 * 
	 * @return void
	 */
	public function replace ( $args, $assoc_args ) {
		if ( empty( $args ) ) {
			WP_CLI::error( 'You must set old and new images paths!' );
		}

		if ( empty( $args[1] ) ) {
			WP_CLI::error( 'You must set new image path!' );
		}

		$old['path'] = $args[0];
		$new['path'] = $args[1];

		if ( $old['path'] == $new['path'] ) {
			WP_CLI::error( 'Old and new images paths is equals!' );
		}

		if ( ! file_exists( $new['path'] ) ) {
			WP_CLI::error( 'New image does not exists!' );
		}

		if ( ! file_exists( $old['path'] ) ) {
			WP_CLI::error( 'Old image does not exists! It needed for restore.' );
		}

		$assoc_args = wp_parse_args(
			$assoc_args,
			[
				'tmp-path'    => false,
				'force-retry' => false,
			]
		);

		if ( empty( $assoc_args['tmp-path'] ) ) {
			WP_CLI::error( 'Path for temporary files not set (--tmp-path=<path>)!' );
		}

		$this->tmp_path = $assoc_args['tmp-path'];

		if ( ! empty( $assoc_args['force-retry'] ) ) {
			$this->force_retry = true;
		}

		// kama-thumbnail cache flush
		if ( function_exists('kthumb_cache') && method_exists( kthumb_cache(), 'clear_img_cache' ) ) { // since v3.4.2
			$kama_thumbnail_removed = kthumb_cache()->clear_img_cache( str_replace( untrailingslashit( ABSPATH ), '', $old['path'] ) );
			if ( ! empty( $kama_thumbnail_removed ) ) {
				WP_CLI::debug( 'kama thumbnail removed images count: ' . $kama_thumbnail_removed, 'zio-helper' );
			}
		}
		else { // older versions
			$kama_thumbnail = get_option( 'kama_thumbnail' );
			if ( ! empty( $kama_thumbnail ) ) {
				$kama_thumbnail_meta_key     = ( ! empty( $kama_thumbnail['meta_key'] ) ) ? $kama_thumbnail['meta_key'] : 'photo_URL';
				$kama_thumbnail_cache_dir    = ( ! empty( $kama_thumbnail['cache_dir'] ) ) ? $kama_thumbnail['cache_dir'] : untrailingslashit( str_replace( '\\', '/', WP_CONTENT_DIR . '/cache/thumb' ) );
				$kama_thumbnail_img_hash     = md5( str_replace( untrailingslashit( ABSPATH ), '', $old['path'] ) );
				$kama_thumbnail_img_mask     = substr( $kama_thumbnail_img_hash, -15 ) . '_*.' . pathinfo( $old['path'], PATHINFO_EXTENSION );
				$kama_thumbnail_img_sub_dir  = substr( $kama_thumbnail_img_hash, -2 );

				WP_CLI::debug( 'kama thumbnail meta_key: ' . $kama_thumbnail_meta_key, 'zio-helper' );
				WP_CLI::debug( 'kama thumbnail cache dir: ' . $kama_thumbnail_cache_dir, 'zio-helper' );
				WP_CLI::debug( 'kama thumbnail full img hash: ' . $kama_thumbnail_img_hash, 'zio-helper' );
				WP_CLI::debug( 'kama thumbnail files mask: ' . $kama_thumbnail_img_mask, 'zio-helper' );
				WP_CLI::debug( 'kama thumbnail image subdir: ' . $kama_thumbnail_img_sub_dir, 'zio-helper' );

				foreach ( glob( $kama_thumbnail_cache_dir . '/' . $kama_thumbnail_img_sub_dir . '/' . $kama_thumbnail_img_mask ) as $file ) {
					WP_CLI::debug( 'kama thumbnail removed image: ' . $file, 'zio-helper' );
					unlink( $file );
				}
			}
		}

		$attachment_id = $this->get_image_id_by_path( $old['path'] );

		if ( false === $attachment_id ) {
			WP_CLI::warning( 'Attachment ID not found!' );
			exit(2);
		}

		$old['name'] = basename( $old['path'] );
		$new['name'] = basename( $new['path'] );
		$old['ext']  = pathinfo( $old['name'], PATHINFO_EXTENSION );
		$new['ext']  = pathinfo( $new['name'], PATHINFO_EXTENSION );
		$old['dir']  = pathinfo( $old['path'], PATHINFO_DIRNAME );
		$new['dir']  = pathinfo( $new['path'], PATHINFO_DIRNAME );
		$old['url']  = wp_get_attachment_url( $attachment_id );
		$new['url']  = str_replace( $old['name'], $new['name'], $old['url'] );
		$old['mime'] = $this->wpdb->get_var(
			$this->wpdb->prepare(
				'SELECT post_mime_type FROM ' . $this->wpdb->posts . ' WHERE ID=%d',
				$attachment_id
			)
		);
		$new['mime'] = wp_get_image_mime( $new['path'] );

		$old['_attached_file']  = get_post_meta( $attachment_id, '_wp_attached_file', true );
		$new['_attached_file']  = str_replace( $old['name'], $new['name'], $old['_attached_file'] );
		$old['attachment_data'] = wp_get_attachment_metadata( $attachment_id );
		$new['attachment_data'] = $this->generate_new_image_metadata( $old, $new );

		WP_CLI::debug( 'attachment ID: ' . $attachment_id, 'zio-helper' );
		WP_CLI::debug( 'old path: ' . $old['path'], 'zio-helper' );
		WP_CLI::debug( 'new path: ' . $new['path'], 'zio-helper' );
		WP_CLI::debug( 'old name: ' . $old['name'], 'zio-helper' );
		WP_CLI::debug( 'new name: ' . $new['name'], 'zio-helper' );
		WP_CLI::debug( 'old ext: ' . $old['ext'], 'zio-helper' );
		WP_CLI::debug( 'new ext: ' . $new['ext'], 'zio-helper' );
		WP_CLI::debug( 'old dir: ' . $old['dir'], 'zio-helper' );
		WP_CLI::debug( 'new dir: ' . $new['dir'], 'zio-helper' );
		WP_CLI::debug( 'old url: ' . $old['url'], 'zio-helper' );
		WP_CLI::debug( 'new url: ' . $new['url'], 'zio-helper' );
		WP_CLI::debug( 'old mime: ' . $old['mime'], 'zio-helper' );
		WP_CLI::debug( 'new mime: ' . $new['mime'], 'zio-helper' );

		WP_CLI::debug( 'old _wp_attached_file: ' . $old['_attached_file'], 'zio-helper' );
		WP_CLI::debug( 'new _wp_attached_file: ' . $new['_attached_file'], 'zio-helper' );
		WP_CLI::debug( 'old _wp_attachment_metadata: ' . serialize( $old['attachment_data'] ), 'zio-helper' );
		WP_CLI::debug( 'new _wp_attachment_metadata: ' . serialize( $new['attachment_data'] ), 'zio-helper' );

		// update post
		$update_attachment = $this->wpdb->update(
			$this->wpdb->posts,
				['guid' => $new['url'], 'post_mime_type' => $new['mime']],
				['ID' => $attachment_id],
				['%s', '%s'],
				['%d']
		);
		if ( empty( $update_attachment ) ) {
			// Regenerate subsizes
			$this->regenerate_subsizes( $attachment_id, $old, $new );

			WP_CLI::error( 'Attachment not updated!' );
		}

		// update postmeta (_wp_attached_file)
		$update_meta_attached_file = update_post_meta( $attachment_id, '_wp_attached_file', $new['_attached_file'] );
		if ( false === $update_meta_attached_file ) {
			// Restore attachment
			$this->wpdb->update(
				$this->wpdb->posts,
					['guid' => $old['url'], 'post_mime_type' => $old['mime']],
					['ID' => $attachment_id],
					['%s', '%s'],
					['%d']
			);

			// Regenerate subsizes
			$this->regenerate_subsizes( $attachment_id, $old, $new );

			WP_CLI::error( 'Meta _wp_attached_file not updated!' );
		}

		// update postmeta (_wp_attachment_metadata)
		$update_meta_attachment_data = update_post_meta( $attachment_id, '_wp_attachment_metadata', $new['attachment_data'] );
		if ( false === $update_meta_attachment_data ) {
			// Restore attachment
			$this->wpdb->update(
				$this->wpdb->posts,
					['guid' => $old['url'], 'post_mime_type' => $old['mime']],
					['ID' => $attachment_id],
					['%s', '%s'],
					['%d']
			);

			// Restore meta _wp_attached_file
			update_post_meta( $attachment_id, '_wp_attached_file', $old['_attached_file'] );

			// Regenerate subsizes
			$this->regenerate_subsizes( $attachment_id, $old, $new );

			WP_CLI::error( 'Meta _wp_attachment_metadata not updated!' );
		}

		WP_CLI::success( 'Attachment data updated.' );
	}

	/**
	 * Get attachment ID by path
	 * 
	 * @param string Relative image path
	 * 
	 * @return int|bool
	 */
	private function get_image_id_by_path ( $path ) {
		$relative_path = str_replace( ABSPATH, '', $path );
		$attachment_id = $this->wpdb->get_var(
			$this->wpdb->prepare(
				'SELECT ID FROM ' . $this->wpdb->posts . ' WHERE post_type="attachment" AND guid LIKE %s',
				'%' . $this->wpdb->esc_like( $relative_path )
			)
		);

		if ( ! empty( $attachment_id ) ) {
			return $attachment_id;
		}

		return false;
	}

	/**
	 * Generate attachment metadata for new image "_wp_attachment_metadata" post meta
	 * 
	 * @param array $old Old image data
	 * @param array $new New image data
	 * 
	 * @return array
	 */
	private function generate_new_image_metadata( $old, $new ) {
		$metadata = $old['attachment_data'];

		$metadata['file'] = str_replace( $old['name'], $new['name'], $metadata['file'] );
		$metadata['filesize'] = wp_filesize( $new['path'] );

		foreach ( $metadata['sizes'] as $size => $data ) {
			$file = preg_replace( '|\.' . $old['ext'] . '$|', '.' . $new['ext'], $data['file'] );
			$metadata['sizes'][$size]['file'] = $file;
			$metadata['sizes'][$size]['mime-type'] = $new['mime'];
			$metadata['sizes'][$size]['filesize'] = wp_filesize( $new['dir'] . '/' . $file );
		}

		return $metadata;
	}

	/**
	 * Revert attachment data
	 * 
	 * @param int   $attachment_id Attachment ID
	 * @param array $old           Old image data
	 * @param array $new           New image data
	 * 
	 * @return void
	 */
	private function regenerate_subsizes( $attachment_id, $old, $new ) {
		require_once( ABSPATH . 'wp-admin/includes/image.php' );
		$attachment_data = wp_generate_attachment_metadata( $attachment_id, $old['path'] );
		update_post_meta( $attachment_id, '_wp_attachment_metadata', $attachment_data );
		$this->remove_converted_subsizes( $new['attachment_data']['sizes'], $new['dir'] );
		if ( false === $this->force_retry ) {
			$this->prepare_subsizes_replace( $old['attachment_data']['sizes'], $new['attachment_data']['sizes'], $new['dir'] );
		}
	}

	/**
	 * Remove new image subsizes
	 * 
	 * @param array  $sizes Array with new attachment subsizes from attachment data
	 * @param string $dir   Attachment dir (full path)
	 * 
	 * @return void
	 */
	private function remove_converted_subsizes( $sizes, $dir ) {
		foreach ( $sizes as $size ) {
			$path = $dir . '/' . $size['file'];
			if ( file_exists( $path ) ) {
				unlink( $path );
			}
		}
	}

	/**
	 * Puts old and new images relative paths in temporary file for reverting replacing urls
	 * 
	 * @param array  $old_sizes Array with old attachment subsizes from attachment data
	 * @param array  $new_sizes Array with new attachment subsizes from attachment data
	 * @param string $dir       Attachment dir (full path)
	 * 
	 * @return void
	 */
	private function prepare_subsizes_replace( $old_sizes, $new_sizes, $dir ) {
		foreach ( $old_sizes as $size => $old_size ) {
			$old_path = $dir . '/' . $old_size['file'];
			$old_relative_path = str_replace( ABSPATH, '', $old_path );
			$new_path = $dir . '/' . $new_sizes[$size]['file'];
			$new_relative_path = str_replace( ABSPATH, '', $new_path );
			file_put_contents( $this->tmp_path . '/zio_wp_revert_subsizes_replacements', $new_relative_path . ':' . $old_relative_path . PHP_EOL, FILE_APPEND );
		}
	}
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command( 'zio', 'zioHelper' );
}
