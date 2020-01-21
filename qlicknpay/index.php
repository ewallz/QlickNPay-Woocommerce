<?php
/**
 * Plugin Name: Qlicknpay
 * Plugin URI: https://www.Qlicknpay.com
 * Description: Enable online payments using FPX online banking. Currently Qlicknpay service is only available to businesses that reside in Malaysia.
 * Version: 3.0.4
 * Author: Qlicknpay
 * Author URI: https://www.linkedin.com/in/m-shahrul-izwan-321361186/
 * WC requires at least: 2.6.0
 * WC tested up to: 3.3.0
 **/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

# Include Qlicknpay Class and register Payment Gateway with WooCommerce
add_action( 'plugins_loaded', 'Qlicknpay_init', 0 );

function Qlicknpay_init() {
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
		return;
	}

	include_once( 'src/Qlicknpay.php' );

	add_filter( 'woocommerce_payment_gateways', 'add_Qlicknpay_to_woocommerce' );
	function add_Qlicknpay_to_woocommerce( $methods ) {
		$methods[] = 'Qlicknpay';

		return $methods;
	}
}

# Add custom action links
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'Qlicknpay_links' );

function Qlicknpay_links( $links ) {
	$plugin_links = array(
		'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=Qlicknpay' ) . '">' . __( 'Settings', 'Qlicknpay' ) . '</a>',
	);

	# Merge our new link with the default ones
	return array_merge( $plugin_links, $links );
}

add_action( 'init', 'Qlicknpay_check_response', 15 );

function Qlicknpay_check_response() {
	# If the parent WC_Payment_Gateway class doesn't exist it means WooCommerce is not installed on the site, so do nothing
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
		return;
	}

	include_once( 'src/Qlicknpay.php' );

	$Qlicknpay = new Qlicknpay();
	$Qlicknpay->check_Qlicknpay_response();
}

function Qlicknpay_hash_error_msg( $content ) {
	return '<div class="woocommerce-error">Invalid data entered. Please contact your merchant for more info.</div>' . $content;
}

function Qlicknpay_payment_declined_msg( $content ) {
	return '<div class="woocommerce-error">Fail transaction. Please check with your bank system.</div>' . $content;
}

function Qlicknpay_success_msg( $content ) {
	return '<div class="woocommerce-info">The payment was successful. Thank you.</div>' . $content;
}
