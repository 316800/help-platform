<?php
/**
 * HELP Platform 实名认证类
 */
class Help_Platform_Verify {
    /**
     * 初始化钩子
     */
    public static function init() {
        add_action('wp_ajax_help_platform_submit_verify', array(__CLASS__, 'handle_verify_submission'));
        add_action('wp_ajax_nopriv_help_platform_submit_verify', array(__CLASS__, 'handle_verify_submission'));
        add_action('transition_post_status', array(__CLASS__, 'handle_verify_status_change'), 10, 3);
    }

    /**
     * 实名认证表单短代码
     */
    public static function verify_form_shortcode($atts) {
        // 检查用户是否已登录
        if (!is_user_logged_in()) {
            return '<p class="help-platform-error">' . __('请先登录后再进行实名认证', 'help-platform') . '</p>';
        }

        // 检查用户是否已认证
        $user_id = get_current_user_id();
        if (self::is_user_verified($user_id)) {
            return '<p class="help-platform-success">' . __('您已完成实名认证', 'help-platform') . '</p>';
        }

        // 检查是否有待审核的认证
        if (self::has_pending_verify($user_id)) {
            return '<p class="help-platform-notice">' . __('您的实名认证正在审核中，请耐心等待', 'help-platform') . '</p>';
        }

        // 加载表单模板
        ob_start();
        wp_enqueue_style('help-platform-style');
        wp_enqueue_script('help-platform-script');
        include HELP_PLATFORM_PLUGIN_DIR . 'templates/verify-form.php';
        return ob_get_clean();
    }

    /**
     * 处理认证提交
     */
    public static function handle_verify_submission() {
        check_ajax_referer('help-platform-nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('请先登录', 'help-platform'));
        }

        $user_id = get_current_user_id();
        
        // 验证必填字段
        $required_fields = array('real_name', 'id_number', 'id_photo');
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                wp_send_json_error(sprintf(__('请填写%s', 'help-platform'), $field));
            }
        }

        // 验证身份证号格式
        if (!self::validate_id_number($_POST['id_number'])) {
            wp_send_json_error(__('身份证号码格式不正确', 'help-platform'));
        }

        // 处理图片上传
        $id_photo = self::handle_image_upload('id_photo');
        if (is_wp_error($id_photo)) {
            wp_send_json_error($id_photo->get_error_message());
        }

        $selfie_photo = null;
        if (!empty($_FILES['selfie_photo']['name'])) {
            $selfie_photo = self::handle_image_upload('selfie_photo');
            if (is_wp_error($selfie_photo)) {
                wp_send_json_error($selfie_photo->get_error_message());
            }
        }

        // 创建认证记录
        $verify_id = wp_insert_post(array(
            'post_title' => sprintf(__('实名认证 - %s', 'help-platform'), $_POST['real_name']),
            'post_type' => 'help_verify',
            'post_status' => 'pending_verify',
            'post_author' => $user_id,
        ));

        if (is_wp_error($verify_id)) {
            wp_send_json_error(__('创建认证记录失败', 'help-platform'));
        }

        // 保存认证信息
        update_post_meta($verify_id, '_real_name', sanitize_text_field($_POST['real_name']));
        update_post_meta($verify_id, '_id_number', sanitize_text_field($_POST['id_number']));
        update_post_meta($verify_id, '_id_photo', $id_photo);
        if ($selfie_photo) {
            update_post_meta($verify_id, '_selfie_photo', $selfie_photo);
        }

        // 发送通知邮件给管理员
        $settings = Help_Platform::get_settings();
        $admin_email = $settings['notification_email'];
        $subject = sprintf(__('新的实名认证申请 - %s', 'help-platform'), $_POST['real_name']);
        $message = self::get_verify_notification_email($verify_id);
        Help_Platform::send_notification($admin_email, $subject, $message);

        wp_send_json_success(__('认证申请已提交，请等待审核', 'help-platform'));
    }

    /**
     * 处理认证状态变更
     */
    public static function handle_verify_status_change($new_status, $old_status, $post) {
        if ($post->post_type !== 'help_verify' || $new_status === $old_status) {
            return;
        }

        $user_id = $post->post_author;
        $user = get_userdata($user_id);
        
        if (!$user) {
            return;
        }

        $subject = '';
        $message = '';

        if ($new_status === 'publish') {
            // 认证通过
            update_user_meta($user_id, '_help_verified', 'yes');
            $subject = __('您的实名认证已通过', 'help-platform');
            $message = self::get_verify_approved_email($post);
        } elseif ($new_status === 'private') {
            // 认证被拒绝
            update_user_meta($user_id, '_help_verified', 'no');
            $subject = __('您的实名认证未通过', 'help-platform');
            $message = self::get_verify_rejected_email($post);
        }

        if ($subject && $message) {
            Help_Platform::send_notification($user->user_email, $subject, $message);
        }
    }

    /**
     * 检查用户是否已认证
     */
    public static function is_user_verified($user_id) {
        return get_user_meta($user_id, '_help_verified', true) === 'yes';
    }

    /**
     * 检查用户是否有待审核的认证
     */
    public static function has_pending_verify($user_id) {
        $pending = get_posts(array(
            'post_type' => 'help_verify',
            'post_status' => 'pending_verify',
            'author' => $user_id,
            'posts_per_page' => 1,
        ));
        return !empty($pending);
    }

    /**
     * 验证身份证号
     */
    private static function validate_id_number($id_number) {
        // 简单的身份证号格式验证
        return preg_match('/^\d{17}[\dXx]$/', $id_number);
    }

    /**
     * 处理图片上传
     */
    private static function handle_image_upload($field_name) {
        if (empty($_FILES[$field_name]['name'])) {
            return new WP_Error('no_file', __('请选择要上传的图片', 'help-platform'));
        }

        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $attachment_id = media_handle_upload($field_name, 0);
        
        if (is_wp_error($attachment_id)) {
            return $attachment_id;
        }

        return $attachment_id;
    }

    /**
     * 获取认证通知邮件内容
     */
    private static function get_verify_notification_email($verify_id) {
        $post = get_post($verify_id);
        $user = get_userdata($post->post_author);
        
        ob_start();
        include HELP_PLATFORM_PLUGIN_DIR . 'templates/emails/verify-notification.php';
        return ob_get_clean();
    }

    /**
     * 获取认证通过邮件内容
     */
    private static function get_verify_approved_email($post) {
        $user = get_userdata($post->post_author);
        
        ob_start();
        include HELP_PLATFORM_PLUGIN_DIR . 'templates/emails/verify-approved.php';
        return ob_get_clean();
    }

    /**
     * 获取认证拒绝邮件内容
     */
    private static function get_verify_rejected_email($post) {
        $user = get_userdata($post->post_author);
        
        ob_start();
        include HELP_PLATFORM_PLUGIN_DIR . 'templates/emails/verify-rejected.php';
        return ob_get_clean();
    }
}

// 初始化
Help_Platform_Verify::init(); 