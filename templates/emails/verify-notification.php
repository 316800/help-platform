<?php
/**
 * 认证通知邮件模板
 */
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?php _e('新的实名认证申请', 'help-platform'); ?></title>
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
        }
        .content {
            margin-bottom: 20px;
        }
        .footer {
            border-top: 1px solid #eee;
            padding-top: 20px;
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
            margin-top: 20px;
        }
        .meta {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
        .meta p {
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2><?php _e('新的实名认证申请', 'help-platform'); ?></h2>
        </div>

        <div class="content">
            <p><?php printf(__('用户 %s 提交了新的实名认证申请，请及时审核。', 'help-platform'), $user->display_name); ?></p>

            <div class="meta">
                <p><strong><?php _e('申请人：', 'help-platform'); ?></strong> <?php echo esc_html($user->display_name); ?></p>
                <p><strong><?php _e('用户名：', 'help-platform'); ?></strong> <?php echo esc_html($user->user_login); ?></p>
                <p><strong><?php _e('邮箱：', 'help-platform'); ?></strong> <?php echo esc_html($user->user_email); ?></p>
                <p><strong><?php _e('申请时间：', 'help-platform'); ?></strong> <?php echo get_the_date('Y-m-d H:i:s', $post->ID); ?></p>
            </div>

            <p><?php _e('您可以在后台查看详细信息并进行审核：', 'help-platform'); ?></p>
            <a href="<?php echo admin_url('post.php?post=' . $post->ID . '&action=edit'); ?>" class="button">
                <?php _e('查看详情', 'help-platform'); ?>
            </a>
        </div>

        <div class="footer">
            <p><?php _e('此邮件由 HELP 平台自动发送，请勿直接回复。', 'help-platform'); ?></p>
        </div>
    </div>
</body>
</html> 