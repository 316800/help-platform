/**
 * 发送消息
 */
public function send_message() {
    check_ajax_referer('help_platform_send_message', '_wpnonce');

    $job_id = isset($_POST['job_id']) ? intval($_POST['job_id']) : 0;
    $receiver_id = isset($_POST['receiver_id']) ? intval($_POST['receiver_id']) : 0;
    $message = isset($_POST['message']) ? sanitize_textarea_field($_POST['message']) : '';

    if (!$job_id || !$receiver_id || !$message) {
        wp_send_json_error(array('message' => __('参数错误', 'help-platform')));
    }

    $current_user_id = get_current_user_id();
    if (!$current_user_id) {
        wp_send_json_error(array('message' => __('请先登录', 'help-platform')));
    }

    // 验证任务是否存在且用户是否有权限
    $job = get_post($job_id);
    if (!$job || $job->post_type !== 'help_job') {
        wp_send_json_error(array('message' => __('任务不存在', 'help-platform')));
    }

    // 验证发送者是否为任务相关方
    if ($current_user_id !== $job->post_author && $current_user_id !== get_post_meta($job_id, '_worker_id', true)) {
        wp_send_json_error(array('message' => __('您没有权限发送消息', 'help-platform')));
    }

    // 验证接收者是否为任务相关方
    if ($receiver_id !== $job->post_author && $receiver_id !== get_post_meta($job_id, '_worker_id', true)) {
        wp_send_json_error(array('message' => __('无效的接收者', 'help-platform')));
    }

    global $wpdb;
    $result = $wpdb->insert(
        $wpdb->prefix . 'help_messages',
        array(
            'job_id' => $job_id,
            'sender_id' => $current_user_id,
            'receiver_id' => $receiver_id,
            'message_type' => 'text',
            'content' => $message,
            'is_read' => 0,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ),
        array('%d', '%d', '%d', '%s', '%s', '%d', '%s', '%s')
    );

    if ($result === false) {
        wp_send_json_error(array('message' => __('发送失败，请重试', 'help-platform')));
    }

    // 发送邮件通知
    $receiver = get_userdata($receiver_id);
    $sender = get_userdata($current_user_id);
    $subject = sprintf(__('新消息：来自 %s', 'help-platform'), $sender->display_name);
    $message = sprintf(
        __('您收到来自 %s 的新消息：%s', 'help-platform'),
        $sender->display_name,
        "\n\n" . $message . "\n\n" . 
        sprintf(__('查看消息：%s', 'help-platform'), admin_url('admin.php?page=help-platform-chat&job_id=' . $job_id))
    );
    wp_mail($receiver->user_email, $subject, $message);

    wp_send_json_success();
}

/**
 * 上传图片消息
 */
public function upload_image() {
    check_ajax_referer('help_platform_send_message', '_wpnonce');

    $job_id = isset($_POST['job_id']) ? intval($_POST['job_id']) : 0;
    $receiver_id = isset($_POST['receiver_id']) ? intval($_POST['receiver_id']) : 0;

    if (!$job_id || !$receiver_id || !isset($_FILES['image'])) {
        wp_send_json_error(array('message' => __('参数错误', 'help-platform')));
    }

    $current_user_id = get_current_user_id();
    if (!$current_user_id) {
        wp_send_json_error(array('message' => __('请先登录', 'help-platform')));
    }

    // 验证任务是否存在且用户是否有权限
    $job = get_post($job_id);
    if (!$job || $job->post_type !== 'help_job') {
        wp_send_json_error(array('message' => __('任务不存在', 'help-platform')));
    }

    // 验证发送者是否为任务相关方
    if ($current_user_id !== $job->post_author && $current_user_id !== get_post_meta($job_id, '_worker_id', true)) {
        wp_send_json_error(array('message' => __('您没有权限发送消息', 'help-platform')));
    }

    // 验证接收者是否为任务相关方
    if ($receiver_id !== $job->post_author && $receiver_id !== get_post_meta($job_id, '_worker_id', true)) {
        wp_send_json_error(array('message' => __('无效的接收者', 'help-platform')));
    }

    // 处理图片上传
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');

    $attachment_id = media_handle_upload('image', $job_id);
    if (is_wp_error($attachment_id)) {
        wp_send_json_error(array('message' => $attachment_id->get_error_message()));
    }

    $attachment_url = wp_get_attachment_url($attachment_id);

    global $wpdb;
    $result = $wpdb->insert(
        $wpdb->prefix . 'help_messages',
        array(
            'job_id' => $job_id,
            'sender_id' => $current_user_id,
            'receiver_id' => $receiver_id,
            'message_type' => 'image',
            'content' => '',
            'attachment_url' => $attachment_url,
            'is_read' => 0,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ),
        array('%d', '%d', '%d', '%s', '%s', '%s', '%d', '%s', '%s')
    );

    if ($result === false) {
        wp_delete_attachment($attachment_id, true);
        wp_send_json_error(array('message' => __('发送失败，请重试', 'help-platform')));
    }

    // 发送邮件通知
    $receiver = get_userdata($receiver_id);
    $sender = get_userdata($current_user_id);
    $subject = sprintf(__('新图片消息：来自 %s', 'help-platform'), $sender->display_name);
    $message = sprintf(
        __('您收到来自 %s 的新图片消息。', 'help-platform'),
        $sender->display_name
    ) . "\n\n" . 
    sprintf(__('查看消息：%s', 'help-platform'), admin_url('admin.php?page=help-platform-chat&job_id=' . $job_id));
    wp_mail($receiver->user_email, $subject, $message);

    wp_send_json_success();
}

/**
 * 获取消息列表
 */
public function get_messages() {
    check_ajax_referer('help_platform_send_message', '_wpnonce');

    $job_id = isset($_POST['job_id']) ? intval($_POST['job_id']) : 0;
    if (!$job_id) {
        wp_send_json_error(array('message' => __('参数错误', 'help-platform')));
    }

    $current_user_id = get_current_user_id();
    if (!$current_user_id) {
        wp_send_json_error(array('message' => __('请先登录', 'help-platform')));
    }

    // 验证任务是否存在且用户是否有权限
    $job = get_post($job_id);
    if (!$job || $job->post_type !== 'help_job') {
        wp_send_json_error(array('message' => __('任务不存在', 'help-platform')));
    }

    // 验证用户是否为任务相关方
    if ($current_user_id !== $job->post_author && $current_user_id !== get_post_meta($job_id, '_worker_id', true)) {
        wp_send_json_error(array('message' => __('您没有权限查看消息', 'help-platform')));
    }

    global $wpdb;
    $messages = $wpdb->get_results($wpdb->prepare(
        "SELECT m.*, 
            s.display_name as sender_name,
            r.display_name as receiver_name
        FROM {$wpdb->prefix}help_messages m
        LEFT JOIN {$wpdb->users} s ON m.sender_id = s.ID
        LEFT JOIN {$wpdb->users} r ON m.receiver_id = r.ID
        WHERE m.job_id = %d
        ORDER BY m.created_at ASC",
        $job_id
    ));

    // 标记消息为已读
    $wpdb->update(
        $wpdb->prefix . 'help_messages',
        array('is_read' => 1),
        array(
            'job_id' => $job_id,
            'receiver_id' => $current_user_id,
            'is_read' => 0
        ),
        array('%d'),
        array('%d', '%d', '%d')
    );

    ob_start();
    if ($messages) {
        foreach ($messages as $message) {
            ?>
            <div class="message <?php echo $message->sender_id === $current_user_id ? 'message-sent' : 'message-received'; ?>">
                <div class="message-content">
                    <?php if ($message->message_type === 'text') : ?>
                        <div class="message-text"><?php echo esc_html($message->content); ?></div>
                    <?php elseif ($message->message_type === 'image') : ?>
                        <div class="message-image">
                            <img src="<?php echo esc_url($message->attachment_url); ?>" alt="<?php _e('图片消息', 'help-platform'); ?>">
                        </div>
                    <?php endif; ?>
                    <div class="message-meta">
                        <span class="message-time"><?php echo esc_html(date_i18n('Y-m-d H:i:s', strtotime($message->created_at))); ?></span>
                        <?php if ($message->sender_id === $current_user_id) : ?>
                            <span class="message-status <?php echo $message->is_read ? 'read' : 'unread'; ?>">
                                <?php echo $message->is_read ? __('已读', 'help-platform') : __('未读', 'help-platform'); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php
        }
    } else {
        ?>
        <div class="no-messages">
            <?php _e('暂无消息记录', 'help-platform'); ?>
        </div>
        <?php
    }
    $html = ob_get_clean();

    wp_send_json_success(array('html' => $html));
}

/**
 * 更新位置信息
 */
public function update_location() {
    check_ajax_referer('help_platform_update_location', '_wpnonce');

    $job_id = isset($_POST['job_id']) ? intval($_POST['job_id']) : 0;
    $latitude = isset($_POST['latitude']) ? floatval($_POST['latitude']) : 0;
    $longitude = isset($_POST['longitude']) ? floatval($_POST['longitude']) : 0;
    $accuracy = isset($_POST['accuracy']) ? floatval($_POST['accuracy']) : 0;
    $speed = isset($_POST['speed']) ? floatval($_POST['speed']) : 0;
    $heading = isset($_POST['heading']) ? floatval($_POST['heading']) : 0;

    if (!$job_id || !$latitude || !$longitude) {
        wp_send_json_error(array('message' => __('参数错误', 'help-platform')));
    }

    $current_user_id = get_current_user_id();
    if (!$current_user_id) {
        wp_send_json_error(array('message' => __('请先登录', 'help-platform')));
    }

    // 验证任务是否存在且用户是否有权限
    $job = get_post($job_id);
    if (!$job || $job->post_type !== 'help_job') {
        wp_send_json_error(array('message' => __('任务不存在', 'help-platform')));
    }

    // 验证用户是否为任务工作者
    if ($current_user_id !== get_post_meta($job_id, '_worker_id', true)) {
        wp_send_json_error(array('message' => __('您没有权限更新位置', 'help-platform')));
    }

    global $wpdb;
    $result = $wpdb->insert(
        $wpdb->prefix . 'help_locations',
        array(
            'worker_id' => $current_user_id,
            'job_id' => $job_id,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'accuracy' => $accuracy,
            'speed' => $speed,
            'heading' => $heading,
            'status' => 'active',
            'created_at' => current_time('mysql')
        ),
        array('%d', '%d', '%f', '%f', '%f', '%f', '%f', '%s', '%s')
    );

    if ($result === false) {
        wp_send_json_error(array('message' => __('更新失败，请重试', 'help-platform')));
    }

    wp_send_json_success();
}

/**
 * 获取位置信息
 */
public function get_location() {
    check_ajax_referer('help_platform_get_location', '_wpnonce');

    $job_id = isset($_POST['job_id']) ? intval($_POST['job_id']) : 0;
    if (!$job_id) {
        wp_send_json_error(array('message' => __('参数错误', 'help-platform')));
    }

    $current_user_id = get_current_user_id();
    if (!$current_user_id) {
        wp_send_json_error(array('message' => __('请先登录', 'help-platform')));
    }

    // 验证任务是否存在且用户是否有权限
    $job = get_post($job_id);
    if (!$job || $job->post_type !== 'help_job') {
        wp_send_json_error(array('message' => __('任务不存在', 'help-platform')));
    }

    // 验证用户是否为任务相关方
    if ($current_user_id !== $job->post_author && $current_user_id !== get_post_meta($job_id, '_worker_id', true)) {
        wp_send_json_error(array('message' => __('您没有权限查看位置', 'help-platform')));
    }

    global $wpdb;
    $location = $wpdb->get_row($wpdb->prepare(
        "SELECT *
        FROM {$wpdb->prefix}help_locations
        WHERE job_id = %d
        ORDER BY created_at DESC
        LIMIT 1",
        $job_id
    ));

    if (!$location) {
        wp_send_json_error(array('message' => __('暂无位置数据', 'help-platform')));
    }

    wp_send_json_success(array('location' => $location));
}

public function submit_verify() {
    check_ajax_referer('help_verify_submit', 'help_verify_nonce');

    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error(array('message' => __('请先登录', 'help-platform')));
    }

    // 验证用户权限
    if (!current_user_can('help_worker')) {
        wp_send_json_error(array('message' => __('您没有权限提交认证', 'help-platform')));
    }

    // 验证必填字段
    $required_fields = array(
        'verify_name',
        'verify_country',
        'verify_id_type',
        'verify_id_number',
        'verify_phone_code',
        'verify_phone',
        'verify_email',
        'verify_profession_category',
        'verify_experience',
        'verify_introduction'
    );

    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            wp_send_json_error(array('message' => sprintf(__('请填写%s', 'help-platform'), $field)));
        }
    }

    // 验证专业领域相关字段
    $category = sanitize_text_field($_POST['verify_profession_category']);
    if ($category !== 'other') {
        if (empty($_POST['verify_profession_subcategory'])) {
            wp_send_json_error(array('message' => __('请选择服务小类', 'help-platform')));
        }
        if (empty($_POST['verify_profession_specific'])) {
            wp_send_json_error(array('message' => __('请选择具体服务', 'help-platform')));
        }
        if (empty($_POST['verify_skills'])) {
            wp_send_json_error(array('message' => __('请至少选择一项专业技能', 'help-platform')));
        }
    }

    // 验证邮箱格式
    if (!is_email($_POST['verify_email'])) {
        wp_send_json_error(array('message' => __('请输入有效的电子邮箱', 'help-platform')));
    }

    // 验证手机号格式（根据国家代码）
    $phone_code = sanitize_text_field($_POST['verify_phone_code']);
    $phone = sanitize_text_field($_POST['verify_phone']);
    if (!$this->validate_phone_number($phone_code, $phone)) {
        wp_send_json_error(array('message' => __('请输入有效的电话号码', 'help-platform')));
    }

    // 处理文件上传
    $upload_dir = wp_upload_dir();
    $verify_dir = $upload_dir['basedir'] . '/help-verify/' . $user_id;
    if (!file_exists($verify_dir)) {
        wp_mkdir_p($verify_dir);
    }

    // 处理证件照片
    $id_photos = array('id_front', 'id_back', 'id_selfie');
    $uploaded_files = array();
    foreach ($id_photos as $photo) {
        if (!empty($_FILES[$photo]) && $_FILES[$photo]['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES[$photo];
            $file_info = wp_check_filetype($file['name']);
            
            if (!$file_info['ext']) {
                wp_send_json_error(array('message' => __('不支持的文件类型', 'help-platform')));
            }

            $file_name = $photo . '_' . time() . '.' . $file_info['ext'];
            $file_path = $verify_dir . '/' . $file_name;
            
            if (move_uploaded_file($file['tmp_name'], $file_path)) {
                $uploaded_files[$photo] = $upload_dir['baseurl'] . '/help-verify/' . $user_id . '/' . $file_name;
            } else {
                wp_send_json_error(array('message' => __('文件上传失败', 'help-platform')));
            }
        } else {
            wp_send_json_error(array('message' => __('请上传所有必需的证件照片', 'help-platform')));
        }
    }

    // 处理证书文件
    $certificates = array();
    if (!empty($_FILES['verify_certificates'])) {
        $cert_files = $this->rearray_files($_FILES['verify_certificates']);
        $cert_count = 0;
        
        foreach ($cert_files as $cert) {
            if ($cert['error'] === UPLOAD_ERR_OK && $cert_count < 5) {
                $file_info = wp_check_filetype($cert['name']);
                if (in_array($file_info['ext'], array('pdf', 'jpg', 'jpeg', 'png'))) {
                    $file_name = 'cert_' . time() . '_' . $cert_count . '.' . $file_info['ext'];
                    $file_path = $verify_dir . '/' . $file_name;
                    
                    if (move_uploaded_file($cert['tmp_name'], $file_path)) {
                        $certificates[] = array(
                            'name' => $cert['name'],
                            'url' => $upload_dir['baseurl'] . '/help-verify/' . $user_id . '/' . $file_name
                        );
                        $cert_count++;
                    }
                }
            }
        }
    }

    // 准备验证数据
    $verify_data = array(
        'name' => sanitize_text_field($_POST['verify_name']),
        'country' => sanitize_text_field($_POST['verify_country']),
        'id_type' => sanitize_text_field($_POST['verify_id_type']),
        'id_number' => sanitize_text_field($_POST['verify_id_number']),
        'phone_code' => $phone_code,
        'phone' => $phone,
        'email' => sanitize_email($_POST['verify_email']),
        'profession' => array(
            'category' => $category,
            'subcategory' => sanitize_text_field($_POST['verify_profession_subcategory'] ?? ''),
            'specific' => sanitize_text_field($_POST['verify_profession_specific'] ?? ''),
            'skills' => isset($_POST['verify_skills']) ? array_map('sanitize_text_field', $_POST['verify_skills']) : array()
        ),
        'experience' => sanitize_text_field($_POST['verify_experience']),
        'introduction' => sanitize_textarea_field($_POST['verify_introduction']),
        'certificates' => $certificates,
        'id_front' => $uploaded_files['id_front'],
        'id_back' => $uploaded_files['id_back'],
        'id_selfie' => $uploaded_files['id_selfie'],
        'submitted_at' => current_time('mysql'),
        'status' => 'pending'
    );

    // 保存验证数据
    update_user_meta($user_id, 'help_verify_data', $verify_data);
    update_user_meta($user_id, 'help_verify_status', 'pending');
    delete_user_meta($user_id, 'help_verify_reason');

    // 发送通知邮件给管理员
    $admin_email = get_option('admin_email');
    $subject = sprintf(__('[%s] 新的实名认证申请', 'help-platform'), get_bloginfo('name'));
    $message = sprintf(
        __('用户 %s 提交了实名认证申请，请登录后台审核。', 'help-platform'),
        $verify_data['name']
    );
    wp_mail($admin_email, $subject, $message);

    wp_send_json_success(array('message' => __('认证申请已提交，请等待审核', 'help-platform')));
}

private function validate_phone_number($country_code, $phone) {
    // 根据不同国家代码验证手机号格式
    switch ($country_code) {
        case '+86': // 中国
            return preg_match('/^1[3-9]\d{9}$/', $phone);
        case '+1': // 美国/加拿大
            return preg_match('/^[2-9]\d{9}$/', $phone);
        case '+34': // 西班牙
            return preg_match('/^[6-9]\d{8}$/', $phone);
        default:
            // 其他国家的手机号验证规则
            return preg_match('/^\d{6,15}$/', $phone);
    }
}

private function rearray_files($files) {
    $rearrayed = array();
    foreach ($files as $key1 => $value1) {
        foreach ($value1 as $key2 => $value2) {
            $rearrayed[$key2][$key1] = $value2;
        }
    }
    return $rearrayed;
} 