<?php
/**
 * 客户欢迎邮件模板
 */
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?php _e('欢迎加入 HELP 平台', 'help-platform'); ?></title>
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
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><?php _e('欢迎加入 HELP 平台', 'help-platform'); ?></h1>
        </div>

        <div class="content">
            <p><?php printf(__('尊敬的 %s：', 'help-platform'), $user->display_name); ?></p>
            
            <p><?php _e('感谢您注册成为 HELP 平台的客户！我们很高兴您加入我们的社区。', 'help-platform'); ?></p>

            <h2><?php _e('作为客户，您可以：', 'help-platform'); ?></h2>
            <ul class="features">
                <li><?php _e('发布各类生活服务需求', 'help-platform'); ?></li>
                <li><?php _e('选择合适的工人为您服务', 'help-platform'); ?></li>
                <li><?php _e('实时跟踪任务进度', 'help-platform'); ?></li>
                <li><?php _e('对服务进行评价和反馈', 'help-platform'); ?></li>
                <li><?php _e('享受平台提供的安全保障', 'help-platform'); ?></li>
            </ul>

            <p><?php _e('为了确保您的账户安全，请尽快完成实名认证：', 'help-platform'); ?></p>
            <a href="<?php echo home_url('/verify'); ?>" class="button">
                <?php _e('立即认证', 'help-platform'); ?>
            </a>

            <p><?php _e('如果您有任何问题，我们的客服团队随时为您服务。', 'help-platform'); ?></p>
        </div>

        <div class="footer">
            <p><?php _e('此邮件由 HELP 平台自动发送，请勿直接回复。', 'help-platform'); ?></p>
            <p><?php _e('如有任何问题，请联系平台客服。', 'help-platform'); ?></p>
        </div>
    </div>
</body>
</html> 