<?php
/**
 * 通信页面模板
 */

if (!defined('ABSPATH')) {
    exit;
}

// 获取任务信息
$job_id = isset($_GET['job_id']) ? intval($_GET['job_id']) : 0;
if (!$job_id) {
    wp_die(__('无效的任务ID', 'help-platform'));
}

$job = get_post($job_id);
if (!$job || $job->post_type !== 'help_job') {
    wp_die(__('任务不存在', 'help-platform'));
}

// 获取当前用户信息
$current_user_id = get_current_user_id();
$current_user = get_userdata($current_user_id);

// 获取对方用户信息
$other_user_id = $current_user_id === $job->post_author ? 
    get_post_meta($job_id, '_worker_id', true) : 
    $job->post_author;
$other_user = get_userdata($other_user_id);

// 获取消息历史
global $wpdb;
$messages = $wpdb->get_results($wpdb->prepare(
    "SELECT m.*, 
        s.display_name as sender_name,
        r.display_name as receiver_name
    FROM {$wpdb->prefix}help_messages m
    LEFT JOIN {$wpdb->users} s ON m.sender_id = s.ID
    LEFT JOIN {$wpdb->users} r ON m.receiver_id = r.ID
    WHERE m.job_id = %d
    ORDER BY m.created_at ASC",
    $job_id
));

// 标记消息为已读
$wpdb->update(
    $wpdb->prefix . 'help_messages',
    array('is_read' => 1),
    array(
        'job_id' => $job_id,
        'receiver_id' => $current_user_id,
        'is_read' => 0
    ),
    array('%d'),
    array('%d', '%d', '%d')
);
?>

<div class="wrap help-platform-chat">
    <div class="chat-container">
        <!-- 聊天头部 -->
        <div class="chat-header">
            <div class="chat-title">
                <h2><?php printf(__('与 %s 的对话', 'help-platform'), esc_html($other_user->display_name)); ?></h2>
                <div class="job-info">
                    <?php printf(__('任务：%s', 'help-platform'), esc_html($job->post_title)); ?>
                </div>
            </div>
            <div class="chat-actions">
                <button type="button" class="button refresh-messages">
                    <span class="dashicons dashicons-update"></span>
                    <?php _e('刷新', 'help-platform'); ?>
                </button>
            </div>
        </div>

        <!-- 消息列表 -->
        <div class="messages-container" id="messagesContainer">
            <?php if ($messages) : ?>
                <?php foreach ($messages as $message) : ?>
                    <div class="message <?php echo $message->sender_id === $current_user_id ? 'message-sent' : 'message-received'; ?>">
                        <div class="message-content">
                            <?php if ($message->message_type === 'text') : ?>
                                <div class="message-text"><?php echo esc_html($message->content); ?></div>
                            <?php elseif ($message->message_type === 'image') : ?>
                                <div class="message-image">
                                    <img src="<?php echo esc_url($message->attachment_url); ?>" alt="<?php _e('图片消息', 'help-platform'); ?>">
                                </div>
                            <?php endif; ?>
                            <div class="message-meta">
                                <span class="message-time"><?php echo esc_html(date_i18n('Y-m-d H:i:s', strtotime($message->created_at))); ?></span>
                                <?php if ($message->sender_id === $current_user_id) : ?>
                                    <span class="message-status <?php echo $message->is_read ? 'read' : 'unread'; ?>">
                                        <?php echo $message->is_read ? __('已读', 'help-platform') : __('未读', 'help-platform'); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else : ?>
                <div class="no-messages">
                    <?php _e('暂无消息记录', 'help-platform'); ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- 消息输入框 -->
        <div class="message-input">
            <form id="messageForm" method="post">
                <?php wp_nonce_field('help_platform_send_message', 'message_nonce'); ?>
                <input type="hidden" name="job_id" value="<?php echo esc_attr($job_id); ?>">
                <input type="hidden" name="receiver_id" value="<?php echo esc_attr($other_user_id); ?>">
                
                <div class="input-container">
                    <textarea name="message" id="messageText" placeholder="<?php esc_attr_e('输入消息...', 'help-platform'); ?>" rows="3"></textarea>
                    <div class="input-actions">
                        <label for="imageUpload" class="button">
                            <span class="dashicons dashicons-format-image"></span>
                            <?php _e('图片', 'help-platform'); ?>
                        </label>
                        <input type="file" id="imageUpload" name="image" accept="image/*" style="display: none;">
                        <button type="submit" class="button button-primary">
                            <?php _e('发送', 'help-platform'); ?>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.help-platform-chat {
    margin: 20px;
}

.chat-container {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,.1);
    display: flex;
    flex-direction: column;
    height: calc(100vh - 100px);
}

.chat-header {
    padding: 20px;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.chat-title h2 {
    margin: 0;
    font-size: 18px;
}

.job-info {
    color: #666;
    font-size: 14px;
    margin-top: 5px;
}

.messages-container {
    flex: 1;
    overflow-y: auto;
    padding: 20px;
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.message {
    max-width: 70%;
    display: flex;
    flex-direction: column;
}

.message-sent {
    align-self: flex-end;
}

.message-received {
    align-self: flex-start;
}

.message-content {
    padding: 12px 16px;
    border-radius: 12px;
    position: relative;
}

.message-sent .message-content {
    background: #2196F3;
    color: #fff;
    border-bottom-right-radius: 4px;
}

.message-received .message-content {
    background: #f1f1f1;
    color: #333;
    border-bottom-left-radius: 4px;
}

.message-text {
    word-break: break-word;
}

.message-image img {
    max-width: 300px;
    max-height: 300px;
    border-radius: 8px;
}

.message-meta {
    font-size: 12px;
    margin-top: 5px;
    display: flex;
    gap: 10px;
    align-items: center;
}

.message-sent .message-meta {
    color: rgba(255,255,255,.8);
}

.message-received .message-meta {
    color: #666;
}

.message-status {
    font-size: 11px;
}

.message-status.unread {
    color: #FFA000;
}

.message-input {
    padding: 20px;
    border-top: 1px solid #eee;
    background: #fff;
}

.input-container {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.input-container textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    resize: none;
}

.input-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.no-messages {
    text-align: center;
    color: #666;
    padding: 40px;
}

.refresh-messages {
    display: flex;
    align-items: center;
    gap: 5px;
}

.refresh-messages .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}
</style>

<script>
jQuery(document).ready(function($) {
    var messagesContainer = $('#messagesContainer');
    var messageForm = $('#messageForm');
    var messageText = $('#messageText');
    var imageUpload = $('#imageUpload');
    var isSubmitting = false;

    // 自动滚动到底部
    function scrollToBottom() {
        messagesContainer.scrollTop(messagesContainer[0].scrollHeight);
    }
    scrollToBottom();

    // 发送消息
    messageForm.on('submit', function(e) {
        e.preventDefault();
        if (isSubmitting) return;

        var formData = new FormData(this);
        formData.append('action', 'help_platform_send_message');

        isSubmitting = true;
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    messageText.val('');
                    refreshMessages();
                } else {
                    alert(response.data.message);
                }
            },
            error: function() {
                alert('<?php _e('发送失败，请重试', 'help-platform'); ?>');
            },
            complete: function() {
                isSubmitting = false;
            }
        });
    });

    // 上传图片
    imageUpload.on('change', function() {
        if (this.files && this.files[0]) {
            var formData = new FormData();
            formData.append('action', 'help_platform_upload_image');
            formData.append('image', this.files[0]);
            formData.append('job_id', $('input[name="job_id"]').val());
            formData.append('receiver_id', $('input[name="receiver_id"]').val());
            formData.append('_wpnonce', $('#message_nonce').val());

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        refreshMessages();
                    } else {
                        alert(response.data.message);
                    }
                },
                error: function() {
                    alert('<?php _e('上传失败，请重试', 'help-platform'); ?>');
                }
            });
        }
    });

    // 刷新消息
    function refreshMessages() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'help_platform_get_messages',
                job_id: $('input[name="job_id"]').val(),
                _wpnonce: $('#message_nonce').val()
            },
            success: function(response) {
                if (response.success) {
                    messagesContainer.html(response.data.html);
                    scrollToBottom();
                }
            }
        });
    }

    // 定期刷新消息
    setInterval(refreshMessages, 30000);

    // 手动刷新
    $('.refresh-messages').on('click', refreshMessages);
});
</script> 