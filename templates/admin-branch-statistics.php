<?php
if (!defined('ABSPATH')) {
    exit;
}

$tasks = $statistics['tasks'];
$commission = $statistics['commission'];
?>

<div class="branch-statistics">
    <h3><?php _e('任务统计', 'help-platform'); ?></h3>
    <ul>
        <li>
            <strong><?php _e('今日任务：', 'help-platform'); ?></strong>
            <?php echo esc_html($tasks->today_tasks); ?>
        </li>
        <li>
            <strong><?php _e('本月任务：', 'help-platform'); ?></strong>
            <?php echo esc_html($tasks->month_tasks); ?>
        </li>
        <li>
            <strong><?php _e('今年任务：', 'help-platform'); ?></strong>
            <?php echo esc_html($tasks->year_tasks); ?>
        </li>
        <li>
            <strong><?php _e('总任务数：', 'help-platform'); ?></strong>
            <?php echo esc_html($tasks->total_tasks); ?>
        </li>
    </ul>

    <h3><?php _e('佣金统计', 'help-platform'); ?></h3>
    <ul>
        <li>
            <strong><?php _e('今日佣金：', 'help-platform'); ?></strong>
            <?php echo esc_html(number_format($commission->today_commission, 2)); ?>
        </li>
        <li>
            <strong><?php _e('本月佣金：', 'help-platform'); ?></strong>
            <?php echo esc_html(number_format($commission->month_commission, 2)); ?>
        </li>
        <li>
            <strong><?php _e('今年佣金：', 'help-platform'); ?></strong>
            <?php echo esc_html(number_format($commission->year_commission, 2)); ?>
        </li>
        <li>
            <strong><?php _e('总佣金：', 'help-platform'); ?></strong>
            <?php echo esc_html(number_format($commission->total_commission, 2)); ?>
        </li>
    </ul>
</div>

<style>
.branch-statistics {
    padding: 10px;
}

.branch-statistics h3 {
    margin: 15px 0 10px;
    padding-bottom: 5px;
    border-bottom: 1px solid #eee;
}

.branch-statistics ul {
    margin: 0;
    padding: 0;
    list-style: none;
}

.branch-statistics li {
    margin: 5px 0;
    padding: 5px 0;
    border-bottom: 1px dashed #eee;
}

.branch-statistics li:last-child {
    border-bottom: none;
}

.branch-statistics strong {
    display: inline-block;
    width: 80px;
    color: #666;
}
</style> 