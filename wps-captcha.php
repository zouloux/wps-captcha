<?php
/**
 * Plugin Name:       WPS Captcha
 * Plugin URI:        https://github.com/zouloux/wps-captcha
 * GitHub Plugin URI: https://github.com/zouloux/wps-captcha
 * Description:       Simplest WP Admin Captcha
 * Author:            Alexis Bouhet
 * Author URI:        https://zouloux.com
 * License:           MIT
 * License URI:       https://opensource.org/licenses/MIT
 * Text Domain:       WPS
 * Domain Path:       /cms
 * Version:           1.0.0
 * Copyright:         © 2025 Alexis Bouhet
 */

if ( !defined("ABSPATH") ) exit;
if ( !is_blog_installed() ) return;
if ( defined('WPS_CAPTCHA_DISABLE') && WPS_CAPTCHA_DISABLE ) return;

/**
 */

// Generate a captcha image from a text.
// Will output base64 code
function wps_captcha_generate_base64_image ( $text ) {
	// Create image
	$im = imagecreate(256, 64);
	$black = imagecolorallocate($im, 0, 0, 0);
	$white = imagecolorallocate($im, 255, 255, 255);
	imagefilledrectangle($im, 0, 0, 512, 128, $white);
	// Create text
	$font = __DIR__.'/arial.ttf';
	$fontSize = 30;
	$x = rand(20, 130);
	for ( $i = 0; $i < strlen($text); $i++ ) {
		$angle = rand(-12, 12);
		$y = rand(30, 50);
		imagettftext( $im, $fontSize, $angle, $x, $y, $black, $font, $text[$i] );
		$x += 24;
	}
	// Capture PNG data
	ob_start();
	imagepng($im);
	$imageData = ob_get_contents();
	ob_end_clean();
	imagedestroy($im);
	// Return base64
	return 'data:image/png;base64,'.base64_encode($imageData);
}

// Ensure session is started for captcha
add_action('login_init', function () {
	if ( session_status() === PHP_SESSION_NONE )
		session_start();
});

// Insert the captcha directly into the login form
add_action('login_form', function () {
	$text = substr(md5(microtime()), rand(0, 26), 5);
	$_SESSION['captcha-text'] = $text;
	$base64 = wps_captcha_generate_base64_image( $text );
	echo '<p class="captcha-wrap">';
	echo '<img class="captcha" src="' . $base64 . '" alt="captcha" style="width: 100%;" />';
	echo '<input type="text" name="captcha" placeholder="Enter captcha" required />';
	echo '</p>';
});

// Authenticate the captcha input
add_filter('authenticate', function ( $user ) {
	if ( isset($_POST['wp-submit'] ) ) {
		if ( isset($_POST['captcha']) && $_POST['captcha'] == $_SESSION['captcha-text'] )
			return $user;
		$error = new WP_Error();
		$error->add('captch_error', "Captcha is incorrect.");
		return $error;
	}
	return $user;
}, 30);
