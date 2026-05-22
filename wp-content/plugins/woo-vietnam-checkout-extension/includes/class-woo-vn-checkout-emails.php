<?php
/**
 * Lớp đăng ký và quản lý các kênh Email tùy biến của Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Woo_VN_Checkout_Emails {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		// Hook đăng ký class email mới vào WooCommerce
		add_filter( 'woocommerce_email_classes', array( $this, 'register_agent_email_class' ) );
	}

	/**
	 * Đăng ký class email thông báo cho Đại lý vào WooCommerce
	 */
	public function register_agent_email_class( $email_classes ) {
		// Nhúng class email
		require_once WOO_VN_CE_PATH . 'includes/emails/class-wc-email-agent-order-notification.php';
		
		// Đăng ký vào mảng của WooCommerce
		$email_classes['WC_Email_Agent_Order_Notification'] = new WC_Email_Agent_Order_Notification();
		
		return $email_classes;
	}
}
