<?php
/**
 * Template Email Văn Bản Thuần thông báo đơn hàng gửi cho Đại lý
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$agent_name      = $order->get_meta( '_billing_agent_name' );
$agent_address   = $order->get_meta( '_billing_agent_address' );
$agent_phone     = $order->get_meta( '_billing_agent_phone' );

$customer_province  = $order->get_meta( '_billing_province_vn' );
$customer_ward      = $order->get_meta( '_billing_ward_vn' );
$customer_latitude  = $order->get_meta( '_billing_latitude' );
$customer_longitude = $order->get_meta( '_billing_longitude' );
$google_maps_url    = 'https://www.google.com/maps/search/?api=1&query=' . $customer_latitude . ',' . $customer_longitude;

echo "= " . esc_html( $email_heading ) . " =\n\n";

echo sprintf( esc_html__( 'Chào bạn, đại lý %s!', 'woo-vietnam-checkout-extension' ), esc_html( $agent_name ) ) . "\n\n";
echo esc_html__( 'Bạn vừa nhận được một đơn hàng mới từ hệ thống cần xử lý. Thông tin chi tiết đơn hàng dưới đây:', 'woo-vietnam-checkout-extension' ) . "\n\n";

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html__( 'ĐỊA CHỈ & ĐỊNH VỊ GPS KHÁCH HÀNG', 'woo-vietnam-checkout-extension' ) . "\n";
echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html__( 'Họ tên khách: ', 'woo-vietnam-checkout-extension' ) . esc_html( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ) . "\n";
echo esc_html__( 'Số điện thoại: ', 'woo-vietnam-checkout-extension' ) . esc_html( $order->get_billing_phone() ) . "\n";
echo esc_html__( 'Địa chỉ chi tiết: ', 'woo-vietnam-checkout-extension' ) . esc_html( $order->get_billing_address_1() ) . "\n";
echo esc_html__( 'Phường / Xã: ', 'woo-vietnam-checkout-extension' ) . esc_html( $customer_ward ) . "\n";
echo esc_html__( 'Tỉnh / Thành phố: ', 'woo-vietnam-checkout-extension' ) . esc_html( $customer_province ) . "\n";

if ( ! empty( $customer_latitude ) && ! empty( $customer_longitude ) ) {
	echo esc_html__( 'Tọa độ thực tế (GPS): ', 'woo-vietnam-checkout-extension' ) . esc_html( $customer_latitude ) . ', ' . esc_html( $customer_longitude ) . "\n";
	echo esc_html__( 'Link xem bản đồ Google Maps: ', 'woo-vietnam-checkout-extension' ) . esc_url( $google_maps_url ) . "\n";
} else {
	echo esc_html__( 'GPS: Không xác định được tọa độ.', 'woo-vietnam-checkout-extension' ) . "\n";
}
echo "\n";

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html__( '🏪 THÔNG TIN ĐẠI LÝ PHỤC VỤ', 'woo-vietnam-checkout-extension' ) . "\n";
echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html__( 'Tên Đại lý: ', 'woo-vietnam-checkout-extension' ) . esc_html( $agent_name ) . "\n";
echo esc_html__( 'Địa chỉ đại lý: ', 'woo-vietnam-checkout-extension' ) . esc_html( $agent_address ) . "\n";
echo esc_html__( 'Số điện thoại: ', 'woo-vietnam-checkout-extension' ) . esc_html( $agent_phone ? $agent_phone : 'Chưa cập nhật' ) . "\n\n";

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html__( 'CHI TIẾT SẢN PHẨM ĐƠN HÀNG', 'woo-vietnam-checkout-extension' ) . "\n";
echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";

do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";

do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

echo "\n" . sprintf( esc_html__( 'Cảm ơn bạn đã đồng hành cùng %s!', 'woo-vietnam-checkout-extension' ), esc_html( $email->get_blogname() ) ) . "\n";
