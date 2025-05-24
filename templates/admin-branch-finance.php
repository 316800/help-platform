<?php
if (!defined('ABSPATH')) {
    exit;
}

$branch_id = isset($_GET['post']) ? intval($_GET['post']) : 0;
if (!$branch_id) {
    wp_die(__('无效的分公司ID', 'help-platform'));
}

$branch = get_post($branch_id);
if (!$branch || $branch->post_type !== 'help_branch') {
    wp_die(__('分公司不存在', 'help-platform'));
}

$branch_data = get_post_meta($branch_id, '_help_branch_data', true);
$currency = isset($branch_data['currency']) ? $branch_data['currency'] : 'CNY';

// 处理导出请求
if (isset($_POST['export_finance']) && check_admin_referer('help_platform_export_finance')) {
    $start_date = sanitize_text_field($_POST['start_date']);
    $end_date = sanitize_text_field($_POST['end_date']);
    $type = sanitize_text_field($_POST['export_type']);
    
    // 设置响应头
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=finance-report-' . date('Y-m-d') . '.csv');
    
    // 创建输出流
    $output = fopen('php://output', 'w');
    
    // 添加 BOM 以支持中文
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // 写入表头
    fputcsv($output, array(
        __('时间', 'help-platform'),
        __('类型', 'help-platform'),
        __('金额', 'help-platform'),
        __('手续费', 'help-platform'),
        __('实际金额', 'help-platform'),
        __('状态', 'help-platform'),
        __('方式', 'help-platform'),
        __('备注', 'help-platform')
    ));
    
    // 获取数据
    global $wpdb;
    $where = $wpdb->prepare("branch_id = %d", $branch_id);
    if ($start_date && $end_date) {
        $where .= $wpdb->prepare(" AND created_at BETWEEN %s AND %s", $start_date, $end_date);
    }
    
    $transactions = $wpdb->get_results("
        SELECT * FROM (
            SELECT 
                'recharge' as type,
                created_at,
                amount,
                NULL as fee,
                amount as final_amount,
                status,
                payment_method as method,
                NULL as note
            FROM {$wpdb->prefix}help_recharges
            WHERE $where
            UNION ALL
            SELECT 
                'withdraw' as type,
                created_at,
                amount,
                fee,
                final_amount,
                status,
                method,
                NULL as note
            FROM {$wpdb->prefix}help_withdrawals
            WHERE $where
            UNION ALL
            SELECT 
                'commission' as type,
                created_at,
                amount,
                commission as fee,
                worker_amount as final_amount,
                status,
                NULL as method,
                CONCAT('订单号: ', order_id) as note
            FROM {$wpdb->prefix}help_commissions
            WHERE $where
        ) as transactions
        ORDER BY created_at DESC
    ");
    
    // 写入数据
    foreach ($transactions as $transaction) {
        $row = array(
            date_i18n('Y-m-d H:i:s', strtotime($transaction->created_at)),
            $transaction->type === 'recharge' ? __('充值', 'help-platform') :
            ($transaction->type === 'withdraw' ? __('提现', 'help-platform') : __('佣金', 'help-platform')),
            number_format($transaction->amount, 2) . ' ' . $currency,
            $transaction->fee ? number_format($transaction->fee, 2) . ' ' . $currency : '-',
            number_format($transaction->final_amount, 2) . ' ' . $currency,
            $transaction->status === 'pending' ? __('待处理', 'help-platform') :
            ($transaction->status === 'completed' ? __('已完成', 'help-platform') :
            ($transaction->status === 'failed' ? __('失败', 'help-platform') : __('已取消', 'help-platform'))),
            $transaction->method ?: '-',
            $transaction->note ?: '-'
        );
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit;
}

// 获取筛选参数
$start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : '';
$end_date = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : '';
$type = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : '';
$status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';

// 构建查询条件
$where = $wpdb->prepare("branch_id = %d", $branch_id);
if ($start_date && $end_date) {
    $where .= $wpdb->prepare(" AND created_at BETWEEN %s AND %s", $start_date, $end_date);
}
if ($type) {
    $where .= $wpdb->prepare(" AND type = %s", $type);
}
if ($status) {
    $where .= $wpdb->prepare(" AND status = %s", $status);
}

// 获取财务数据
$finance = Help_Platform_Finance::get_instance();
$balance = $finance->get_branch_balance($branch_id);
$revenue = $finance->get_branch_revenue($branch_id);
$commission = $finance->get_branch_commission($branch_id);
$platform_fee = $finance->get_branch_platform_fee($branch_id);

// 获取交易记录
$transactions = $wpdb->get_results("
    SELECT * FROM (
        SELECT 
            'recharge' as type,
            r.created_at as date,
            r.amount as amount,
            r.status,
            r.payment_method as method,
            NULL as fee,
            NULL as final_amount,
            NULL as note
        FROM {$wpdb->prefix}help_recharges r
        WHERE $where
        UNION ALL
        SELECT 
            'withdraw' as type,
            w.created_at as date,
            w.amount as amount,
            w.status,
            w.method,
            w.fee,
            w.final_amount,
            NULL as note
        FROM {$wpdb->prefix}help_withdrawals w
        WHERE $where
        UNION ALL
        SELECT 
            'commission' as type,
            c.created_at as date,
            c.amount as amount,
            c.status,
            NULL as method,
            c.commission as fee,
            c.worker_amount as final_amount,
            CONCAT('订单号: ', c.order_id) as note
        FROM {$wpdb->prefix}help_commissions c
        WHERE $where
    ) as transactions
    ORDER BY date DESC
    LIMIT 50
");
?>

<div class="wrap">
    <h1><?php printf(__('%s - 财务概览', 'help-platform'), $branch->post_title); ?></h1>

    <div class="help-platform-finance-overview">
        <div class="help-platform-finance-card">
            <h3><?php _e('账户余额', 'help-platform'); ?></h3>
            <div class="help-platform-finance-amount">
                <?php echo number_format($balance, 2) . ' ' . $currency; ?>
            </div>
        </div>

        <div class="help-platform-finance-card">
            <h3><?php _e('总收入', 'help-platform'); ?></h3>
            <div class="help-platform-finance-amount">
                <?php echo number_format($revenue, 2) . ' ' . $currency; ?>
            </div>
        </div>

        <div class="help-platform-finance-card">
            <h3><?php _e('佣金支出', 'help-platform'); ?></h3>
            <div class="help-platform-finance-amount">
                <?php echo number_format($commission, 2) . ' ' . $currency; ?>
            </div>
        </div>

        <div class="help-platform-finance-card">
            <h3><?php _e('平台收入', 'help-platform'); ?></h3>
            <div class="help-platform-finance-amount">
                <?php echo number_format($platform_fee, 2) . ' ' . $currency; ?>
            </div>
        </div>
    </div>

    <!-- 筛选和导出表单 -->
    <div class="help-platform-finance-filters">
        <form method="get" class="help-platform-filter-form">
            <input type="hidden" name="post" value="<?php echo esc_attr($branch_id); ?>">
            <input type="hidden" name="page" value="help-platform-branch-finance">
            
            <div class="filter-group">
                <label for="start_date"><?php _e('开始日期', 'help-platform'); ?></label>
                <input type="date" id="start_date" name="start_date" value="<?php echo esc_attr($start_date); ?>">
            </div>
            
            <div class="filter-group">
                <label for="end_date"><?php _e('结束日期', 'help-platform'); ?></label>
                <input type="date" id="end_date" name="end_date" value="<?php echo esc_attr($end_date); ?>">
            </div>
            
            <div class="filter-group">
                <label for="type"><?php _e('交易类型', 'help-platform'); ?></label>
                <select id="type" name="type">
                    <option value=""><?php _e('全部', 'help-platform'); ?></option>
                    <option value="recharge" <?php selected($type, 'recharge'); ?>><?php _e('充值', 'help-platform'); ?></option>
                    <option value="withdraw" <?php selected($type, 'withdraw'); ?>><?php _e('提现', 'help-platform'); ?></option>
                    <option value="commission" <?php selected($type, 'commission'); ?>><?php _e('佣金', 'help-platform'); ?></option>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="status"><?php _e('状态', 'help-platform'); ?></label>
                <select id="status" name="status">
                    <option value=""><?php _e('全部', 'help-platform'); ?></option>
                    <option value="pending" <?php selected($status, 'pending'); ?>><?php _e('待处理', 'help-platform'); ?></option>
                    <option value="completed" <?php selected($status, 'completed'); ?>><?php _e('已完成', 'help-platform'); ?></option>
                    <option value="failed" <?php selected($status, 'failed'); ?>><?php _e('失败', 'help-platform'); ?></option>
                    <option value="cancelled" <?php selected($status, 'cancelled'); ?>><?php _e('已取消', 'help-platform'); ?></option>
                </select>
            </div>
            
            <div class="filter-actions">
                <button type="submit" class="button"><?php _e('筛选', 'help-platform'); ?></button>
                <a href="?post=<?php echo esc_attr($branch_id); ?>&page=help-platform-branch-finance" class="button"><?php _e('重置', 'help-platform'); ?></a>
            </div>
        </form>

        <form method="post" class="help-platform-export-form">
            <?php wp_nonce_field('help_platform_export_finance'); ?>
            <input type="hidden" name="start_date" value="<?php echo esc_attr($start_date); ?>">
            <input type="hidden" name="end_date" value="<?php echo esc_attr($end_date); ?>">
            <input type="hidden" name="type" value="<?php echo esc_attr($type); ?>">
            <input type="hidden" name="status" value="<?php echo esc_attr($status); ?>">
            <button type="submit" name="export_finance" class="button button-primary"><?php _e('导出报表', 'help-platform'); ?></button>
        </form>
    </div>

    <div class="help-platform-finance-transactions">
        <h2><?php _e('交易记录', 'help-platform'); ?></h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('时间', 'help-platform'); ?></th>
                    <th><?php _e('类型', 'help-platform'); ?></th>
                    <th><?php _e('金额', 'help-platform'); ?></th>
                    <th><?php _e('手续费', 'help-platform'); ?></th>
                    <th><?php _e('实际金额', 'help-platform'); ?></th>
                    <th><?php _e('状态', 'help-platform'); ?></th>
                    <th><?php _e('方式', 'help-platform'); ?></th>
                    <th><?php _e('备注', 'help-platform'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transactions as $transaction): ?>
                    <tr>
                        <td><?php echo date_i18n('Y-m-d H:i:s', strtotime($transaction->date)); ?></td>
                        <td>
                            <?php
                            switch ($transaction->type) {
                                case 'recharge':
                                    _e('充值', 'help-platform');
                                    break;
                                case 'withdraw':
                                    _e('提现', 'help-platform');
                                    break;
                                case 'commission':
                                    _e('佣金', 'help-platform');
                                    break;
                            }
                            ?>
                        </td>
                        <td><?php echo number_format($transaction->amount, 2) . ' ' . $currency; ?></td>
                        <td>
                            <?php
                            if ($transaction->fee !== null) {
                                echo number_format($transaction->fee, 2) . ' ' . $currency;
                            } else {
                                echo '-';
                            }
                            ?>
                        </td>
                        <td>
                            <?php
                            if ($transaction->final_amount !== null) {
                                echo number_format($transaction->final_amount, 2) . ' ' . $currency;
                            } else {
                                echo '-';
                            }
                            ?>
                        </td>
                        <td>
                            <span class="help-platform-status-badge status-<?php echo esc_attr($transaction->status); ?>">
                                <?php
                                switch ($transaction->status) {
                                    case 'pending':
                                        _e('待处理', 'help-platform');
                                        break;
                                    case 'completed':
                                        _e('已完成', 'help-platform');
                                        break;
                                    case 'failed':
                                        _e('失败', 'help-platform');
                                        break;
                                    case 'cancelled':
                                        _e('已取消', 'help-platform');
                                        break;
                                }
                                ?>
                            </span>
                        </td>
                        <td><?php echo $transaction->method ? esc_html($transaction->method) : '-'; ?></td>
                        <td><?php echo $transaction->note ? esc_html($transaction->note) : '-'; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
.help-platform-finance-overview {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.help-platform-finance-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 20px;
    text-align: center;
}

.help-platform-finance-card h3 {
    margin: 0 0 10px;
    color: #23282d;
}

.help-platform-finance-amount {
    font-size: 24px;
    font-weight: bold;
    color: #0073aa;
}

.help-platform-finance-transactions {
    margin-top: 30px;
}

.help-platform-status-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.help-platform-status-badge.status-pending {
    background: #f0b849;
    color: #fff;
}

.help-platform-status-badge.status-completed {
    background: #46b450;
    color: #fff;
}

.help-platform-status-badge.status-failed {
    background: #dc3232;
    color: #fff;
}

.help-platform-status-badge.status-cancelled {
    background: #999;
    color: #fff;
}

.help-platform-finance-filters {
    margin: 20px 0;
    padding: 15px;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.help-platform-filter-form {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    align-items: flex-end;
    margin-bottom: 15px;
}

.filter-group {
    flex: 1;
    min-width: 200px;
}

.filter-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
}

.filter-group input,
.filter-group select {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.filter-actions {
    display: flex;
    gap: 10px;
}

.help-platform-export-form {
    text-align: right;
}

@media screen and (max-width: 782px) {
    .help-platform-filter-form {
        flex-direction: column;
    }
    
    .filter-group {
        width: 100%;
    }
    
    .filter-actions {
        width: 100%;
        justify-content: flex-end;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // 日期选择器初始化
    $('#start_date, #end_date').datepicker({
        dateFormat: 'yy-mm-dd',
        changeMonth: true,
        changeYear: true
    });
    
    // 导出表单提交前验证
    $('.help-platform-export-form').on('submit', function(e) {
        var startDate = $('#start_date').val();
        var endDate = $('#end_date').val();
        
        if (startDate && endDate && new Date(startDate) > new Date(endDate)) {
            alert('<?php _e('开始日期不能大于结束日期', 'help-platform'); ?>');
            e.preventDefault();
            return false;
        }
    });
});
</script> 