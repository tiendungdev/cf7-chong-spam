<?php
/**
 * Plugin Name: CF7 chống spam
 * Description: Chỉ nhận số di động Việt Nam và thêm bẫy bot cho mọi form Contact Form 7.
 * Version: 1.0.0
 * Author: Vu Tru So
 * Author URI: https://vutruso.com/
 * License: GPL-2.0-or-later
 */

defined( 'ABSPATH' ) || exit;

/**
 * Kiểm tra số di động Việt Nam: 0xxx, 84xxx hoặc +84xxx.
 */
function cf7vn_la_so_di_dong( $tel ) {
    $tel = preg_replace( '/[\s.\-()]/', '', $tel );

    return (bool) preg_match( '/^(?:0|\+?84)(?:3[2-9]|5[25689]|7[06-9]|8[1-9]|9[0-46-9])\d{7}$/', $tel );
}

/**
 * Áp dụng kiểm tra trên cho mọi trường tel khi form được gửi.
 */
function cf7vn_validate_tel( $result, $tag ) {
    $submission = WPCF7_Submission::get_instance();
    $tel        = $submission ? $submission->get_posted_string( $tag->name ) : '';

    if ( '' !== $tel && ! cf7vn_la_so_di_dong( $tel ) ) {
        $result->invalidate( $tag, 'Vui lòng nhập số di động Việt Nam hợp lệ (VD: 0912345678).' );
    }

    return $result;
}
add_filter( 'wpcf7_validate_tel', 'cf7vn_validate_tel', 20, 2 );
add_filter( 'wpcf7_validate_tel*', 'cf7vn_validate_tel', 20, 2 );

/**
 * Giá trị token, script bên dưới sẽ điền vào form khi có thao tác thật.
 */
function cf7vn_token() {
    return substr( wp_hash( 'cf7vn_token' ), 0, 16 );
}

/**
 * Thêm honeypot và trường token vào mọi form CF7.
 */
function cf7vn_them_bay( $html ) {
    return $html
        . '<div style="position:absolute;left:-9999px;height:0;overflow:hidden" aria-hidden="true">'
        . '<input type="text" name="cf7vn_hp" value="" tabindex="-1" autocomplete="off">'
        . '</div>'
        . '<input type="hidden" name="cf7vn_tk" value="">';
}
add_filter( 'wpcf7_form_elements', 'cf7vn_them_bay' );

/**
 * Điền token khi khách click, chạm, gõ phím hoặc focus vào form.
 */
function cf7vn_in_script() {
    if ( ! did_filter( 'wpcf7_form_elements' ) ) {
        return;
    }
    ?>
    <script>
    (function () {
        var token = <?php echo wp_json_encode( cf7vn_token() ); ?>;
        ['pointerdown', 'keydown', 'focusin'].forEach(function (name) {
            document.addEventListener(name, function (e) {
                var form = e.target && e.target.closest ? e.target.closest('form.wpcf7-form') : null;
                if (form && form.elements.cf7vn_tk) {
                    form.elements.cf7vn_tk.value = token;
                }
            }, true);
        });
    })();
    </script>
    <?php
}
add_action( 'wp_footer', 'cf7vn_in_script', 99 );

/**
 * Đánh dấu spam nếu dính honeypot hoặc không có token đúng.
 */
function cf7vn_kiem_tra_bay( $spam, $submission ) {
    if ( $spam ) {
        return $spam;
    }

    $token = isset( $_POST['cf7vn_tk'] ) ? wp_unslash( $_POST['cf7vn_tk'] ) : '';
    $ly_do = '';

    if ( ! empty( $_POST['cf7vn_hp'] ) ) {
        $ly_do = 'honeypot';
    } elseif ( ! is_string( $token ) || ! hash_equals( cf7vn_token(), $token ) ) {
        $ly_do = 'missing-token';
    }

    if ( '' === $ly_do ) {
        return $spam;
    }

    error_log( sprintf(
        '[cf7-chong-spam] chan %s form=%d ip=%s',
        $ly_do,
        $submission->get_contact_form()->id(),
        $submission->get_meta( 'remote_ip' )
    ) );

    return true;
}
add_filter( 'wpcf7_spam', 'cf7vn_kiem_tra_bay', 8, 2 );
