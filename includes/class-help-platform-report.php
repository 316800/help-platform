/**
 * 生成财务报表
 */
public function generate_financial_report($args = array()) {
    global $wpdb;

    $defaults = array(
        'date_start' => date('Y-m-d', strtotime('-30 days')),
        'date_end' => date('Y-m-d'),
        'branch_id' => 0,
        'type' => 'daily' // daily, weekly, monthly
    );

    $args = wp_parse_args($args, $defaults);
    $where = array();
    $params = array();

    if ($args['branch_id']) {
        $where[] = 'branch_id = %d';
        $params[] = $args['branch_id'];
    }

    $where[] = 'date BETWEEN %s AND %s';
    $params[] = $args['date_start'];
    $params[] = $args['date_end'];

    $where = 'WHERE ' . implode(' AND ', $where);

    // 按时间分组
    $group_by = '';
    switch ($args['type']) {
        case 'weekly':
            $group_by = 'YEARWEEK(date)';
            break;
        case 'monthly':
            $group_by = 'DATE_FORMAT(date, "%Y-%m")';
            break;
        default:
            $group_by = 'DATE(date)';
    }

    // 查询收入数据
    $income_query = $wpdb->prepare(
        "SELECT 
            {$group_by} as period,
            COUNT(*) as order_count,
            SUM(amount) as total_amount,
            SUM(platform_commission) as platform_income,
            SUM(branch_commission) as branch_income,
            SUM(worker_commission) as worker_income,
            SUM(tax_amount) as tax_amount
        FROM {$wpdb->prefix}help_commission
        {$where}
        GROUP BY {$group_by}
        ORDER BY period ASC",
        $params
    );

    $income_data = $wpdb->get_results($income_query);

    // 查询支出数据
    $expense_query = $wpdb->prepare(
        "SELECT 
            {$group_by} as period,
            COUNT(*) as withdrawal_count,
            SUM(amount) as total_amount,
            SUM(fee) as total_fee
        FROM {$wpdb->prefix}help_withdrawals
        {$where}
        GROUP BY {$group_by}
        ORDER BY period ASC",
        $params
    );

    $expense_data = $wpdb->get_results($expense_query);

    // 合并数据
    $report_data = array();
    $periods = array_unique(array_merge(
        wp_list_pluck($income_data, 'period'),
        wp_list_pluck($expense_data, 'period')
    ));
    sort($periods);

    foreach ($periods as $period) {
        $income = array_filter($income_data, function($item) use ($period) {
            return $item->period === $period;
        });
        $income = reset($income);

        $expense = array_filter($expense_data, function($item) use ($period) {
            return $item->period === $period;
        });
        $expense = reset($expense);

        $report_data[] = array(
            'period' => $period,
            'order_count' => $income ? $income->order_count : 0,
            'income_amount' => $income ? $income->total_amount : 0,
            'platform_income' => $income ? $income->platform_income : 0,
            'branch_income' => $income ? $income->branch_income : 0,
            'worker_income' => $income ? $income->worker_income : 0,
            'tax_amount' => $income ? $income->tax_amount : 0,
            'withdrawal_count' => $expense ? $expense->withdrawal_count : 0,
            'expense_amount' => $expense ? $expense->total_amount : 0,
            'withdrawal_fee' => $expense ? $expense->total_fee : 0,
            'net_income' => ($income ? $income->platform_income : 0) - 
                          ($expense ? $expense->total_amount : 0)
        );
    }

    return $report_data;
}

/**
 * 生成分公司报表
 */
public function generate_branch_report($args = array()) {
    global $wpdb;

    $defaults = array(
        'date_start' => date('Y-m-d', strtotime('-30 days')),
        'date_end' => date('Y-m-d'),
        'branch_id' => 0
    );

    $args = wp_parse_args($args, $defaults);
    $where = array();
    $params = array();

    if ($args['branch_id']) {
        $where[] = 'branch_id = %d';
        $params[] = $args['branch_id'];
    }

    $where[] = 'date BETWEEN %s AND %s';
    $params[] = $args['date_start'];
    $params[] = $args['date_end'];

    $where = 'WHERE ' . implode(' AND ', $where);

    // 查询分公司数据
    $query = $wpdb->prepare(
        "SELECT 
            b.id as branch_id,
            b.post_title as branch_name,
            COUNT(DISTINCT j.id) as job_count,
            COUNT(DISTINCT j.worker_id) as worker_count,
            SUM(j.budget) as total_amount,
            SUM(c.branch_commission) as branch_income,
            SUM(c.worker_commission) as worker_income,
            SUM(c.tax_amount) as tax_amount,
            COUNT(DISTINCT w.id) as withdrawal_count,
            SUM(w.amount) as withdrawal_amount,
            SUM(w.fee) as withdrawal_fee
        FROM {$wpdb->posts} b
        LEFT JOIN {$wpdb->prefix}help_jobs j ON b.id = j.branch_id
        LEFT JOIN {$wpdb->prefix}help_commission c ON j.commission_id = c.id
        LEFT JOIN {$wpdb->prefix}help_withdrawals w ON b.id = w.branch_id
        {$where}
        GROUP BY b.id
        ORDER BY branch_income DESC",
        $params
    );

    return $wpdb->get_results($query);
}

/**
 * 生成工人报表
 */
public function generate_worker_report($args = array()) {
    global $wpdb;

    $defaults = array(
        'date_start' => date('Y-m-d', strtotime('-30 days')),
        'date_end' => date('Y-m-d'),
        'branch_id' => 0,
        'worker_id' => 0
    );

    $args = wp_parse_args($args, $defaults);
    $where = array();
    $params = array();

    if ($args['branch_id']) {
        $where[] = 'j.branch_id = %d';
        $params[] = $args['branch_id'];
    }

    if ($args['worker_id']) {
        $where[] = 'j.worker_id = %d';
        $params[] = $args['worker_id'];
    }

    $where[] = 'j.completed_at BETWEEN %s AND %s';
    $params[] = $args['date_start'];
    $params[] = $args['date_end'];

    $where = 'WHERE ' . implode(' AND ', $where);

    // 查询工人数据
    $query = $wpdb->prepare(
        "SELECT 
            u.ID as worker_id,
            u.display_name as worker_name,
            b.post_title as branch_name,
            COUNT(j.id) as job_count,
            SUM(j.budget) as total_amount,
            SUM(c.worker_commission) as commission_amount,
            SUM(c.tax_amount) as tax_amount,
            COUNT(w.id) as withdrawal_count,
            SUM(w.amount) as withdrawal_amount,
            SUM(w.fee) as withdrawal_fee,
            AVG(j.rating) as avg_rating
        FROM {$wpdb->users} u
        LEFT JOIN {$wpdb->prefix}help_jobs j ON u.ID = j.worker_id
        LEFT JOIN {$wpdb->posts} b ON j.branch_id = b.id
        LEFT JOIN {$wpdb->prefix}help_commission c ON j.commission_id = c.id
        LEFT JOIN {$wpdb->prefix}help_withdrawals w ON u.ID = w.user_id
        {$where}
        GROUP BY u.ID
        ORDER BY commission_amount DESC",
        $params
    );

    return $wpdb->get_results($query);
}

/**
 * 导出报表
 */
public function export_report($report_data, $filename, $type = 'csv') {
    if (empty($report_data)) {
        return false;
    }

    // 设置响应头
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    // 创建输出流
    $output = fopen('php://output', 'w');

    // 添加 BOM 以支持中文
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    // 写入表头
    fputcsv($output, array_keys(reset($report_data)));

    // 写入数据
    foreach ($report_data as $row) {
        fputcsv($output, $row);
    }

    fclose($output);
    exit;
}

/**
 * 生成报表图表数据
 */
public function generate_chart_data($report_data, $type = 'line') {
    if (empty($report_data)) {
        return false;
    }

    $chart_data = array(
        'labels' => array(),
        'datasets' => array()
    );

    // 获取所有可能的指标
    $metrics = array_keys(reset($report_data));
    unset($metrics[array_search('period', $metrics)]);

    // 设置标签
    $chart_data['labels'] = wp_list_pluck($report_data, 'period');

    // 设置数据集
    foreach ($metrics as $metric) {
        $dataset = array(
            'label' => $this->get_metric_label($metric),
            'data' => wp_list_pluck($report_data, $metric),
            'borderColor' => $this->get_metric_color($metric),
            'fill' => false
        );

        if ($type === 'bar') {
            $dataset['backgroundColor'] = $this->get_metric_color($metric);
        }

        $chart_data['datasets'][] = $dataset;
    }

    return $chart_data;
}

/**
 * 获取指标标签
 */
private function get_metric_label($metric) {
    $labels = array(
        'order_count' => __('订单数', 'help-platform'),
        'income_amount' => __('收入金额', 'help-platform'),
        'platform_income' => __('平台收入', 'help-platform'),
        'branch_income' => __('分公司收入', 'help-platform'),
        'worker_income' => __('工人收入', 'help-platform'),
        'tax_amount' => __('税费', 'help-platform'),
        'withdrawal_count' => __('提现笔数', 'help-platform'),
        'expense_amount' => __('支出金额', 'help-platform'),
        'withdrawal_fee' => __('提现手续费', 'help-platform'),
        'net_income' => __('净收入', 'help-platform')
    );

    return isset($labels[$metric]) ? $labels[$metric] : $metric;
}

/**
 * 获取指标颜色
 */
private function get_metric_color($metric) {
    $colors = array(
        'order_count' => '#4CAF50',
        'income_amount' => '#2196F3',
        'platform_income' => '#FFC107',
        'branch_income' => '#9C27B0',
        'worker_income' => '#00BCD4',
        'tax_amount' => '#F44336',
        'withdrawal_count' => '#FF9800',
        'expense_amount' => '#E91E63',
        'withdrawal_fee' => '#795548',
        'net_income' => '#3F51B5'
    );

    return isset($colors[$metric]) ? $colors[$metric] : '#607D8B';
} 