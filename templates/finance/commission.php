<?php
/**
 * 佣金页面模板
 */

if (!defined('ABSPATH')) {
    exit;
}

// 获取用户信息
$user_id = get_current_user_id();
$user = get_userdata($user_id);

// 获取佣金统计
$commission = new Help_Platform_Commission();
$stats = $commission->get_commission_stats(array(
    'worker_id' => $user_id,
    'date_start' => date('Y-m-d', strtotime('-30 days')),
    'date_end' => date('Y-m-d')
));

// 获取佣金趋势
$trend = $commission->get_commission_trend(array(
    'worker_id' => $user_id,
    'days' => 30,
    'group_by' => 'day'
));

// 获取佣金记录
global $wpdb;
$commission_records = $wpdb->get_results($wpdb->prepare(
    "SELECT c.*, j.title as job_title, j.budget as job_budget, b.post_title as branch_name
    FROM {$wpdb->prefix}help_commission c
    LEFT JOIN {$wpdb->prefix}help_jobs j ON c.id = j.commission_id
    LEFT JOIN {$wpdb->posts} b ON j.branch_id = b.id
    WHERE c.worker_id = %d
    ORDER BY c.date DESC
    LIMIT 10",
    $user_id
));
?>

<div class="wrap help-platform-commission">
    <h1><?php _e('佣金管理', 'help-platform'); ?></h1>

    <!-- 佣金统计卡片 -->
    <div class="stats-cards">
        <div class="stat-card">
            <div class="stat-title"><?php _e('总佣金', 'help-platform'); ?></div>
            <div class="stat-value">¥<?php echo number_format($stats->total_worker_commission, 2); ?></div>
            <div class="stat-trend">
                <?php
                $yesterday_stats = $commission->get_commission_stats(array(
                    'worker_id' => $user_id,
                    'date_start' => date('Y-m-d', strtotime('-31 days')),
                    'date_end' => date('Y-m-d', strtotime('-1 day'))
                ));
                $trend_rate = $yesterday_stats->total_worker_commission ? 
                    ($stats->total_worker_commission - $yesterday_stats->total_worker_commission) / $yesterday_stats->total_worker_commission * 100 : 0;
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
            <div class="stat-title"><?php _e('本月佣金', 'help-platform'); ?></div>
            <div class="stat-value">¥<?php echo number_format($stats->total_worker_commission, 2); ?></div>
            <div class="stat-trend">
                <?php
                $last_month_stats = $commission->get_commission_stats(array(
                    'worker_id' => $user_id,
                    'date_start' => date('Y-m-d', strtotime('first day of last month')),
                    'date_end' => date('Y-m-d', strtotime('last day of last month'))
                ));
                $trend_rate = $last_month_stats->total_worker_commission ? 
                    ($stats->total_worker_commission - $last_month_stats->total_worker_commission) / $last_month_stats->total_worker_commission * 100 : 0;
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
            <div class="stat-title"><?php _e('任务数', 'help-platform'); ?></div>
            <div class="stat-value"><?php echo number_format($stats->total_count); ?></div>
            <div class="stat-trend">
                <?php
                $trend_rate = $yesterday_stats->total_count ? 
                    ($stats->total_count - $yesterday_stats->total_count) / $yesterday_stats->total_count * 100 : 0;
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
            <div class="stat-title"><?php _e('平均佣金', 'help-platform'); ?></div>
            <div class="stat-value">¥<?php echo number_format($stats->avg_worker_rate * 100, 2); ?></div>
            <div class="stat-trend">
                <?php
                $trend_rate = $yesterday_stats->avg_worker_rate ? 
                    ($stats->avg_worker_rate - $yesterday_stats->avg_worker_rate) / $yesterday_stats->avg_worker_rate * 100 : 0;
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
    </div>

    <!-- 佣金趋势图表 -->
    <div class="chart-container">
        <canvas id="commissionChart"></canvas>
    </div>

    <!-- 佣金记录 -->
    <div class="commission-records">
        <h2><?php _e('佣金记录', 'help-platform'); ?></h2>
        
        <?php if ($commission_records) : ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('任务', 'help-platform'); ?></th>
                    <th><?php _e('分公司', 'help-platform'); ?></th>
                    <th><?php _e('任务金额', 'help-platform'); ?></th>
                    <th><?php _e('佣金金额', 'help-platform'); ?></th>
                    <th><?php _e('佣金比例', 'help-platform'); ?></th>
                    <th><?php _e('税费', 'help-platform'); ?></th>
                    <th><?php _e('实际佣金', 'help-platform'); ?></th>
                    <th><?php _e('状态', 'help-platform'); ?></th>
                    <th><?php _e('结算时间', 'help-platform'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($commission_records as $record) : ?>
                <tr>
                    <td><?php echo esc_html($record->job_title); ?></td>
                    <td><?php echo esc_html($record->branch_name); ?></td>
                    <td>¥<?php echo number_format($record->job_budget, 2); ?></td>
                    <td>¥<?php echo number_format($record->worker_commission, 2); ?></td>
                    <td><?php echo number_format($record->worker_commission / $record->job_budget * 100, 2); ?>%</td>
                    <td>¥<?php echo number_format($record->tax_amount, 2); ?></td>
                    <td>¥<?php echo number_format($record->worker_commission - $record->tax_amount, 2); ?></td>
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
            <p><?php _e('暂无佣金记录。', 'help-platform'); ?></p>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
.help-platform-commission {
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

.commission-records {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,.1);
}

.commission-records h2 {
    margin-top: 0;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.commission-records table {
    width: 100%;
    border-collapse: collapse;
}

.commission-records th,
.commission-records td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.commission-records th {
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
</style>

<script>
jQuery(document).ready(function($) {
    // 初始化图表
    var ctx = document.getElementById('commissionChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode(wp_list_pluck($trend, 'period')); ?>,
            datasets: [{
                label: '<?php _e('佣金金额', 'help-platform'); ?>',
                data: <?php echo json_encode(wp_list_pluck($trend, 'worker_commission')); ?>,
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
                    text: '<?php _e('佣金趋势', 'help-platform'); ?>'
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