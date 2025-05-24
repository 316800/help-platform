<?php
/**
 * HELP Platform 财务管理类
 */
class Help_Platform_Finance {
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
        // 添加管理菜单
        add_action('admin_menu', array($this, 'add_finance_menu'));
        
        // 处理充值请求
        add_action('wp_ajax_help_platform_process_recharge', array($this, 'process_recharge'));
        add_action('wp_ajax_help_platform_process_withdraw', array($this, 'process_withdraw'));
        
        // 处理佣金计算
        add_action('woocommerce_order_status_completed', array($this, 'calculate_commission'), 10, 1);
        
        // 添加用户余额显示
        add_action('woocommerce_account_dashboard', array($this, 'display_user_balance'));
        add_action('woocommerce_account_navigation', array($this, 'add_finance_account_menu'));
        add_action('woocommerce_account_finance_endpoint', array($this, 'render_finance_page'));
        
        // 注册自定义端点
        add_action('init', array($this, 'add_finance_endpoints'));
        add_filter('query_vars', array($this, 'add_finance_query_vars'));

        // 添加分公司财务统计
        add_action('admin_init', array($this, 'add_branch_finance_columns'));
        add_filter('manage_help_branch_posts_columns', array($this, 'branch_finance_columns'));
        add_action('manage_help_branch_posts_custom_column', array($this, 'branch_finance_column_content'), 10, 2);
    }

    /**
     * 添加财务管理菜单
     */
    public function add_finance_menu() {
        add_submenu_page(
            'help-platform',
            __('财务管理', 'help-platform'),
            __('财务管理', 'help-platform'),
            'help_manage_finance',
            'help-finance',
            array($this, 'render_finance_settings_page')
        );
    }

    /**
     * 添加自定义端点
     */
    public function add_finance_endpoints() {
        add_rewrite_endpoint('finance', EP_ROOT | EP_PAGES);
    }

    /**
     * 添加查询变量
     */
    public function add_finance_query_vars($vars) {
        $vars[] = 'finance';
        return $vars;
    }

    /**
     * 添加财务账户菜单
     */
    public function add_finance_account_menu($items) {
        $items['finance'] = __('我的财务', 'help-platform');
        return $items;
    }

    /**
     * 渲染财务页面
     */
    public function render_finance_page() {
        $user_id = get_current_user_id();
        $balance = $this->get_user_balance($user_id);
        $transactions = $this->get_user_transactions($user_id);
        $commission = $this->get_user_commission($user_id);
        
        include HELP_PLATFORM_PLUGIN_DIR . 'templates/finance-page.php';
    }

    /**
     * 渲染财务设置页面
     */
    public function render_finance_settings_page() {
        if (!current_user_can('help_manage_finance')) {
            wp_die(__('您没有权限访问此页面。', 'help-platform'));
        }

        // 保存设置
        if (isset($_POST['help_platform_finance_settings_nonce']) && 
            wp_verify_nonce($_POST['help_platform_finance_settings_nonce'], 'help_platform_finance_settings')) {
            
            $settings = array(
                'commission_rate' => floatval($_POST['commission_rate']),
                'min_withdraw' => floatval($_POST['min_withdraw']),
                'withdraw_fee' => floatval($_POST['withdraw_fee']),
                'platform_fee' => floatval($_POST['platform_fee']),
            );
            
            update_option('help_platform_finance_settings', $settings);
            echo '<div class="notice notice-success"><p>' . __('设置已保存', 'help-platform') . '</p></div>';
        }

        // 获取当前设置
        $settings = get_option('help_platform_finance_settings', array(
            'commission_rate' => 0.1, // 10% 佣金率
            'min_withdraw' => 100, // 最低提现金额
            'withdraw_fee' => 0.01, // 1% 提现手续费
            'platform_fee' => 0.05, // 5% 平台服务费
        ));

        include HELP_PLATFORM_PLUGIN_DIR . 'templates/admin-finance-settings.php';
    }

    /**
     * 添加分公司财务统计列
     */
    public function add_branch_finance_columns() {
        add_filter('manage_help_branch_posts_columns', array($this, 'branch_finance_columns'));
        add_action('manage_help_branch_posts_custom_column', array($this, 'branch_finance_column_content'), 10, 2);
    }

    /**
     * 定义分公司财务统计列
     */
    public function branch_finance_columns($columns) {
        $new_columns = array();
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            if ($key === 'title') {
                $new_columns['branch_balance'] = __('账户余额', 'help-platform');
                $new_columns['branch_revenue'] = __('总收入', 'help-platform');
                $new_columns['branch_commission'] = __('佣金支出', 'help-platform');
                $new_columns['branch_platform_fee'] = __('平台收入', 'help-platform');
            }
        }
        return $new_columns;
    }

    /**
     * 显示分公司财务统计内容
     */
    public function branch_finance_column_content($column, $post_id) {
        if (!in_array($column, array('branch_balance', 'branch_revenue', 'branch_commission', 'branch_platform_fee'))) {
            return;
        }

        $branch_data = get_post_meta($post_id, '_help_branch_data', true);
        $currency = isset($branch_data['currency']) ? $branch_data['currency'] : 'CNY';

        switch ($column) {
            case 'branch_balance':
                $balance = $this->get_branch_balance($post_id);
                echo number_format($balance, 2) . ' ' . $currency;
                break;

            case 'branch_revenue':
                $revenue = $this->get_branch_revenue($post_id);
                echo number_format($revenue, 2) . ' ' . $currency;
                break;

            case 'branch_commission':
                $commission = $this->get_branch_commission($post_id);
                echo number_format($commission, 2) . ' ' . $currency;
                break;

            case 'branch_platform_fee':
                $platform_fee = $this->get_branch_platform_fee($post_id);
                echo number_format($platform_fee, 2) . ' ' . $currency;
                break;
        }
    }

    /**
     * 获取分公司余额
     */
    public function get_branch_balance($branch_id) {
        global $wpdb;
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                (SELECT COALESCE(SUM(amount), 0) FROM {$wpdb->prefix}help_recharges 
                 WHERE status = 'completed' AND branch_id = %d) -
                (SELECT COALESCE(SUM(amount), 0) FROM {$wpdb->prefix}help_withdrawals 
                 WHERE status = 'completed' AND branch_id = %d) as balance",
            $branch_id,
            $branch_id
        ));
        return $result ? $result->balance : 0;
    }

    /**
     * 获取分公司总收入
     */
    public function get_branch_revenue($branch_id) {
        global $wpdb;
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT COALESCE(SUM(amount), 0) as revenue 
             FROM {$wpdb->prefix}help_commissions 
             WHERE branch_id = %d",
            $branch_id
        ));
        return $result ? $result->revenue : 0;
    }

    /**
     * 获取分公司佣金支出
     */
    public function get_branch_commission($branch_id) {
        global $wpdb;
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT COALESCE(SUM(commission), 0) as commission 
             FROM {$wpdb->prefix}help_commissions 
             WHERE branch_id = %d",
            $branch_id
        ));
        return $result ? $result->commission : 0;
    }

    /**
     * 获取分公司平台收入
     */
    public function get_branch_platform_fee($branch_id) {
        global $wpdb;
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT COALESCE(SUM(platform_fee), 0) as platform_fee 
             FROM {$wpdb->prefix}help_commissions 
             WHERE branch_id = %d",
            $branch_id
        ));
        return $result ? $result->platform_fee : 0;
    }

    /**
     * 处理充值请求
     */
    public function process_recharge() {
        check_ajax_referer('help-platform-finance', 'nonce');

        $user_id = get_current_user_id();
        $amount = floatval($_POST['amount']);
        $payment_method = sanitize_text_field($_POST['payment_method']);

        // 获取用户所属分公司
        $branch = Help_Platform_Branch::get_instance()->get_user_branch($user_id);
        if (!$branch) {
            wp_send_json_error(__('您不属于任何分公司，无法进行充值', 'help-platform'));
        }

        if ($amount <= 0) {
            wp_send_json_error(__('充值金额必须大于0', 'help-platform'));
        }

        // 创建充值订单
        $order = wc_create_order();
        $order->set_customer_id($user_id);
        $order->set_payment_method($payment_method);
        $order->set_total($amount);
        $order->set_status('pending');
        $order->add_order_note(__('用户充值订单', 'help-platform'));
        $order->save();

        // 记录充值信息
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'help_recharges',
            array(
                'user_id' => $user_id,
                'branch_id' => $branch['id'],
                'order_id' => $order->get_id(),
                'amount' => $amount,
                'payment_method' => $payment_method,
                'status' => 'pending',
                'created_at' => current_time('mysql')
            ),
            array('%d', '%d', '%d', '%f', '%s', '%s', '%s')
        );

        // 返回支付链接
        wp_send_json_success(array(
            'redirect' => $order->get_checkout_payment_url()
        ));
    }

    /**
     * 处理提现请求
     */
    public function process_withdraw() {
        check_ajax_referer('help-platform-finance', 'nonce');

        $user_id = get_current_user_id();
        $amount = floatval($_POST['amount']);
        $withdraw_method = sanitize_text_field($_POST['withdraw_method']);
        $account_info = sanitize_text_field($_POST['account_info']);

        // 获取用户所属分公司
        $branch = Help_Platform_Branch::get_instance()->get_user_branch($user_id);
        if (!$branch) {
            wp_send_json_error(__('您不属于任何分公司，无法申请提现', 'help-platform'));
        }

        $branch_data = $branch['data'];
        $balance = $this->get_user_balance($user_id);

        if ($amount < $branch_data['min_withdraw']) {
            wp_send_json_error(sprintf(
                __('提现金额不能低于 %s %s', 'help-platform'),
                $branch_data['min_withdraw'],
                $branch_data['currency']
            ));
        }

        if ($amount > $balance) {
            wp_send_json_error(__('提现金额不能大于可用余额', 'help-platform'));
        }

        // 计算手续费
        $fee = $amount * $branch_data['withdraw_fee'];
        $final_amount = $amount - $fee;

        // 创建提现记录
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'help_withdrawals',
            array(
                'user_id' => $user_id,
                'branch_id' => $branch['id'],
                'amount' => $amount,
                'fee' => $fee,
                'final_amount' => $final_amount,
                'method' => $withdraw_method,
                'account_info' => $account_info,
                'status' => 'pending',
                'created_at' => current_time('mysql')
            ),
            array('%d', '%d', '%f', '%f', '%f', '%s', '%s', '%s', '%s')
        );

        // 扣除用户余额
        $this->update_user_balance($user_id, -$amount);

        wp_send_json_success(__('提现申请已提交，请等待审核', 'help-platform'));
    }

    /**
     * 计算佣金
     */
    public function calculate_commission($order_id) {
        $order = wc_get_order($order_id);
        $branch = Help_Platform_Branch::get_instance()->get_user_branch($order->get_customer_id());
        
        if (!$branch) {
            return;
        }

        $branch_data = $branch['data'];

        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if ($product && $product->get_type() === 'help_task') {
                $task_id = get_posts(array(
                    'post_type' => 'help_job',
                    'meta_key' => '_order_id',
                    'meta_value' => $order_id,
                    'posts_per_page' => 1
                ));

                if (!empty($task_id)) {
                    $worker_id = get_post_meta($task_id[0]->ID, '_worker_id', true);
                    if ($worker_id) {
                        // 计算佣金
                        $total = $item->get_total();
                        $commission = $total * $branch_data['commission_rate'];
                        $platform_fee = $total * $branch_data['platform_fee'];
                        $worker_amount = $total - $commission - $platform_fee;

                        // 记录佣金
                        global $wpdb;
                        $wpdb->insert(
                            $wpdb->prefix . 'help_commissions',
                            array(
                                'order_id' => $order_id,
                                'branch_id' => $branch['id'],
                                'task_id' => $task_id[0]->ID,
                                'worker_id' => $worker_id,
                                'amount' => $total,
                                'commission' => $commission,
                                'platform_fee' => $platform_fee,
                                'worker_amount' => $worker_amount,
                                'status' => 'completed',
                                'created_at' => current_time('mysql')
                            ),
                            array('%d', '%d', '%d', '%d', '%f', '%f', '%f', '%f', '%s', '%s')
                        );

                        // 更新工人余额
                        $this->update_user_balance($worker_id, $worker_amount);
                    }
                }
            }
        }
    }

    /**
     * 获取用户余额
     */
    public function get_user_balance($user_id) {
        return floatval(get_user_meta($user_id, '_help_platform_balance', true));
    }

    /**
     * 更新用户余额
     */
    public function update_user_balance($user_id, $amount) {
        $current_balance = $this->get_user_balance($user_id);
        $new_balance = $current_balance + $amount;
        update_user_meta($user_id, '_help_platform_balance', $new_balance);
        return $new_balance;
    }

    /**
     * 获取用户交易记录
     */
    public function get_user_transactions($user_id, $limit = 10) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM (
                SELECT 'recharge' as type, created_at, amount, status, NULL as fee
                FROM {$wpdb->prefix}help_recharges
                WHERE user_id = %d
                UNION ALL
                SELECT 'withdraw' as type, created_at, amount, status, fee
                FROM {$wpdb->prefix}help_withdrawals
                WHERE user_id = %d
                UNION ALL
                SELECT 'commission' as type, created_at, worker_amount as amount, status, commission as fee
                FROM {$wpdb->prefix}help_commissions
                WHERE worker_id = %d
            ) as transactions
            ORDER BY created_at DESC
            LIMIT %d",
            $user_id,
            $user_id,
            $user_id,
            $limit
        ));
    }

    /**
     * 获取用户佣金统计
     */
    public function get_user_commission($user_id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT 
                SUM(worker_amount) as total_earned,
                COUNT(*) as total_tasks,
                SUM(CASE WHEN status = 'completed' THEN worker_amount ELSE 0 END) as completed_earned,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_tasks
            FROM {$wpdb->prefix}help_commissions
            WHERE worker_id = %d",
            $user_id
        ));
    }
}

// 初始化
function help_platform_finance_init() {
    return Help_Platform_Finance::get_instance();
}
add_action('plugins_loaded', 'help_platform_finance_init'); 