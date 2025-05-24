<?php
/**
 * 认证通过邮件模板
 */
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?php _e('实名认证已通过', 'help-platform'); ?></title>
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
            border-bottom: 1px solid #eee;
            padding-bottom: 20px;
            margin-bottom: 20px;
            text-align: center;
        }
        .content {
            margin-bottom: 20px;
            text-align: center;
        }
        .footer {
            border-top: 1px solid #eee;
            padding-top: 20px;
            font-size: 12px;
            color: #666;
            text-align: center;
        }
        .success-icon {
            color: #3c763d;
            font-size: 48px;
            margin-bottom: 20px;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            background: #0073aa;
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 20px;
        }
        .meta {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
            text-align: left;
        }
        .meta p {
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="success-icon">✓</div>
            <h2><?php _e('恭喜！您的实名认证已通过', 'help-platform'); ?></h2>
        </div>

        <div class="content">
            <p><?php printf(__('尊敬的 %s：', 'help-platform'), $user->display_name); ?></p>
            <p><?php _e('您的实名认证申请已通过审核，现在您可以：', 'help-platform'); ?></p>
            <ul style="text-align: left; display: inline-block;">
                <li><?php _e('发布任务', 'help-platform'); ?></li>
                <li><?php _e('接取任务', 'help-platform'); ?></li>
                <li><?php _e('使用平台所有功能', 'help-platform'); ?></li>
            </ul>

            <div class="meta">
                <p><strong><?php _e('认证时间：', 'help-platform'); ?></strong> <?php echo get_the_date('Y-m-d H:i:s', $post->ID); ?></p>
                <p><strong><?php _e('认证状态：', 'help-platform'); ?></strong> <?php _e('已通过', 'help-platform'); ?></p>
            </div>

            <p><?php _e('立即开始使用平台功能：', 'help-platform'); ?></p>
            <a href="<?php echo home_url(); ?>" class="button">
                <?php _e('访问平台', 'help-platform'); ?>
            </a>
        </div>

        <div class="footer">
            <p><?php _e('此邮件由 HELP 平台自动发送，请勿直接回复。', 'help-platform'); ?></p>
            <p><?php _e('如有任何问题，请联系平台客服。', 'help-platform'); ?></p>
        </div>
    </div>
</body>
</html> 