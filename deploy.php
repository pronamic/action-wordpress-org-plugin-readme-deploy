<?php

/**
 * Functions.
 */
function escape_sequence( $code ) {
	return "\e[" . $code . 'm';
}

function run_command( $command ) {
	echo escape_sequence( '36' ), $command, escape_sequence( '0' ), PHP_EOL;

	$output = shell_exec( $command );

	return $output;
}

function start_group( $name ) {
	echo '::group::', $name, PHP_EOL;
}

function end_group() {
	echo '::endgroup::', PHP_EOL;
}

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

echo '• ', escape_sequence( '1' ), 'Subversion URL:', escape_sequence( '0' ), ' ', $svn_url, PHP_EOL;
echo '• ', escape_sequence( '1' ), 'Subversion username:', escape_sequence( '0' ), ' ', $svn_username, PHP_EOL;
echo '• ', escape_sequence( '1' ), 'Subversion password:', escape_sequence( '0' ), ' ', $svn_password, PHP_EOL;
echo '• ', escape_sequence( '1' ), 'Subversion checkout directory:', escape_sequence( '0' ), ' ', $svn_checkout_dir, PHP_EOL;
echo '• ', escape_sequence( '1' ), 'Path readme.txt:', escape_sequence( '0' ), ' ', $readme_file, PHP_EOL;
echo '• ', escape_sequence( '1' ), 'Path assets:', escape_sequence( '0' ), ' ', $assets_dir, PHP_EOL;
echo '• ', escape_sequence( '1' ), 'Stable tag:', escape_sequence( '0' ), ' ', $stable_tag, PHP_EOL;
echo PHP_EOL;

/**
 * Subversion.
 * 
 * @link https://stackoverflow.com/a/122291
 */
start_group( '⬇ Subversion checkout WordPress.org' );

run_command( "svn checkout $svn_url $svn_checkout_dir --depth empty" );

chdir( $svn_checkout_dir );

run_command( 'svn update trunk --depth=empty' );
run_command( 'svn update trunk/readme.txt' );
run_command( 'svn update assets' );

if ( '' !== $stable_tag ) {
	run_command( "svn update tags --depth=empty" );
	run_command( "svn update tags/$stable_tag --depth=empty" );
	run_command( "svn update tags/$stable_tag/readme.txt" );
}

end_group();

/**
 * Synchronize.
 */
start_group( '🔄 Synchronize' );

run_command( "cp $readme_file trunk/readme.txt" );

if ( '' !== $stable_tag ) {
	run_command( "cp $readme_file tags/$stable_tag/readme.txt" );
}

if ( is_dir( $assets_dir ) ) {
	run_command( "rsync --recursive --checksum $assets_dir/ assets/ --delete --delete-excluded" );
}

end_group();

/**
 * Subversion modifications.
 */
start_group( '💾 Subversion modifications' );

$output = run_command( 'svn status --xml' );

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
			run_command( "svn rm $path" );

			break;
		case 'modified';
			// Modified entry will be commited.

			break;
		case 'unversioned':
			run_command( "svn add $path" );

			break;
		default:
			echo "Unsupport working copy status: $wc_status - $path.";

			exit( 1 );
	}
}

end_group();

/**
 * Fix screenshots getting force downloaded when clicking them.
 * 
 * @link https://developer.wordpress.org/plugins/wordpress-org/plugin-assets/
 */
start_group( '🐛 Fix downloading assets images issue' );

$mime_types = [
	'png' => 'image/png',
	'jpg' => 'image/jpeg',
	'gif' => 'image/gif',
	'svg' => 'image/svg+xml',
];

foreach ( $mime_types as $ext => $type ) {
	foreach ( glob( 'assets/*.' . $ext ) as $file ) {
		run_command( "svn propset svn:mime-type '$type' '$file'" );
	}
}

end_group();

/**
 * Commit.
 */
start_group( '⬆ Subversion commit WordPress.org' );

run_command( "svn commit --message 'Update readme.txt' --non-interactive --username '$svn_username' --password '$svn_password'" );

end_group();

/**
 * Clean up.
 */
start_group( '🗑️ Clean up' );

run_command( "rm -f -R $svn_checkout_dir" );

end_group();
