<?php
if (!defined('ABSPATH')) {
    exit;
}

$user_id = get_current_user_id();
$messages = help_platform_get_user_messages($user_id, array(
    'posts_per_page' => 20,
    'paged' => get_query_var('paged') ? get_query_var('paged') : 1
));

// 获取当前查看的消息ID
$current_message_id = isset($_GET['message_id']) ? intval($_GET['message_id']) : 0;
?>

<div class="help-platform-messages">
    <div class="messages-header">
        <h2><?php _e('消息中心', 'help-platform'); ?></h2>
        <a href="<?php echo esc_url(add_query_arg('help', 'message')); ?>" class="help-button" target="_blank">
            <span class="dashicons dashicons-editor-help"></span>
            <?php _e('帮助', 'help-platform'); ?>
        </a>
    </div>

    <div class="messages-container">
        <!-- 消息列表侧边栏 -->
        <div class="messages-sidebar">
            <div class="sidebar-header">
                <h2><?php _e('消息列表', 'help-platform'); ?></h2>
                <div class="message-actions">
                    <button type="button" class="button" id="mark-all-read">
                        <span class="dashicons dashicons-yes"></span>
                        <?php _e('全部标为已读', 'help-platform'); ?>
                    </button>
                    <button type="button" class="button" id="delete-all-messages">
                        <span class="dashicons dashicons-trash"></span>
                        <?php _e('清空消息', 'help-platform'); ?>
                    </button>
                </div>
            </div>

            <div class="message-filters">
                <select id="message-type">
                    <option value=""><?php _e('全部消息', 'help-platform'); ?></option>
                    <option value="system"><?php _e('系统消息', 'help-platform'); ?></option>
                    <option value="job"><?php _e('任务消息', 'help-platform'); ?></option>
                    <option value="payment"><?php _e('支付消息', 'help-platform'); ?></option>
                </select>
                <select id="message-status">
                    <option value=""><?php _e('全部状态', 'help-platform'); ?></option>
                    <option value="unread"><?php _e('未读消息', 'help-platform'); ?></option>
                    <option value="read"><?php _e('已读消息', 'help-platform'); ?></option>
                </select>
            </div>

            <div class="message-list">
                <?php if ($messages->have_posts()): ?>
                    <?php while ($messages->have_posts()): $messages->the_post(); 
                        $message_type = get_post_meta(get_the_ID(), 'message_type', true);
                        $is_read = get_post_meta(get_the_ID(), 'is_read', true);
                        $message_class = $is_read ? 'message-read' : 'message-unread';
                        if ($current_message_id === get_the_ID()) {
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
                    <?php endwhile; ?>
                    <?php wp_reset_postdata(); ?>

                    <div class="pagination">
                        <?php
                        echo paginate_links(array(
                            'total' => $messages->max_num_pages,
                            'current' => get_query_var('paged') ? get_query_var('paged') : 1,
                            'prev_text' => '&laquo;',
                            'next_text' => '&raquo;',
                        ));
                        ?>
                    </div>
                <?php else: ?>
                    <div class="no-messages">
                        <p><?php _e('暂无消息。', 'help-platform'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- 消息详情区域 -->
        <div class="message-detail">
            <?php if ($current_message_id): 
                $current_message = get_post($current_message_id);
                if ($current_message && $current_message->post_author == $user_id):
                    // 标记消息为已读
                    update_post_meta($current_message_id, 'is_read', true);
            ?>
                <div class="detail-header">
                    <h2><?php echo esc_html($current_message->post_title); ?></h2>
                    <div class="detail-meta">
                        <span class="message-time"><?php echo get_the_date('Y-m-d H:i', $current_message_id); ?></span>
                        <span class="message-type">
                            <?php
                            $type_labels = array(
                                'system' => __('系统消息', 'help-platform'),
                                'job' => __('任务消息', 'help-platform'),
                                'payment' => __('支付消息', 'help-platform')
                            );
                            $message_type = get_post_meta($current_message_id, 'message_type', true);
                            echo isset($type_labels[$message_type]) ? $type_labels[$message_type] : $message_type;
                            ?>
                        </span>
                    </div>
                </div>
                <div class="detail-content">
                    <?php echo wpautop($current_message->post_content); ?>
                </div>
                <div class="detail-actions">
                    <button type="button" class="button" id="delete-message" data-id="<?php echo $current_message_id; ?>">
                        <span class="dashicons dashicons-trash"></span>
                        <?php _e('删除消息', 'help-platform'); ?>
                    </button>
                </div>
            <?php else: ?>
                <div class="no-message-selected">
                    <p><?php _e('消息不存在或已被删除。', 'help-platform'); ?></p>
                </div>
            <?php endif; ?>
            <?php else: ?>
                <div class="no-message-selected">
                    <p><?php _e('请选择一条消息查看详情。', 'help-platform'); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.help-platform-messages {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.messages-container {
    display: grid;
    grid-template-columns: 350px 1fr;
    gap: 20px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    overflow: hidden;
}

.messages-sidebar {
    border-right: 1px solid #eee;
    display: flex;
    flex-direction: column;
}

.sidebar-header {
    padding: 20px;
    border-bottom: 1px solid #eee;
}

.sidebar-header h2 {
    margin: 0 0 15px;
    font-size: 20px;
    color: #333;
}

.message-actions {
    display: flex;
    gap: 10px;
}

.message-actions .button {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 8px 12px;
    font-size: 13px;
}

.message-filters {
    padding: 15px 20px;
    border-bottom: 1px solid #eee;
    display: flex;
    gap: 10px;
}

.message-filters select {
    flex: 1;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.message-list {
    flex: 1;
    overflow-y: auto;
    padding: 10px 0;
}

.message-item {
    display: flex;
    gap: 15px;
    padding: 15px 20px;
    cursor: pointer;
    transition: all 0.3s ease;
    border-bottom: 1px solid #f5f5f5;
}

.message-item:hover {
    background: #f8f9fa;
}

.message-item.active {
    background: #e3f2fd;
}

.message-item.message-unread {
    background: #f8f9fa;
}

.message-item.message-unread .message-title {
    font-weight: 600;
}

.message-icon {
    width: 40px;
    height: 40px;
    background: #f8f9fa;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.message-icon .dashicons {
    font-size: 20px;
    width: 20px;
    height: 20px;
    color: #2196f3;
}

.message-content {
    flex: 1;
    min-width: 0;
}

.message-title {
    margin: 0 0 5px;
    font-size: 15px;
    color: #333;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.message-excerpt {
    font-size: 13px;
    color: #666;
    margin-bottom: 5px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.message-meta {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 12px;
    color: #999;
}

.unread-badge {
    width: 8px;
    height: 8px;
    background: #2196f3;
    border-radius: 50%;
}

.message-detail {
    padding: 20px;
    display: flex;
    flex-direction: column;
}

.detail-header {
    margin-bottom: 20px;
    padding-bottom: 20px;
    border-bottom: 1px solid #eee;
}

.detail-header h2 {
    margin: 0 0 10px;
    font-size: 24px;
    color: #333;
}

.detail-meta {
    display: flex;
    gap: 15px;
    color: #666;
    font-size: 14px;
}

.detail-content {
    flex: 1;
    font-size: 15px;
    line-height: 1.6;
    color: #333;
}

.detail-actions {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #eee;
}

.no-messages,
.no-message-selected {
    padding: 40px 20px;
    text-align: center;
    color: #666;
}

.pagination {
    padding: 15px 20px;
    text-align: center;
    border-top: 1px solid #eee;
}

.pagination .page-numbers {
    display: inline-block;
    padding: 5px 10px;
    margin: 0 2px;
    border: 1px solid #ddd;
    border-radius: 4px;
    color: #666;
    text-decoration: none;
    transition: all 0.3s ease;
}

.pagination .page-numbers.current {
    background: #2196f3;
    border-color: #2196f3;
    color: #fff;
}

.pagination .page-numbers:hover:not(.current) {
    background: #f8f9fa;
    border-color: #2196f3;
    color: #2196f3;
}

@media screen and (max-width: 768px) {
    .messages-container {
        grid-template-columns: 1fr;
    }

    .messages-sidebar {
        border-right: none;
        border-bottom: 1px solid #eee;
    }

    .message-filters {
        flex-direction: column;
    }

    .message-filters select {
        width: 100%;
    }
}

.messages-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.help-button {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 8px 15px;
    background: #e3f2fd;
    color: #2196f3;
    text-decoration: none;
    border-radius: 4px;
    transition: all 0.3s ease;
}

.help-button:hover {
    background: #bbdefb;
    color: #1976d2;
}

.help-button .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}
</style>

<script>
jQuery(document).ready(function($) {
    // 消息点击事件
    $('.message-item').on('click', function() {
        var messageId = $(this).data('id');
        window.location.href = addQueryParam('message_id', messageId);
    });

    // 消息类型筛选
    $('#message-type').on('change', function() {
        filterMessages();
    });

    // 消息状态筛选
    $('#message-status').on('change', function() {
        filterMessages();
    });

    // 筛选消息
    function filterMessages() {
        var type = $('#message-type').val();
        var status = $('#message-status').val();
        
        $.ajax({
            url: helpPlatform.ajaxurl,
            type: 'POST',
            data: {
                action: 'help_platform_filter_messages',
                type: type,
                status: status,
                nonce: helpPlatform.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('.message-list').html(response.data);
                }
            }
        });
    }

    // 标记全部已读
    $('#mark-all-read').on('click', function() {
        if (!confirm(helpPlatform.i18n.confirmMarkAllRead)) {
            return;
        }
        
        $.ajax({
            url: helpPlatform.ajaxurl,
            type: 'POST',
            data: {
                action: 'help_platform_mark_all_read',
                nonce: helpPlatform.nonce
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data);
                }
            }
        });
    });

    // 删除单条消息
    $('#delete-message').on('click', function() {
        if (!confirm(helpPlatform.i18n.confirmDeleteMessage)) {
            return;
        }
        
        var messageId = $(this).data('id');
        
        $.ajax({
            url: helpPlatform.ajaxurl,
            type: 'POST',
            data: {
                action: 'help_platform_delete_message',
                message_id: messageId,
                nonce: helpPlatform.nonce
            },
            success: function(response) {
                if (response.success) {
                    window.location.href = removeQueryParam('message_id');
                } else {
                    alert(response.data);
                }
            }
        });
    });

    // 清空所有消息
    $('#delete-all-messages').on('click', function() {
        if (!confirm(helpPlatform.i18n.confirmDeleteAllMessages)) {
            return;
        }
        
        $.ajax({
            url: helpPlatform.ajaxurl,
            type: 'POST',
            data: {
                action: 'help_platform_delete_all_messages',
                nonce: helpPlatform.nonce
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data);
                }
            }
        });
    });

    // 添加URL参数
    function addQueryParam(key, value) {
        var url = new URL(window.location.href);
        url.searchParams.set(key, value);
        return url.toString();
    }

    // 移除URL参数
    function removeQueryParam(key) {
        var url = new URL(window.location.href);
        url.searchParams.delete(key);
        return url.toString();
    }
});
</script> 