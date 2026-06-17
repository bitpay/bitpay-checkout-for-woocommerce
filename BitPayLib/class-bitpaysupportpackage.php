<?php

declare(strict_types=1);

namespace BitPayLib;

use WP_HTTP_Response;
use WP_REST_Response;

class BitPaySupportPackage {

	private BitPayWordpressHelper $bitpay_wordpress;
	private BitPayLogger $bitpay_logger;

	public function __construct(
		BitPayWordpressHelper $bitpay_wordpress,
		BitPayLogger $bitpay_logger
	) {
		$this->bitpay_wordpress = $bitpay_wordpress;
		$this->bitpay_logger    = $bitpay_logger;
	}

	public function get_zip(): WP_REST_Response {
		$zipfile_string = $this->create_site_info_zip();

		return $this->get_zip_rest_response( $zipfile_string );
	}

	private function create_site_info_zip(): string {
		$json_data    = $this->get_site_data_as_json();
		$tmp_file     = tmpfile();
		$tmp_location = stream_get_meta_data( $tmp_file )['uri'];

		$zip = new \ZipArchive();

		if ( true !== $zip->open( $tmp_location, \ZipArchive::CREATE ) ) {
			throw new \RuntimeException( 'Could not create zip file' );
		}

		$zip->addFromString( 'site-info.json', $json_data );
		$log_directory = $this->bitpay_logger->get_log_directory();

		if ( is_readable( $log_directory ) ) {
			$zip->addGlob(
				$log_directory . '*.log',
				0,
				array(
					'remove_all_path' => true,
				)
			);
		}

		$zip->close();
		$file_contents = file_get_contents( $tmp_location );

		// This removes the file.
		fclose( $tmp_file );

		return $file_contents;
	}

	private function get_site_data_as_json(): bool|string {
		$active_plugins     = get_plugins();
		$active_plugin_data = array();

		foreach ( $active_plugins as $plugin_path => $plugin_data ) {
			if ( is_plugin_active( $plugin_path ) ) {
				$active_plugin_data[] = array(
					'name'    => $plugin_data['Name'],
					'version' => $plugin_data['Version'],
				);
			}
		}

		$wpdb      = $this->bitpay_wordpress->get_wpdb();
		$extension = null;

		// Populate the database debug fields.
		if ( is_object( $wpdb->dbh ) ) {
			// mysqli or PDO.
			$extension = get_class( $wpdb->dbh );
		}

		$json_data = array(
			'bitpay_plugin_version' => BitPayPluginSetup::VERSION,
			'plugins'               => $active_plugin_data,
			'database'              => array(
				array(
					'dbms'         => $extension,
					'dbms_version' => $wpdb->get_var( 'SELECT VERSION()' ), // phpcs:ignore
					'char_set'     => $wpdb->charset,
					'collation'    => $wpdb->collate,
					'tables'       => array(
						array(
							'table'  => '_bitpay_checkout_transactions',
							'exists' => $wpdb->get_var( "SHOW TABLES LIKE '_bitpay_checkout_transactions'" ) ? 'yes' : 'no',
						),
					),
				),
			),
			'wordpress'             => array(
				array(
					'url'     => home_url(),
					'version' => get_bloginfo( 'version' ),
				),
			),
			'server'                => array(
				array(
					'software'      => $_SERVER['SERVER_SOFTWARE'], // phpcs:ignore
					'document_root' => $_SERVER['DOCUMENT_ROOT'], // phpcs:ignore
				),
			),
			'php'                   => array(
				'version'              => phpversion(),
				'memory_limit'         => ini_get( 'memory_limit' ),
				'max_execution_time'   => ini_get( 'max_execution_time' ),
				'max_file_upload_size' => ini_get( 'upload_max_filesize' ),
				'max_post_size'        => ini_get( 'post_max_size' ),
				'max_input_variables'  => ini_get( 'max_input_vars' ),
				'curl_enabled'         => function_exists( 'curl_version' ) ? 'yes' : 'no',
				'curl_version'         => function_exists( 'curl_version' ) ? curl_version()['version'] : '',
				'openssl_version'      => defined( 'OPENSSL_VERSION_TEXT' ) ? OPENSSL_VERSION_TEXT : '',
				'mcrypt_enabled'       => function_exists( 'mcrypt_encrypt' ) ? 'yes' : 'no',
				'mbstring_enabled'     => function_exists( 'mb_detect_encoding' ) ? 'yes' : 'no',
				'extensions'           => get_loaded_extensions(),
			),
		);

		return json_encode( $json_data, JSON_PRETTY_PRINT );
	}

	/**
	 * Serves a zip via the REST endpoint.
	 *
	 * By default, every REST response is passed through json_encode(), as the
	 * typical REST response contains JSON data.
	 *
	 * This method hooks into the REST server to return a binary zip.
	 *
	 * @param string $data Data of the ZIP to serve.
	 *
	 * @return WP_REST_Response The REST response object to serve the zip.
	 */
	private function get_zip_rest_response( string $data ): WP_REST_Response {
		$response = new WP_REST_Response();

		$response->set_data( $data );
		$response->set_headers(
			array(
				'Content-Type'   => 'application/zip',
				'Content-Length' => strlen( $data ),
			)
		);

		// This filter will return our binary zip.
		add_filter( 'rest_pre_serve_request', array( $this, 'serve_zip_action_handler' ), 0, 2 );

		return $response;
	}

	/**
	 * Action handler that is used by `get_zip_rest_response()` to serve a binary image
	 * instead of a JSON string.
	 *
	 * @param bool             $served Whether the request has already been served. Default false.
	 * @param WP_HTTP_Response $result Result to send to the client. Usually a WP_REST_Response.
	 *
	 * @return bool Returns true, if the image was served; this will skip the
	 *              default REST response logic.
	 *
	 * @see https://developer.wordpress.org/reference/hooks/rest_pre_serve_request/
	 * */
	public function serve_zip_action_handler( bool $served, WP_HTTP_Response $result ): bool {
		$is_zip   = false;
		$zip_data = null;

		// Check the "Content-Type" header to confirm that we really want to return
		// binary zip data.
		foreach ( $result->get_headers() as $header => $value ) {
			if ( 'content-type' === strtolower( $header ) ) {
				$is_zip   = 0 === strpos( $value, 'application/zip' );
				$zip_data = $result->get_data();
				break;
			}
		}

		// Output the binary data and tell the REST server to not send any other
		// details (via "return true").
		if ( $is_zip && is_string( $zip_data ) ) {
			// phpcs:ignore
            echo $zip_data;

			return true;
		}

		return $served;
	}
}
