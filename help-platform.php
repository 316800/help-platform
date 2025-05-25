<?php
/**
 * Plugin Name: HELP 全球生活服务平台
 * Plugin URI: https://help-platform.com
 * Description: HELP 全球生活服务平台插件，支持实名认证、任务发布、前端短代码调用与后台审核管理
 * Version: 1.0.2
 * Author: HELP Team
 * Author URI: https://help-platform.com
 * Text Domain: help-platform
 * Domain Path: /languages
 * License: GPL v2 or later
 */

// 防止直接访问
if (!defined('ABSPATH')) {
    exit;
}

// 定义插件常量
define('HELP_PLATFORM_VERSION', '1.0.2');
define('HELP_PLATFORM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('HELP_PLATFORM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('HELP_PLATFORM_PLUGIN_BASENAME', plugin_basename(__FILE__));

// 加载语言文件
function help_platform_load_textdomain() {
    load_plugin_textdomain('help-platform', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('plugins_loaded', 'help_platform_load_textdomain');

// 添加主菜单
function help_platform_add_menu() {
    add_menu_page(
        __('HELP 平台', 'help-platform'),
        __('HELP 平台', 'help-platform'),
        'manage_options',
        'help-platform',
        'help_platform_main_page',
        'dashicons-admin-generic',
        30
    );

    // 添加子菜单
    add_submenu_page(
        'help-platform',
        __('平台设置', 'help-platform'),
        __('平台设置', 'help-platform'),
        'manage_options',
        'help-platform-settings',
        'help_platform_settings_page'
    );
}
add_action('admin_menu', 'help_platform_add_menu');

// 主页面
function help_platform_main_page() {
    echo '<div class="wrap">';
    echo '<h1>' . __('HELP 平台管理', 'help-platform') . '</h1>';
    echo '<p>' . __('欢迎使用 HELP 平台插件。', 'help-platform') . '</p>';
    echo '</div>';
}

// 设置页面
function help_platform_settings_page() {
    if (isset($_POST['help_platform_settings_nonce']) && wp_verify_nonce($_POST['help_platform_settings_nonce'], 'help_platform_settings')) {
        // 保存设置
        update_option('help_platform_settings', array(
            'verify_required' => isset($_POST['verify_required']) ? true : false,
            'job_approval' => isset($_POST['job_approval']) ? true : false,
            'payment_enabled' => isset($_POST['payment_enabled']) ? true : false,
        ));
        echo '<div class="notice notice-success"><p>' . __('设置已保存。', 'help-platform') . '</p></div>';
    }

    $settings = get_option('help_platform_settings', array(
        'verify_required' => true,
        'job_approval' => true,
        'payment_enabled' => false,
    ));

    include HELP_PLATFORM_PLUGIN_DIR . 'templates/admin-settings.php';
}

// 注册自定义文章类型
function help_platform_register_post_types() {
    // 注册实名认证文章类型
    register_post_type('help_verify', array(
        'labels' => array(
            'name' => __('实名认证', 'help-platform'),
            'singular_name' => __('实名认证', 'help-platform'),
            'add_new' => __('新建认证', 'help-platform'),
            'add_new_item' => __('新建认证', 'help-platform'),
            'edit_item' => __('编辑认证', 'help-platform'),
            'view_item' => __('查看认证', 'help-platform'),
        ),
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => 'help-platform',
        'menu_icon' => 'dashicons-id',
        'supports' => array('title', 'author'),
        'capability_type' => 'post',
        'map_meta_cap' => true,
    ));

    // 注册任务文章类型
    register_post_type('help_job', array(
        'labels' => array(
            'name' => __('任务管理', 'help-platform'),
            'singular_name' => __('任务', 'help-platform'),
            'add_new' => __('发布任务', 'help-platform'),
            'add_new_item' => __('发布新任务', 'help-platform'),
            'edit_item' => __('编辑任务', 'help-platform'),
            'view_item' => __('查看任务', 'help-platform'),
        ),
        'public' => true,
        'show_ui' => true,
        'show_in_menu' => 'help-platform',
        'menu_icon' => 'dashicons-list-view',
        'supports' => array('title', 'editor', 'author'),
        'capability_type' => 'post',
        'map_meta_cap' => true,
        'rewrite' => array('slug' => 'jobs'),
    ));
}
add_action('init', 'help_platform_register_post_types');

// 注册自定义状态
function help_platform_register_post_status() {
    register_post_status('pending_verify', array(
        'label' => __('待审核', 'help-platform'),
        'public' => false,
        'exclude_from_search' => true,
        'show_in_admin_all_list' => true,
        'show_in_admin_status_list' => true,
        'label_count' => _n_noop('待审核 <span class="count">(%s)</span>', '待审核 <span class="count">(%s)</span>', 'help-platform'),
    ));
}
add_action('init', 'help_platform_register_post_status');

// 注册短代码
function help_platform_register_shortcodes() {
    add_shortcode('help_verify', 'help_platform_verify_shortcode');
    add_shortcode('help_job', 'help_platform_job_shortcode');
}
add_action('init', 'help_platform_register_shortcodes');

// 实名认证短代码
function help_platform_verify_shortcode($atts) {
    if (!is_user_logged_in()) {
        return '<div class="help-platform-message">' . __('请先登录后再进行实名认证。', 'help-platform') . '</div>';
    }

    $user_id = get_current_user_id();
    $verify_post = get_posts(array(
        'post_type' => 'help_verify',
        'author' => $user_id,
        'posts_per_page' => 1,
    ));

    if (!empty($verify_post)) {
        $status = get_post_status($verify_post[0]->ID);
        if ($status === 'publish') {
            return '<div class="help-platform-message success">' . __('您已完成实名认证。', 'help-platform') . '</div>';
        } elseif ($status === 'pending_verify') {
            return '<div class="help-platform-message warning">' . __('您的认证正在审核中，请耐心等待。', 'help-platform') . '</div>';
        }
    }

    ob_start();
    include HELP_PLATFORM_PLUGIN_DIR . 'templates/verify-form.php';
    return ob_get_clean();
}

// 任务发布短代码
function help_platform_job_shortcode($atts) {
    if (!is_user_logged_in()) {
        return '<div class="help-platform-message">' . __('请先登录后再发布任务。', 'help-platform') . '</div>';
    }

    $settings = get_option('help_platform_settings', array(
        'verify_required' => true,
        'job_approval' => true,
    ));

    if ($settings['verify_required']) {
        $user_id = get_current_user_id();
        $verify_post = get_posts(array(
            'post_type' => 'help_verify',
            'author' => $user_id,
            'post_status' => 'publish',
            'posts_per_page' => 1,
        ));

        if (empty($verify_post)) {
            return '<div class="help-platform-message">' . __('请先完成实名认证后再发布任务。', 'help-platform') . '</div>';
        }
    }

    ob_start();
    include HELP_PLATFORM_PLUGIN_DIR . 'templates/job-form.php';
    return ob_get_clean();
}

// 注册样式和脚本
function help_platform_enqueue_scripts() {
    // 注册样式
    wp_register_style(
        'help-platform-style',
        HELP_PLATFORM_PLUGIN_URL . 'assets/css/style.css',
        array(),
        HELP_PLATFORM_VERSION
    );

    // 注册脚本
    wp_register_script(
        'help-platform-script',
        HELP_PLATFORM_PLUGIN_URL . 'assets/js/script.js',
        array('jquery'),
        HELP_PLATFORM_VERSION,
        true
    );

    // 添加本地化数据
    wp_localize_script('help-platform-script', 'helpPlatform', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('help_platform_nonce'),
        'i18n' => array(
            'confirmDelete' => __('确定要删除吗？', 'help-platform'),
            'submitSuccess' => __('提交成功！', 'help-platform'),
            'submitError' => __('提交失败，请重试。', 'help-platform'),
            'confirmMarkAllRead' => __('确定要将所有消息标记为已读吗？', 'help-platform'),
            'confirmDeleteMessage' => __('确定要删除这条消息吗？', 'help-platform'),
            'confirmDeleteAllMessages' => __('确定要删除所有消息吗？此操作不可恢复。', 'help-platform'),
            'insufficientBalance' => __('余额不足。', 'help-platform'),
        ),
    ));

    // 在短代码页面加载样式和脚本
    global $post;
    if (is_a($post, 'WP_Post') && (
        has_shortcode($post->post_content, 'help_verify') || 
        has_shortcode($post->post_content, 'help_job') ||
        has_shortcode($post->post_content, 'help_dashboard')
    )) {
        wp_enqueue_style('help-platform-style');
        wp_enqueue_script('help-platform-script');
    }
}
add_action('wp_enqueue_scripts', 'help_platform_enqueue_scripts');

// 处理表单提交
function help_platform_handle_form_submit() {
    if (!isset($_POST['help_platform_nonce']) || !wp_verify_nonce($_POST['help_platform_nonce'], 'help_platform_nonce')) {
        wp_send_json_error(__('安全验证失败。', 'help-platform'));
    }

    $action = $_POST['action'] ?? '';
    $user_id = get_current_user_id();

    if (!$user_id) {
        wp_send_json_error(__('请先登录。', 'help-platform'));
    }

    switch ($action) {
        case 'submit_verify':
            $post_data = array(
                'post_title' => sanitize_text_field($_POST['name'] ?? ''),
                'post_type' => 'help_verify',
                'post_status' => 'pending_verify',
                'post_author' => $user_id,
            );

            $post_id = wp_insert_post($post_data);
            if (is_wp_error($post_id)) {
                wp_send_json_error($post_id->get_error_message());
            }

            // 保存认证信息
            update_post_meta($post_id, '_verify_id_card', sanitize_text_field($_POST['id_card'] ?? ''));
            update_post_meta($post_id, '_verify_phone', sanitize_text_field($_POST['phone'] ?? ''));
            update_post_meta($post_id, '_verify_address', sanitize_textarea_field($_POST['address'] ?? ''));

            wp_send_json_success(__('认证信息已提交，请等待审核。', 'help-platform'));
            break;

        case 'submit_job':
            $settings = get_option('help_platform_settings', array('job_approval' => true));
            $post_data = array(
                'post_title' => sanitize_text_field($_POST['title'] ?? ''),
                'post_content' => wp_kses_post($_POST['content'] ?? ''),
                'post_type' => 'help_job',
                'post_status' => $settings['job_approval'] ? 'pending' : 'publish',
                'post_author' => $user_id,
            );

            $post_id = wp_insert_post($post_data);
            if (is_wp_error($post_id)) {
                wp_send_json_error($post_id->get_error_message());
            }

            // 保存任务信息
            update_post_meta($post_id, '_job_budget', floatval($_POST['budget'] ?? 0));
            update_post_meta($post_id, '_job_location', sanitize_text_field($_POST['location'] ?? ''));
            update_post_meta($post_id, '_job_deadline', sanitize_text_field($_POST['deadline'] ?? ''));

            wp_send_json_success($settings['job_approval'] ? 
                __('任务已提交，请等待审核。', 'help-platform') : 
                __('任务已发布。', 'help-platform')
            );
            break;

        default:
            wp_send_json_error(__('无效的操作。', 'help-platform'));
    }
}
add_action('wp_ajax_help_platform_submit', 'help_platform_handle_form_submit');

// 激活插件时的操作
function help_platform_activate() {
    // 创建必要的目录
    $upload_dir = wp_upload_dir();
    $verify_dir = $upload_dir['basedir'] . '/help-platform/verify';
    $job_dir = $upload_dir['basedir'] . '/help-platform/job';

    if (!file_exists($verify_dir)) {
        wp_mkdir_p($verify_dir);
    }
    if (!file_exists($job_dir)) {
        wp_mkdir_p($job_dir);
    }

    // 添加默认设置
    add_option('help_platform_settings', array(
        'verify_required' => true,
        'job_approval' => true,
        'payment_enabled' => false,
    ));

    // 刷新重写规则
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'help_platform_activate');

// 停用插件时的操作
function help_platform_deactivate() {
    // 刷新重写规则
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'help_platform_deactivate');

// 注册消息文章类型
function help_platform_register_message_post_type() {
    register_post_type('help_message', array(
        'labels' => array(
            'name' => __('消息', 'help-platform'),
            'singular_name' => __('消息', 'help-platform'),
            'menu_name' => __('消息', 'help-platform'),
            'all_items' => __('所有消息', 'help-platform'),
            'add_new' => __('发送消息', 'help-platform'),
            'add_new_item' => __('发送新消息', 'help-platform'),
            'edit_item' => __('编辑消息', 'help-platform'),
        ),
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => 'help-platform',
        'supports' => array('title', 'editor'),
        'capability_type' => 'post',
        'map_meta_cap' => true,
        'hierarchical' => false,
        'rewrite' => false,
        'query_var' => false,
    ));
}
add_action('init', 'help_platform_register_message_post_type');

// 获取用户消息
function help_platform_get_user_messages($user_id, $args = array()) {
    $defaults = array(
        'post_type' => 'help_message',
        'post_status' => 'publish',
        'author' => $user_id,
        'posts_per_page' => 20,
        'paged' => get_query_var('paged') ? get_query_var('paged') : 1,
        'orderby' => 'date',
        'order' => 'DESC',
    );

    $args = wp_parse_args($args, $defaults);
    return new WP_Query($args);
}

// 发送消息
function help_platform_send_message($user_id, $title, $content, $type = 'system') {
    $post_data = array(
        'post_title' => $title,
        'post_content' => $content,
        'post_type' => 'help_message',
        'post_status' => 'publish',
        'post_author' => $user_id,
    );

    $post_id = wp_insert_post($post_data);
    if (is_wp_error($post_id)) {
        return $post_id;
    }

    // 保存消息类型
    update_post_meta($post_id, 'message_type', $type);
    update_post_meta($post_id, 'is_read', false);

    // 发送邮件通知
    $user = get_userdata($user_id);
    if ($user && $user->user_email) {
        $subject = sprintf(__('[%s] %s', 'help-platform'), get_bloginfo('name'), $title);
        $message = wpautop($content);
        $headers = array('Content-Type: text/html; charset=UTF-8');
        wp_mail($user->user_email, $subject, $message, $headers);
    }

    return $post_id;
}

// 标记消息为已读
function help_platform_mark_message_read($message_id) {
    return update_post_meta($message_id, 'is_read', true);
}

// 标记所有消息为已读
function help_platform_mark_all_messages_read($user_id) {
    $messages = help_platform_get_user_messages($user_id, array('posts_per_page' => -1));
    if ($messages->have_posts()) {
        while ($messages->have_posts()) {
            $messages->the_post();
            update_post_meta(get_the_ID(), 'is_read', true);
        }
        wp_reset_postdata();
        return true;
    }
    return false;
}

// 删除消息
function help_platform_delete_message($message_id) {
    $post = get_post($message_id);
    if (!$post || $post->post_type !== 'help_message') {
        return false;
    }
    return wp_delete_post($message_id, true);
}

// 删除用户所有消息
function help_platform_delete_all_messages($user_id) {
    $messages = help_platform_get_user_messages($user_id, array('posts_per_page' => -1));
    if ($messages->have_posts()) {
        while ($messages->have_posts()) {
            $messages->the_post();
            wp_delete_post(get_the_ID(), true);
        }
        wp_reset_postdata();
        return true;
    }
    return false;
}

// 处理消息相关的AJAX请求
function help_platform_handle_message_ajax() {
    check_ajax_referer('help_platform_nonce', 'nonce');

    $action = $_POST['action'] ?? '';
    $user_id = get_current_user_id();

    if (!$user_id) {
        wp_send_json_error(__('请先登录。', 'help-platform'));
    }

    switch ($action) {
        case 'help_platform_filter_messages':
            $type = sanitize_text_field($_POST['type'] ?? '');
            $status = sanitize_text_field($_POST['status'] ?? '');
            
            $args = array(
                'posts_per_page' => 20,
                'paged' => get_query_var('paged') ? get_query_var('paged') : 1,
            );

            if ($type) {
                $args['meta_query'][] = array(
                    'key' => 'message_type',
                    'value' => $type,
                );
            }

            if ($status === 'unread') {
                $args['meta_query'][] = array(
                    'key' => 'is_read',
                    'value' => false,
                );
            } elseif ($status === 'read') {
                $args['meta_query'][] = array(
                    'key' => 'is_read',
                    'value' => true,
                );
            }

            $messages = help_platform_get_user_messages($user_id, $args);
            ob_start();
            if ($messages->have_posts()) {
                while ($messages->have_posts()) {
                    $messages->the_post();
                    get_template_part('templates/message-item');
                }
                wp_reset_postdata();

                echo '<div class="pagination">';
                echo paginate_links(array(
                    'total' => $messages->max_num_pages,
                    'current' => get_query_var('paged') ? get_query_var('paged') : 1,
                    'prev_text' => '&laquo;',
                    'next_text' => '&raquo;',
                ));
                echo '</div>';
            } else {
                echo '<div class="no-messages">';
                echo '<p>' . __('暂无消息。', 'help-platform') . '</p>';
                echo '</div>';
            }
            wp_send_json_success(ob_get_clean());
            break;

        case 'help_platform_mark_all_read':
            if (help_platform_mark_all_messages_read($user_id)) {
                wp_send_json_success(__('所有消息已标记为已读。', 'help-platform'));
            } else {
                wp_send_json_error(__('操作失败，请重试。', 'help-platform'));
            }
            break;

        case 'help_platform_delete_message':
            $message_id = intval($_POST['message_id'] ?? 0);
            if (!$message_id) {
                wp_send_json_error(__('无效的消息ID。', 'help-platform'));
            }

            $post = get_post($message_id);
            if (!$post || $post->post_author != $user_id) {
                wp_send_json_error(__('您没有权限删除此消息。', 'help-platform'));
            }

            if (help_platform_delete_message($message_id)) {
                wp_send_json_success(__('消息已删除。', 'help-platform'));
            } else {
                wp_send_json_error(__('删除失败，请重试。', 'help-platform'));
            }
            break;

        case 'help_platform_delete_all_messages':
            if (help_platform_delete_all_messages($user_id)) {
                wp_send_json_success(__('所有消息已删除。', 'help-platform'));
            } else {
                wp_send_json_error(__('删除失败，请重试。', 'help-platform'));
            }
            break;

        default:
            wp_send_json_error(__('无效的操作。', 'help-platform'));
    }
}
add_action('wp_ajax_help_platform_filter_messages', 'help_platform_handle_message_ajax');
add_action('wp_ajax_help_platform_mark_all_read', 'help_platform_handle_message_ajax');
add_action('wp_ajax_help_platform_delete_message', 'help_platform_handle_message_ajax');
add_action('wp_ajax_help_platform_delete_all_messages', 'help_platform_handle_message_ajax');

// 注册帮助文档页面
function help_platform_register_help_page() {
    add_rewrite_rule(
        'help-platform/help/?([^/]*)/?$',
        'index.php?help_platform_help=1&section=$matches[1]',
        'top'
    );
    add_rewrite_tag('%help_platform_help%', '([^&]+)');
    add_rewrite_tag('%section%', '([^&]+)');
}
add_action('init', 'help_platform_register_help_page');

// 添加查询变量
function help_platform_add_query_vars($vars) {
    $vars[] = 'help_platform_help';
    $vars[] = 'section';
    return $vars;
}
add_filter('query_vars', 'help_platform_add_query_vars');

// 加载帮助文档模板
function help_platform_template_include($template) {
    if (get_query_var('help_platform_help')) {
        $help_template = HELP_PLATFORM_PLUGIN_DIR . 'templates/help-docs.php';
        if (file_exists($help_template)) {
            return $help_template;
        }
    }
    return $template;
}
add_filter('template_include', 'help_platform_template_include');

// 处理帮助按钮点击
function help_platform_handle_help_click() {
    if (isset($_GET['help'])) {
        $section = sanitize_text_field($_GET['help']);
        wp_redirect(home_url('help-platform/help/' . $section));
        exit;
    }
}
add_action('template_redirect', 'help_platform_handle_help_click'); 