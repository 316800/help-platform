<?php
if (!defined('ABSPATH')) {
    exit;
}

// 获取筛选参数
$branch_id = isset($_GET['branch_id']) ? intval($_GET['branch_id']) : 0;
$date_start = isset($_GET['date_start']) ? sanitize_text_field($_GET['date_start']) : date('Y-m-01');
$date_end = isset($_GET['date_end']) ? sanitize_text_field($_GET['date_end']) : date('Y-m-d');
$status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';

// 获取所有分公司
$branches = get_posts(array(
    'post_type' => 'help_branch',
    'posts_per_page' => -1,
    'orderby' => 'title',
    'order' => 'ASC',
));

// 获取佣金统计数据
global $wpdb;
$where = array('1=1');
$params = array();

if ($branch_id) {
    $where[] = 'branch_id = %d';
    $params[] = $branch_id;
}

if ($date_start) {
    $where[] = 'date >= %s';
    $params[] = $date_start;
}

if ($date_end) {
    $where[] = 'date <= %s';
    $params[] = $date_end;
}

if ($status) {
    $where[] = 'status = %s';
    $params[] = $status;
}

$where = implode(' AND ', $where);
$query = $wpdb->prepare("
    SELECT 
        branch_id,
        SUM(platform_commission) as total_platform_commission,
        SUM(branch_commission) as total_branch_commission,
        SUM(worker_commission) as total_worker_commission,
        SUM(tax_amount) as total_tax_amount,
        SUM(amount) as total_amount,
        COUNT(*) as total_transactions
    FROM {$wpdb->prefix}help_commission
    WHERE {$where}
    GROUP BY branch_id
", $params);

$commission_stats = $wpdb->get_results($query);

// 计算总计
$totals = array(
    'platform_commission' => 0,
    'branch_commission' => 0,
    'worker_commission' => 0,
    'tax_amount' => 0,
    'amount' => 0,
    'transactions' => 0,
);

foreach ($commission_stats as $stat) {
    $totals['platform_commission'] += $stat->total_platform_commission;
    $totals['branch_commission'] += $stat->total_branch_commission;
    $totals['worker_commission'] += $stat->total_worker_commission;
    $totals['tax_amount'] += $stat->total_tax_amount;
    $totals['amount'] += $stat->total_amount;
    $totals['transactions'] += $stat->total_transactions;
}
?>

<div class="wrap">
    <h1><?php _e('佣金统计', 'help-platform'); ?></h1>

    <!-- 筛选表单 -->
    <form method="get" class="commission-filter">
        <input type="hidden" name="page" value="help-platform-commission">
        
        <select name="branch_id">
            <option value=""><?php _e('所有分公司', 'help-platform'); ?></option>
            <?php foreach ($branches as $branch): ?>
                <option value="<?php echo esc_attr($branch->ID); ?>" <?php selected($branch_id, $branch->ID); ?>>
                    <?php echo esc_html($branch->post_title); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <input type="date" name="date_start" value="<?php echo esc_attr($date_start); ?>" placeholder="<?php esc_attr_e('开始日期', 'help-platform'); ?>">
        <input type="date" name="date_end" value="<?php echo esc_attr($date_end); ?>" placeholder="<?php esc_attr_e('结束日期', 'help-platform'); ?>">

        <select name="status">
            <option value=""><?php _e('所有状态', 'help-platform'); ?></option>
            <option value="pending" <?php selected($status, 'pending'); ?>><?php _e('待处理', 'help-platform'); ?></option>
            <option value="completed" <?php selected($status, 'completed'); ?>><?php _e('已完成', 'help-platform'); ?></option>
            <option value="cancelled" <?php selected($status, 'cancelled'); ?>><?php _e('已取消', 'help-platform'); ?></option>
        </select>

        <button type="submit" class="button"><?php _e('筛选', 'help-platform'); ?></button>
        <a href="<?php echo esc_url(admin_url('admin.php?page=help-platform-commission')); ?>" class="button"><?php _e('重置', 'help-platform'); ?></a>
    </form>

    <!-- 统计表格 -->
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php _e('分公司', 'help-platform'); ?></th>
                <th><?php _e('平台佣金', 'help-platform'); ?></th>
                <th><?php _e('分公司佣金', 'help-platform'); ?></th>
                <th><?php _e('工作者佣金', 'help-platform'); ?></th>
                <th><?php _e('税费', 'help-platform'); ?></th>
                <th><?php _e('总金额', 'help-platform'); ?></th>
                <th><?php _e('交易笔数', 'help-platform'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ($commission_stats): ?>
                <?php foreach ($commission_stats as $stat): 
                    $branch = get_post($stat->branch_id);
                    if (!$branch) continue;
                ?>
                    <tr>
                        <td><?php echo esc_html($branch->post_title); ?></td>
                        <td><?php echo esc_html(number_format($stat->total_platform_commission, 2)); ?></td>
                        <td><?php echo esc_html(number_format($stat->total_branch_commission, 2)); ?></td>
                        <td><?php echo esc_html(number_format($stat->total_worker_commission, 2)); ?></td>
                        <td><?php echo esc_html(number_format($stat->total_tax_amount, 2)); ?></td>
                        <td><?php echo esc_html(number_format($stat->total_amount, 2)); ?></td>
                        <td><?php echo esc_html($stat->total_transactions); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7"><?php _e('暂无数据', 'help-platform'); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <th><?php _e('总计', 'help-platform'); ?></th>
                <th><?php echo esc_html(number_format($totals['platform_commission'], 2)); ?></th>
                <th><?php echo esc_html(number_format($totals['branch_commission'], 2)); ?></th>
                <th><?php echo esc_html(number_format($totals['worker_commission'], 2)); ?></th>
                <th><?php echo esc_html(number_format($totals['tax_amount'], 2)); ?></th>
                <th><?php echo esc_html(number_format($totals['amount'], 2)); ?></th>
                <th><?php echo esc_html($totals['transactions']); ?></th>
            </tr>
        </tfoot>
    </table>

    <!-- 导出按钮 -->
    <p class="submit">
        <a href="<?php echo esc_url(add_query_arg('action', 'export_commission', wp_nonce_url(admin_url('admin-post.php'), 'export_commission'))); ?>" class="button button-primary">
            <?php _e('导出数据', 'help-platform'); ?>
        </a>
    </p>
</div>

<style>
.commission-filter {
    margin: 20px 0;
    padding: 15px;
    background: #fff;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.commission-filter select,
.commission-filter input[type="date"] {
    margin-right: 10px;
    min-width: 150px;
}

.wp-list-table th {
    font-weight: 600;
}

.wp-list-table td,
.wp-list-table th {
    text-align: right;
}

.wp-list-table td:first-child,
.wp-list-table th:first-child {
    text-align: left;
}

.wp-list-table tfoot th {
    background: #f0f0f1;
}
</style>

<script>
jQuery(document).ready(function($) {
    // 日期选择器
    $('input[type="date"]').datepicker({
        dateFormat: 'yy-mm-dd',
        changeMonth: true,
        changeYear: true
    });
});
</script> 