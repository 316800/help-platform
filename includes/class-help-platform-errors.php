<?php
/**
 * HELP Platform 错误处理类
 */
class Help_Platform_Errors {
    /**
     * 错误消息数组
     */
    private static $errors = array();

    /**
     * 初始化钩子
     */
    public static function init() {
        add_action('admin_notices', array(__CLASS__, 'display_errors'));
        add_action('admin_init', array(__CLASS__, 'check_requirements'));
    }

    /**
     * 检查系统要求
     */
    public static function check_requirements() {
        // 检查 WordPress 版本
        if (version_compare(get_bloginfo('version'), '5.0', '<')) {
            self::add_error(
                'wp_version',
                __('HELP 平台插件需要 WordPress 5.0 或更高版本。', 'help-platform'),
                'error'
            );
        }

        // 检查 PHP 版本
        if (version_compare(PHP_VERSION, '7.2', '<')) {
            self::add_error(
                'php_version',
                __('HELP 平台插件需要 PHP 7.2 或更高版本。', 'help-platform'),
                'error'
            );
        }

        // 检查必要的 PHP 扩展
        $required_extensions = array('curl', 'json', 'mbstring');
        foreach ($required_extensions as $ext) {
            if (!extension_loaded($ext)) {
                self::add_error(
                    'php_extension_' . $ext,
                    sprintf(__('HELP 平台插件需要 PHP %s 扩展。', 'help-platform'), $ext),
                    'error'
                );
            }
        }

        // 检查 WooCommerce
        if (!class_exists('WooCommerce')) {
            self::add_error(
                'woocommerce_missing',
                __('HELP 平台插件需要 WooCommerce 插件支持。请安装并激活 WooCommerce。', 'help-platform'),
                'error'
            );
        } elseif (defined('WC_VERSION') && version_compare(WC_VERSION, '5.0', '<')) {
            self::add_error(
                'woocommerce_version',
                __('HELP 平台插件需要 WooCommerce 5.0 或更高版本。', 'help-platform'),
                'error'
            );
        }

        // 检查目录权限
        $upload_dir = wp_upload_dir();
        if (!is_writable($upload_dir['basedir'])) {
            self::add_error(
                'upload_dir',
                __('上传目录没有写入权限，这可能会影响图片上传功能。', 'help-platform'),
                'warning'
            );
        }

        // 检查必要的 WordPress 功能
        if (!function_exists('wp_mail')) {
            self::add_error(
                'wp_mail',
                __('WordPress 邮件功能不可用，这可能会影响通知功能。', 'help-platform'),
                'warning'
            );
        }

        // 检查数据库表
        self::check_database_tables();

        // 检查 API 密钥
        self::check_api_keys();
    }

    /**
     * 检查数据库表
     */
    private static function check_database_tables() {
        global $wpdb;
        $tables = array(
            $wpdb->prefix . 'help_verify',
            $wpdb->prefix . 'help_jobs',
            $wpdb->prefix . 'help_applications'
        );

        foreach ($tables as $table) {
            if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
                self::add_error(
                    'db_table_' . $table,
                    sprintf(__('数据库表 %s 不存在，请重新激活插件。', 'help-platform'), $table),
                    'error'
                );
            }
        }
    }

    /**
     * 检查 API 密钥
     */
    private static function check_api_keys() {
        // 检查 OpenAI API 密钥
        $openai_key = get_option('help_platform_openai_api_key');
        if (empty($openai_key)) {
            self::add_error(
                'openai_key',
                __('未设置 OpenAI API 密钥，AI 功能将不可用。', 'help-platform'),
                'warning'
            );
        }

        // 检查 Google Maps API 密钥
        $google_maps_key = get_option('help_platform_google_maps_api_key');
        if (empty($google_maps_key)) {
            self::add_error(
                'google_maps_key',
                __('未设置 Google Maps API 密钥，地图功能将不可用。', 'help-platform'),
                'warning'
            );
        }
    }

    /**
     * 添加错误消息
     */
    public static function add_error($code, $message, $type = 'error') {
        self::$errors[$code] = array(
            'message' => $message,
            'type' => $type
        );
    }

    /**
     * 显示错误消息
     */
    public static function display_errors() {
        if (empty(self::$errors)) {
            return;
        }

        foreach (self::$errors as $code => $error) {
            printf(
                '<div class="notice notice-%s is-dismissible"><p><strong>HELP 平台：</strong> %s</p></div>',
                esc_attr($error['type']),
                esc_html($error['message'])
            );
        }
    }

    /**
     * 获取所有错误
     */
    public static function get_errors() {
        return self::$errors;
    }

    /**
     * 清除所有错误
     */
    public static function clear_errors() {
        self::$errors = array();
    }

    /**
     * 检查是否有错误
     */
    public static function has_errors() {
        return !empty(self::$errors);
    }

    /**
     * 检查是否有致命错误
     */
    public static function has_fatal_errors() {
        foreach (self::$errors as $error) {
            if ($error['type'] === 'error') {
                return true;
            }
        }
        return false;
    }
}

// 初始化错误处理
Help_Platform_Errors::init(); 