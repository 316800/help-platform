<?php
/**
 * HELP Platform 任务管理类
 */
class Help_Platform_Job {
    /**
     * 初始化钩子
     */
    public static function init() {
        add_action('wp_ajax_help_platform_submit_job', array(__CLASS__, 'handle_job_submission'));
        add_action('wp_ajax_nopriv_help_platform_submit_job', array(__CLASS__, 'handle_job_submission'));
        add_action('transition_post_status', array(__CLASS__, 'handle_job_status_change'), 10, 3);
        add_action('add_meta_boxes', array(__CLASS__, 'add_job_meta_boxes'));
        add_action('save_post_help_job', array(__CLASS__, 'save_job_meta'));
    }

    /**
     * 任务发布表单短代码
     */
    public static function job_form_shortcode($atts) {
        // 检查用户是否已登录
        if (!is_user_logged_in()) {
            return '<p class="help-platform-error">' . __('请先登录后再发布任务', 'help-platform') . '</p>';
        }

        // 检查用户是否已认证
        $user_id = get_current_user_id();
        if (!Help_Platform_Verify::is_user_verified($user_id)) {
            return '<p class="help-platform-error">' . __('请先完成实名认证后再发布任务', 'help-platform') . '</p>';
        }

        // 检查是否允许发布任务
        $settings = Help_Platform::get_settings();
        if (!$settings['enable_job_posting']) {
            return '<p class="help-platform-error">' . __('当前暂未开放任务发布功能', 'help-platform') . '</p>';
        }

        // 加载表单模板
        ob_start();
        wp_enqueue_style('help-platform-style');
        wp_enqueue_script('help-platform-script');
        wp_enqueue_script('google-maps', 'https://maps.googleapis.com/maps/api/js?key=' . self::get_google_maps_api_key() . '&callback=initMap', array(), null, true);
        include HELP_PLATFORM_PLUGIN_DIR . 'templates/job-form.php';
        return ob_get_clean();
    }

    /**
     * 处理任务提交
     */
    public static function handle_job_submission() {
        check_ajax_referer('help-platform-nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('请先登录', 'help-platform'));
        }

        $user_id = get_current_user_id();
        
        // 检查用户是否已认证
        if (!Help_Platform_Verify::is_user_verified($user_id)) {
            wp_send_json_error(__('请先完成实名认证', 'help-platform'));
        }

        // 验证必填字段
        $required_fields = array('title', 'description');
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                wp_send_json_error(sprintf(__('请填写%s', 'help-platform'), $field));
            }
        }

        // 处理图片上传
        $job_images = array();
        if (!empty($_FILES['job_images']['name'][0])) {
            foreach ($_FILES['job_images']['name'] as $key => $value) {
                if (!empty($value)) {
                    $file = array(
                        'name' => $_FILES['job_images']['name'][$key],
                        'type' => $_FILES['job_images']['type'][$key],
                        'tmp_name' => $_FILES['job_images']['tmp_name'][$key],
                        'error' => $_FILES['job_images']['error'][$key],
                        'size' => $_FILES['job_images']['size'][$key]
                    );
                    $_FILES['job_image'] = $file;
                    
                    $attachment_id = self::handle_image_upload('job_image');
                    if (is_wp_error($attachment_id)) {
                        wp_send_json_error($attachment_id->get_error_message());
                    }
                    $job_images[] = $attachment_id;
                }
            }
        }

        // 创建任务
        $job_id = wp_insert_post(array(
            'post_title' => sanitize_text_field($_POST['title']),
            'post_content' => wp_kses_post($_POST['description']),
            'post_type' => 'help_job',
            'post_status' => 'pending',
            'post_author' => $user_id,
        ));

        if (is_wp_error($job_id)) {
            wp_send_json_error(__('创建任务失败', 'help-platform'));
        }

        // 保存任务元数据
        if (!empty($_POST['latitude']) && !empty($_POST['longitude'])) {
            update_post_meta($job_id, '_latitude', floatval($_POST['latitude']));
            update_post_meta($job_id, '_longitude', floatval($_POST['longitude']));
        }

        if (!empty($job_images)) {
            update_post_meta($job_id, '_job_images', $job_images);
            set_post_thumbnail($job_id, $job_images[0]); // 设置第一张图片为特色图片
        }

        // 发送通知邮件给管理员
        $settings = Help_Platform::get_settings();
        $admin_email = $settings['notification_email'];
        $subject = sprintf(__('新的任务发布申请 - %s', 'help-platform'), $_POST['title']);
        $message = self::get_job_notification_email($job_id);
        Help_Platform::send_notification($admin_email, $subject, $message);

        wp_send_json_success(__('任务已提交，请等待审核', 'help-platform'));
    }

    /**
     * 处理任务状态变更
     */
    public static function handle_job_status_change($new_status, $old_status, $post) {
        if ($post->post_type !== 'help_job' || $new_status === $old_status) {
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
            $subject = __('您的任务已通过审核', 'help-platform');
            $message = self::get_job_approved_email($post);
        } elseif ($new_status === 'private') {
            $subject = __('您的任务未通过审核', 'help-platform');
            $message = self::get_job_rejected_email($post);
        }

        if ($subject && $message) {
            Help_Platform::send_notification($user->user_email, $subject, $message);
        }
    }

    /**
     * 添加任务元数据框
     */
    public static function add_job_meta_boxes() {
        add_meta_box(
            'help_job_location',
            __('任务位置', 'help-platform'),
            array(__CLASS__, 'render_location_meta_box'),
            'help_job',
            'side',
            'default'
        );

        add_meta_box(
            'help_job_images',
            __('任务图片', 'help-platform'),
            array(__CLASS__, 'render_images_meta_box'),
            'help_job',
            'side',
            'default'
        );
    }

    /**
     * 渲染位置元数据框
     */
    public static function render_location_meta_box($post) {
        $latitude = get_post_meta($post->ID, '_latitude', true);
        $longitude = get_post_meta($post->ID, '_longitude', true);
        ?>
        <p>
            <label for="job_latitude"><?php _e('纬度:', 'help-platform'); ?></label>
            <input type="text" id="job_latitude" name="job_latitude" value="<?php echo esc_attr($latitude); ?>" readonly>
        </p>
        <p>
            <label for="job_longitude"><?php _e('经度:', 'help-platform'); ?></label>
            <input type="text" id="job_longitude" name="job_longitude" value="<?php echo esc_attr($longitude); ?>" readonly>
        </p>
        <?php if ($latitude && $longitude): ?>
        <div id="job_map" style="height: 200px;"></div>
        <script>
            function initJobMap() {
                var map = new google.maps.Map(document.getElementById('job_map'), {
                    center: {lat: <?php echo floatval($latitude); ?>, lng: <?php echo floatval($longitude); ?>},
                    zoom: 15
                });
                new google.maps.Marker({
                    position: {lat: <?php echo floatval($latitude); ?>, lng: <?php echo floatval($longitude); ?>},
                    map: map
                });
            }
            if (typeof google !== 'undefined' && google.maps) {
                initJobMap();
            } else {
                window.addEventListener('load', function() {
                    if (typeof google !== 'undefined' && google.maps) {
                        initJobMap();
                    }
                });
            }
        </script>
        <?php endif;
    }

    /**
     * 渲染图片元数据框
     */
    public static function render_images_meta_box($post) {
        $images = get_post_meta($post->ID, '_job_images', true);
        if (!empty($images)) {
            echo '<div class="job-images-preview">';
            foreach ($images as $image_id) {
                $image = wp_get_attachment_image($image_id, 'thumbnail');
                if ($image) {
                    echo '<div class="job-image">' . $image . '</div>';
                }
            }
            echo '</div>';
        } else {
            echo '<p>' . __('暂无图片', 'help-platform') . '</p>';
        }
    }

    /**
     * 保存任务元数据
     */
    public static function save_job_meta($post_id) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (isset($_POST['job_latitude']) && isset($_POST['job_longitude'])) {
            update_post_meta($post_id, '_latitude', floatval($_POST['job_latitude']));
            update_post_meta($post_id, '_longitude', floatval($_POST['job_longitude']));
        }
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
     * 获取 Google Maps API Key
     */
    private static function get_google_maps_api_key() {
        return get_option('help_platform_google_maps_api_key', '');
    }

    /**
     * 获取任务通知邮件内容
     */
    private static function get_job_notification_email($job_id) {
        $post = get_post($job_id);
        $user = get_userdata($post->post_author);
        
        ob_start();
        include HELP_PLATFORM_PLUGIN_DIR . 'templates/emails/job-notification.php';
        return ob_get_clean();
    }

    /**
     * 获取任务通过邮件内容
     */
    private static function get_job_approved_email($post) {
        $user = get_userdata($post->post_author);
        
        ob_start();
        include HELP_PLATFORM_PLUGIN_DIR . 'templates/emails/job-approved.php';
        return ob_get_clean();
    }

    /**
     * 获取任务拒绝邮件内容
     */
    private static function get_job_rejected_email($post) {
        $user = get_userdata($post->post_author);
        
        ob_start();
        include HELP_PLATFORM_PLUGIN_DIR . 'templates/emails/job-rejected.php';
        return ob_get_clean();
    }
}

// 初始化
Help_Platform_Job::init(); 