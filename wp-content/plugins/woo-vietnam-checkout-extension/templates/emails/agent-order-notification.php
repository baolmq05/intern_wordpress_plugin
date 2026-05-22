<?php
/**
 * Template Email HTML thông báo đơn hàng gửi cho Đại lý
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Lấy thông tin đại lý và khách hàng từ order
$agent_name      = $order->get_meta( '_billing_agent_name' );
$agent_address   = $order->get_meta( '_billing_agent_address' );
$agent_phone     = $order->get_meta( '_billing_agent_phone' );

$customer_province  = $order->get_meta( '_billing_province_vn' );
$customer_ward      = $order->get_meta( '_billing_ward_vn' );
$customer_latitude  = $order->get_meta( '_billing_latitude' );
$customer_longitude = $order->get_meta( '_billing_longitude' );
$google_maps_url    = 'https://www.google.com/maps/search/?api=1&query=' . $customer_latitude . ',' . $customer_longitude;

/*
 * @hooked woocommerce_email_header() xuất header chuẩn của WooCommerce
 */
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<p><?php printf( __( 'Chào bạn, đại lý <strong>%s</strong>!', 'woo-vietnam-checkout-extension' ), esc_html( $agent_name ) ); ?></p>
<p><?php _e( 'Bạn vừa nhận được một đơn hàng mới từ hệ thống cần xử lý. Thông tin chi tiết đơn hàng dưới đây:', 'woo-vietnam-checkout-extension' ); ?></p>

<!-- HỘP THÔNG TIN VỊ TRÍ GIAO HÀNG GPS (GIAO DIỆN PREMIUM) -->
<div style="background-color: #f9f9f9; border-left: 4px solid #2ecc71; padding: 15px; margin-bottom: 20px; border-radius: 4px;">
	<h3 style="margin-top: 0; color: #27ae60; font-size: 16px; font-weight: bold; border-bottom: 1px solid #eee; padding-bottom: 6px;">
		📍 <?php _e( 'ĐỊA CHỈ & ĐỊNH VỊ GPS KHÁCH HÀNG', 'woo-vietnam-checkout-extension' ); ?>
	</h3>
	<p style="margin: 5px 0;"><strong>Họ tên khách:</strong> <?php echo esc_html( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ); ?></p>
	<p style="margin: 5px 0;"><strong>Số điện thoại:</strong> <?php echo esc_html( $order->get_billing_phone() ); ?></p>
	<p style="margin: 5px 0;"><strong>Địa chỉ chi tiết:</strong> <?php echo esc_html( $order->get_billing_address_1() ); ?></p>
	<p style="margin: 5px 0;"><strong>Phường / Xã:</strong> <?php echo esc_html( $customer_ward ); ?></p>
	<p style="margin: 5px 0;"><strong>Tỉnh / Thành phố:</strong> <?php echo esc_html( $customer_province ); ?></p>
	
	<?php if ( ! empty( $customer_latitude ) && ! empty( $customer_longitude ) ) : ?>
		<div style="margin-top: 12px; padding-top: 8px; border-top: 1px dashed #ddd;">
			<p style="margin: 3px 0; font-size: 13px; color: #555;"><strong>Tọa độ thực tế (GPS):</strong> <?php echo esc_html( $customer_latitude ); ?>, <?php echo esc_html( $customer_longitude ); ?></p>
			<p style="margin: 8px 0 0 0;">
				<a href="<?php echo esc_url( $google_maps_url ); ?>" target="_blank" style="background-color: #2ecc71; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-weight: bold; display: inline-block; font-size: 13px; box-shadow: 0 2px 4px rgba(46,204,113,0.2);">
					🗺️ Mở Vị Trí Giao Hàng Trên Google Maps
				</a>
			</p>
		</div>
	<?php else : ?>
		<p style="margin: 5px 0; color: #e74c3c;"><strong>GPS:</strong> Không xác định được tọa độ GPS.</p>
	<?php endif; ?>
</div>

<!-- THÔNG TIN ĐẠI LÝ PHỤC VỤ (XÁC NHẬN) -->
<div style="background-color: #f9f9f9; border-left: 4px solid #3498db; padding: 15px; margin-bottom: 25px; border-radius: 4px;">
	<h3 style="margin-top: 0; color: #2980b9; font-size: 15px; font-weight: bold;">
		🏪 ĐẠI LÝ PHỤC VỤ ĐƯỢC CHỌN
	</h3>
	<p style="margin: 4px 0;"><strong>Tên Đại lý:</strong> <?php echo esc_html( $agent_name ); ?></p>
	<p style="margin: 4px 0;"><strong>Địa chỉ đại lý:</strong> <?php echo esc_html( $agent_address ); ?></p>
	<p style="margin: 4px 0;"><strong>Số điện thoại:</strong> <?php echo esc_html( $agent_phone ? $agent_phone : 'Chưa cập nhật' ); ?></p>
</div>

<?php
/*
 * @hooked woocommerce_email_order_details() xuất bảng danh sách sản phẩm chuẩn
 */
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

/*
 * @hooked woocommerce_email_order_meta() xuất thông tin meta chuẩn
 */
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

/*
 * @hooked woocommerce_email_customer_details() xuất thông tin khách hàng chuẩn
 */
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

/*
 * @hooked woocommerce_email_footer() xuất footer chuẩn của WooCommerce
 */
do_action( 'woocommerce_email_footer', $email );
