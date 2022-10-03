<?php
class zioHelper {

	function __construct() {
		global $wpdb;

		$this->wpdb = $wpdb;
	}

	public function replace ( $args ) {
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

	private function remove_converted_subsizes( $sizes, $dir ) {
		foreach ( $sizes as $size ) {
			$path = $dir . '/' . $size['file'];
			if ( file_exists( $path ) ) {
				unlink( $path );
			} 
		}
	}

	private function regenerate_subsizes( $attachment_id, $old, $new ) {
		require_once( ABSPATH . 'wp-admin/includes/image.php' );
		$attachment_data = wp_generate_attachment_metadata( $attachment_id, $old['path'] );
		update_post_meta( $attachment_id, '_wp_attachment_metadata', $attachment_data );
		$this->remove_converted_subsizes( $new['attachment_data']['sizes'], $new['dir'] );
	}
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command( 'zio', 'zioHelper' );
}
