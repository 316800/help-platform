<?php
/**
 * HELP Platform 佣金管理类
 */
class Help_Platform_Commission {
    /**
     * 单例实例
     */
    private static $instance = null;

    /**
     * 获取单例实例
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 构造函数
     */
    private function __construct() {
        $this->init_hooks();
    }

    /**
     * 初始化钩子
     */
    private function init_hooks() {
        // 处理佣金导出
        add_action('admin_post_export_commission', array($this, 'handle_export_commission'));
    }

    /**
     * 处理佣金导出
     */
    public function handle_export_commission() {
        // 检查权限
        if (!current_user_can('manage_options')) {
            wp_die(__('您没有权限执行此操作', 'help-platform'));
        }

        // 验证nonce
        check_admin_referer('export_commission');

        // 获取筛选参数
        $branch_id = isset($_GET['branch_id']) ? intval($_GET['branch_id']) : 0;
        $date_start = isset($_GET['date_start']) ? sanitize_text_field($_GET['date_start']) : date('Y-m-01');
        $date_end = isset($_GET['date_end']) ? sanitize_text_field($_GET['date_end']) : date('Y-m-d');
        $status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';

        // 获取佣金数据
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
                c.*,
                b.post_title as branch_name
            FROM {$wpdb->prefix}help_commission c
            LEFT JOIN {$wpdb->posts} b ON c.branch_id = b.ID
            WHERE {$where}
            ORDER BY c.date DESC, c.id DESC
        ", $params);

        $commissions = $wpdb->get_results($query);

        // 设置CSV文件头
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=commission-export-' . date('Y-m-d') . '.csv');

        // 创建CSV文件
        $output = fopen('php://output', 'w');

        // 添加BOM以支持中文
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // 写入CSV头
        fputcsv($output, array(
            __('交易ID', 'help-platform'),
            __('分公司', 'help-platform'),
            __('日期', 'help-platform'),
            __('平台佣金', 'help-platform'),
            __('分公司佣金', 'help-platform'),
            __('工作者佣金', 'help-platform'),
            __('税费', 'help-platform'),
            __('总金额', 'help-platform'),
            __('状态', 'help-platform'),
            __('备注', 'help-platform')
        ));

        // 写入数据
        foreach ($commissions as $commission) {
            $status_text = '';
            switch ($commission->status) {
                case 'pending':
                    $status_text = __('待处理', 'help-platform');
                    break;
                case 'completed':
                    $status_text = __('已完成', 'help-platform');
                    break;
                case 'cancelled':
                    $status_text = __('已取消', 'help-platform');
                    break;
            }

            fputcsv($output, array(
                $commission->id,
                $commission->branch_name,
                $commission->date,
                number_format($commission->platform_commission, 2),
                number_format($commission->branch_commission, 2),
                number_format($commission->worker_commission, 2),
                number_format($commission->tax_amount, 2),
                number_format($commission->amount, 2),
                $status_text,
                $commission->notes
            ));
        }

        fclose($output);
        exit;
    }

    /**
     * 计算佣金
     */
    public function calculate_commission($amount, $branch_id) {
        // 获取分公司佣金设置
        $commission_data = get_post_meta($branch_id, '_commission_data', true);
        if (!$commission_data) {
            return false;
        }

        // 计算各项佣金
        $platform_commission = $amount * ($commission_data['platform_fee'] / 100);
        $branch_commission = $amount * ($commission_data['branch_fee'] / 100);
        $worker_commission = $amount * ($commission_data['worker_fee'] / 100);

        // 应用最低/最高佣金限制
        if ($commission_data['min_amount'] > 0) {
            $platform_commission = max($platform_commission, $commission_data['min_amount']);
            $branch_commission = max($branch_commission, $commission_data['min_amount']);
            $worker_commission = max($worker_commission, $commission_data['min_amount']);
        }

        if ($commission_data['max_amount'] > 0) {
            $platform_commission = min($platform_commission, $commission_data['max_amount']);
            $branch_commission = min($branch_commission, $commission_data['max_amount']);
            $worker_commission = min($worker_commission, $commission_data['max_amount']);
        }

        // 计算税费
        $tax_amount = 0;
        if ($commission_data['tax_rate'] > 0) {
            $tax_amount = ($platform_commission + $branch_commission + $worker_commission) * ($commission_data['tax_rate'] / 100);
        }

        return array(
            'platform_commission' => $platform_commission,
            'branch_commission' => $branch_commission,
            'worker_commission' => $worker_commission,
            'tax_amount' => $tax_amount,
            'total_amount' => $amount
        );
    }

    /**
     * 记录佣金交易
     */
    public function record_commission($data) {
        global $wpdb;

        $defaults = array(
            'branch_id' => 0,
            'amount' => 0,
            'platform_commission' => 0,
            'branch_commission' => 0,
            'worker_commission' => 0,
            'tax_amount' => 0,
            'status' => 'pending',
            'notes' => '',
            'date' => current_time('mysql')
        );

        $data = wp_parse_args($data, $defaults);

        // 验证数据
        if (!$data['branch_id'] || $data['amount'] <= 0) {
            return false;
        }

        // 插入数据
        $result = $wpdb->insert(
            $wpdb->prefix . 'help_commission',
            array(
                'branch_id' => $data['branch_id'],
                'amount' => $data['amount'],
                'platform_commission' => $data['platform_commission'],
                'branch_commission' => $data['branch_commission'],
                'worker_commission' => $data['worker_commission'],
                'tax_amount' => $data['tax_amount'],
                'status' => $data['status'],
                'notes' => $data['notes'],
                'date' => $data['date']
            ),
            array('%d', '%f', '%f', '%f', '%f', '%f', '%s', '%s', '%s')
        );

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * 更新佣金状态
     */
    public function update_commission_status($commission_id, $status, $notes = '') {
        global $wpdb;

        return $wpdb->update(
            $wpdb->prefix . 'help_commission',
            array(
                'status' => $status,
                'notes' => $notes
            ),
            array('id' => $commission_id),
            array('%s', '%s'),
            array('%d')
        );
    }

    /**
     * 获取佣金记录
     */
    public function get_commission($commission_id) {
        global $wpdb;

        return $wpdb->get_row($wpdb->prepare("
            SELECT c.*, b.post_title as branch_name
            FROM {$wpdb->prefix}help_commission c
            LEFT JOIN {$wpdb->posts} b ON c.branch_id = b.ID
            WHERE c.id = %d
        ", $commission_id));
    }

    /**
     * 获取分公司佣金列表
     */
    public function get_branch_commissions($branch_id, $args = array()) {
        global $wpdb;

        $defaults = array(
            'status' => '',
            'date_start' => '',
            'date_end' => '',
            'per_page' => 20,
            'page' => 1
        );

        $args = wp_parse_args($args, $defaults);
        $where = array('branch_id = %d');
        $params = array($branch_id);

        if ($args['status']) {
            $where[] = 'status = %s';
            $params[] = $args['status'];
        }

        if ($args['date_start']) {
            $where[] = 'date >= %s';
            $params[] = $args['date_start'];
        }

        if ($args['date_end']) {
            $where[] = 'date <= %s';
            $params[] = $args['date_end'];
        }

        $where = implode(' AND ', $where);
        $offset = ($args['page'] - 1) * $args['per_page'];

        $query = $wpdb->prepare("
            SELECT c.*, b.post_title as branch_name
            FROM {$wpdb->prefix}help_commission c
            LEFT JOIN {$wpdb->posts} b ON c.branch_id = b.ID
            WHERE {$where}
            ORDER BY c.date DESC, c.id DESC
            LIMIT %d OFFSET %d
        ", array_merge($params, array($args['per_page'], $offset)));

        return $wpdb->get_results($query);
    }

    /**
     * 计算任务佣金
     */
    public function calculate_job_commission($job_id) {
        global $wpdb;

        // 获取任务信息
        $job = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}help_jobs WHERE id = %d",
            $job_id
        ));

        if (!$job || $job->status !== 'completed') {
            return false;
        }

        // 获取佣金比例设置
        $platform_rate = floatval(get_option('help_platform_commission_rate', 0.1));
        $branch_rate = floatval(get_option('help_platform_branch_commission_rate', 0.2));
        $tax_rate = floatval(get_option('help_platform_tax_rate', 0.05));

        // 计算各项佣金
        $amount = floatval($job->budget);
        $platform_commission = round($amount * $platform_rate, 2);
        $branch_commission = round($amount * $branch_rate, 2);
        $tax_amount = round($amount * $tax_rate, 2);
        $worker_commission = $amount - $platform_commission - $branch_commission - $tax_amount;

        // 创建佣金记录
        $data = array(
            'branch_id' => $job->branch_id,
            'amount' => $amount,
            'platform_commission' => $platform_commission,
            'branch_commission' => $branch_commission,
            'worker_commission' => $worker_commission,
            'tax_amount' => $tax_amount,
            'status' => 'pending',
            'notes' => sprintf(__('任务ID：%d，工人ID：%d', 'help-platform'), $job_id, $job->worker_id),
            'date' => current_time('mysql')
        );

        $commission_id = $this->record_commission($data);
        if (!$commission_id) {
            return false;
        }

        // 更新任务佣金信息
        $wpdb->update(
            $wpdb->prefix . 'help_jobs',
            array('commission_id' => $commission_id),
            array('id' => $job_id),
            array('%d'),
            array('%d')
        );

        return $commission_id;
    }

    /**
     * 处理佣金分配
     */
    public function process_commission($commission_id) {
        global $wpdb;

        // 获取佣金记录
        $commission = $wpdb->get_row($wpdb->prepare(
            "SELECT c.*, j.worker_id 
            FROM {$wpdb->prefix}help_commission c
            LEFT JOIN {$wpdb->prefix}help_jobs j ON c.id = j.commission_id
            WHERE c.id = %d AND c.status = 'pending'",
            $commission_id
        ));

        if (!$commission) {
            return false;
        }

        // 开始事务
        $wpdb->query('START TRANSACTION');

        try {
            // 更新佣金记录状态
            $wpdb->update(
                $wpdb->prefix . 'help_commission',
                array('status' => 'processing'),
                array('id' => $commission_id),
                array('%s'),
                array('%d')
            );

            // 更新平台收入
            $platform_income = get_option('help_platform_income', 0);
            update_option('help_platform_income', $platform_income + $commission->platform_commission);

            // 更新分公司余额
            $branch_balance = get_post_meta($commission->branch_id, '_help_branch_balance', true);
            $branch_balance = floatval($branch_balance) + $commission->branch_commission;
            update_post_meta($commission->branch_id, '_help_branch_balance', $branch_balance);

            // 更新工人余额
            if ($commission->worker_id) {
                $worker_balance = get_user_meta($commission->worker_id, '_help_worker_balance', true);
                $worker_balance = floatval($worker_balance) + $commission->worker_commission;
                update_user_meta($commission->worker_id, '_help_worker_balance', $worker_balance);

                // 发送通知给工人
                $worker = get_userdata($commission->worker_id);
                if ($worker) {
                    $subject = sprintf(__('佣金已发放 - %s元', 'help-platform'), $commission->worker_commission);
                    $message = sprintf(
                        __('您的任务佣金已发放。\n任务金额：%s元\n工人佣金：%s元\n发放时间：%s', 'help-platform'),
                        $commission->amount,
                        $commission->worker_commission,
                        current_time('mysql')
                    );
                    $this->send_notification($worker->user_email, $subject, $message);
                }
            }

            // 更新佣金记录状态为已完成
            $wpdb->update(
                $wpdb->prefix . 'help_commission',
                array(
                    'status' => 'completed',
                    'notes' => sprintf(
                        __('佣金分配完成。\n平台佣金：%s元\n分公司佣金：%s元\n工人佣金：%s元\n税费：%s元', 'help-platform'),
                        $commission->platform_commission,
                        $commission->branch_commission,
                        $commission->worker_commission,
                        $commission->tax_amount
                    )
                ),
                array('id' => $commission_id),
                array('%s', '%s'),
                array('%d')
            );

            $wpdb->query('COMMIT');
            return true;
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            return false;
        }
    }

    /**
     * 获取佣金统计
     */
    public function get_commission_stats($args = array()) {
        global $wpdb;

        $defaults = array(
            'branch_id' => 0,
            'worker_id' => 0,
            'date_start' => '',
            'date_end' => '',
            'status' => ''
        );

        $args = wp_parse_args($args, $defaults);
        $where = array();
        $params = array();

        if ($args['branch_id']) {
            $where[] = 'branch_id = %d';
            $params[] = $args['branch_id'];
        }

        if ($args['worker_id']) {
            $where[] = 'worker_id = %d';
            $params[] = $args['worker_id'];
        }

        if ($args['date_start']) {
            $where[] = 'date >= %s';
            $params[] = $args['date_start'];
        }

        if ($args['date_end']) {
            $where[] = 'date <= %s';
            $params[] = $args['date_end'];
        }

        if ($args['status']) {
            $where[] = 'status = %s';
            $params[] = $args['status'];
        }

        $where = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $query = $wpdb->prepare(
            "SELECT 
                COUNT(*) as total_count,
                SUM(amount) as total_amount,
                SUM(platform_commission) as total_platform_commission,
                SUM(branch_commission) as total_branch_commission,
                SUM(worker_commission) as total_worker_commission,
                SUM(tax_amount) as total_tax_amount,
                AVG(platform_commission / amount) as avg_platform_rate,
                AVG(branch_commission / amount) as avg_branch_rate,
                AVG(worker_commission / amount) as avg_worker_rate,
                AVG(tax_amount / amount) as avg_tax_rate
            FROM {$wpdb->prefix}help_commission
            {$where}",
            $params
        );

        return $wpdb->get_row($query);
    }

    /**
     * 获取佣金趋势
     */
    public function get_commission_trend($args = array()) {
        global $wpdb;

        $defaults = array(
            'branch_id' => 0,
            'worker_id' => 0,
            'days' => 30,
            'group_by' => 'day' // day, week, month
        );

        $args = wp_parse_args($args, $defaults);
        $where = array();
        $params = array();

        if ($args['branch_id']) {
            $where[] = 'branch_id = %d';
            $params[] = $args['branch_id'];
        }

        if ($args['worker_id']) {
            $where[] = 'worker_id = %d';
            $params[] = $args['worker_id'];
        }

        $where[] = 'date >= DATE_SUB(NOW(), INTERVAL %d DAY)';
        $params[] = $args['days'];

        $where = 'WHERE ' . implode(' AND ', $where);

        $group_by = '';
        switch ($args['group_by']) {
            case 'week':
                $group_by = 'YEARWEEK(date)';
                break;
            case 'month':
                $group_by = 'DATE_FORMAT(date, "%Y-%m")';
                break;
            default:
                $group_by = 'DATE(date)';
        }

        $query = $wpdb->prepare(
            "SELECT 
                {$group_by} as period,
                COUNT(*) as count,
                SUM(amount) as amount,
                SUM(platform_commission) as platform_commission,
                SUM(branch_commission) as branch_commission,
                SUM(worker_commission) as worker_commission,
                SUM(tax_amount) as tax_amount
            FROM {$wpdb->prefix}help_commission
            {$where}
            GROUP BY {$group_by}
            ORDER BY period ASC",
            $params
        );

        return $wpdb->get_results($query);
    }

    /**
     * 发送通知
     */
    private function send_notification($to, $subject, $message) {
        $headers = array('Content-Type: text/html; charset=UTF-8');
        wp_mail($to, $subject, wpautop($message), $headers);
    }
}

// 初始化
function help_platform_commission_init() {
    return Help_Platform_Commission::get_instance();
}
add_action('plugins_loaded', 'help_platform_commission_init'); 