<?php
/**
 * HELP Platform 主类
 */
class Help_Platform {
    /**
     * 单例实例
     */
    private static $instance = null;

    /**
     * 获取单例实例
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 构造函数
     */
    private function __construct() {
        $this->init_hooks();
    }

    /**
     * 初始化钩子
     */
    private function init_hooks() {
        // 添加管理菜单
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // 添加设置链接
        add_filter('plugin_action_links_' . plugin_basename(HELP_PLATFORM_PLUGIN_DIR . 'help-platform.php'), 
            array($this, 'add_settings_link'));
    }

    /**
     * 添加管理菜单
     */
    public function add_admin_menu() {
        add_menu_page(
            __('HELP 平台设置', 'help-platform'),
            __('HELP 平台', 'help-platform'),
            'manage_options',
            'help-platform',
            array($this, 'render_settings_page'),
            'dashicons-store',
            30
        );

        add_submenu_page(
            'help-platform',
            __('平台设置', 'help-platform'),
            __('平台设置', 'help-platform'),
            'manage_options',
            'help-platform',
            array($this, 'render_settings_page')
        );
    }

    /**
     * 添加设置链接
     */
    public function add_settings_link($links) {
        $settings_link = '<a href="admin.php?page=help-platform">' . __('设置', 'help-platform') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    /**
     * 渲染设置页面
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        // 保存设置
        if (isset($_POST['help_platform_settings_nonce']) && 
            wp_verify_nonce($_POST['help_platform_settings_nonce'], 'help_platform_settings')) {
            
            $settings = array(
                'enable_registration' => isset($_POST['enable_registration']),
                'enable_job_posting' => isset($_POST['enable_job_posting']),
                'notification_email' => sanitize_email($_POST['notification_email']),
            );
            
            update_option('help_platform_settings', $settings);
            echo '<div class="notice notice-success"><p>' . __('设置已保存', 'help-platform') . '</p></div>';
        }

        // 获取当前设置
        $settings = get_option('help_platform_settings', array(
            'enable_registration' => true,
            'enable_job_posting' => true,
            'notification_email' => get_option('admin_email'),
        ));

        // 加载设置页面模板
        include HELP_PLATFORM_PLUGIN_DIR . 'templates/admin-settings.php';
    }

    /**
     * 获取设置
     */
    public static function get_settings() {
        return get_option('help_platform_settings', array(
            'enable_registration' => true,
            'enable_job_posting' => true,
            'notification_email' => get_option('admin_email'),
        ));
    }

    /**
     * 发送邮件通知
     */
    public static function send_notification($to, $subject, $message) {
        $headers = array('Content-Type: text/html; charset=UTF-8');
        return wp_mail($to, $subject, $message, $headers);
    }
}

// 初始化插件
function help_platform_init() {
    return Help_Platform::get_instance();
}
add_action('plugins_loaded', 'help_platform_init'); 