<?php
/**
 * Plugin Name: HELP 全球生活服务平台
 * Plugin URI: https://help-platform.com
 * Description: HELP 全球生活服务平台插件，支持实名认证、任务发布、前端短代码调用与后台审核管理
 * Version: 1.0.0
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
define('HELP_PLATFORM_VERSION', '1.0.0');
define('HELP_PLATFORM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('HELP_PLATFORM_PLUGIN_URL', plugin_dir_url(__FILE__));

// 加载语言文件
function help_platform_load_textdomain() {
    load_plugin_textdomain('help-platform', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('plugins_loaded', 'help_platform_load_textdomain');

// 注册自定义文章类型
function help_platform_register_post_types() {
    // 注册实名认证文章类型
    register_post_type('help_verify', array(
        'labels' => array(
            'name' => __('实名认证', 'help-platform'),
            'singular_name' => __('实名认证', 'help-platform'),
            'menu_name' => __('实名认证', 'help-platform'),
            'all_items' => __('所有认证', 'help-platform'),
            'add_new' => __('添加认证', 'help-platform'),
            'add_new_item' => __('添加新认证', 'help-platform'),
            'edit_item' => __('编辑认证', 'help-platform'),
        ),
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_icon' => 'dashicons-id',
        'supports' => array('title', 'author'),
        'capability_type' => 'post',
        'map_meta_cap' => true,
        'hierarchical' => false,
        'rewrite' => false,
        'query_var' => false,
    ));

    // 注册任务文章类型
    register_post_type('help_job', array(
        'labels' => array(
            'name' => __('任务管理', 'help-platform'),
            'singular_name' => __('任务', 'help-platform'),
            'menu_name' => __('任务管理', 'help-platform'),
            'all_items' => __('所有任务', 'help-platform'),
            'add_new' => __('发布任务', 'help-platform'),
            'add_new_item' => __('发布新任务', 'help-platform'),
            'edit_item' => __('编辑任务', 'help-platform'),
        ),
        'public' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_icon' => 'dashicons-list-view',
        'supports' => array('title', 'editor', 'author', 'thumbnail'),
        'capability_type' => 'post',
        'map_meta_cap' => true,
        'hierarchical' => false,
        'rewrite' => array('slug' => 'jobs'),
        'has_archive' => true,
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

// 加载必要的文件
require_once HELP_PLATFORM_PLUGIN_DIR . 'includes/class-help-platform-errors.php';
require_once HELP_PLATFORM_PLUGIN_DIR . 'includes/class-help-platform.php';
require_once HELP_PLATFORM_PLUGIN_DIR . 'includes/class-help-platform-verify.php';
require_once HELP_PLATFORM_PLUGIN_DIR . 'includes/class-help-platform-job.php';
require_once HELP_PLATFORM_PLUGIN_DIR . 'includes/class-help-platform-ai.php';
require_once HELP_PLATFORM_PLUGIN_DIR . 'includes/class-help-platform-roles.php';
require_once HELP_PLATFORM_PLUGIN_DIR . 'includes/class-help-platform-woocommerce.php';
require_once HELP_PLATFORM_PLUGIN_DIR . 'includes/class-help-platform-finance.php';
require_once HELP_PLATFORM_PLUGIN_DIR . 'includes/class-help-platform-worker-import.php';

// 注册短代码
function help_platform_register_shortcodes() {
    add_shortcode('help_verify', array('Help_Platform_Verify', 'verify_form_shortcode'));
    add_shortcode('help_job', array('Help_Platform_Job', 'job_form_shortcode'));
    add_shortcode('help_finance', array('Help_Platform_Finance', 'finance_page_shortcode'));
}
add_action('init', 'help_platform_register_shortcodes');

// 注册样式和脚本
function help_platform_enqueue_scripts() {
    // 注册样式
    wp_register_style(
        'help-platform-style',
        HELP_PLATFORM_PLUGIN_URL . 'assets/css/style.css',
        array(),
        HELP_PLATFORM_VERSION
    );

    wp_register_style(
        'help-platform-responsive',
        HELP_PLATFORM_PLUGIN_URL . 'assets/css/responsive.css',
        array('help-platform-style'),
        HELP_PLATFORM_VERSION
    );

    wp_register_style(
        'help-platform-finance',
        HELP_PLATFORM_PLUGIN_URL . 'assets/css/finance.css',
        array('help-platform-style', 'help-platform-responsive'),
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

    wp_register_script(
        'help-platform-responsive',
        HELP_PLATFORM_PLUGIN_URL . 'assets/js/responsive.js',
        array('jquery', 'help-platform-script'),
        HELP_PLATFORM_VERSION,
        true
    );

    wp_register_script(
        'help-platform-finance',
        HELP_PLATFORM_PLUGIN_URL . 'assets/js/finance.js',
        array('jquery', 'help-platform-script'),
        HELP_PLATFORM_VERSION,
        true
    );

    // 本地化脚本
    wp_localize_script('help-platform-responsive', 'helpPlatformResponsive', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('help-platform-nonce'),
        'i18n' => array(
            'mobileMode' => __('移动设备模式', 'help-platform'),
            'desktopMode' => __('桌面模式', 'help-platform'),
            'switchToMobile' => __('切换到移动设备视图', 'help-platform'),
            'switchToDesktop' => __('切换到桌面视图', 'help-platform'),
        ),
    ));

    // 在插件页面加载响应式资源
    if (is_admin() || has_shortcode(get_post()->post_content, 'help_verify') || 
        has_shortcode(get_post()->post_content, 'help_job') ||
        has_shortcode(get_post()->post_content, 'help_finance')) {
        wp_enqueue_style('help-platform-responsive');
        wp_enqueue_script('help-platform-responsive');
    }

    // 为财务页面添加特定的脚本和样式
    if (has_shortcode(get_post()->post_content, 'help_finance')) {
        wp_enqueue_style('help-platform-finance');
        wp_enqueue_script('help-platform-finance');
        wp_localize_script('help-platform-finance', 'helpPlatformFinance', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('help-platform-finance'),
            'i18n' => array(
                'rechargeSuccess' => __('充值请求已提交，请完成支付', 'help-platform'),
                'rechargeError' => __('充值请求失败，请稍后重试', 'help-platform'),
                'withdrawSuccess' => __('提现申请已提交，请等待审核', 'help-platform'),
                'withdrawError' => __('提现申请失败，请稍后重试', 'help-platform'),
                'confirmWithdraw' => __('确认要申请提现吗？', 'help-platform'),
                'processing' => __('处理中...', 'help-platform'),
            ),
        ));
    }
}
add_action('wp_enqueue_scripts', 'help_platform_enqueue_scripts');

// 激活插件时的操作
function help_platform_activate() {
    // 检查系统要求
    Help_Platform_Errors::check_requirements();
    
    // 检查 WooCommerce 依赖
    if (!class_exists('WooCommerce')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(
            '<h1>' . __('插件激活失败', 'help-platform') . '</h1>' .
            '<p>' . __('HELP 平台插件需要 WooCommerce 插件支持。请先安装并激活 WooCommerce。', 'help-platform') . '</p>' .
            '<p><a href="' . admin_url('plugins.php') . '">' . __('返回插件列表', 'help-platform') . '</a></p>'
        );
    }

    // 检查 WooCommerce 版本
    if (defined('WC_VERSION') && version_compare(WC_VERSION, '5.0', '<')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(
            '<h1>' . __('插件激活失败', 'help-platform') . '</h1>' .
            '<p>' . __('HELP 平台插件需要 WooCommerce 5.0 或更高版本。', 'help-platform') . '</p>' .
            '<p><a href="' . admin_url('plugins.php') . '">' . __('返回插件列表', 'help-platform') . '</a></p>'
        );
    }

    // 如果有致命错误，停止激活
    if (Help_Platform_Errors::has_fatal_errors()) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(
            '<h1>' . __('插件激活失败', 'help-platform') . '</h1>' .
            '<p>' . __('HELP 平台插件无法激活，请解决以下问题：', 'help-platform') . '</p>' .
            '<ul><li>' . implode('</li><li>', array_map(function($error) {
                return esc_html($error['message']);
            }, array_filter(Help_Platform_Errors::get_errors(), function($error) {
                return $error['type'] === 'error';
            }))) . '</li></ul>' .
            '<p><a href="' . admin_url('plugins.php') . '">' . __('返回插件列表', 'help-platform') . '</a></p>'
        );
    }

    // 创建自定义用户角色
    add_role(
        'help_customer', // 角色标识符
        __('HELP 客户', 'help-platform'), // 显示名称
        array(
            'read' => true,
            'upload_files' => true,
            'help_post_job' => true, // 发布任务权限
            'help_verify' => true, // 实名认证权限
        )
    );

    add_role(
        'help_worker', // 角色标识符
        __('HELP 工人', 'help-platform'), // 显示名称
        array(
            'read' => true,
            'upload_files' => true,
            'help_verify' => true, // 实名认证权限
            'help_apply_job' => true, // 申请任务权限
        )
    );

    add_role(
        'help_manager', // 角色标识符
        __('HELP 平台管理员', 'help-platform'), // 显示名称
        array(
            'read' => true,
            'edit_posts' => true,
            'edit_published_posts' => true,
            'publish_posts' => true,
            'delete_posts' => true,
            'upload_files' => true,
            'manage_options' => true,
            'help_manage_verify' => true, // 管理认证权限
            'help_manage_job' => true, // 管理任务权限
            'help_manage_users' => true, // 管理用户权限
        )
    );

    // 为管理员添加 HELP 平台权限
    $admin = get_role('administrator');
    $admin->add_cap('help_manage_verify');
    $admin->add_cap('help_manage_job');
    $admin->add_cap('help_manage_users');
    $admin->add_cap('help_post_job');
    $admin->add_cap('help_apply_job');
    $admin->add_cap('help_verify');

    // 为管理员添加财务管理权限
    $admin = get_role('administrator');
    $admin->add_cap('help_manage_finance');

    // 为管理员添加工人管理权限
    $admin = get_role('administrator');
    $admin->add_cap('manage_help_platform_users');

    // 创建必要的数据库表
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    // 创建实名认证表
    $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}help_verify (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        real_name varchar(50) NOT NULL,
        id_number varchar(18) NOT NULL,
        id_photo bigint(20) NOT NULL,
        selfie_photo bigint(20) DEFAULT NULL,
        status varchar(20) NOT NULL DEFAULT 'pending',
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY user_id (user_id),
        KEY status (status)
    ) $charset_collate;";

    // 创建任务表
    $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}help_jobs (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        post_id bigint(20) NOT NULL,
        user_id bigint(20) NOT NULL,
        title varchar(255) NOT NULL,
        description text NOT NULL,
        location_lat decimal(10,8) DEFAULT NULL,
        location_lng decimal(11,8) DEFAULT NULL,
        status varchar(20) NOT NULL DEFAULT 'pending',
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY post_id (post_id),
        KEY user_id (user_id),
        KEY status (status)
    ) $charset_collate;";

    // 创建任务申请表
    $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}help_applications (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        job_id bigint(20) NOT NULL,
        user_id bigint(20) NOT NULL,
        status varchar(20) NOT NULL DEFAULT 'pending',
        message text DEFAULT NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY job_id (job_id),
        KEY user_id (user_id),
        KEY status (status)
    ) $charset_collate;";

    // 创建充值记录表
    $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}help_recharges (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        branch_id bigint(20) NOT NULL,
        order_id bigint(20) NOT NULL,
        amount decimal(10,2) NOT NULL,
        payment_method varchar(50) NOT NULL,
        status varchar(20) NOT NULL DEFAULT 'pending',
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY user_id (user_id),
        KEY branch_id (branch_id),
        KEY order_id (order_id),
        KEY status (status)
    ) $charset_collate;";

    // 创建提现记录表
    $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}help_withdrawals (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        branch_id bigint(20) NOT NULL,
        amount decimal(10,2) NOT NULL,
        fee decimal(10,2) NOT NULL,
        final_amount decimal(10,2) NOT NULL,
        method varchar(50) NOT NULL,
        account_info text NOT NULL,
        status varchar(20) NOT NULL DEFAULT 'pending',
        processed_by bigint(20) DEFAULT NULL,
        processed_at datetime DEFAULT NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY user_id (user_id),
        KEY branch_id (branch_id),
        KEY status (status)
    ) $charset_collate;";

    // 创建佣金记录表
    $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}help_commissions (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        order_id bigint(20) NOT NULL,
        branch_id bigint(20) NOT NULL,
        task_id bigint(20) NOT NULL,
        worker_id bigint(20) NOT NULL,
        amount decimal(10,2) NOT NULL,
        commission decimal(10,2) NOT NULL,
        platform_fee decimal(10,2) NOT NULL,
        worker_amount decimal(10,2) NOT NULL,
        status varchar(20) NOT NULL DEFAULT 'pending',
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY order_id (order_id),
        KEY branch_id (branch_id),
        KEY task_id (task_id),
        KEY worker_id (worker_id),
        KEY status (status)
    ) $charset_collate;";

    // 执行数据库更新
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    foreach ($sql as $query) {
        dbDelta($query);
    }

    // 添加管理员权限
    $admin_role = get_role('administrator');
    if ($admin_role) {
        $admin_role->add_cap('manage_help_platform_finance');
        $admin_role->add_cap('manage_help_platform_branch');
    }

    // 清除错误
    Help_Platform_Errors::clear_errors();

    // 刷新重写规则
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'help_platform_activate');

// 停用插件时的操作
function help_platform_deactivate() {
    // 清除定时任务
    wp_clear_scheduled_hook('help_platform_daily_cleanup');
    
    // 清除错误
    Help_Platform_Errors::clear_errors();

    // 移除自定义用户角色
    remove_role('help_customer');
    remove_role('help_worker');
    remove_role('help_manager');

    // 从管理员角色中移除 HELP 平台权限
    $admin = get_role('administrator');
    $admin->remove_cap('help_manage_verify');
    $admin->remove_cap('help_manage_job');
    $admin->remove_cap('help_manage_users');
    $admin->remove_cap('help_post_job');
    $admin->remove_cap('help_apply_job');
    $admin->remove_cap('help_verify');

    // 从管理员角色中移除财务管理权限
    $admin = get_role('administrator');
    $admin->remove_cap('help_manage_finance');

    // 从管理员角色中移除工人管理权限
    $admin = get_role('administrator');
    $admin->remove_cap('manage_help_platform_users');

    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'help_platform_deactivate');

// 注册转账相关的自定义文章类型
function help_platform_register_transfer_post_type() {
    $labels = array(
        'name'               => __('转账记录', 'help-platform'),
        'singular_name'      => __('转账记录', 'help-platform'),
        'menu_name'          => __('转账记录', 'help-platform'),
        'add_new'            => __('新建转账', 'help-platform'),
        'add_new_item'       => __('新建转账', 'help-platform'),
        'edit_item'          => __('编辑转账', 'help-platform'),
        'new_item'           => __('新建转账', 'help-platform'),
        'view_item'          => __('查看转账', 'help-platform'),
        'search_items'       => __('搜索转账', 'help-platform'),
        'not_found'          => __('没有找到转账记录', 'help-platform'),
        'not_found_in_trash' => __('回收站中没有转账记录', 'help-platform'),
    );

    $args = array(
        'labels'              => $labels,
        'public'              => false,
        'show_ui'             => true,
        'show_in_menu'        => 'help-platform',
        'capability_type'     => 'post',
        'hierarchical'        => false,
        'supports'            => array('title'),
        'menu_position'       => null,
        'menu_icon'           => 'dashicons-money-alt',
        'show_in_admin_bar'   => false,
        'show_in_nav_menus'   => false,
        'can_export'          => true,
        'has_archive'         => false,
        'exclude_from_search' => true,
        'publicly_queryable'  => false,
        'capabilities'        => array(
            'create_posts'    => 'manage_help_platform_finance',
            'edit_post'       => 'manage_help_platform_finance',
            'edit_posts'      => 'manage_help_platform_finance',
            'edit_others_posts' => 'manage_help_platform_finance',
            'publish_posts'   => 'manage_help_platform_finance',
            'read_post'       => 'manage_help_platform_finance',
            'read_private_posts' => 'manage_help_platform_finance',
            'delete_post'     => 'manage_help_platform_finance',
            'delete_posts'    => 'manage_help_platform_finance'
        ),
        'map_meta_cap'        => true,
    );

    register_post_type('help_transfer', $args);
}
add_action('init', 'help_platform_register_transfer_post_type');

// 添加转账管理菜单
function help_platform_add_transfer_menu() {
    add_submenu_page(
        'help-platform',
        __('转账管理', 'help-platform'),
        __('转账管理', 'help-platform'),
        'manage_help_platform_finance',
        'edit.php?post_type=help_transfer'
    );
}
add_action('admin_menu', 'help_platform_add_transfer_menu');

// 加载转账管理类
require_once plugin_dir_path(__FILE__) . 'includes/class-help-platform-transfer.php';

// 初始化转账管理
function help_platform_transfer_init() {
    Help_Platform_Transfer::get_instance();
}
add_action('plugins_loaded', 'help_platform_transfer_init');

// 添加工人导入菜单
function help_platform_add_worker_import_menu() {
    add_submenu_page(
        'help-platform',
        __('工人批量导入', 'help-platform'),
        __('工人批量导入', 'help-platform'),
        'manage_help_platform_users',
        'help-platform-worker-import',
        'help_platform_render_worker_import_page'
    );
}
add_action('admin_menu', 'help_platform_add_worker_import_menu');

// 渲染工人导入页面
function help_platform_render_worker_import_page() {
    include HELP_PLATFORM_PLUGIN_DIR . 'templates/admin-worker-import.php';
} 