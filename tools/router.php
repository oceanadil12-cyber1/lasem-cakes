<?php
/**
 * Router for PHP's built-in server, so WordPress pretty URLs work locally.
 *
 *   C:\xampp\php\php.exe -S localhost:8080 -t site tools/router.php
 */

$path = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );
$file = $_SERVER['DOCUMENT_ROOT'] . $path;

// Serve real files (CSS, JS, images, wp-admin/*.php) as they are.
if ( '/' !== $path && file_exists( $file ) ) {
	if ( is_dir( $file ) && file_exists( rtrim( $file, '/' ) . '/index.php' ) ) {
		$_SERVER['SCRIPT_NAME'] = rtrim( $path, '/' ) . '/index.php';
		chdir( $file );
		require rtrim( $file, '/' ) . '/index.php';
		return true;
	}
	if ( ! is_dir( $file ) ) {
		if ( str_ends_with( $file, '.php' ) ) {
			$_SERVER['SCRIPT_NAME'] = $path;
			chdir( dirname( $file ) );
			require $file;
			return true;
		}
		return false;
	}
}

// Everything else goes to WordPress.
$_SERVER['SCRIPT_NAME'] = '/index.php';
chdir( $_SERVER['DOCUMENT_ROOT'] );
require $_SERVER['DOCUMENT_ROOT'] . '/index.php';
