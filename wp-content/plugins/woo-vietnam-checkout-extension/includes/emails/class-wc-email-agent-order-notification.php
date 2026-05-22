<?php
/**
 * Lớp Email gửi thông báo Đơn hàng mới cho Đại lý được chọn
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Email_Agent_Order_Notification extends WC_Email {

	/**
	 * Khởi tạo cấu hình Email
	 */
	public function __construct() {
		$this->id             = 'agent_order_notification';
		$this->title          = __( 'Thông báo đơn hàng gửi Đại lý', 'woo-vietnam-checkout-extension' );
		$this->description    = __( 'Gửi email thông báo chi tiết đơn hàng đến Đại lý được chọn khi khách đặt hàng thành công.', 'woo-vietnam-checkout-extension' );
		
		// Chỉ định file template nằm trong thư mục templates của plugin
		$this->template_html  = 'emails/agent-order-notification.php';
		$this->template_plain = 'emails/plain/agent-order-notification.php';
		$this->template_base  = WOO_VN_CE_PATH . 'templates/';

		// Lắng nghe sự kiện sau khi checkout thành công để gửi email
		add_action( 'woocommerce_checkout_order_processed', array( $this, 'trigger' ), 10, 1 );

		// Gọi hàm dựng của lớp cha WC_Email
		parent::__construct();
	}

	/**
	 * Lấy tiêu đề mặc định của email
	 */
	public function get_default_subject() {
		return __( '[{site_title}] Đơn hàng mới #{order_number} cần xử lý', 'woo-vietnam-checkout-extension' );
	}

	/**
	 * Lấy tiêu đề phụ (heading) mặc định của email
	 */
	public function get_default_heading() {
		return __( 'Đơn hàng mới: #{order_number}', 'woo-vietnam-checkout-extension' );
	}

	/**
	 * Hàm kích hoạt gửi email khi đơn hàng được tạo thành công
	 */
	public function trigger( $order_id ) {
		if ( ! $order_id ) {
			return;
		}

		$this->object = wc_get_order( $order_id );
		if ( ! is_a( $this->object, 'WC_Order' ) ) {
			return;
		}

		// Lấy email của đại lý được chọn
		$agent_email = $this->object->get_meta( '_billing_agent_email' );
		
		// Nếu không có đại lý hoặc đại lý không có email, lấy email quản trị làm dự phòng
		if ( ! empty( $agent_email ) ) {
			$this->recipient = $agent_email;
		} else {
			$this->recipient = $this->get_option( 'recipient', get_option( 'admin_email' ) );
		}

		// Thiết lập placeholders
		$this->placeholders['{order_date}']   = wc_format_datetime( $this->object->get_date_created() );
		$this->placeholders['{order_number}'] = $this->object->get_order_number();
		$this->placeholders['{site_title}']   = $this->get_blogname();

		// Nếu email bị tắt hoặc không có người nhận, dừng lại
		if ( ! $this->is_enabled() || ! $this->get_recipient() ) {
			return;
		}

		// Tiến hành gửi email
		$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
	}

	/**
	 * Tạo nội dung HTML của email thông báo
	 */
	public function get_content_html() {
		return wc_get_template_html(
			$this->template_html,
			array(
				'order'         => $this->object,
				'email_heading' => $this->get_heading(),
				'sent_to_admin' => false,
				'plain_text'    => false,
				'email'         => $this,
			),
			'',
			$this->template_base
		);
	}

	/**
	 * Tạo nội dung văn bản thuần (Plain text) của email thông báo
	 */
	public function get_content_plain() {
		return wc_get_template_html(
			$this->template_plain,
			array(
				'order'         => $this->object,
				'email_heading' => $this->get_heading(),
				'sent_to_admin' => false,
				'plain_text'    => true,
				'email'         => $this,
			),
			'',
			$this->template_base
		);
	}

	/**
	 * Đăng ký các trường cài đặt trong Dashboard Admin cho Email này
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
				'title'   => __( 'Kích hoạt/Vô hiệu hóa', 'woo-vietnam-checkout-extension' ),
				'type'    => 'checkbox',
				'label'   => __( 'Bật email thông báo này gửi đến Đại lý', 'woo-vietnam-checkout-extension' ),
				'default' => 'yes',
			),
			'recipient' => array(
				'title'       => __( 'Người nhận mặc định (nếu lỗi đại lý)', 'woo-vietnam-checkout-extension' ),
				'type'        => 'text',
				'description' => __( 'Địa chỉ email dự phòng nhận thông báo nếu đơn hàng không được gán đại lý nào.', 'woo-vietnam-checkout-extension' ),
				'placeholder' => get_option( 'admin_email' ),
				'default'     => get_option( 'admin_email' ),
				'desc_tip'    => true,
			),
			'subject' => array(
				'title'       => __( 'Tiêu đề Email', 'woo-vietnam-checkout-extension' ),
				'type'        => 'text',
				'description' => sprintf( __( 'Mặc định: %s', 'woo-vietnam-checkout-extension' ), '<code>' . $this->get_default_subject() . '</code>' ),
				'placeholder' => $this->get_default_subject(),
				'default'     => '',
				'desc_tip'    => true,
			),
			'heading' => array(
				'title'       => __( 'Tiêu đề phụ (Heading)', 'woo-vietnam-checkout-extension' ),
				'type'        => 'text',
				'description' => sprintf( __( 'Mặc định: %s', 'woo-vietnam-checkout-extension' ), '<code>' . $this->get_default_heading() . '</code>' ),
				'placeholder' => $this->get_default_heading(),
				'default'     => '',
				'desc_tip'    => true,
			),
			'email_type' => array(
				'title'       => __( 'Định dạng Email', 'woo-vietnam-checkout-extension' ),
				'type'        => 'select',
				'description' => __( 'Chọn định dạng email gửi đi.', 'woo-vietnam-checkout-extension' ),
				'default'     => 'html',
				'class'       => 'email_type wc-enhanced-select',
				'options'     => $this->get_email_type_options(),
				'desc_tip'    => true,
			),
		);
	}
}
