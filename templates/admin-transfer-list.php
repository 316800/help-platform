<?php
if (!defined('ABSPATH')) {
    exit;
}

// 检查权限
if (!current_user_can('manage_help_platform_finance')) {
    wp_die(__('您没有权限访问此页面。', 'help-platform'));
}

// 获取筛选参数
$status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
$from_branch = isset($_GET['from_branch']) ? intval($_GET['from_branch']) : 0;
$to_branch = isset($_GET['to_branch']) ? intval($_GET['to_branch']) : 0;
$date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';

// 构建查询参数
$args = array(
    'post_type' => 'help_transfer',
    'posts_per_page' => 20,
    'paged' => get_query_var('paged') ? get_query_var('paged') : 1,
    'meta_query' => array()
);

if ($status) {
    $args['meta_query'][] = array(
        'key' => '_help_transfer_status',
        'value' => $status
    );
}

if ($from_branch) {
    $args['meta_query'][] = array(
        'key' => '_help_transfer_from_branch',
        'value' => $from_branch
    );
}

if ($to_branch) {
    $args['meta_query'][] = array(
        'key' => '_help_transfer_to_branch',
        'value' => $to_branch
    );
}

if ($date_from || $date_to) {
    $date_query = array();
    if ($date_from) {
        $date_query['after'] = $date_from;
    }
    if ($date_to) {
        $date_query['before'] = $date_to;
    }
    $args['date_query'] = array($date_query);
}

// 获取转账记录
$transfers = new WP_Query($args);

// 获取所有分公司
$branches = get_posts(array(
    'post_type' => 'help_branch',
    'posts_per_page' => -1,
    'orderby' => 'title',
    'order' => 'ASC'
));
?>

<div class="wrap help-platform-transfer-list">
    <h1 class="wp-heading-inline"><?php _e('分公司转账管理', 'help-platform'); ?></h1>
    <a href="<?php echo admin_url('post-new.php?post_type=help_transfer'); ?>" class="page-title-action">
        <?php _e('新建转账', 'help-platform'); ?>
    </a>

    <!-- 筛选表单 -->
    <div class="tablenav top">
        <form method="get" class="alignleft actions">
            <input type="hidden" name="post_type" value="help_transfer">
            
            <select name="status">
                <option value=""><?php _e('所有状态', 'help-platform'); ?></option>
                <option value="pending" <?php selected($status, 'pending'); ?>><?php _e('待处理', 'help-platform'); ?></option>
                <option value="completed" <?php selected($status, 'completed'); ?>><?php _e('已完成', 'help-platform'); ?></option>
                <option value="failed" <?php selected($status, 'failed'); ?>><?php _e('失败', 'help-platform'); ?></option>
                <option value="cancelled" <?php selected($status, 'cancelled'); ?>><?php _e('已取消', 'help-platform'); ?></option>
            </select>

            <select name="from_branch">
                <option value=""><?php _e('所有转出分公司', 'help-platform'); ?></option>
                <?php foreach ($branches as $branch): ?>
                    <option value="<?php echo $branch->ID; ?>" <?php selected($from_branch, $branch->ID); ?>>
                        <?php echo esc_html($branch->post_title); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="to_branch">
                <option value=""><?php _e('所有转入分公司', 'help-platform'); ?></option>
                <?php foreach ($branches as $branch): ?>
                    <option value="<?php echo $branch->ID; ?>" <?php selected($to_branch, $branch->ID); ?>>
                        <?php echo esc_html($branch->post_title); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <input type="date" name="date_from" value="<?php echo esc_attr($date_from); ?>" placeholder="<?php _e('开始日期', 'help-platform'); ?>">
            <input type="date" name="date_to" value="<?php echo esc_attr($date_to); ?>" placeholder="<?php _e('结束日期', 'help-platform'); ?>">

            <input type="submit" class="button" value="<?php _e('筛选', 'help-platform'); ?>">
        </form>
    </div>

    <!-- 转账列表 -->
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php _e('转账编号', 'help-platform'); ?></th>
                <th><?php _e('转出分公司', 'help-platform'); ?></th>
                <th><?php _e('转入分公司', 'help-platform'); ?></th>
                <th><?php _e('金额', 'help-platform'); ?></th>
                <th><?php _e('状态', 'help-platform'); ?></th>
                <th><?php _e('创建时间', 'help-platform'); ?></th>
                <th><?php _e('完成时间', 'help-platform'); ?></th>
                <th><?php _e('操作', 'help-platform'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ($transfers->have_posts()): ?>
                <?php while ($transfers->have_posts()): $transfers->the_post(); 
                    $transfer_data = get_post_meta(get_the_ID(), '_help_transfer_data', true);
                    $from_branch = get_post($transfer_data['from_branch_id']);
                    $to_branch = get_post($transfer_data['to_branch_id']);
                    $from_branch_data = get_post_meta($from_branch->ID, '_help_branch_data', true);
                ?>
                    <tr>
                        <td><?php echo get_the_ID(); ?></td>
                        <td><?php echo esc_html($from_branch->post_title); ?></td>
                        <td><?php echo esc_html($to_branch->post_title); ?></td>
                        <td>
                            <?php 
                            echo number_format($transfer_data['amount'], 2) . ' ' . 
                                 esc_html($from_branch_data['currency']); 
                            ?>
                        </td>
                        <td>
                            <?php
                            $status_labels = array(
                                'pending' => __('待处理', 'help-platform'),
                                'completed' => __('已完成', 'help-platform'),
                                'failed' => __('失败', 'help-platform'),
                                'cancelled' => __('已取消', 'help-platform')
                            );
                            $status_class = array(
                                'pending' => 'status-pending',
                                'completed' => 'status-completed',
                                'failed' => 'status-failed',
                                'cancelled' => 'status-cancelled'
                            );
                            ?>
                            <span class="status-badge <?php echo $status_class[$transfer_data['status']]; ?>">
                                <?php echo $status_labels[$transfer_data['status']]; ?>
                            </span>
                        </td>
                        <td><?php echo get_the_date('Y-m-d H:i:s'); ?></td>
                        <td>
                            <?php 
                            if ($transfer_data['status'] === 'completed') {
                                echo get_post_meta(get_the_ID(), '_help_transfer_completed_time', true);
                            } else {
                                echo '—';
                            }
                            ?>
                        </td>
                        <td>
                            <?php if ($transfer_data['status'] === 'pending'): ?>
                                <a href="<?php echo get_edit_post_link(); ?>" class="button button-small">
                                    <?php _e('编辑', 'help-platform'); ?>
                                </a>
                                <a href="#" class="button button-small button-primary execute-transfer" 
                                   data-id="<?php echo get_the_ID(); ?>">
                                    <?php _e('执行转账', 'help-platform'); ?>
                                </a>
                                <a href="#" class="button button-small button-link-delete cancel-transfer" 
                                   data-id="<?php echo get_the_ID(); ?>">
                                    <?php _e('取消', 'help-platform'); ?>
                                </a>
                            <?php endif; ?>
                            <a href="<?php echo get_edit_post_link(); ?>" class="button button-small">
                                <?php _e('查看', 'help-platform'); ?>
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8"><?php _e('没有找到转账记录。', 'help-platform'); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- 分页 -->
    <?php
    echo paginate_links(array(
        'base' => str_replace(999999999, '%#%', esc_url(get_pagenum_link(999999999))),
        'format' => '?paged=%#%',
        'current' => max(1, get_query_var('paged')),
        'total' => $transfers->max_num_pages
    ));
    ?>
</div>

<style>
.help-platform-transfer-list .tablenav {
    margin: 15px 0;
}

.help-platform-transfer-list .tablenav select,
.help-platform-transfer-list .tablenav input[type="date"] {
    margin-right: 8px;
}

.help-platform-transfer-list .status-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: 600;
}

.help-platform-transfer-list .status-pending {
    background: #f0f0f1;
    color: #50575e;
}

.help-platform-transfer-list .status-completed {
    background: #d1e7dd;
    color: #0f5132;
}

.help-platform-transfer-list .status-failed {
    background: #f8d7da;
    color: #842029;
}

.help-platform-transfer-list .status-cancelled {
    background: #fff3cd;
    color: #664d03;
}

.help-platform-transfer-list .button-small {
    margin-right: 5px;
}

.help-platform-transfer-list .button-link-delete {
    color: #dc3545;
}

.help-platform-transfer-list .button-link-delete:hover {
    color: #bb2d3b;
}
</style>

<script>
jQuery(document).ready(function($) {
    // 执行转账
    $('.execute-transfer').on('click', function(e) {
        e.preventDefault();
        var transferId = $(this).data('id');
        
        if (!confirm('<?php _e('确定要执行此转账吗？此操作不可撤销。', 'help-platform'); ?>')) {
            return;
        }

        $.post(ajaxurl, {
            action: 'help_platform_execute_transfer',
            transfer_id: transferId,
            nonce: '<?php echo wp_create_nonce('help_platform_execute_transfer'); ?>'
        }, function(response) {
            if (response.success) {
                alert(response.data.message);
                location.reload();
            } else {
                alert(response.data.message);
            }
        });
    });

    // 取消转账
    $('.cancel-transfer').on('click', function(e) {
        e.preventDefault();
        var transferId = $(this).data('id');
        
        if (!confirm('<?php _e('确定要取消此转账吗？此操作不可撤销。', 'help-platform'); ?>')) {
            return;
        }

        $.post(ajaxurl, {
            action: 'help_platform_cancel_transfer',
            transfer_id: transferId,
            nonce: '<?php echo wp_create_nonce('help_platform_cancel_transfer'); ?>'
        }, function(response) {
            if (response.success) {
                alert(response.data.message);
                location.reload();
            } else {
                alert(response.data.message);
            }
        });
    });
});
</script> 