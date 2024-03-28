<?php

/**
 * Setup.
 */
$svn_username = getenv( 'SVN_USERNAME' );
$svn_password = getenv( 'SVN_PASSWORD' );
$wp_slug      = getenv( 'WP_SLUG' );

$svn_url = "https://plugins.svn.wordpress.org/$wp_slug";

$readme_file = getcwd() . '/readme.txt';

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

copy( $readme_file, 'trunk/readme.txt' );

if ( '' !== $stable_tag ) {
	passthru( "svn update tags --depth=empty" );
	passthru( "svn update tags/$stable_tag --depth=empty" );
	passthru( "svn update tags/$stable_tag/readme.txt --depth=empty" );

	copy( $readme_file, "tags/$stable_tag/readme.txt" );
}

passthru( "svn commit --message 'Update readme.txt' --non-interactive --username '$svn_username' --password '$svn_password'" );

/**
 * Cleanup.
 */
passthru( "rm -f -R $svn_checkout_dir" );
