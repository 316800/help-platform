<?php
/**
 * 管理员欢迎邮件模板
 */
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?php _e('欢迎加入 HELP 平台管理团队', 'help-platform'); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: #fff;
            padding: 20px;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            padding: 20px 0;
            border-bottom: 1px solid #eee;
        }
        .content {
            padding: 20px 0;
        }
        .footer {
            text-align: center;
            padding: 20px 0;
            border-top: 1px solid #eee;
            font-size: 12px;
            color: #666;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            background: #0073aa;
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
            margin: 20px 0;
        }
        .features {
            margin: 20px 0;
            padding: 0;
            list-style: none;
        }
        .features li {
            margin: 10px 0;
            padding-left: 20px;
            position: relative;
        }
        .features li:before {
            content: "✓";
            color: #0073aa;
            position: absolute;
            left: 0;
        }
        .important {
            background: #f8f9fa;
            border-left: 4px solid #0073aa;
            padding: 15px;
            margin: 20px 0;
        }
        .admin-panel {
            background: #f0f6fc;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><?php _e('欢迎加入 HELP 平台管理团队', 'help-platform'); ?></h1>
        </div>

        <div class="content">
            <p><?php printf(__('尊敬的 %s：', 'help-platform'), $user->display_name); ?></p>
            
            <p><?php _e('感谢您加入 HELP 平台管理团队！作为平台管理员，您将负责维护平台的正常运营和提供优质服务。', 'help-platform'); ?></p>

            <div class="important">
                <h3><?php _e('重要提示', 'help-platform'); ?></h3>
                <p><?php _e('作为管理员，您拥有重要的平台管理权限，请妥善保管您的账号信息。', 'help-platform'); ?></p>
            </div>

            <div class="admin-panel">
                <h2><?php _e('管理后台访问', 'help-platform'); ?></h2>
                <p><?php _e('您可以通过以下地址访问管理后台：', 'help-platform'); ?></p>
                <p><strong><?php echo admin_url(); ?></strong></p>
            </div>

            <h2><?php _e('作为管理员，您可以：', 'help-platform'); ?></h2>
            <ul class="features">
                <li><?php _e('审核用户实名认证申请', 'help-platform'); ?></li>
                <li><?php _e('管理任务发布和审核', 'help-platform'); ?></li>
                <li><?php _e('处理用户反馈和投诉', 'help-platform'); ?></li>
                <li><?php _e('查看平台运营数据', 'help-platform'); ?></li>
                <li><?php _e('管理用户账号和权限', 'help-platform'); ?></li>
            </ul>

            <h3><?php _e('管理职责：', 'help-platform'); ?></h3>
            <ol>
                <li><?php _e('及时处理用户认证申请', 'help-platform'); ?></li>
                <li><?php _e('确保任务内容合规', 'help-platform'); ?></li>
                <li><?php _e('维护平台秩序', 'help-platform'); ?></li>
                <li><?php _e('提供用户支持', 'help-platform'); ?></li>
            </ol>

            <p><?php _e('立即访问管理后台，开始您的工作：', 'help-platform'); ?></p>
            <a href="<?php echo admin_url(); ?>" class="button">
                <?php _e('进入管理后台', 'help-platform'); ?>
            </a>

            <p><?php _e('如有任何问题，请联系平台超级管理员。', 'help-platform'); ?></p>
        </div>

        <div class="footer">
            <p><?php _e('此邮件由 HELP 平台自动发送，请勿直接回复。', 'help-platform'); ?></p>
            <p><?php _e('此邮件包含敏感信息，请勿转发给他人。', 'help-platform'); ?></p>
        </div>
    </div>
</body>
</html> 