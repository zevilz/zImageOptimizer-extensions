<?php
// don't save filename (override vars *_SAVE_FILENAME)

class zioHelper {

	function __construct() {
		global $wpdb;

		$this->wpdb = $wpdb;
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

	public function replace ( $args ) {
		if ( empty( $args ) ) {
			WP_CLI::error( 'You must set old and new attachments paths!' );
		}

		if ( empty( $args[1] ) ) {
			WP_CLI::error( 'You must set new attachment path!' );
		}

		$old_path = $args[0];
		$new_path = $args[1];

		if ( ! file_exists( $new_path ) ) {
			WP_CLI::error( 'New attachment file does not exists!' );
		}

		if ( ! file_exists( $old_path ) ) {
			WP_CLI::error( 'Old attachment file does not exists! It needed for restore.' );
		}

		$attachment_id = $this->get_image_id_by_path( $old_path );

		if ( false === $attachment_id ) {
			WP_CLI::warning( 'Attachment ID not found!' );
			exit(2);
		}

		$old_name = basename( $old_path );
		$new_name = basename( $new_path );
		$old_url  = wp_get_attachment_url( $attachment_id );
		$new_url  = str_replace( $old_name, $new_name, $old_url );
		$new_mime = wp_get_image_mime( $new_path );
		$old_mime = $this->wpdb->get_var(
			$this->wpdb->prepare(
				'SELECT post_mime_type FROM ' . $this->wpdb->posts . ' WHERE ID=%d',
				$attachment_id
			)
		);

		WP_CLI::debug( 'attachment ID: ' . $attachment_id, 'zio-helper' );
		WP_CLI::debug( 'old path: ' . $old_path, 'zio-helper' );
		WP_CLI::debug( 'new path: ' . $new_path, 'zio-helper' );
		WP_CLI::debug( 'old name: ' . $old_name, 'zio-helper' );
		WP_CLI::debug( 'new name: ' . $new_name, 'zio-helper' );
		WP_CLI::debug( 'old url: ' . $old_url, 'zio-helper' );
		WP_CLI::debug( 'new url: ' . $new_url, 'zio-helper' );
		WP_CLI::debug( 'old mime: ' . $old_mime, 'zio-helper' );
		WP_CLI::debug( 'new mime: ' . $new_mime, 'zio-helper' );

		// update post
		/*$update_attachment = $this->wpdb->update(
			$this->wpdb->posts,
				['guid' => $new_url, 'post_mime_type' => $new_mime],
				['ID' => $attachment_id],
				['%s', '%s'],
				['%d']
		);
		if ( empty( $update_attachment ) ) {
			WP_CLI::error( 'Attachment not updated!' );
		}*/

		// update postmeta (_wp_attached_file)
		$old_meta_attached_file = get_post_meta( $attachment_id, '_wp_attached_file', true );
		$new_meta_attached_file = str_replace( $old_name, $new_name, $old_meta_attached_file );
		WP_CLI::debug( 'old _wp_attached_file: ' . $old_meta_attached_file, 'zio-helper' );
		WP_CLI::debug( 'new _wp_attached_file: ' . $new_meta_attached_file, 'zio-helper' );
		/*$update_meta_attached_file = update_post_meta( $attachment_id, '_wp_attached_file', $new_meta_attached_file );
		if ( false === $update_meta_attached_file ) {
			// Restore attachment
			$this->wpdb->update(
				$this->wpdb->posts,
					['guid' => $old_url, 'post_mime_type' => $old_mime],
					['ID' => $attachment_id],
					['%s', '%s'],
					['%d']
			);

			WP_CLI::error( 'Meta _wp_attached_file not updated!' );
		}*/

		// update postmeta (_wp_attachment_metadata)
		require_once( ABSPATH . 'wp-admin/includes/image.php' );
		$attachment_data = wp_generate_attachment_metadata( $attachment_id, $new_path );
		WP_CLI::debug( 'new _wp_attachment_metadata: ' . serialize( $attachment_data ), 'zio-helper' );
		/*$update_meta_attachment_data = update_post_meta( $attachment_id, '_wp_attachment_metadata', $attachment_data );
		if ( false === $update_meta_attachment_data ) {
			// Restore attachment
			$this->wpdb->update(
				$this->wpdb->posts,
					['guid' => $old_url, 'post_mime_type' => $old_mime],
					['ID' => $attachment_id],
					['%s', '%s'],
					['%d']
			);

			// Restore meta _wp_attached_file
			update_post_meta( $attachment_id, '_wp_attached_file', $old_meta_attached_file );

			WP_CLI::error( 'Meta _wp_attached_file not updated!' );
		}*/

		WP_CLI::success( 'Attachment data updated.' );
	}

}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command( 'zio', 'zioHelper' );
}
