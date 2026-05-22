<?php
/**
 * Lớp quản lý các trường checkout địa chỉ Việt Nam 2 cấp và định vị GPS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Woo_VN_Checkout_Fields {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		// Can thiệp và tùy biến các trường checkout mặc định
		add_filter( 'woocommerce_checkout_fields', array( $this, 'custom_checkout_fields' ), 9999 );
		
		// Đăng ký JS và CSS cho trang checkout
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_checkout_assets' ) );
		
		// Khai báo AJAX để load Phường/Xã dựa trên Tỉnh/Thành
		add_action( 'wp_ajax_woo_vn_get_wards', array( $this, 'ajax_get_wards' ) );
		add_action( 'wp_ajax_nopriv_woo_vn_get_wards', array( $this, 'ajax_get_wards' ) );

		// Ràng buộc kiểm tra (Validation) trước khi đặt hàng
		add_action( 'woocommerce_after_checkout_validation', array( $this, 'validate_checkout_fields' ), 10, 2 );

		// Lưu thông tin tùy biến vào dữ liệu Đơn hàng (Order Meta)
		add_action( 'woocommerce_checkout_create_order', array( $this, 'save_custom_order_meta' ), 10, 2 );

		// Lưu thông tin tùy biến vào thông tin Khách hàng (User Meta) để dùng cho lần sau
		add_action( 'woocommerce_checkout_update_customer', array( $this, 'save_custom_customer_meta' ), 10, 2 );

		// Hiển thị thông tin địa chỉ mới và tọa độ GPS trong Admin Order Details
		add_action( 'woocommerce_admin_order_data_after_billing_address', array( $this, 'display_custom_order_data_in_admin' ) );

		// Đè lại địa chỉ hiển thị trong Chi tiết đơn hàng (Cả Admin và My Account)
		add_filter( 'woocommerce_order_formatted_address_variables', array( $this, 'custom_address_variables' ), 10, 3 );
		add_filter( 'woocommerce_formatted_address_formats', array( $this, 'custom_address_formats' ), 10, 2 );
	}

	/**
	 * Tải dữ liệu các tỉnh thành từ file JSON cục bộ
	 */
	public function get_provinces_data() {
		$json_file = WOO_VN_CE_PATH . 'assets/data/vietnam-locations.json';
		if ( file_exists( $json_file ) ) {
			$content = file_get_contents( $json_file );
			$data = json_decode( $content, true );
			if ( is_array( $data ) ) {
				return $data;
			}
		}
		return array();
	}

	/**
	 * Can thiệp các trường Checkout của WooCommerce
	 */
	public function custom_checkout_fields( $fields ) {
		// 1. Ẩn các trường địa chỉ mặc định không cần thiết hoặc trùng lặp cho Việt Nam
		unset( $fields['billing']['billing_state'] );    // Tỉnh thành mặc định
		unset( $fields['billing']['billing_city'] );     // Huyện lỵ mặc định
		unset( $fields['billing']['billing_address_2'] ); // Địa chỉ dòng 2 mặc định
		unset( $fields['billing']['billing_postcode'] );  // Mã bưu điện mặc định

		// Tương tự cho shipping nếu có bật
		unset( $fields['shipping']['shipping_state'] );
		unset( $fields['shipping']['shipping_city'] );
		unset( $fields['shipping']['shipping_address_2'] );
		unset( $fields['shipping']['shipping_postcode'] );

		// 2. Thêm trường Tỉnh/Thành phố mới
		$provinces_data = $this->get_provinces_data();
		$province_options = array( '' => __( 'Chọn Tỉnh / Thành phố', 'woo-vietnam-checkout-extension' ) );
		foreach ( $provinces_data as $prov ) {
			$province_options[$prov['code']] = $prov['name'];
		}

		$fields['billing']['billing_state_vn'] = array(
			'type'        => 'select',
			'label'       => __( 'Tỉnh / Thành phố', 'woo-vietnam-checkout-extension' ),
			'required'    => true,
			'class'       => array( 'form-row-first' ),
			'options'     => $province_options,
			'priority'    => 40,
			'clear'       => false,
		);

		// 3. Thêm trường Phường / Xã mới
		$fields['billing']['billing_ward_vn'] = array(
			'type'        => 'select',
			'label'       => __( 'Phường / Xã', 'woo-vietnam-checkout-extension' ),
			'required'    => true,
			'class'       => array( 'form-row-last' ),
			'options'     => array( '' => __( 'Vui lòng chọn Tỉnh/Thành phố trước', 'woo-vietnam-checkout-extension' ) ),
			'priority'    => 50,
			'clear'       => true,
		);

		// 4. Định dạng lại trường địa chỉ chi tiết (billing_address_1)
		$fields['billing']['billing_address_1']['label'] = __( 'Địa chỉ chi tiết (Số nhà, tên đường)', 'woo-vietnam-checkout-extension' );
		$fields['billing']['billing_address_1']['placeholder'] = __( 'Ví dụ: 123 Đường Nguyễn Trãi', 'woo-vietnam-checkout-extension' );
		$fields['billing']['billing_address_1']['priority'] = 60;
		$fields['billing']['billing_address_1']['class'] = array( 'form-row-wide' );

		// 5. Thêm các trường ẩn để lưu tọa độ GPS
		$fields['billing']['billing_latitude'] = array(
			'type'     => 'hidden',
			'required' => false,
			'default'  => '',
		);
		$fields['billing']['billing_longitude'] = array(
			'type'     => 'hidden',
			'required' => false,
			'default'  => '',
		);

		return $fields;
	}

	/**
	 * Đăng ký script và style cho trang checkout
	 */
	public function enqueue_checkout_assets() {
		if ( is_checkout() && ! is_order_received_page() ) {
			// CSS tùy biến giao diện checkout
			wp_enqueue_style( 
				'woo-vn-ce-checkout-style', 
				WOO_VN_CE_URL . 'assets/css/checkout-custom.css', 
				array(), 
				WOO_VN_CE_VERSION 
			);

			// JS xử lý định vị GPS và tải xã phường động
			wp_enqueue_script( 
				'woo-vn-ce-checkout-script', 
				WOO_VN_CE_URL . 'assets/js/checkout-location.js', 
				array( 'jquery' ), 
				WOO_VN_CE_VERSION, 
				true 
			);

			// Truyền dữ liệu sang file JS
			wp_localize_script( 'woo-vn-ce-checkout-script', 'wooVnCeParams', array(
				'ajax_url'    => admin_url( 'admin-ajax.php' ),
				'get_wards_nonce' => wp_create_nonce( 'woo-vn-get-wards-nonce' ),
				'placeholder_ward' => __( 'Chọn Phường / Xã', 'woo-vietnam-checkout-extension' ),
				'loading_text' => __( 'Đang lấy vị trí GPS...', 'woo-vietnam-checkout-extension' ),
				'gps_required_err' => __( 'Vui lòng nhấn nút xác thực vị trí GPS trước khi đặt hàng!', 'woo-vietnam-checkout-extension' )
			) );
		}
	}

	/**
	 * AJAX lấy danh sách Phường/Xã dựa trên mã Tỉnh/Thành phố được chọn
	 */
	public function ajax_get_wards() {
		check_ajax_referer( 'woo-vn-get-wards-nonce', 'security' );

		$province_code = isset( $_POST['province_code'] ) ? intval( $_POST['province_code'] ) : 0;
		if ( ! $province_code ) {
			wp_send_json_error( 'Mã tỉnh thành không hợp lệ.' );
		}

		$provinces_data = $this->get_provinces_data();
		$wards = array();

		foreach ( $provinces_data as $prov ) {
			if ( intval( $prov['code'] ) === $province_code ) {
				if ( isset( $prov['wards'] ) && is_array( $prov['wards'] ) ) {
					foreach ( $prov['wards'] as $ward ) {
						$wards[] = array(
							'code' => $ward['code'],
							'name' => $ward['name'],
						);
					}
				}
				break;
			}
		}

		wp_send_json_success( $wards );
	}

	/**
	 * Ràng buộc kiểm tra vị trí GPS và địa chỉ 2 cấp trước khi đặt hàng
	 */
	public function validate_checkout_fields( $data, $errors ) {
		// Kiểm tra GPS
		$latitude  = isset( $_POST['billing_latitude'] ) ? sanitize_text_field( $_POST['billing_latitude'] ) : '';
		$longitude = isset( $_POST['billing_longitude'] ) ? sanitize_text_field( $_POST['billing_longitude'] ) : '';

		if ( empty( $latitude ) || empty( $longitude ) ) {
			$errors->add( 'validation', '<strong>' . __( 'Định vị GPS bắt buộc', 'woo-vietnam-checkout-extension' ) . '</strong>: ' . __( 'Vui lòng nhấn nút "📍 Xác định vị trí của tôi" trên biểu mẫu để cho phép và ghi nhận GPS trước khi đặt hàng.', 'woo-vietnam-checkout-extension' ) );
		}

		// Kiểm tra Tỉnh/Thành
		if ( empty( $_POST['billing_state_vn'] ) ) {
			$errors->add( 'validation', '<strong>' . __( 'Tỉnh / Thành phố', 'woo-vietnam-checkout-extension' ) . '</strong> ' . __( 'là trường bắt buộc.', 'woo-vietnam-checkout-extension' ) );
		}

		// Kiểm tra Phường/Xã
		if ( empty( $_POST['billing_ward_vn'] ) ) {
			$errors->add( 'validation', '<strong>' . __( 'Phường / Xã', 'woo-vietnam-checkout-extension' ) . '</strong> ' . __( 'là trường bắt buộc.', 'woo-vietnam-checkout-extension' ) );
		}
	}

	/**
	 * Lưu trữ dữ liệu tùy biến vào metadata của Đơn hàng (Order Meta)
	 */
	public function save_custom_order_meta( $order, $data ) {
		$provinces_data = $this->get_provinces_data();

		// Tìm tên Tỉnh/Thành
		$province_code = isset( $_POST['billing_state_vn'] ) ? intval( $_POST['billing_state_vn'] ) : 0;
		$province_name = '';
		$ward_name = '';

		if ( $province_code ) {
			foreach ( $provinces_data as $prov ) {
				if ( intval( $prov['code'] ) === $province_code ) {
					$province_name = $prov['name'];
					
					// Tìm tên Phường/Xã
					$ward_code = isset( $_POST['billing_ward_vn'] ) ? intval( $_POST['billing_ward_vn'] ) : 0;
					if ( $ward_code && isset( $prov['wards'] ) ) {
						foreach ( $prov['wards'] as $ward ) {
							if ( intval( $ward['code'] ) === $ward_code ) {
								$ward_name = $ward['name'];
								break;
							}
						}
					}
					break;
				}
			}
		}

		// Lưu vào Order Meta
		if ( ! empty( $province_name ) ) {
			$order->update_meta_data( '_billing_province_vn_code', $province_code );
			$order->update_meta_data( '_billing_province_vn', $province_name );
			// Ghi đè vào billing_state chuẩn của WC để tương thích tốt nhất
			$order->set_billing_state( $province_name );
		}

		if ( ! empty( $ward_name ) ) {
			$order->update_meta_data( '_billing_ward_vn_code', isset( $_POST['billing_ward_vn'] ) ? sanitize_text_field( $_POST['billing_ward_vn'] ) : '' );
			$order->update_meta_data( '_billing_ward_vn', $ward_name );
			// Ghi đè vào billing_city chuẩn của WC để hiển thị mặc định đẹp mắt
			$order->set_billing_city( $ward_name );
		}

		if ( isset( $_POST['billing_latitude'] ) ) {
			$order->update_meta_data( '_billing_latitude', sanitize_text_field( $_POST['billing_latitude'] ) );
		}

		if ( isset( $_POST['billing_longitude'] ) ) {
			$order->update_meta_data( '_billing_longitude', sanitize_text_field( $_POST['billing_longitude'] ) );
		}
	}

	/**
	 * Lưu dữ liệu vào User Meta của Khách hàng để tự động điền trong lần mua sau
	 */
	public function save_custom_customer_meta( $customer, $data ) {
		if ( isset( $_POST['billing_state_vn'] ) ) {
			$customer->update_meta_data( 'billing_state_vn', sanitize_text_field( $_POST['billing_state_vn'] ) );
		}
		if ( isset( $_POST['billing_ward_vn'] ) ) {
			$customer->update_meta_data( 'billing_ward_vn', sanitize_text_field( $_POST['billing_ward_vn'] ) );
		}
	}

	/**
	 * Hiển thị dữ liệu địa chỉ Việt Nam 2 cấp mới và vị trí GPS trong màn hình Admin Order Details
	 */
	public function display_custom_order_data_in_admin( $order ) {
		$province  = $order->get_meta( '_billing_province_vn' );
		$ward      = $order->get_meta( '_billing_ward_vn' );
		$latitude  = $order->get_meta( '_billing_latitude' );
		$longitude = $order->get_meta( '_billing_longitude' );

		echo '<h3>' . __( 'Địa chỉ VN & Định vị GPS', 'woo-vietnam-checkout-extension' ) . '</h3>';
		echo '<div class="address">';
		if ( ! empty( $province ) ) {
			echo '<p><strong>Tỉnh / Thành phố:</strong> ' . esc_html( $province ) . '</p>';
		}
		if ( ! empty( $ward ) ) {
			echo '<p><strong>Phường / Xã:</strong> ' . esc_html( $ward ) . '</p>';
		}
		if ( ! empty( $latitude ) && ! empty( $longitude ) ) {
			echo '<p><strong>Tọa độ GPS:</strong> ' . esc_html( $latitude ) . ', ' . esc_html( $longitude ) . '</p>';
			
			// Nút xem bản đồ cao cấp
			$google_maps_url = 'https://www.google.com/maps/search/?api=1&query=' . $latitude . ',' . $longitude;
			echo '<p><a href="' . esc_url( $google_maps_url ) . '" target="_blank" class="button button-primary" style="margin-top: 5px; background-color: #2ecc71; border-color: #27ae60;"><span class="dashicons dashicons-location" style="margin-top: 4px;"></span> Xem vị trí trên Google Maps</a></p>';
		} else {
			echo '<p style="color: #e74c3c;"><strong>GPS:</strong> Chưa có tọa độ (Đơn hàng cũ hoặc lỗi định vị).</p>';
		}
		echo '</div>';
	}

	/**
	 * Đăng ký biến địa chỉ tùy biến để thay đổi cách hiển thị địa chỉ của WooCommerce
	 */
	public function custom_address_variables( $variables, $order, $address_type ) {
		// Lấy giá trị tùy biến từ order meta
		$province = $order->get_meta( '_billing_province_vn' );
		$ward     = $order->get_meta( '_billing_ward_vn' );

		$variables['{state_vn}'] = ! empty( $province ) ? $province : '';
		$variables['{ward_vn}']  = ! empty( $ward ) ? $ward : '';

		return $variables;
	}

	/**
	 * Đè lại định dạng hiển thị địa chỉ cho Việt Nam trong WooCommerce
	 */
	public function custom_address_formats( $formats, $address ) {
		// Thay thế định dạng địa chỉ mặc định của Việt Nam bằng định dạng 2 cấp mới rõ ràng hơn
		$formats['VN'] = "{name}\n{company}\n{address_1}\n{ward_vn}, {state_vn}\n{country}";
		return $formats;
	}
}
