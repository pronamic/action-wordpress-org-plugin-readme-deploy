<?php

/**
 * Setup.
 */
$svn_username = getenv( 'SVN_USERNAME' );
$svn_password = getenv( 'SVN_PASSWORD' );
$wp_slug      = getenv( 'WP_SLUG' );

$svn_url = "https://plugins.svn.wordpress.org/$wp_slug";

$readme_file = getcwd() . '/readme.txt';
$assets_dir  = getcwd() . '/.wordpress-org';

$svn_checkout_dir = tempnam( sys_get_temp_dir(), '' );

unlink( $svn_checkout_dir );

mkdir( $svn_checkout_dir );

/**
 * Parse stable tag.
 */
$readme_content = file_get_contents( $readme_file );

$pattern = "/Stable tag: (.*)/";

$stable_tag = '';

if ( 1 === preg_match( $pattern, $readme_content, $matches ) ) {
	$stable_tag = $matches[1];
}

/**
 * Start.
 */
echo '🚀 Deploy readme.txt to WordPress.org', PHP_EOL;

echo '- Subversion URL: ', $svn_url, PHP_EOL;
echo '- Subversion username: ', $svn_username, PHP_EOL;
echo '- Subversion password: ', $svn_password, PHP_EOL;
echo '- Subversion checkout directory: ', $svn_checkout_dir, PHP_EOL;
echo '- Path readme.txt: ', $readme_file, PHP_EOL;
echo '- Path assets: ', $assets_dir, PHP_EOL;
echo '- Stable tag: ', $stable_tag, PHP_EOL;

/**
 * Subversion.
 * 
 * @link https://stackoverflow.com/a/122291
 */
passthru( "svn checkout $svn_url $svn_checkout_dir --depth empty" );

chdir( $svn_checkout_dir );

passthru( 'svn update trunk --depth=empty' );
passthru( 'svn update trunk/readme.txt' );
passthru( 'svn update assets' );

if ( is_dir( $assets_dir ) ) {
	passthru( "rsync --recursive --checksum $assets_dir/ assets/ --delete --delete-excluded" );
}

copy( $readme_file, 'trunk/readme.txt' );

if ( '' !== $stable_tag ) {
	passthru( "svn update tags --depth=empty" );
	passthru( "svn update tags/$stable_tag --depth=empty" );
	passthru( "svn update tags/$stable_tag/readme.txt --depth=empty" );

	copy( $readme_file, "tags/$stable_tag/readme.txt" );
}

$output = shell_exec( 'svn status --xml' );

$xml = simplexml_load_string( $output );

if ( false === $xml ) {
	echo 'A problem occurred while reading the `svn status --xml`.';

	exit( 1 );
}

foreach ( $xml->target->entry as $entry ) {
	$path = (string) $entry['path'];

	$wc_status = (string) $entry->{'wc-status'}['item'];

	switch ( $wc_status ) {
		case 'missing':
			passthru( "svn rm $path" );

			break;
		case 'modified';
			// Modified entry will be commited.

			break;
		case 'unversioned':
			passthru( "svn add $path" );

			break;
		default:
			echo "Unsupport working copy status: $wc_status - $path.";

			exit( 1 );
	}
}

/**
 * Fix screenshots getting force downloaded when clicking them.
 * 
 * @link https://developer.wordpress.org/plugins/wordpress-org/plugin-assets/
 */
echo '::group::Fix downloading assets images issue 🏞️', PHP_EOL;

$mime_types = [
	'png' => 'image/png',
	'jpg' => 'image/jpeg',
	'gif' => 'image/gif',
	'svg' => 'image/svg+xml',
];

foreach ( $mime_types as $ext => $type ) {
	foreach ( glob( 'assets/*.' . $ext ) as $file ) {
		passthru( "svn propset svn:mime-type '$type' '$file'" );		
	}
}

echo '::endgroup::', PHP_EOL;

passthru( "svn commit --message 'Update readme.txt' --non-interactive --username '$svn_username' --password '$svn_password'" );

/**
 * Cleanup.
 */
passthru( "rm -f -R $svn_checkout_dir" );
