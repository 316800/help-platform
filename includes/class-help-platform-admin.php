/**
 * 添加管理菜单
 */
public function add_admin_menu() {
    // ... existing code ...

    // 添加通信页面
    add_submenu_page(
        'help-platform',
        __('通信', 'help-platform'),
        __('通信', 'help-platform'),
        'read',
        'help-platform-chat',
        array($this, 'render_chat_page')
    );

    // 添加地图页面
    add_submenu_page(
        'help-platform',
        __('位置追踪', 'help-platform'),
        __('位置追踪', 'help-platform'),
        'read',
        'help-platform-map',
        array($this, 'render_map_page')
    );

    // ... existing code ...
}

/**
 * 渲染通信页面
 */
public function render_chat_page() {
    if (!current_user_can('read')) {
        wp_die(__('您没有权限访问此页面', 'help-platform'));
    }

    // 获取任务列表
    $current_user_id = get_current_user_id();
    $args = array(
        'post_type' => 'help_job',
        'posts_per_page' => -1,
        'meta_query' => array(
            'relation' => 'OR',
            array(
                'key' => '_worker_id',
                'value' => $current_user_id,
                'compare' => '='
            ),
            array(
                'key' => '_post_author',
                'value' => $current_user_id,
                'compare' => '='
            )
        )
    );
    $jobs = get_posts($args);

    // 如果有任务ID参数，显示聊天界面
    if (isset($_GET['job_id'])) {
        include HELP_PLATFORM_PLUGIN_DIR . 'templates/communication/chat.php';
        return;
    }

    // 否则显示任务列表
    ?>
    <div class="wrap">
        <h1><?php _e('通信', 'help-platform'); ?></h1>
        
        <?php if ($jobs) : ?>
            <div class="job-list">
                <?php foreach ($jobs as $job) : 
                    $other_user_id = $current_user_id === $job->post_author ? 
                        get_post_meta($job->ID, '_worker_id', true) : 
                        $job->post_author;
                    $other_user = get_userdata($other_user_id);
                    
                    // 获取最新消息
                    global $wpdb;
                    $latest_message = $wpdb->get_row($wpdb->prepare(
                        "SELECT m.*, 
                            s.display_name as sender_name,
                            r.display_name as receiver_name
                        FROM {$wpdb->prefix}help_messages m
                        LEFT JOIN {$wpdb->users} s ON m.sender_id = s.ID
                        LEFT JOIN {$wpdb->users} r ON m.receiver_id = r.ID
                        WHERE m.job_id = %d
                        ORDER BY m.created_at DESC
                        LIMIT 1",
                        $job->ID
                    ));

                    // 获取未读消息数
                    $unread_count = $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*)
                        FROM {$wpdb->prefix}help_messages
                        WHERE job_id = %d
                        AND receiver_id = %d
                        AND is_read = 0",
                        $job->ID,
                        $current_user_id
                    ));
                    ?>
                    <div class="job-item">
                        <div class="job-info">
                            <h3>
                                <a href="<?php echo esc_url(add_query_arg('job_id', $job->ID)); ?>">
                                    <?php echo esc_html($job->post_title); ?>
                                </a>
                                <?php if ($unread_count > 0) : ?>
                                    <span class="unread-badge"><?php echo esc_html($unread_count); ?></span>
                                <?php endif; ?>
                            </h3>
                            <div class="job-meta">
                                <?php printf(__('与 %s 的对话', 'help-platform'), esc_html($other_user->display_name)); ?>
                            </div>
                            <?php if ($latest_message) : ?>
                                <div class="latest-message">
                                    <span class="message-sender">
                                        <?php echo esc_html($latest_message->sender_name); ?>:
                                    </span>
                                    <span class="message-content">
                                        <?php 
                                        if ($latest_message->message_type === 'text') {
                                            echo esc_html($latest_message->content);
                                        } else {
                                            _e('[图片]', 'help-platform');
                                        }
                                        ?>
                                    </span>
                                    <span class="message-time">
                                        <?php echo esc_html(date_i18n('Y-m-d H:i:s', strtotime($latest_message->created_at))); ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else : ?>
            <div class="notice notice-info">
                <p><?php _e('暂无相关任务', 'help-platform'); ?></p>
            </div>
        <?php endif; ?>
    </div>

    <style>
    .job-list {
        margin-top: 20px;
    }

    .job-item {
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 4px;
        margin-bottom: 15px;
        padding: 15px;
    }

    .job-item h3 {
        margin: 0 0 10px;
        font-size: 16px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .job-item h3 a {
        text-decoration: none;
        color: #2271b1;
    }

    .unread-badge {
        background: #d63638;
        color: #fff;
        border-radius: 10px;
        padding: 2px 8px;
        font-size: 12px;
        line-height: 1.4;
    }

    .job-meta {
        color: #666;
        font-size: 13px;
        margin-bottom: 10px;
    }

    .latest-message {
        background: #f8f9fa;
        padding: 10px;
        border-radius: 4px;
        font-size: 13px;
        display: flex;
        gap: 5px;
        align-items: baseline;
    }

    .message-sender {
        color: #2271b1;
        font-weight: 500;
    }

    .message-content {
        color: #333;
        flex: 1;
    }

    .message-time {
        color: #666;
        font-size: 12px;
        white-space: nowrap;
    }
    </style>
    <?php
}

/**
 * 渲染地图页面
 */
public function render_map_page() {
    if (!current_user_can('read')) {
        wp_die(__('您没有权限访问此页面', 'help-platform'));
    }

    // 获取任务列表
    $current_user_id = get_current_user_id();
    $args = array(
        'post_type' => 'help_job',
        'posts_per_page' => -1,
        'meta_query' => array(
            'relation' => 'OR',
            array(
                'key' => '_worker_id',
                'value' => $current_user_id,
                'compare' => '='
            ),
            array(
                'key' => '_post_author',
                'value' => $current_user_id,
                'compare' => '='
            )
        )
    );
    $jobs = get_posts($args);

    // 如果有任务ID参数，显示地图界面
    if (isset($_GET['job_id'])) {
        include HELP_PLATFORM_PLUGIN_DIR . 'templates/map/tracking.php';
        return;
    }

    // 否则显示任务列表
    ?>
    <div class="wrap">
        <h1><?php _e('位置追踪', 'help-platform'); ?></h1>
        
        <?php if ($jobs) : ?>
            <div class="job-list">
                <?php foreach ($jobs as $job) : 
                    $other_user_id = $current_user_id === $job->post_author ? 
                        get_post_meta($job->ID, '_worker_id', true) : 
                        $job->post_author;
                    $other_user = get_userdata($other_user_id);
                    
                    // 获取最新位置
                    global $wpdb;
                    $latest_location = $wpdb->get_row($wpdb->prepare(
                        "SELECT *
                        FROM {$wpdb->prefix}help_locations
                        WHERE job_id = %d
                        ORDER BY created_at DESC
                        LIMIT 1",
                        $job->ID
                    ));
                    ?>
                    <div class="job-item">
                        <div class="job-info">
                            <h3>
                                <a href="<?php echo esc_url(add_query_arg('job_id', $job->ID)); ?>">
                                    <?php echo esc_html($job->post_title); ?>
                                </a>
                            </h3>
                            <div class="job-meta">
                                <?php printf(__('工作者：%s', 'help-platform'), esc_html($other_user->display_name)); ?>
                            </div>
                            <?php if ($latest_location) : ?>
                                <div class="location-info">
                                    <div class="info-item">
                                        <span class="label"><?php _e('最后更新：', 'help-platform'); ?></span>
                                        <span class="value">
                                            <?php echo esc_html(date_i18n('Y-m-d H:i:s', strtotime($latest_location->created_at))); ?>
                                        </span>
                                    </div>
                                    <div class="info-item">
                                        <span class="label"><?php _e('位置：', 'help-platform'); ?></span>
                                        <span class="value">
                                            <?php printf(__('%.6f, %.6f', 'help-platform'), $latest_location->latitude, $latest_location->longitude); ?>
                                        </span>
                                    </div>
                                    <div class="info-item">
                                        <span class="label"><?php _e('速度：', 'help-platform'); ?></span>
                                        <span class="value">
                                            <?php printf(__('%.1f km/h', 'help-platform'), $latest_location->speed); ?>
                                        </span>
                                    </div>
                                </div>
                            <?php else : ?>
                                <div class="no-location">
                                    <?php _e('暂无位置数据', 'help-platform'); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else : ?>
            <div class="notice notice-info">
                <p><?php _e('暂无相关任务', 'help-platform'); ?></p>
            </div>
        <?php endif; ?>
    </div>

    <style>
    .job-list {
        margin-top: 20px;
    }

    .job-item {
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 4px;
        margin-bottom: 15px;
        padding: 15px;
    }

    .job-item h3 {
        margin: 0 0 10px;
        font-size: 16px;
    }

    .job-item h3 a {
        text-decoration: none;
        color: #2271b1;
    }

    .job-meta {
        color: #666;
        font-size: 13px;
        margin-bottom: 10px;
    }

    .location-info {
        background: #f8f9fa;
        padding: 10px;
        border-radius: 4px;
        font-size: 13px;
    }

    .location-info .info-item {
        display: flex;
        gap: 5px;
        margin-bottom: 5px;
    }

    .location-info .info-item:last-child {
        margin-bottom: 0;
    }

    .location-info .label {
        color: #666;
        min-width: 80px;
    }

    .location-info .value {
        color: #333;
        font-weight: 500;
    }

    .no-location {
        color: #666;
        font-size: 13px;
        font-style: italic;
    }
    </style>
    <?php
} 