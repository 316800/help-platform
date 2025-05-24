<?php
/**
 * 任务审核通过邮件模板
 */
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?php _e('您的任务已通过审核', 'help-platform'); ?></title>
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
        .job-content {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
            text-align: left;
        }
        .job-content h3 {
            margin-top: 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="success-icon">✓</div>
            <h2><?php _e('恭喜！您的任务已通过审核', 'help-platform'); ?></h2>
        </div>

        <div class="content">
            <p><?php printf(__('尊敬的 %s：', 'help-platform'), $user->display_name); ?></p>
            <p><?php _e('您的任务已通过审核，现在已发布在平台上。', 'help-platform'); ?></p>

            <div class="job-content">
                <h3><?php echo esc_html($post->post_title); ?></h3>
                <?php echo wpautop($post->post_content); ?>
            </div>

            <div class="meta">
                <p><strong><?php _e('发布时间：', 'help-platform'); ?></strong> <?php echo get_the_date('Y-m-d H:i:s', $post->ID); ?></p>
                <p><strong><?php _e('审核状态：', 'help-platform'); ?></strong> <?php _e('已通过', 'help-platform'); ?></p>
                <?php
                $latitude = get_post_meta($post->ID, '_latitude', true);
                $longitude = get_post_meta($post->ID, '_longitude', true);
                if ($latitude && $longitude) {
                    echo '<p><strong>' . __('任务位置：', 'help-platform') . '</strong> ' . 
                         sprintf(__('纬度：%s，经度：%s', 'help-platform'), $latitude, $longitude) . '</p>';
                }
                ?>
            </div>

            <p><?php _e('您的任务现在可以被其他用户查看和接取：', 'help-platform'); ?></p>
            <a href="<?php echo get_permalink($post->ID); ?>" class="button">
                <?php _e('查看任务', 'help-platform'); ?>
            </a>
        </div>

        <div class="footer">
            <p><?php _e('此邮件由 HELP 平台自动发送，请勿直接回复。', 'help-platform'); ?></p>
            <p><?php _e('如有任何问题，请联系平台客服。', 'help-platform'); ?></p>
        </div>
    </div>
</body>
</html> 