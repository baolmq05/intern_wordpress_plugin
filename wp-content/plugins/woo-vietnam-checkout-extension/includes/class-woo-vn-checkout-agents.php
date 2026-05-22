<?php
/**
 * Lớp quản lý danh sách Đại lý, trang cài đặt Admin và chọn Đại lý tại Checkout
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Woo_VN_Checkout_Agents {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		// Thêm tab cài đặt Đại lý vào WooCommerce Settings
		add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_tab' ), 50 );
		add_action( 'woocommerce_settings_tabs_woo_vn_agents', array( $this, 'settings_tab_content' ) );
		add_action( 'woocommerce_update_options_woo_vn_agents', array( $this, 'update_settings' ) );

		// Hiển thị trường chọn đại lý tại trang Checkout
		add_action( 'woocommerce_after_checkout_billing_form', array( $this, 'display_agent_selection_checkout' ), 20 );

		// Ràng buộc kiểm tra chọn đại lý tại Checkout
		add_action( 'woocommerce_after_checkout_validation', array( $this, 'validate_agent_selection' ), 10, 2 );

		// Lưu đại lý được chọn vào Order Meta
		add_action( 'woocommerce_checkout_create_order', array( $this, 'save_chosen_agent_to_order' ), 10, 2 );

		// Hiển thị đại lý được chọn trong trang Admin chi tiết Đơn hàng
		add_action( 'woocommerce_admin_order_data_after_billing_address', array( $this, 'display_chosen_agent_in_admin' ), 20 );
	}

	/**
	 * Lấy danh sách đại lý từ CSDL
	 */
	public function get_agents() {
		$agents = get_option( 'woo_checkout_agents', array() );
		if ( ! is_array( $agents ) ) {
			return array();
		}
		return $agents;
	}

	/**
	 * Thêm tab Đại lý vào menu Cài đặt WooCommerce
	 */
	public function add_settings_tab( $settings_tabs ) {
		$settings_tabs['woo_vn_agents'] = __( 'Đại lý (Agents)', 'woo-vietnam-checkout-extension' );
		return $settings_tabs;
	}

	/**
	 * Render giao diện quản trị quản lý Đại lý
	 */
	public function settings_tab_content() {
		$agents = $this->get_agents();
		?>
		<div class="wrap">
			<h2><?php _e( 'Quản lý danh sách Đại lý nhận đơn hàng', 'woo-vietnam-checkout-extension' ); ?></h2>
			<p><?php _e( 'Danh sách các đại lý phân phối. Khách hàng sẽ chọn đại lý này khi thanh toán, và đại lý được chọn sẽ nhận email thông báo chứa đầy đủ thông tin đơn hàng.', 'woo-vietnam-checkout-extension' ); ?></p>
			
			<style>
				.woo-agents-table { width: 100%; border-collapse: collapse; margin-top: 15px; margin-bottom: 20px; }
				.woo-agents-table th, .woo-agents-table td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
				.woo-agents-table th { background-color: #f8f9fa; font-weight: bold; }
				.woo-agents-table tr:hover { background-color: #f1f2f6; }
				.woo-agents-input { width: 100%; padding: 6px 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
				.woo-agents-card { background: #fff; border: 1px solid #e5e5e5; padding: 20px; margin-top: 20px; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
				.woo-agents-title { font-size: 16px; font-weight: bold; margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 15px; }
				.btn-delete { color: #e74c3c; cursor: pointer; text-decoration: none; font-weight: bold; }
				.btn-delete:hover { color: #c0392b; }
			</style>

			<table class="woo-agents-table">
				<thead>
					<tr>
						<th style="width: 25%;"><?php _e( 'Tên Đại lý', 'woo-vietnam-checkout-extension' ); ?></th>
						<th style="width: 30%;"><?php _e( 'Địa chỉ', 'woo-vietnam-checkout-extension' ); ?></th>
						<th style="width: 25%;"><?php _e( 'Email nhận thông báo', 'woo-vietnam-checkout-extension' ); ?></th>
						<th style="width: 15%;"><?php _e( 'Số điện thoại', 'woo-vietnam-checkout-extension' ); ?></th>
						<th style="width: 5%; text-align: center;"><?php _e( 'Xóa', 'woo-vietnam-checkout-extension' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( ! empty( $agents ) ) : ?>
						<?php foreach ( $agents as $id => $agent ) : ?>
							<tr>
								<td>
									<input type="text" name="agents[<?php echo esc_attr( $id ); ?>][name]" class="woo-agents-input" value="<?php echo esc_attr( $agent['name'] ); ?>" required />
								</td>
								<td>
									<input type="text" name="agents[<?php echo esc_attr( $id ); ?>][address]" class="woo-agents-input" value="<?php echo esc_attr( $agent['address'] ); ?>" required />
								</td>
								<td>
									<input type="email" name="agents[<?php echo esc_attr( $id ); ?>][email]" class="woo-agents-input" value="<?php echo esc_attr( $agent['email'] ); ?>" required />
								</td>
								<td>
									<input type="text" name="agents[<?php echo esc_attr( $id ); ?>][phone]" class="woo-agents-input" value="<?php echo esc_attr( $agent['phone'] ); ?>" />
								</td>
								<td style="text-align: center;">
									<input type="checkbox" name="delete_agents[]" value="<?php echo esc_attr( $id ); ?>" style="transform: scale(1.2);" />
								</td>
							</tr>
						<?php endforeach; ?>
					<?php else : ?>
						<tr>
							<td colspan="5" style="text-align: center; color: #7f8c8d; font-style: italic;">
								<?php _e( 'Chưa có đại lý nào được cấu hình. Vui lòng thêm đại lý ở mục phía dưới.', 'woo-vietnam-checkout-extension' ); ?>
							</td>
						</tr>
					<?php endif; ?>
				</tbody>
			</table>

			<!-- Form thêm đại lý mới -->
			<div class="woo-agents-card">
				<h3 class="woo-agents-title"><?php _e( '➕ Thêm Đại lý mới', 'woo-vietnam-checkout-extension' ); ?></h3>
				<table class="form-table">
					<tr>
						<th scope="row"><label><?php _e( 'Tên Đại lý', 'woo-vietnam-checkout-extension' ); ?> <span style="color:red;">*</span></label></th>
						<td><input type="text" name="new_agent_name" class="regular-text" placeholder="Đại lý Hà Nội, Đại lý HCMC..." /></td>
					</tr>
					<tr>
						<th scope="row"><label><?php _e( 'Địa chỉ đại lý', 'woo-vietnam-checkout-extension' ); ?> <span style="color:red;">*</span></label></th>
						<td><input type="text" name="new_agent_address" class="large-text" placeholder="Địa chỉ chi tiết của đại lý..." /></td>
					</tr>
					<tr>
						<th scope="row"><label><?php _e( 'Email nhận đơn hàng', 'woo-vietnam-checkout-extension' ); ?> <span style="color:red;">*</span></label></th>
						<td><input type="email" name="new_agent_email" class="regular-text" placeholder="daily@example.com" /></td>
					</tr>
					<tr>
						<th scope="row"><label><?php _e( 'Số điện thoại', 'woo-vietnam-checkout-extension' ); ?></label></th>
						<td><input type="text" name="new_agent_phone" class="regular-text" placeholder="09xxxxxxx" /></td>
					</tr>
				</table>
			</div>
		</div>
		<?php
	}

	/**
	 * Xử lý lưu cài đặt đại lý
	 */
	public function update_settings() {
		$agents = array();
		
		// 1. Cập nhật các đại lý cũ
		if ( isset( $_POST['agents'] ) && is_array( $_POST['agents'] ) ) {
			$post_agents = $_POST['agents'];
			$delete_agents = isset( $_POST['delete_agents'] ) ? $_POST['delete_agents'] : array();
			
			foreach ( $post_agents as $id => $agent_data ) {
				// Bỏ qua nếu đánh dấu xóa
				if ( in_array( $id, $delete_agents ) ) {
					continue;
				}
				
				if ( ! empty( $agent_data['name'] ) && ! empty( $agent_data['address'] ) && ! empty( $agent_data['email'] ) ) {
					$agents[$id] = array(
						'id'      => $id,
						'name'    => sanitize_text_field( $agent_data['name'] ),
						'address' => sanitize_text_field( $agent_data['address'] ),
						'email'   => sanitize_email( $agent_data['email'] ),
						'phone'   => sanitize_text_field( $agent_data['phone'] ),
					);
				}
			}
		}

		// 2. Thêm đại lý mới nếu điền đủ thông tin
		if ( ! empty( $_POST['new_agent_name'] ) && ! empty( $_POST['new_agent_address'] ) && ! empty( $_POST['new_agent_email'] ) ) {
			$new_id = 'agent_' . time() . '_' . rand( 100, 999 );
			$agents[$new_id] = array(
				'id'      => $new_id,
				'name'    => sanitize_text_field( $_POST['new_agent_name'] ),
				'address' => sanitize_text_field( $_POST['new_agent_address'] ),
				'email'   => sanitize_email( $_POST['new_agent_email'] ),
				'phone'   => sanitize_text_field( $_POST['new_agent_phone'] ),
			);
		}

		// Lưu lại option
		update_option( 'woo_checkout_agents', $agents );
	}

	/**
	 * Hiển thị trường chọn đại lý tại trang Checkout
	 */
	public function display_agent_selection_checkout() {
		$agents = $this->get_agents();
		if ( empty( $agents ) ) {
			return;
		}
		
		?>
		<div class="woo-vn-ce-agent-selection-wrapper" style="margin-top: 30px; margin-bottom: 20px;">
			<h3 style="font-size: 18px; font-weight: bold; margin-bottom: 15px; border-bottom: 2px solid #3498db; padding-bottom: 8px;">
				<span class="dashicons dashicons-store" style="font-size: 22px; width: 22px; height: 22px; vertical-align: middle;"></span> 
				<?php _e( 'Chọn Đại lý phục vụ', 'woo-vietnam-checkout-extension' ); ?> <span class="required">*</span>
			</h3>
			
			<p class="form-row form-row-wide">
				<label for="billing_agent_id" style="font-weight: bold; display: block; margin-bottom: 8px;">
					<?php _e( 'Vui lòng lựa chọn đại lý gần bạn nhất để phục vụ nhanh chóng:', 'woo-vietnam-checkout-extension' ); ?>
				</label>
				<select name="billing_agent_id" id="billing_agent_id" class="input-text" style="width: 100%; padding: 10px; border-radius: 5px; height: auto;">
					<option value=""><?php _e( '--- Vui lòng chọn Đại lý ---', 'woo-vietnam-checkout-extension' ); ?></option>
					<?php foreach ( $agents as $id => $agent ) : ?>
						<option value="<?php echo esc_attr( $id ); ?>" data-address="<?php echo esc_attr( $agent['address'] ); ?>" data-phone="<?php echo esc_attr( $agent['phone'] ); ?>">
							<?php echo esc_html( $agent['name'] ); ?> (<?php echo esc_html( $agent['address'] ); ?>)
						</option>
					<?php endforeach; ?>
				</select>
			</p>
			
			<!-- Hộp hiển thị thông tin đại lý được chọn (Micro UI premium) -->
			<div id="chosen-agent-details-box" style="display: none; background: rgba(52, 152, 219, 0.05); border-left: 4px solid #3498db; border-radius: 4px; padding: 15px; margin-top: 15px; box-shadow: inset 0 0 10px rgba(0,0,0,0.02);">
				<p style="margin: 0 0 6px 0; font-weight: bold; color: #2c3e50;"><span class="dashicons dashicons-location-alt" style="margin-top: 2px;"></span> <?php _e( 'Địa chỉ đại lý:', 'woo-vietnam-checkout-extension' ); ?> <span id="chosen-agent-address" style="font-weight: normal; color: #555;"></span></p>
				<p style="margin: 0; font-weight: bold; color: #2c3e50;"><span class="dashicons dashicons-phone" style="margin-top: 2px;"></span> <?php _e( 'Số điện thoại:', 'woo-vietnam-checkout-extension' ); ?> <span id="chosen-agent-phone" style="font-weight: normal; color: #555;"></span></p>
			</div>
		</div>
		
		<script>
			jQuery(document).ready(function($) {
				$('#billing_agent_id').on('change', function() {
					var $selectedOption = $(this).find('option:selected');
					var val = $(this).val();
					
					if (val) {
						var address = $selectedOption.attr('data-address');
						var phone = $selectedOption.attr('data-phone') || 'Chưa cập nhật';
						
						$('#chosen-agent-address').text(address);
						$('#chosen-agent-phone').text(phone);
						$('#chosen-agent-details-box').slideDown(300);
					} else {
						$('#chosen-agent-details-box').slideUp(200);
					}
				});
			});
		</script>
		<?php
	}

	/**
	 * Xác thực tính hợp lệ tại Checkout (Yêu cầu phải chọn Đại lý)
	 */
	public function validate_agent_selection( $data, $errors ) {
		$agents = $this->get_agents();
		if ( empty( $agents ) ) {
			return; // Bỏ qua nếu admin chưa cấu hình đại lý nào
		}

		if ( empty( $_POST['billing_agent_id'] ) ) {
			$errors->add( 'validation', '<strong>' . __( 'Đại lý phục vụ', 'woo-vietnam-checkout-extension' ) . '</strong> ' . __( 'là trường bắt buộc. Vui lòng chọn một đại lý gần nhất.', 'woo-vietnam-checkout-extension' ) );
		}
	}

	/**
	 * Lưu đại lý đã chọn vào metadata của Đơn hàng
	 */
	public function save_chosen_agent_to_order( $order, $data ) {
		if ( isset( $_POST['billing_agent_id'] ) && ! empty( $_POST['billing_agent_id'] ) ) {
			$agent_id = sanitize_text_field( $_POST['billing_agent_id'] );
			$agents = $this->get_agents();
			
			if ( isset( $agents[$agent_id] ) ) {
				$agent = $agents[$agent_id];
				
				$order->update_meta_data( '_billing_agent_id', $agent_id );
				$order->update_meta_data( '_billing_agent_name', $agent['name'] );
				$order->update_meta_data( '_billing_agent_address', $agent['address'] );
				$order->update_meta_data( '_billing_agent_email', $agent['email'] );
				$order->update_meta_data( '_billing_agent_phone', $agent['phone'] );
			}
		}
	}

	/**
	 * Hiển thị đại lý đã chọn trong trang chi tiết đơn hàng của Admin
	 */
	public function display_chosen_agent_in_admin( $order ) {
		$agent_name = $order->get_meta( '_billing_agent_name' );
		$agent_address = $order->get_meta( '_billing_agent_address' );
		$agent_phone = $order->get_meta( '_billing_agent_phone' );

		if ( ! empty( $agent_name ) ) {
			echo '<div style="margin-top: 15px; border-top: 1px solid #eee; padding-top: 10px;">';
			echo '<h4>🏪 ' . __( 'Đại lý phục vụ', 'woo-vietnam-checkout-extension' ) . '</h4>';
			echo '<p><strong>Tên đại lý:</strong> ' . esc_html( $agent_name ) . '</p>';
			echo '<p><strong>Địa chỉ:</strong> ' . esc_html( $agent_address ) . '</p>';
			if ( ! empty( $agent_phone ) ) {
				echo '<p><strong>Điện thoại:</strong> ' . esc_html( $agent_phone ) . '</p>';
			}
			echo '</div>';
		}
	}
}
