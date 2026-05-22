jQuery(document).ready(function($) {
    // 1. Chèn giao diện Định vị GPS cao cấp ngay dưới trường Phường/Xã
    if ($('#billing_ward_vn_field').length && !$('.woo-vn-ce-gps-container').length) {
        var gpsHtml = 
            '<div class="woo-vn-ce-gps-container">' +
                '<h4 class="woo-vn-ce-gps-title">' +
                    '<span class="dashicons dashicons-location" style="font-size: 20px; width: 20px; height: 20px; vertical-align: middle;"></span> ' +
                    'Xác thực vị trí giao hàng (GPS)' +
                '</h4>' +
                '<p class="woo-vn-ce-gps-desc">Nhằm phục vụ công tác giao hàng chính xác và nhanh chóng nhất, hệ thống yêu cầu xác thực tọa độ GPS của bạn. Vui lòng cho phép truy cập vị trí và nhấn nút bên dưới:</p>' +
                '<button type="button" id="woo-vn-ce-gps-trigger" class="woo-vn-ce-gps-btn">' +
                    '<span class="gps-spinner"></span> <span class="gps-btn-text">📍 Xác thực vị trí GPS</span>' +
                '</button>' +
                '<div id="woo-vn-ce-gps-status-badge" class="woo-vn-ce-gps-badge"></div>' +
            '</div>';
        
        $('#billing_ward_vn_field').after(gpsHtml);
    }

    // 2. Logic tương tác gọi HTML5 Geolocation API
    $(document).on('click', '#woo-vn-ce-gps-trigger', function(e) {
        e.preventDefault();
        
        var $btn = $(this);
        var $btnText = $btn.find('.gps-btn-text');
        var $badge = $('#woo-vn-ce-gps-status-badge');
        
        // Cập nhật trạng thái loading
        $btn.addClass('loading');
        $btnText.text(wooVnCeParams.loading_text);
        $badge.removeClass('success-badge error-badge').hide();

        if (!navigator.geolocation) {
            showGpsError('Trình duyệt của bạn không hỗ trợ định vị GPS. Vui lòng sử dụng Chrome, Safari hoặc các thiết bị di động.');
            return;
        }

        var geoOptions = {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 0
        };

        navigator.geolocation.getCurrentPosition(
            // Thành công
            function(position) {
                var lat = position.coords.latitude;
                var lng = position.coords.longitude;
                var accuracy = Math.round(position.coords.accuracy);

                // Ghi tọa độ vào hidden inputs của WooCommerce
                $('input[name="billing_latitude"]').val(lat);
                $('input[name="billing_longitude"]').val(lng);

                // Cập nhật UI button thành công
                $btn.removeClass('loading').addClass('success');
                $btnText.html('✓ Đã xác thực thành công');
                
                // Hiển thị badge tọa độ glassmorphism
                $badge.addClass('success-badge')
                      .html('<span><strong>✅ Đã ghi nhận tọa độ thành công:</strong> ' + lat.toFixed(5) + ', ' + lng.toFixed(5) + ' (Độ chính xác: ~' + accuracy + 'm)</span>')
                      .fadeIn(400);

                // Trigger cập nhật lại checkout (nếu cần thiết để xóa các thông báo lỗi cũ)
                $(document.body).trigger('update_checkout');
            },
            // Thất bại
            function(error) {
                var errMsg = 'Không thể lấy vị trí. ';
                switch(error.code) {
                    case error.PERMISSION_DENIED:
                        errMsg += 'Bạn đã từ chối quyền truy cập vị trí. Vui lòng bật lại quyền định vị cho trang web này trong cài đặt trình duyệt.';
                        break;
                    case error.POSITION_UNAVAILABLE:
                        errMsg += 'Thông tin định vị không khả dụng trên thiết bị của bạn.';
                        break;
                    case error.TIMEOUT:
                        errMsg += 'Quá thời gian yêu cầu định vị GPS. Vui lòng nhấn nút thử lại.';
                        break;
                    default:
                        errMsg += 'Lỗi không xác định (' + error.message + ').';
                }
                showGpsError(errMsg);
            },
            geoOptions
        );

        function showGpsError(msg) {
            $btn.removeClass('loading');
            $btnText.text('📍 Thử lại xác thực GPS');
            $badge.addClass('error-badge')
                  .html('<span><strong>❌ Lỗi định vị:</strong> ' + msg + '</span>')
                  .fadeIn(400);
        }
    });

    // 3. Logic nạp động Phường/Xã khi Tỉnh/Thành phố thay đổi
    $(document).on('change', '#billing_state_vn', function() {
        var provinceCode = $(this).val();
        var $wardSelect = $('#billing_ward_vn');

        // Reset dropdown Phường/Xã
        $wardSelect.html('<option value="">' + wooVnCeParams.placeholder_ward + '</option>').trigger('change');

        if (!provinceCode) {
            return;
        }

        // Hiện trạng thái đang tải
        $wardSelect.html('<option value="">Đang tải danh sách Phường / Xã...</option>');

        // Gọi AJAX lên Server WordPress để lấy danh sách xã phường
        $.ajax({
            url: wooVnCeParams.ajax_url,
            type: 'POST',
            data: {
                action: 'woo_vn_get_wards',
                province_code: provinceCode,
                security: wooVnCeParams.get_wards_nonce
            },
            success: function(response) {
                if (response.success && response.data.length > 0) {
                    var options = '<option value="">' + wooVnCeParams.placeholder_ward + '</option>';
                    $.each(response.data, function(index, ward) {
                        options += '<option value="' + ward.code + '">' + ward.name + '</option>';
                    });
                    $wardSelect.html(options);
                } else {
                    $wardSelect.html('<option value="">Không tìm thấy Phường / Xã nào</option>');
                }
            },
            error: function() {
                $wardSelect.html('<option value="">Lỗi tải dữ liệu. Vui lòng tải lại trang.</option>');
            }
        });
    });

    // 4. Nếu là đơn hàng cũ hoặc khi quay lại trang checkout và đã có GPS, tự động hiển thị UI thành công
    var existingLat = $('input[name="billing_latitude"]').val();
    var existingLng = $('input[name="billing_longitude"]').val();
    
    if (existingLat && existingLng) {
        setTimeout(function() {
            var $btn = $('#woo-vn-ce-gps-trigger');
            var $btnText = $btn.find('.gps-btn-text');
            var $badge = $('#woo-vn-ce-gps-status-badge');

            $btn.addClass('success');
            $btnText.html('✓ Đã xác thực thành công');
            $badge.addClass('success-badge')
                  .html('<span><strong>✅ Đã tải vị trí trước đó:</strong> ' + parseFloat(existingLat).toFixed(5) + ', ' + parseFloat(existingLng).toFixed(5) + '</span>')
                  .show();
        }, 800);
    }
});
