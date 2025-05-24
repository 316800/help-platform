<?php
if (!defined('ABSPATH')) {
    exit;
}

// 检查权限
if (!current_user_can('manage_help_platform_users')) {
    wp_die(__('您没有权限访问此页面。', 'help-platform'));
}

// 处理文件上传
$import_result = array();
if (isset($_POST['import_workers']) && check_admin_referer('help_platform_import_workers')) {
    if (!empty($_FILES['worker_file']['tmp_name'])) {
        require_once HELP_PLATFORM_PLUGIN_DIR . 'includes/class-help-platform-worker-import.php';
        $importer = new Help_Platform_Worker_Import();
        $import_result = $importer->import_workers($_FILES['worker_file']);
    } else {
        $import_result = array(
            'success' => false,
            'message' => __('请选择要导入的文件。', 'help-platform')
        );
    }
}
?>

<div class="wrap help-platform-worker-import">
    <h1><?php _e('工人批量导入', 'help-platform'); ?></h1>

    <?php if (!empty($import_result)): ?>
        <div class="notice notice-<?php echo $import_result['success'] ? 'success' : 'error'; ?> is-dismissible">
            <p><?php echo esc_html($import_result['message']); ?></p>
            <?php if (!empty($import_result['details'])): ?>
                <ul>
                    <?php foreach ($import_result['details'] as $detail): ?>
                        <li><?php echo esc_html($detail); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="help-platform-import-container">
        <div class="help-platform-import-instructions">
            <h2><?php _e('导入说明', 'help-platform'); ?></h2>
            <ol>
                <li><?php _e('下载导入模板文件', 'help-platform'); ?></li>
                <li><?php _e('按照模板格式填写工人信息', 'help-platform'); ?></li>
                <li><?php _e('上传填写好的文件', 'help-platform'); ?></li>
                <li><?php _e('系统将自动处理导入', 'help-platform'); ?></li>
            </ol>
            <p class="description">
                <?php _e('注意：', 'help-platform'); ?>
                <ul>
                    <li><?php _e('文件格式必须为 .xlsx 或 .xls', 'help-platform'); ?></li>
                    <li><?php _e('必填字段：姓名、手机号、邮箱、专业、工作经验', 'help-platform'); ?></li>
                    <li><?php _e('手机号必须是11位数字', 'help-platform'); ?></li>
                    <li><?php _e('邮箱必须是有效的邮箱格式', 'help-platform'); ?></li>
                    <li><?php _e('工作经验必须是数字（年）', 'help-platform'); ?></li>
                </ul>
            </p>
            <a href="<?php echo wp_nonce_url(admin_url('admin-ajax.php?action=help_platform_download_worker_template'), 'help_platform_download_template'); ?>" 
               class="button button-primary">
                <?php _e('下载导入模板', 'help-platform'); ?>
            </a>
        </div>

        <div class="help-platform-import-form">
            <h2><?php _e('上传文件', 'help-platform'); ?></h2>
            <form method="post" enctype="multipart/form-data">
                <?php wp_nonce_field('help_platform_import_workers'); ?>
                
                <div class="form-field">
                    <label for="worker_file"><?php _e('选择文件', 'help-platform'); ?></label>
                    <input type="file" 
                           name="worker_file" 
                           id="worker_file" 
                           accept=".xlsx,.xls"
                           required>
                    <p class="description">
                        <?php _e('支持的文件格式：.xlsx, .xls', 'help-platform'); ?>
                    </p>
                </div>

                <div class="form-field">
                    <label>
                        <input type="checkbox" 
                               name="send_notification" 
                               value="1" 
                               checked>
                        <?php _e('导入成功后发送通知邮件给工人', 'help-platform'); ?>
                    </label>
                </div>

                <div class="form-field">
                    <label>
                        <input type="checkbox" 
                               name="auto_verify" 
                               value="1">
                        <?php _e('自动通过实名认证', 'help-platform'); ?>
                    </label>
                </div>

                <p class="submit">
                    <input type="submit" 
                           name="import_workers" 
                           class="button button-primary" 
                           value="<?php _e('开始导入', 'help-platform'); ?>">
                </p>
            </form>
        </div>
    </div>
</div>

<style>
.help-platform-worker-import {
    margin: 20px;
}

.help-platform-import-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
    margin-top: 20px;
}

.help-platform-import-instructions,
.help-platform-import-form {
    background: #fff;
    padding: 20px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.help-platform-import-instructions h2,
.help-platform-import-form h2 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.help-platform-import-instructions ol {
    margin-left: 20px;
}

.help-platform-import-instructions .description {
    margin-top: 20px;
    color: #666;
}

.help-platform-import-instructions .description ul {
    margin-left: 20px;
    list-style-type: disc;
}

.form-field {
    margin-bottom: 20px;
}

.form-field label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
}

.form-field input[type="file"] {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.form-field .description {
    margin-top: 5px;
    color: #666;
}

.form-field input[type="checkbox"] {
    margin-right: 5px;
}

.notice ul {
    margin: 5px 0;
    padding-left: 20px;
    list-style-type: disc;
}

@media screen and (max-width: 782px) {
    .help-platform-import-container {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // 文件类型验证
    $('#worker_file').on('change', function() {
        var file = this.files[0];
        var allowedTypes = ['.xlsx', '.xls'];
        var fileExt = file.name.substring(file.name.lastIndexOf('.')).toLowerCase();
        
        if (allowedTypes.indexOf(fileExt) === -1) {
            alert('<?php _e('请选择 .xlsx 或 .xls 格式的文件', 'help-platform'); ?>');
            this.value = '';
        }
    });

    // 表单提交前验证
    $('form').on('submit', function(e) {
        var file = $('#worker_file')[0].files[0];
        if (!file) {
            alert('<?php _e('请选择要导入的文件', 'help-platform'); ?>');
            e.preventDefault();
            return false;
        }
    });
});
</script> 