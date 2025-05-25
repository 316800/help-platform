<?php
if (!defined('ABSPATH')) {
    exit;
}

$message_type = get_post_meta(get_the_ID(), 'message_type', true);
$is_read = get_post_meta(get_the_ID(), 'is_read', true);
$message_class = $is_read ? 'message-read' : 'message-unread';
if (isset($_GET['message_id']) && $_GET['message_id'] == get_the_ID()) {
    $message_class .= ' active';
}
?>

<div class="message-item <?php echo esc_attr($message_class); ?>" data-id="<?php echo get_the_ID(); ?>">
    <div class="message-icon">
        <?php
        $icon_class = 'dashicons-';
        switch ($message_type) {
            case 'system':
                $icon_class .= 'megaphone';
                break;
            case 'job':
                $icon_class .= 'list-view';
                break;
            case 'payment':
                $icon_class .= 'money-alt';
                break;
            default:
                $icon_class .= 'email-alt';
        }
        ?>
        <span class="dashicons <?php echo esc_attr($icon_class); ?>"></span>
    </div>
    <div class="message-content">
        <div class="message-title"><?php the_title(); ?></div>
        <div class="message-excerpt"><?php echo get_the_excerpt(); ?></div>
        <div class="message-meta">
            <span class="message-time"><?php echo get_the_date('Y-m-d H:i'); ?></span>
            <?php if (!$is_read): ?>
                <span class="unread-badge"></span>
            <?php endif; ?>
        </div>
    </div>
</div> 