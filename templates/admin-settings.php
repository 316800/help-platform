<?php
/**
 * 后台设置页面模板
 */
?>
<div class="wrap">
    <h1><?php _e('HELP 平台设置', 'help-platform'); ?></h1>

    <form method="post" action="">
        <?php wp_nonce_field('help_platform_settings', 'help_platform_settings_nonce'); ?>

        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('开放注册', 'help-platform'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="enable_registration" value="1" <?php checked($settings['enable_registration']); ?>>
                        <?php _e('允许新用户注册', 'help-platform'); ?>
                    </label>
                </td>
            </tr>

            <tr>
                <th scope="row"><?php _e('任务发布', 'help-platform'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="enable_job_posting" value="1" <?php checked($settings['enable_job_posting']); ?>>
                        <?php _e('允许用户发布任务', 'help-platform'); ?>
                    </label>
                </td>
            </tr>

            <tr>
                <th scope="row"><?php _e('通知邮箱', 'help-platform'); ?></th>
                <td>
                    <input type="email" name="notification_email" value="<?php echo esc_attr($settings['notification_email']); ?>" class="regular-text">
                    <p class="description"><?php _e('用于接收新认证和任务通知的邮箱地址', 'help-platform'); ?></p>
                </td>
            </tr>

            <tr>
                <th scope="row"><?php _e('Google Maps API Key', 'help-platform'); ?></th>
                <td>
                    <input type="text" name="google_maps_api_key" value="<?php echo esc_attr(get_option('help_platform_google_maps_api_key')); ?>" class="regular-text">
                    <p class="description"><?php _e('用于地图定位功能的 Google Maps API Key', 'help-platform'); ?></p>
                </td>
            </tr>
        </table>

        <h2><?php _e('短代码使用说明', 'help-platform'); ?></h2>
        <div class="card">
            <p><?php _e('在页面或文章中使用以下短代码来显示相应的表单：', 'help-platform'); ?></p>
            <ul>
                <li><code>[help_verify]</code> - <?php _e('显示实名认证表单', 'help-platform'); ?></li>
                <li><code>[help_job]</code> - <?php _e('显示任务发布表单', 'help-platform'); ?></li>
            </ul>
        </div>

        <h2><?php _e('数据导出', 'help-platform'); ?></h2>
        <div class="card">
            <p><?php _e('导出认证和任务数据：', 'help-platform'); ?></p>
            <p>
                <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=help_platform_export&type=verify'), 'help_platform_export'); ?>" class="button">
                    <?php _e('导出认证数据', 'help-platform'); ?>
                </a>
                <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=help_platform_export&type=job'), 'help_platform_export'); ?>" class="button">
                    <?php _e('导出任务数据', 'help-platform'); ?>
                </a>
            </p>
        </div>

        <?php submit_button(); ?>
    </form>
</div>

<style>
.card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    margin: 20px 0;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}
.card ul {
    margin-left: 20px;
}
.card code {
    background: #f0f0f1;
    padding: 3px 5px;
    border-radius: 3px;
}
</style> 