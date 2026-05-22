<?php
/**
 * Plugin Name: WooCommerce Vietnam Checkout Extension
 * Plugin URI: https://github.com/baolmq05/intern_wordpress_plugin
 * Description: Plugin mở rộng WooCommerce hỗ trợ chọn Địa chỉ Việt Nam 2 cấp (Tỉnh/Thành -> Phường/Xã) theo API v2 provinces.open-api.vn, bắt buộc xác thực GPS trước khi đặt hàng, chọn Đại lý phục vụ và gửi Email thông báo tự động cho Đại lý.
 * Version: 1.0.0
 * Author: Antigravity
 * Author URI: https://github.com/baolmq05
 * License: GPL2
 * Text Domain: woo-vietnam-checkout-extension
 * Domain Path: /languages
 */

// Chặn truy cập trực tiếp
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Khai báo các hằng số hữu ích
define( 'WOO_VN_CE_VERSION', '1.0.0' );
define( 'WOO_VN_CE_PATH', plugin_dir_path( __FILE__ ) );
define( 'WOO_VN_CE_URL', plugin_dir_url( __FILE__ ) );
define( 'WOO_VN_CE_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Lớp chính khởi chạy Plugin
 */
class Woo_Vietnam_Checkout_Extension {

	/**
	 * Instance duy nhất
	 */
	private static $instance = null;

	/**
	 * Khởi tạo Instance
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Hàm khởi dựng
	 */
	private function __construct() {
		// Kiểm tra xem WooCommerce đã được kích hoạt chưa
		add_action( 'plugins_loaded', array( $this, 'init_plugin' ) );
		
		// Đăng ký hook kích hoạt và hủy kích hoạt plugin
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
	}

	/**
	 * Khởi chạy các tính năng sau khi các plugin khác đã tải
	 */
	public function init_plugin() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action( 'admin_notices', array( $this, 'woocommerce_missing_notice' ) );
			return;
		}

		// Nạp các file thành phần
		$this->includes();
		
		// Khởi tạo các lớp chức năng
		Woo_VN_Checkout_Fields::get_instance();
		Woo_VN_Checkout_Agents::get_instance();
		Woo_VN_Checkout_Emails::get_instance();
	}

	/**
	 * Nạp các file PHP logic
	 */
	private function includes() {
		require_once WOO_VN_CE_PATH . 'includes/class-woo-vn-checkout-fields.php';
		require_once WOO_VN_CE_PATH . 'includes/class-woo-vn-checkout-agents.php';
		require_once WOO_VN_CE_PATH . 'includes/class-woo-vn-checkout-emails.php';
	}

	/**
	 * Hook chạy khi kích hoạt plugin
	 */
	public function activate() {
		// Tạo thư mục assets/data nếu chưa tồn tại
		$data_dir = WOO_VN_CE_PATH . 'assets/data';
		if ( ! file_exists( $data_dir ) ) {
			wp_mkdir_p( $data_dir );
		}

		// Tải dữ liệu địa lý từ API v2 và cache lại
		$this->download_and_cache_locations();
	}

	/**
	 * Hook chạy khi hủy kích hoạt plugin
	 */
	public function deactivate() {
		// Xóa các thiết lập tạm thời nếu cần (giữ lại cấu hình đại lý để tránh mất dữ liệu)
	}

	/**
	 * Tải và lưu cache dữ liệu địa lý từ API provinces.open-api.vn v2
	 */
	public function download_and_cache_locations() {
		$json_file = WOO_VN_CE_PATH . 'assets/data/vietnam-locations.json';
		
		// Thử tải dữ liệu từ API v2 với depth=2 để lấy Tỉnh/Thành và Phường/Xã
		$response = wp_remote_get( 'https://provinces.open-api.vn/api/v2/p/?depth=2', array(
			'timeout' => 15,
		) );

		if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
			$body = wp_remote_retrieve_body( $response );
			// Kiểm tra xem dữ liệu JSON có hợp lệ không trước khi ghi file
			$data = json_decode( $body, true );
			if ( is_array( $data ) && ! empty( $data ) ) {
				file_put_contents( $json_file, $body );
				return true;
			}
		}

		// Nếu tải thất bại và file cache chưa tồn tại, tạo file dự phòng với các thành phố chính để plugin chạy được ngay
		if ( ! file_exists( $json_file ) ) {
			$fallback_data = $this->get_fallback_locations();
			file_put_contents( $json_file, json_encode( $fallback_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ) );
		}

		return false;
	}

	/**
	 * Bộ dữ liệu dự phòng cực kỳ chi tiết trong trường hợp không kết nối được API lúc kích hoạt
	 */
	private function get_fallback_locations() {
		return array(
			array(
				"code" => 1,
				"name" => "Thành phố Hà Nội",
				"codename" => "thanh_pho_ha_noi",
				"division_type" => "thành phố trung ương",
				"phone_code" => 24,
				"wards" => array(
					array("code" => 1, "name" => "Phường Phúc Xá", "codename" => "phuong_phuc_xa"),
					array("code" => 2, "name" => "Phường Trúc Bạch", "codename" => "phuong_truc_bach"),
					array("code" => 3, "name" => "Phường Vĩnh Phúc", "codename" => "phuong_vinh_phuc"),
					array("code" => 4, "name" => "Phường Cống Vị", "codename" => "phuong_cong_vi"),
					array("code" => 5, "name" => "Phường Kim Mã", "codename" => "phuong_kim_ma"),
					array("code" => 6, "name" => "Phường Giảng Võ", "codename" => "phuong_giang_vo"),
					array("code" => 7, "name" => "Phường Thành Công", "codename" => "phuong_thanh_cong"),
					array("code" => 8, "name" => "Phường Hàng Đào", "codename" => "phuong_hang_dao"),
					array("code" => 9, "name" => "Phường Hàng Bông", "codename" => "phuong_hang_bong"),
					array("code" => 10, "name" => "Phường Tràng Tiền", "codename" => "phuong_trang_tien"),
					array("code" => 11, "name" => "Phường Dịch Vọng Hậu", "codename" => "phuong_dich_vong_hau"),
					array("code" => 12, "name" => "Phường Mai Dịch", "codename" => "phuong_mai_dich"),
					array("code" => 13, "name" => "Phường Trung Hòa", "codename" => "phuong_trung_hoa"),
				)
			),
			array(
				"code" => 79,
				"name" => "Thành phố Hồ Chí Minh",
				"codename" => "thanh_pho_ho_chi_minh",
				"division_type" => "thành phố trung ương",
				"phone_code" => 28,
				"wards" => array(
					array("code" => 26734, "name" => "Phường Tân Định", "codename" => "phuong_tan_dinh"),
					array("code" => 26737, "name" => "Phường Đa Kao", "codename" => "phuong_da_kao"),
					array("code" => 26740, "name" => "Phường Bến Nghé", "codename" => "phuong_ben_nghe"),
					array("code" => 26743, "name" => "Phường Bến Thành", "codename" => "phuong_ben_thanh"),
					array("code" => 26746, "name" => "Phường Nguyễn Thái Bình", "codename" => "phuong_nguyen_thai_binh"),
					array("code" => 26749, "name" => "Phường Phạm Ngũ Lão", "codename" => "phuong_pham_ngu_lao"),
					array("code" => 26752, "name" => "Phường Cô Giang", "codename" => "phuong_co_giang"),
					array("code" => 26755, "name" => "Phường Cầu Ông Lãnh", "codename" => "phuong_cau_ong_lanh"),
					array("code" => 26758, "name" => "Phường Nguyễn Cư Trinh", "codename" => "phuong_nguyen_cu_trinh"),
					array("code" => 26860, "name" => "Phường Thảo Điền", "codename" => "phuong_thao_dien"),
					array("code" => 26863, "name" => "Phường An Phú", "codename" => "phuong_an_phu"),
					array("code" => 26866, "name" => "Phường Bình An", "codename" => "phuong_binh_an"),
				)
			),
			array(
				"code" => 48,
				"name" => "Thành phố Đà Nẵng",
				"codename" => "thanh_pho_da_nang",
				"division_type" => "thành phố trung ương",
				"phone_code" => 236,
				"wards" => array(
					array("code" => 20119, "name" => "Phường Tam Thuận", "codename" => "phuong_tam_thuan"),
					array("code" => 20122, "name" => "Phường Thanh Khê Tây", "codename" => "phuong_thanh_khe_tay"),
					array("code" => 20125, "name" => "Phường Thanh Khê Đông", "codename" => "phuong_thanh_khe_dong"),
					array("code" => 20128, "name" => "Phường Xuân Hà", "codename" => "phuong_xuan_ha"),
					array("code" => 20131, "name" => "Phường Tân Chính", "codename" => "phuong_tan_chinh"),
					array("code" => 20134, "name" => "Phường Chính Gián", "codename" => "phuong_chinh_gian"),
					array("code" => 20137, "name" => "Phường Vĩnh Trung", "codename" => "phuong_vinh_trung"),
					array("code" => 20140, "name" => "Phường Thạc Gián", "codename" => "phuong_thac_gian"),
					array("code" => 20143, "name" => "Phường An Khê", "codename" => "phuong_an_khe"),
					array("code" => 20146, "name" => "Phường Hòa Khê", "codename" => "phuong_hoa_khe"),
				)
			)
		);
	}

	/**
	 * Thông báo nếu thiếu WooCommerce
	 */
	public function woocommerce_missing_notice() {
		?>
		<div class="error notice">
			<p><?php _e( '<strong>WooCommerce Vietnam Checkout Extension</strong> yêu cầu plugin <strong>WooCommerce</strong> phải được kích hoạt để hoạt động!', 'woo-vietnam-checkout-extension' ); ?></p>
		</div>
		<?php
	}
}

// Khởi chạy plugin
Woo_Vietnam_Checkout_Extension::get_instance();
