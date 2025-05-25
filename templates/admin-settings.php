<?php
if (!defined('ABSPATH')) {
    exit;
}

// 保存设置
if (isset($_POST['help_platform_settings_nonce']) && wp_verify_nonce($_POST['help_platform_settings_nonce'], 'help_platform_settings')) {
    $settings = array(
        // 基础设置
        'verify_required' => isset($_POST['verify_required']) ? true : false,
        'job_approval' => isset($_POST['job_approval']) ? true : false,
        'payment_enabled' => isset($_POST['payment_enabled']) ? true : false,
        
        // API设置
        'openai_api_key' => sanitize_text_field($_POST['openai_api_key'] ?? ''),
        'openai_model' => sanitize_text_field($_POST['openai_model'] ?? 'gpt-3.5-turbo'),
        'google_maps_api_key' => sanitize_text_field($_POST['google_maps_api_key'] ?? ''),
        'sms_api_key' => sanitize_text_field($_POST['sms_api_key'] ?? ''),
        'sms_api_secret' => sanitize_text_field($_POST['sms_api_secret'] ?? ''),
        
        // 支付设置
        'alipay_app_id' => sanitize_text_field($_POST['alipay_app_id'] ?? ''),
        'alipay_private_key' => sanitize_textarea_field($_POST['alipay_private_key'] ?? ''),
        'alipay_public_key' => sanitize_textarea_field($_POST['alipay_public_key'] ?? ''),
        'wechat_app_id' => sanitize_text_field($_POST['wechat_app_id'] ?? ''),
        'wechat_mch_id' => sanitize_text_field($_POST['wechat_mch_id'] ?? ''),
        'wechat_key' => sanitize_text_field($_POST['wechat_key'] ?? ''),
        
        // 通知设置
        'email_notification' => isset($_POST['email_notification']) ? true : false,
        'sms_notification' => isset($_POST['sms_notification']) ? true : false,
        'notification_email' => sanitize_email($_POST['notification_email'] ?? ''),
        
        // 安全设置
        'login_attempts' => intval($_POST['login_attempts'] ?? 5),
        'lockout_time' => intval($_POST['lockout_time'] ?? 30),
        'password_min_length' => intval($_POST['password_min_length'] ?? 8),
        
        // 其他设置
        'maintenance_mode' => isset($_POST['maintenance_mode']) ? true : false,
        'debug_mode' => isset($_POST['debug_mode']) ? true : false,
        'cache_enabled' => isset($_POST['cache_enabled']) ? true : false,
    );
    
    update_option('help_platform_settings', $settings);
    echo '<div class="notice notice-success"><p>' . __('设置已保存。', 'help-platform') . '</p></div>';
}

$settings = get_option('help_platform_settings', array(
    'verify_required' => true,
    'job_approval' => true,
    'payment_enabled' => false,
    'openai_model' => 'gpt-3.5-turbo',
    'login_attempts' => 5,
    'lockout_time' => 30,
    'password_min_length' => 8,
));
?>

<div class="wrap">
    <h1><?php _e('HELP平台设置', 'help-platform'); ?></h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('help_platform_settings', 'help_platform_settings_nonce'); ?>
        
        <div class="nav-tab-wrapper">
            <a href="#basic" class="nav-tab nav-tab-active"><?php _e('基础设置', 'help-platform'); ?></a>
            <a href="#api" class="nav-tab"><?php _e('API设置', 'help-platform'); ?></a>
            <a href="#payment" class="nav-tab"><?php _e('支付设置', 'help-platform'); ?></a>
            <a href="#notification" class="nav-tab"><?php _e('通知设置', 'help-platform'); ?></a>
            <a href="#security" class="nav-tab"><?php _e('安全设置', 'help-platform'); ?></a>
            <a href="#advanced" class="nav-tab"><?php _e('高级设置', 'help-platform'); ?></a>
        </div>

        <!-- 基础设置 -->
        <div id="basic" class="tab-content">
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('实名认证', 'help-platform'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="verify_required" value="1" <?php checked($settings['verify_required']); ?>>
                            <?php _e('启用实名认证', 'help-platform'); ?>
                        </label>
                        <p class="description"><?php _e('启用后，用户必须完成实名认证才能使用平台功能。', 'help-platform'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('任务审核', 'help-platform'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="job_approval" value="1" <?php checked($settings['job_approval']); ?>>
                            <?php _e('启用任务审核', 'help-platform'); ?>
                        </label>
                        <p class="description"><?php _e('启用后，新发布的任务需要管理员审核。', 'help-platform'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('支付功能', 'help-platform'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="payment_enabled" value="1" <?php checked($settings['payment_enabled']); ?>>
                            <?php _e('启用支付功能', 'help-platform'); ?>
                        </label>
                        <p class="description"><?php _e('启用后，用户可以使用支付功能。', 'help-platform'); ?></p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- API设置 -->
        <div id="api" class="tab-content" style="display: none;">
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('OpenAI API密钥', 'help-platform'); ?></th>
                    <td>
                        <input type="password" name="openai_api_key" value="<?php echo esc_attr($settings['openai_api_key'] ?? ''); ?>" class="regular-text">
                        <p class="description"><?php _e('用于AI智能助手功能。', 'help-platform'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('OpenAI模型', 'help-platform'); ?></th>
                    <td>
                        <select name="openai_model">
                            <option value="gpt-3.5-turbo" <?php selected($settings['openai_model'] ?? '', 'gpt-3.5-turbo'); ?>>GPT-3.5 Turbo</option>
                            <option value="gpt-4" <?php selected($settings['openai_model'] ?? '', 'gpt-4'); ?>>GPT-4</option>
                        </select>
                        <p class="description"><?php _e('选择要使用的OpenAI模型。', 'help-platform'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Google Maps API密钥', 'help-platform'); ?></th>
                    <td>
                        <input type="text" name="google_maps_api_key" value="<?php echo esc_attr($settings['google_maps_api_key'] ?? ''); ?>" class="regular-text">
                        <p class="description"><?php _e('用于地图定位功能。', 'help-platform'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('短信API密钥', 'help-platform'); ?></th>
                    <td>
                        <input type="text" name="sms_api_key" value="<?php echo esc_attr($settings['sms_api_key'] ?? ''); ?>" class="regular-text">
                        <p class="description"><?php _e('短信服务API密钥。', 'help-platform'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('短信API密钥', 'help-platform'); ?></th>
                    <td>
                        <input type="password" name="sms_api_secret" value="<?php echo esc_attr($settings['sms_api_secret'] ?? ''); ?>" class="regular-text">
                        <p class="description"><?php _e('短信服务API密钥。', 'help-platform'); ?></p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- 支付设置 -->
        <div id="payment" class="tab-content" style="display: none;">
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('支付宝设置', 'help-platform'); ?></th>
                    <td>
                        <fieldset>
                            <label>
                                <?php _e('App ID', 'help-platform'); ?><br>
                                <input type="text" name="alipay_app_id" value="<?php echo esc_attr($settings['alipay_app_id'] ?? ''); ?>" class="regular-text">
                            </label><br><br>
                            <label>
                                <?php _e('应用私钥', 'help-platform'); ?><br>
                                <textarea name="alipay_private_key" rows="5" class="large-text code"><?php echo esc_textarea($settings['alipay_private_key'] ?? ''); ?></textarea>
                            </label><br><br>
                            <label>
                                <?php _e('支付宝公钥', 'help-platform'); ?><br>
                                <textarea name="alipay_public_key" rows="5" class="large-text code"><?php echo esc_textarea($settings['alipay_public_key'] ?? ''); ?></textarea>
                            </label>
                        </fieldset>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('微信支付设置', 'help-platform'); ?></th>
                    <td>
                        <fieldset>
                            <label>
                                <?php _e('App ID', 'help-platform'); ?><br>
                                <input type="text" name="wechat_app_id" value="<?php echo esc_attr($settings['wechat_app_id'] ?? ''); ?>" class="regular-text">
                            </label><br><br>
                            <label>
                                <?php _e('商户号', 'help-platform'); ?><br>
                                <input type="text" name="wechat_mch_id" value="<?php echo esc_attr($settings['wechat_mch_id'] ?? ''); ?>" class="regular-text">
                            </label><br><br>
                            <label>
                                <?php _e('API密钥', 'help-platform'); ?><br>
                                <input type="password" name="wechat_key" value="<?php echo esc_attr($settings['wechat_key'] ?? ''); ?>" class="regular-text">
                            </label>
                        </fieldset>
                    </td>
                </tr>
            </table>
        </div>

        <!-- 通知设置 -->
        <div id="notification" class="tab-content" style="display: none;">
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('邮件通知', 'help-platform'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="email_notification" value="1" <?php checked($settings['email_notification'] ?? false); ?>>
                            <?php _e('启用邮件通知', 'help-platform'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('短信通知', 'help-platform'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="sms_notification" value="1" <?php checked($settings['sms_notification'] ?? false); ?>>
                            <?php _e('启用短信通知', 'help-platform'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('通知邮箱', 'help-platform'); ?></th>
                    <td>
                        <input type="email" name="notification_email" value="<?php echo esc_attr($settings['notification_email'] ?? ''); ?>" class="regular-text">
                        <p class="description"><?php _e('用于接收系统通知的邮箱地址。', 'help-platform'); ?></p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- 安全设置 -->
        <div id="security" class="tab-content" style="display: none;">
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('登录尝试次数', 'help-platform'); ?></th>
                    <td>
                        <input type="number" name="login_attempts" value="<?php echo esc_attr($settings['login_attempts']); ?>" min="3" max="10" class="small-text">
                        <p class="description"><?php _e('超过此次数将被锁定账号。', 'help-platform'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('锁定时间(分钟)', 'help-platform'); ?></th>
                    <td>
                        <input type="number" name="lockout_time" value="<?php echo esc_attr($settings['lockout_time']); ?>" min="5" max="1440" class="small-text">
                        <p class="description"><?php _e('账号被锁定后的等待时间。', 'help-platform'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('密码最小长度', 'help-platform'); ?></th>
                    <td>
                        <input type="number" name="password_min_length" value="<?php echo esc_attr($settings['password_min_length']); ?>" min="6" max="32" class="small-text">
                        <p class="description"><?php _e('用户密码的最小长度要求。', 'help-platform'); ?></p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- 高级设置 -->
        <div id="advanced" class="tab-content" style="display: none;">
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('维护模式', 'help-platform'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="maintenance_mode" value="1" <?php checked($settings['maintenance_mode'] ?? false); ?>>
                            <?php _e('启用维护模式', 'help-platform'); ?>
                        </label>
                        <p class="description"><?php _e('启用后，只有管理员可以访问网站。', 'help-platform'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('调试模式', 'help-platform'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="debug_mode" value="1" <?php checked($settings['debug_mode'] ?? false); ?>>
                            <?php _e('启用调试模式', 'help-platform'); ?>
                        </label>
                        <p class="description"><?php _e('启用后将显示详细的错误信息。', 'help-platform'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('缓存', 'help-platform'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="cache_enabled" value="1" <?php checked($settings['cache_enabled'] ?? false); ?>>
                            <?php _e('启用缓存', 'help-platform'); ?>
                        </label>
                        <p class="description"><?php _e('启用后将缓存部分数据以提高性能。', 'help-platform'); ?></p>
                    </td>
                </tr>
            </table>
        </div>

        <p class="submit">
            <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php esc_attr_e('保存设置', 'help-platform'); ?>">
        </p>
    </form>
</div>

<style>
.nav-tab-wrapper {
    margin-bottom: 20px;
}

.tab-content {
    background: #fff;
    padding: 20px;
    border: 1px solid #ccd0d4;
    border-top: none;
}

.form-table th {
    width: 200px;
}

.form-table td {
    padding: 15px 10px;
}

.form-table input[type="text"],
.form-table input[type="email"],
.form-table input[type="password"],
.form-table input[type="number"],
.form-table select,
.form-table textarea {
    width: 100%;
    max-width: 400px;
}

.form-table textarea.code {
    font-family: monospace;
}

.description {
    color: #666;
    font-style: italic;
    margin: 5px 0 0;
}

.notice {
    margin: 20px 0;
}
</style>

<script>
jQuery(document).ready(function($) {
    // 标签页切换
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        
        // 更新标签页状态
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        
        // 显示对应内容
        var target = $(this).attr('href');
        $('.tab-content').hide();
        $(target).show();
    });

    // 密码字段显示切换
    $('.password-toggle').on('click', function() {
        var $input = $(this).prev('input');
        var type = $input.attr('type');
        $input.attr('type', type === 'password' ? 'text' : 'password');
        $(this).text(type === 'password' ? '隐藏' : '显示');
    });
});
</script> 