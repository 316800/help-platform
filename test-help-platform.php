<?php
/**
 * Plugin Name: HELP Platform Test
 * Plugin URI: https://help-platform.com
 * Description: 测试版本
 * Version: 1.0.0
 * Author: HELP Team
 * Author URI: https://help-platform.com
 * Text Domain: help-platform
 * License: GPL v2 or later
 */

// 防止直接访问
if (!defined('ABSPATH')) {
    exit;
}

// 添加一个简单的菜单
function help_platform_test_menu() {
    add_menu_page(
        'HELP Platform Test',
        'HELP Test',
        'manage_options',
        'help-platform-test',
        'help_platform_test_page',
        'dashicons-admin-generic'
    );
}
add_action('admin_menu', 'help_platform_test_menu');

// 添加一个简单的页面
function help_platform_test_page() {
    echo '<div class="wrap">';
    echo '<h1>HELP Platform Test</h1>';
    echo '<p>这是一个测试页面。</p>';
    echo '</div>';
}

// 激活插件时的操作
function help_platform_test_activate() {
    // 不做任何操作
    return;
}
register_activation_hook(__FILE__, 'help_platform_test_activate');

// 停用插件时的操作
function help_platform_test_deactivate() {
    // 不做任何操作
    return;
}
register_deactivation_hook(__FILE__, 'help_platform_test_deactivate'); 