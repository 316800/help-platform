<?php
/**
 * 税费页面模板
 */

if (!defined('ABSPATH')) {
    exit;
}

// 获取用户信息
$user_id = get_current_user_id();
$user = get_userdata($user_id);

// 获取税费统计
$commission = new Help_Platform_Commission();
$stats = $commission->get_tax_stats(array(
    'worker_id' => $user_id,
    'date_start' => date('Y-m-d', strtotime('-30 days')),
    'date_end' => date('Y-m-d')
));

// 获取税费趋势
$trend = $commission->get_tax_trend(array(
    'worker_id' => $user_id,
    'days' => 30,
    'group_by' => 'day'
));

// 获取税费记录
global $wpdb;
$tax_records = $wpdb->get_results($wpdb->prepare(
    "SELECT t.*, j.title as job_title, j.budget as job_budget, b.post_title as branch_name
    FROM {$wpdb->prefix}help_taxes t
    LEFT JOIN {$wpdb->prefix}help_jobs j ON t.job_id = j.id
    LEFT JOIN {$wpdb->posts} b ON j.branch_id = b.id
    WHERE t.worker_id = %d
    ORDER BY t.date DESC
    LIMIT 10",
    $user_id
));

// 获取税费设置
$tax_rate = floatval(get_option('help_platform_tax_rate', 0.06));
$tax_threshold = floatval(get_option('help_platform_tax_threshold', 50000));
?>

<div class="wrap help-platform-tax">
    <h1><?php _e('税费管理', 'help-platform'); ?></h1>

    <!-- 税费统计卡片 -->
    <div class="stats-cards">
        <div class="stat-card">
            <div class="stat-title"><?php _e('总税费', 'help-platform'); ?></div>
            <div class="stat-value">¥<?php echo number_format($stats->total_tax, 2); ?></div>
            <div class="stat-trend">
                <?php
                $yesterday_stats = $commission->get_tax_stats(array(
                    'worker_id' => $user_id,
                    'date_start' => date('Y-m-d', strtotime('-31 days')),
                    'date_end' => date('Y-m-d', strtotime('-1 day'))
                ));
                $trend_rate = $yesterday_stats->total_tax ? 
                    ($stats->total_tax - $yesterday_stats->total_tax) / $yesterday_stats->total_tax * 100 : 0;
                if ($trend_rate > 0) {
                    echo '<span class="trend-up">↑ ' . number_format($trend_rate, 1) . '%</span>';
                } elseif ($trend_rate < 0) {
                    echo '<span class="trend-down">↓ ' . number_format(abs($trend_rate), 1) . '%</span>';
                } else {
                    echo '<span class="trend-flat">0%</span>';
                }
                ?>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-title"><?php _e('本月税费', 'help-platform'); ?></div>
            <div class="stat-value">¥<?php echo number_format($stats->total_tax, 2); ?></div>
            <div class="stat-trend">
                <?php
                $last_month_stats = $commission->get_tax_stats(array(
                    'worker_id' => $user_id,
                    'date_start' => date('Y-m-d', strtotime('first day of last month')),
                    'date_end' => date('Y-m-d', strtotime('last day of last month'))
                ));
                $trend_rate = $last_month_stats->total_tax ? 
                    ($stats->total_tax - $last_month_stats->total_tax) / $last_month_stats->total_tax * 100 : 0;
                if ($trend_rate > 0) {
                    echo '<span class="trend-up">↑ ' . number_format($trend_rate, 1) . '%</span>';
                } elseif ($trend_rate < 0) {
                    echo '<span class="trend-down">↓ ' . number_format(abs($trend_rate), 1) . '%</span>';
                } else {
                    echo '<span class="trend-flat">0%</span>';
                }
                ?>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-title"><?php _e('本年税费', 'help-platform'); ?></div>
            <div class="stat-value">¥<?php echo number_format($stats->year_total_tax, 2); ?></div>
            <div class="stat-trend">
                <?php
                $last_year_stats = $commission->get_tax_stats(array(
                    'worker_id' => $user_id,
                    'date_start' => date('Y-m-d', strtotime('first day of last year')),
                    'date_end' => date('Y-m-d', strtotime('last day of last year'))
                ));
                $trend_rate = $last_year_stats->year_total_tax ? 
                    ($stats->year_total_tax - $last_year_stats->year_total_tax) / $last_year_stats->year_total_tax * 100 : 0;
                if ($trend_rate > 0) {
                    echo '<span class="trend-up">↑ ' . number_format($trend_rate, 1) . '%</span>';
                } elseif ($trend_rate < 0) {
                    echo '<span class="trend-down">↓ ' . number_format(abs($trend_rate), 1) . '%</span>';
                } else {
                    echo '<span class="trend-flat">0%</span>';
                }
                ?>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-title"><?php _e('税费比例', 'help-platform'); ?></div>
            <div class="stat-value"><?php echo number_format($tax_rate * 100, 2); ?>%</div>
            <div class="stat-description">
                <?php 
                printf(
                    __('起征点：%s元/年', 'help-platform'),
                    number_format($tax_threshold)
                ); 
                ?>
            </div>
        </div>
    </div>

    <!-- 税费趋势图表 -->
    <div class="chart-container">
        <canvas id="taxChart"></canvas>
    </div>

    <!-- 税费记录 -->
    <div class="tax-records">
        <h2><?php _e('税费记录', 'help-platform'); ?></h2>
        
        <?php if ($tax_records) : ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('任务', 'help-platform'); ?></th>
                    <th><?php _e('分公司', 'help-platform'); ?></th>
                    <th><?php _e('任务金额', 'help-platform'); ?></th>
                    <th><?php _e('佣金金额', 'help-platform'); ?></th>
                    <th><?php _e('税费金额', 'help-platform'); ?></th>
                    <th><?php _e('税费比例', 'help-platform'); ?></th>
                    <th><?php _e('实际佣金', 'help-platform'); ?></th>
                    <th><?php _e('状态', 'help-platform'); ?></th>
                    <th><?php _e('结算时间', 'help-platform'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tax_records as $record) : ?>
                <tr>
                    <td><?php echo esc_html($record->job_title); ?></td>
                    <td><?php echo esc_html($record->branch_name); ?></td>
                    <td>¥<?php echo number_format($record->job_budget, 2); ?></td>
                    <td>¥<?php echo number_format($record->commission_amount, 2); ?></td>
                    <td>¥<?php echo number_format($record->tax_amount, 2); ?></td>
                    <td><?php echo number_format($record->tax_rate * 100, 2); ?>%</td>
                    <td>¥<?php echo number_format($record->commission_amount - $record->tax_amount, 2); ?></td>
                    <td>
                        <?php
                        switch ($record->status) {
                            case 'pending':
                                echo '<span class="status-pending">' . __('待结算', 'help-platform') . '</span>';
                                break;
                            case 'processing':
                                echo '<span class="status-processing">' . __('结算中', 'help-platform') . '</span>';
                                break;
                            case 'completed':
                                echo '<span class="status-completed">' . __('已结算', 'help-platform') . '</span>';
                                break;
                            case 'failed':
                                echo '<span class="status-failed">' . __('结算失败', 'help-platform') . '</span>';
                                break;
                        }
                        ?>
                    </td>
                    <td><?php echo $record->completed_at ? esc_html($record->completed_at) : '-'; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else : ?>
        <div class="notice notice-info">
            <p><?php _e('暂无税费记录。', 'help-platform'); ?></p>
        </div>
        <?php endif; ?>
    </div>

    <!-- 税费说明 -->
    <div class="tax-info">
        <h2><?php _e('税费说明', 'help-platform'); ?></h2>
        
        <div class="info-content">
            <h3><?php _e('税费计算规则', 'help-platform'); ?></h3>
            <ol>
                <li><?php printf(__('平台按照%s%%的税率代扣代缴个人所得税。', 'help-platform'), number_format($tax_rate * 100, 2)); ?></li>
                <li><?php printf(__('年度收入超过%s元时，需要缴纳个人所得税。', 'help-platform'), number_format($tax_threshold)); ?></li>
                <li><?php _e('税费计算公式：佣金金额 × 税费比例 = 税费金额', 'help-platform'); ?></li>
                <li><?php _e('实际佣金计算公式：佣金金额 - 税费金额 = 实际佣金', 'help-platform'); ?></li>
            </ol>

            <h3><?php _e('税费缴纳说明', 'help-platform'); ?></h3>
            <ol>
                <li><?php _e('平台会在每月结算时，自动从佣金中扣除相应的税费。', 'help-platform'); ?></li>
                <li><?php _e('税费缴纳记录可以在"税费记录"中查看。', 'help-platform'); ?></li>
                <li><?php _e('如需开具完税证明，请联系平台客服。', 'help-platform'); ?></li>
            </ol>

            <h3><?php _e('税费优惠政策', 'help-platform'); ?></h3>
            <ol>
                <li><?php _e('年度收入未超过起征点的，可以申请退税。', 'help-platform'); ?></li>
                <li><?php _e('符合国家税收优惠政策的，可以享受相应的税收减免。', 'help-platform'); ?></li>
                <li><?php _e('具体优惠政策请咨询平台客服或当地税务部门。', 'help-platform'); ?></li>
            </ol>
        </div>
    </div>
</div>

<style>
.help-platform-tax {
    margin: 20px;
}

.stats-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,.1);
}

.stat-title {
    font-size: 14px;
    color: #666;
    margin-bottom: 10px;
}

.stat-value {
    font-size: 24px;
    font-weight: bold;
    color: #2196F3;
    margin-bottom: 10px;
}

.stat-trend {
    font-size: 13px;
}

.stat-description {
    font-size: 13px;
    color: #666;
}

.trend-up {
    color: #4CAF50;
}

.trend-down {
    color: #F44336;
}

.trend-flat {
    color: #9E9E9E;
}

.chart-container {
    background: #fff;
    padding: 20px;
    margin-bottom: 30px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,.1);
}

.tax-records {
    background: #fff;
    padding: 20px;
    margin-bottom: 30px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,.1);
}

.tax-records h2 {
    margin-top: 0;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.tax-records table {
    width: 100%;
    border-collapse: collapse;
}

.tax-records th,
.tax-records td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.tax-records th {
    font-weight: 600;
    background: #f9f9f9;
}

.status-pending {
    color: #FFA000;
}

.status-processing {
    color: #2196F3;
}

.status-completed {
    color: #4CAF50;
}

.status-failed {
    color: #F44336;
}

.tax-info {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,.1);
}

.tax-info h2 {
    margin-top: 0;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.tax-info h3 {
    margin: 20px 0 10px;
    color: #333;
}

.tax-info ol {
    margin: 0;
    padding-left: 20px;
}

.tax-info li {
    margin-bottom: 10px;
    color: #666;
    line-height: 1.6;
}

.tax-info li:last-child {
    margin-bottom: 0;
}
</style>

<script>
jQuery(document).ready(function($) {
    // 初始化图表
    var ctx = document.getElementById('taxChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode(wp_list_pluck($trend, 'period')); ?>,
            datasets: [{
                label: '<?php _e('税费金额', 'help-platform'); ?>',
                data: <?php echo json_encode(wp_list_pluck($trend, 'tax_amount')); ?>,
                borderColor: '#2196F3',
                backgroundColor: 'rgba(33, 150, 243, 0.1)',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: '<?php _e('税费趋势', 'help-platform'); ?>'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return '¥' + context.parsed.y.toLocaleString();
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '¥' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });
});
</script> 