<?php
/**
 * 认证拒绝邮件模板
 */
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?php _e('实名认证未通过', 'help-platform'); ?></title>
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
        .error-icon {
            color: #a94442;
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
        .notice {
            background: #fcf8e3;
            border: 1px solid #faebcc;
            color: #8a6d3b;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="error-icon">✕</div>
            <h2><?php _e('您的实名认证未通过', 'help-platform'); ?></h2>
        </div>

        <div class="content">
            <p><?php printf(__('尊敬的 %s：', 'help-platform'), $user->display_name); ?></p>
            <p><?php _e('很抱歉，您的实名认证申请未能通过审核。', 'help-platform'); ?></p>

            <div class="notice">
                <p><strong><?php _e('可能的原因：', 'help-platform'); ?></strong></p>
                <ul style="text-align: left; display: inline-block;">
                    <li><?php _e('身份证照片不清晰', 'help-platform'); ?></li>
                    <li><?php _e('身份证信息与填写信息不符', 'help-platform'); ?></li>
                    <li><?php _e('自拍照片未能清晰显示身份证', 'help-platform'); ?></li>
                    <li><?php _e('其他原因', 'help-platform'); ?></li>
                </ul>
            </div>

            <div class="meta">
                <p><strong><?php _e('申请时间：', 'help-platform'); ?></strong> <?php echo get_the_date('Y-m-d H:i:s', $post->ID); ?></p>
                <p><strong><?php _e('认证状态：', 'help-platform'); ?></strong> <?php _e('未通过', 'help-platform'); ?></p>
            </div>

            <p><?php _e('您可以重新提交认证申请：', 'help-platform'); ?></p>
            <a href="<?php echo home_url('/verify'); ?>" class="button">
                <?php _e('重新认证', 'help-platform'); ?>
            </a>
        </div>

        <div class="footer">
            <p><?php _e('此邮件由 HELP 平台自动发送，请勿直接回复。', 'help-platform'); ?></p>
            <p><?php _e('如有任何问题，请联系平台客服。', 'help-platform'); ?></p>
        </div>
    </div>
</body>
</html> 