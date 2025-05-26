<?php
/**
 * 财务报表页面模板
 */

if (!defined('ABSPATH')) {
    exit;
}

// 获取报表类型
$report_type = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : 'financial';
$date_start = isset($_GET['date_start']) ? sanitize_text_field($_GET['date_start']) : date('Y-m-d', strtotime('-30 days'));
$date_end = isset($_GET['date_end']) ? sanitize_text_field($_GET['date_end']) : date('Y-m-d');
$branch_id = isset($_GET['branch_id']) ? intval($_GET['branch_id']) : 0;
$worker_id = isset($_GET['worker_id']) ? intval($_GET['worker_id']) : 0;

// 获取报表数据
$report = new Help_Platform_Report();
$report_data = array();

switch ($report_type) {
    case 'financial':
        $report_data = $report->generate_financial_report(array(
            'date_start' => $date_start,
            'date_end' => $date_end,
            'branch_id' => $branch_id,
            'type' => isset($_GET['group_by']) ? sanitize_text_field($_GET['group_by']) : 'daily'
        ));
        break;
    case 'branch':
        $report_data = $report->generate_branch_report(array(
            'date_start' => $date_start,
            'date_end' => $date_end,
            'branch_id' => $branch_id
        ));
        break;
    case 'worker':
        $report_data = $report->generate_worker_report(array(
            'date_start' => $date_start,
            'date_end' => $date_end,
            'branch_id' => $branch_id,
            'worker_id' => $worker_id
        ));
        break;
}

// 生成图表数据
$chart_data = $report->generate_chart_data($report_data, isset($_GET['chart_type']) ? sanitize_text_field($_GET['chart_type']) : 'line');
?>

<div class="wrap help-platform-report">
    <h1><?php _e('财务报表', 'help-platform'); ?></h1>

    <!-- 筛选表单 -->
    <form method="get" class="help-platform-filter">
        <input type="hidden" name="page" value="help-platform-report">
        
        <div class="filter-row">
            <div class="filter-item">
                <label for="report_type"><?php _e('报表类型', 'help-platform'); ?></label>
                <select name="type" id="report_type">
                    <option value="financial" <?php selected($report_type, 'financial'); ?>><?php _e('财务报表', 'help-platform'); ?></option>
                    <option value="branch" <?php selected($report_type, 'branch'); ?>><?php _e('分公司报表', 'help-platform'); ?></option>
                    <option value="worker" <?php selected($report_type, 'worker'); ?>><?php _e('工人报表', 'help-platform'); ?></option>
                </select>
            </div>

            <div class="filter-item">
                <label for="date_start"><?php _e('开始日期', 'help-platform'); ?></label>
                <input type="date" name="date_start" id="date_start" value="<?php echo esc_attr($date_start); ?>">
            </div>

            <div class="filter-item">
                <label for="date_end"><?php _e('结束日期', 'help-platform'); ?></label>
                <input type="date" name="date_end" id="date_end" value="<?php echo esc_attr($date_end); ?>">
            </div>

            <div class="filter-item branch-filter" style="display: <?php echo $report_type === 'worker' ? 'block' : 'none'; ?>">
                <label for="branch_id"><?php _e('分公司', 'help-platform'); ?></label>
                <select name="branch_id" id="branch_id">
                    <option value=""><?php _e('全部', 'help-platform'); ?></option>
                    <?php
                    $branches = get_posts(array(
                        'post_type' => 'help_branch',
                        'posts_per_page' => -1,
                        'orderby' => 'title',
                        'order' => 'ASC'
                    ));
                    foreach ($branches as $branch) {
                        printf(
                            '<option value="%d" %s>%s</option>',
                            $branch->ID,
                            selected($branch_id, $branch->ID, false),
                            esc_html($branch->post_title)
                        );
                    }
                    ?>
                </select>
            </div>

            <div class="filter-item worker-filter" style="display: <?php echo $report_type === 'worker' ? 'block' : 'none'; ?>">
                <label for="worker_id"><?php _e('工人', 'help-platform'); ?></label>
                <select name="worker_id" id="worker_id">
                    <option value=""><?php _e('全部', 'help-platform'); ?></option>
                    <?php
                    $workers = get_users(array(
                        'role' => 'help_worker',
                        'orderby' => 'display_name',
                        'order' => 'ASC'
                    ));
                    foreach ($workers as $worker) {
                        printf(
                            '<option value="%d" %s>%s</option>',
                            $worker->ID,
                            selected($worker_id, $worker->ID, false),
                            esc_html($worker->display_name)
                        );
                    }
                    ?>
                </select>
            </div>

            <div class="filter-item group-by-filter" style="display: <?php echo $report_type === 'financial' ? 'block' : 'none'; ?>">
                <label for="group_by"><?php _e('分组方式', 'help-platform'); ?></label>
                <select name="group_by" id="group_by">
                    <option value="daily" <?php selected(isset($_GET['group_by']) ? $_GET['group_by'] : 'daily', 'daily'); ?>><?php _e('按日', 'help-platform'); ?></option>
                    <option value="weekly" <?php selected(isset($_GET['group_by']) ? $_GET['group_by'] : 'daily', 'weekly'); ?>><?php _e('按周', 'help-platform'); ?></option>
                    <option value="monthly" <?php selected(isset($_GET['group_by']) ? $_GET['group_by'] : 'daily', 'monthly'); ?>><?php _e('按月', 'help-platform'); ?></option>
                </select>
            </div>

            <div class="filter-item chart-type-filter" style="display: <?php echo $report_type === 'financial' ? 'block' : 'none'; ?>">
                <label for="chart_type"><?php _e('图表类型', 'help-platform'); ?></label>
                <select name="chart_type" id="chart_type">
                    <option value="line" <?php selected(isset($_GET['chart_type']) ? $_GET['chart_type'] : 'line', 'line'); ?>><?php _e('折线图', 'help-platform'); ?></option>
                    <option value="bar" <?php selected(isset($_GET['chart_type']) ? $_GET['chart_type'] : 'line', 'bar'); ?>><?php _e('柱状图', 'help-platform'); ?></option>
                </select>
            </div>

            <div class="filter-item">
                <button type="submit" class="button button-primary"><?php _e('查询', 'help-platform'); ?></button>
                <button type="button" class="button export-report"><?php _e('导出', 'help-platform'); ?></button>
            </div>
        </div>
    </form>

    <!-- 图表区域 -->
    <?php if ($report_type === 'financial' && $chart_data) : ?>
    <div class="chart-container">
        <canvas id="reportChart"></canvas>
    </div>
    <?php endif; ?>

    <!-- 数据表格 -->
    <?php if ($report_data) : ?>
    <div class="table-container">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <?php foreach (array_keys(reset($report_data)) as $key) : ?>
                    <th><?php echo esc_html($report->get_metric_label($key)); ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($report_data as $row) : ?>
                <tr>
                    <?php foreach ($row as $key => $value) : ?>
                    <td>
                        <?php
                        if (strpos($key, 'amount') !== false || strpos($key, 'income') !== false || strpos($key, 'fee') !== false) {
                            echo '¥' . number_format($value, 2);
                        } elseif (strpos($key, 'rate') !== false) {
                            echo number_format($value * 100, 2) . '%';
                        } else {
                            echo esc_html($value);
                        }
                        ?>
                    </td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else : ?>
    <div class="notice notice-warning">
        <p><?php _e('没有找到相关数据。', 'help-platform'); ?></p>
    </div>
    <?php endif; ?>
</div>

<style>
.help-platform-report {
    margin: 20px;
}

.help-platform-filter {
    background: #fff;
    padding: 20px;
    margin-bottom: 20px;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.filter-row {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    align-items: flex-end;
}

.filter-item {
    flex: 1;
    min-width: 200px;
}

.filter-item label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
}

.filter-item select,
.filter-item input {
    width: 100%;
}

.chart-container {
    background: #fff;
    padding: 20px;
    margin-bottom: 20px;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.table-container {
    background: #fff;
    padding: 20px;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
    overflow-x: auto;
}

.table-container table {
    width: 100%;
    border-collapse: collapse;
}

.table-container th,
.table-container td {
    padding: 8px;
    text-align: left;
    border-bottom: 1px solid #e5e5e5;
}

.table-container th {
    font-weight: 600;
    background: #f9f9f9;
}

.export-report {
    margin-left: 10px;
}
</style>

<script>
jQuery(document).ready(function($) {
    // 切换报表类型时显示/隐藏相关筛选条件
    $('#report_type').on('change', function() {
        var type = $(this).val();
        $('.branch-filter, .worker-filter').hide();
        $('.group-by-filter, .chart-type-filter').hide();
        
        if (type === 'worker') {
            $('.branch-filter, .worker-filter').show();
        } else if (type === 'financial') {
            $('.group-by-filter, .chart-type-filter').show();
        }
    });

    // 导出报表
    $('.export-report').on('click', function() {
        var url = new URL(window.location.href);
        url.searchParams.set('action', 'export_report');
        window.location.href = url.toString();
    });

    // 初始化图表
    <?php if ($report_type === 'financial' && $chart_data) : ?>
    var ctx = document.getElementById('reportChart').getContext('2d');
    new Chart(ctx, {
        type: '<?php echo isset($_GET['chart_type']) ? esc_js($_GET['chart_type']) : 'line'; ?>',
        data: <?php echo json_encode($chart_data); ?>,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '¥' + value.toLocaleString();
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            var label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.dataset.label.indexOf('金额') !== -1 || 
                                context.dataset.label.indexOf('收入') !== -1 || 
                                context.dataset.label.indexOf('手续费') !== -1) {
                                label += '¥' + context.parsed.y.toLocaleString();
                            } else if (context.dataset.label.indexOf('率') !== -1) {
                                label += (context.parsed.y * 100).toFixed(2) + '%';
                            } else {
                                label += context.parsed.y;
                            }
                            return label;
                        }
                    }
                }
            }
        }
    });
    <?php endif; ?>
});
</script> 