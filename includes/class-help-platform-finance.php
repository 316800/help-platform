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
        check_ajax_referer('help_platform_recharge', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('请先登录。', 'help-platform'));
        }

        $user_id = get_current_user_id();
        $amount = floatval($_POST['amount'] ?? 0);
        $payment_method = sanitize_text_field($_POST['payment_method'] ?? '');

        if ($amount <= 0) {
            wp_send_json_error(__('充值金额必须大于0。', 'help-platform'));
        }

        if (empty($payment_method)) {
            wp_send_json_error(__('请选择支付方式。', 'help-platform'));
        }

        // 创建充值记录
        global $wpdb;
        $result = $wpdb->insert(
            $wpdb->prefix . 'help_recharges',
            array(
                'user_id' => $user_id,
                'amount' => $amount,
                'payment_method' => $payment_method,
                'status' => 'pending'
            ),
            array('%d', '%f', '%s', '%s')
        );

        if (!$result) {
            wp_send_json_error(__('创建充值记录失败，请重试。', 'help-platform'));
        }

        $recharge_id = $wpdb->insert_id;

        // 根据支付方式处理支付
        switch ($payment_method) {
            case 'alipay':
                $this->process_alipay_payment($recharge_id, $amount);
                break;
            case 'wechat':
                $this->process_wechat_payment($recharge_id, $amount);
                break;
            case 'bank':
                $this->process_bank_payment($recharge_id, $amount);
                break;
            default:
                wp_send_json_error(__('不支持的支付方式。', 'help-platform'));
        }
    }

    /**
     * 处理支付宝支付
     */
    private function process_alipay_payment($recharge_id, $amount) {
        // 获取支付宝配置
        $alipay_config = get_option('help_platform_alipay_config', array());
        if (empty($alipay_config['app_id']) || empty($alipay_config['private_key'])) {
            wp_send_json_error(__('支付宝配置不完整，请联系管理员。', 'help-platform'));
        }

        // 生成订单号
        $order_no = 'R' . date('YmdHis') . $recharge_id;

        // 更新充值记录
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'help_recharges',
            array('transaction_id' => $order_no),
            array('id' => $recharge_id),
            array('%s'),
            array('%d')
        );

        // 构建支付宝支付参数
        $params = array(
            'app_id' => $alipay_config['app_id'],
            'method' => 'alipay.trade.page.pay',
            'charset' => 'UTF-8',
            'sign_type' => 'RSA2',
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => '1.0',
            'notify_url' => home_url('wp-json/help-platform/v1/alipay/notify'),
            'return_url' => home_url('wp-json/help-platform/v1/alipay/return'),
            'biz_content' => json_encode(array(
                'out_trade_no' => $order_no,
                'total_amount' => number_format($amount, 2, '.', ''),
                'subject' => sprintf(__('HELP平台充值 - %s元', 'help-platform'), $amount),
                'product_code' => 'FAST_INSTANT_TRADE_PAY'
            ))
        );

        // 生成签名
        $params['sign'] = $this->generate_alipay_sign($params, $alipay_config['private_key']);

        // 构建支付表单
        $form = '<form id="alipay_form" action="https://openapi.alipay.com/gateway.do" method="POST">';
        foreach ($params as $key => $value) {
            $form .= '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '">';
        }
        $form .= '</form>';
        $form .= '<script>document.getElementById("alipay_form").submit();</script>';

        wp_send_json_success(array(
            'html' => $form,
            'message' => __('正在跳转到支付宝支付...', 'help-platform')
        ));
    }

    /**
     * 处理微信支付
     */
    private function process_wechat_payment($recharge_id, $amount) {
        // 获取微信支付配置
        $wechat_config = get_option('help_platform_wechat_config', array());
        if (empty($wechat_config['app_id']) || empty($wechat_config['mch_id']) || empty($wechat_config['key'])) {
            wp_send_json_error(__('微信支付配置不完整，请联系管理员。', 'help-platform'));
        }

        // 生成订单号
        $order_no = 'R' . date('YmdHis') . $recharge_id;

        // 更新充值记录
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'help_recharges',
            array('transaction_id' => $order_no),
            array('id' => $recharge_id),
            array('%s'),
            array('%d')
        );

        // 构建微信支付参数
        $params = array(
            'appid' => $wechat_config['app_id'],
            'mch_id' => $wechat_config['mch_id'],
            'nonce_str' => $this->generate_nonce_str(),
            'body' => sprintf(__('HELP平台充值 - %s元', 'help-platform'), $amount),
            'out_trade_no' => $order_no,
            'total_fee' => intval($amount * 100),
            'spbill_create_ip' => $_SERVER['REMOTE_ADDR'],
            'notify_url' => home_url('wp-json/help-platform/v1/wechat/notify'),
            'trade_type' => 'NATIVE'
        );

        // 生成签名
        $params['sign'] = $this->generate_wechat_sign($params, $wechat_config['key']);

        // 调用微信支付统一下单接口
        $xml = $this->array_to_xml($params);
        $response = wp_remote_post('https://api.mch.weixin.qq.com/pay/unifiedorder', array(
            'body' => $xml,
            'headers' => array('Content-Type' => 'text/xml')
        ));

        if (is_wp_error($response)) {
            wp_send_json_error(__('微信支付接口调用失败，请重试。', 'help-platform'));
        }

        $result = $this->xml_to_array($response['body']);
        if ($result['return_code'] !== 'SUCCESS' || $result['result_code'] !== 'SUCCESS') {
            wp_send_json_error($result['return_msg'] ?? __('微信支付下单失败，请重试。', 'help-platform'));
        }

        wp_send_json_success(array(
            'code_url' => $result['code_url'],
            'order_no' => $order_no,
            'message' => __('请使用微信扫描二维码支付', 'help-platform')
        ));
    }

    /**
     * 处理银行转账
     */
    private function process_bank_payment($recharge_id, $amount) {
        // 获取银行账户信息
        $bank_info = get_option('help_platform_bank_info', array());
        if (empty($bank_info['account_name']) || empty($bank_info['account_number']) || empty($bank_info['bank_name'])) {
            wp_send_json_error(__('银行账户信息不完整，请联系管理员。', 'help-platform'));
        }

        // 生成订单号
        $order_no = 'R' . date('YmdHis') . $recharge_id;

        // 更新充值记录
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'help_recharges',
            array(
                'transaction_id' => $order_no,
                'notes' => sprintf(
                    __('请转账到以下账户：\n开户行：%s\n账户名：%s\n账号：%s\n金额：%s元\n订单号：%s', 'help-platform'),
                    $bank_info['bank_name'],
                    $bank_info['account_name'],
                    $bank_info['account_number'],
                    $amount,
                    $order_no
                )
            ),
            array('id' => $recharge_id),
            array('%s', '%s'),
            array('%d')
        );

        wp_send_json_success(array(
            'bank_info' => $bank_info,
            'order_no' => $order_no,
            'amount' => $amount,
            'message' => __('请按照提示信息进行银行转账', 'help-platform')
        ));
    }

    /**
     * 生成支付宝签名
     */
    private function generate_alipay_sign($params, $private_key) {
        ksort($params);
        $string = '';
        foreach ($params as $key => $value) {
            if ($key != 'sign' && $value !== '' && !is_array($value)) {
                $string .= $key . '=' . $value . '&';
            }
        }
        $string = rtrim($string, '&');
        
        openssl_sign($string, $sign, $private_key, OPENSSL_ALGO_SHA256);
        return base64_encode($sign);
    }

    /**
     * 生成微信支付签名
     */
    private function generate_wechat_sign($params, $key) {
        ksort($params);
        $string = '';
        foreach ($params as $k => $v) {
            if ($v !== '' && !is_array($v)) {
                $string .= $k . '=' . $v . '&';
            }
        }
        $string .= 'key=' . $key;
        return strtoupper(md5($string));
    }

    /**
     * 生成随机字符串
     */
    private function generate_nonce_str($length = 32) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $str = '';
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    /**
     * 数组转XML
     */
    private function array_to_xml($arr) {
        $xml = '<xml>';
        foreach ($arr as $key => $val) {
            if (is_numeric($val)) {
                $xml .= '<' . $key . '>' . $val . '</' . $key . '>';
            } else {
                $xml .= '<' . $key . '><![CDATA[' . $val . ']]></' . $key . '>';
            }
        }
        $xml .= '</xml>';
        return $xml;
    }

    /**
     * XML转数组
     */
    private function xml_to_array($xml) {
        return json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
    }

    /**
     * 处理支付回调
     */
    public function handle_payment_callback($payment_method) {
        switch ($payment_method) {
            case 'alipay':
                $this->handle_alipay_callback();
                break;
            case 'wechat':
                $this->handle_wechat_callback();
                break;
            default:
                wp_die(__('不支持的支付方式。', 'help-platform'));
        }
    }

    /**
     * 处理支付宝回调
     */
    private function handle_alipay_callback() {
        $alipay_config = get_option('help_platform_alipay_config', array());
        if (empty($alipay_config['public_key'])) {
            wp_die(__('支付宝配置不完整。', 'help-platform'));
        }

        $params = $_POST;
        $sign = $params['sign'];
        unset($params['sign'], $params['sign_type']);

        ksort($params);
        $string = '';
        foreach ($params as $key => $value) {
            if ($value !== '' && !is_array($value)) {
                $string .= $key . '=' . $value . '&';
            }
        }
        $string = rtrim($string, '&');

        $result = openssl_verify($string, base64_decode($sign), $alipay_config['public_key'], OPENSSL_ALGO_SHA256);

        if ($result === 1 && $params['trade_status'] === 'TRADE_SUCCESS') {
            $this->complete_recharge($params['out_trade_no'], $params['trade_no']);
            echo 'success';
        } else {
            echo 'fail';
        }
        exit;
    }

    /**
     * 处理微信支付回调
     */
    private function handle_wechat_callback() {
        $wechat_config = get_option('help_platform_wechat_config', array());
        if (empty($wechat_config['key'])) {
            wp_die(__('微信支付配置不完整。', 'help-platform'));
        }

        $xml = file_get_contents('php://input');
        $params = $this->xml_to_array($xml);
        $sign = $params['sign'];
        unset($params['sign']);

        if ($this->generate_wechat_sign($params, $wechat_config['key']) === $sign && $params['return_code'] === 'SUCCESS' && $params['result_code'] === 'SUCCESS') {
            $this->complete_recharge($params['out_trade_no'], $params['transaction_id']);
            echo '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
        } else {
            echo '<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[签名验证失败]]></return_msg></xml>';
        }
        exit;
    }

    /**
     * 完成充值
     */
    private function complete_recharge($order_no, $transaction_id) {
        global $wpdb;

        // 获取充值记录
        $recharge = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}help_recharges WHERE transaction_id = %s AND status = 'pending'",
            $order_no
        ));

        if (!$recharge) {
            return false;
        }

        // 开始事务
        $wpdb->query('START TRANSACTION');

        try {
            // 更新充值记录状态
            $wpdb->update(
                $wpdb->prefix . 'help_recharges',
                array(
                    'status' => 'completed',
                    'notes' => sprintf(__('支付成功，交易号：%s', 'help-platform'), $transaction_id)
                ),
                array('id' => $recharge->id),
                array('%s', '%s'),
                array('%d')
            );

            // 更新用户余额
            $this->update_user_balance($recharge->user_id, $recharge->amount);

            // 发送通知
            $user = get_userdata($recharge->user_id);
            if ($user) {
                $subject = sprintf(__('充值成功 - %s元', 'help-platform'), $recharge->amount);
                $message = sprintf(
                    __('您的账户已成功充值 %s 元。\n交易号：%s\n充值时间：%s', 'help-platform'),
                    $recharge->amount,
                    $transaction_id,
                    current_time('mysql')
                );
                $this->send_notification($user->user_email, $subject, $message);
            }

            $wpdb->query('COMMIT');
            return true;
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            return false;
        }
    }

    /**
     * 发送通知
     */
    private function send_notification($to, $subject, $message) {
        $headers = array('Content-Type: text/html; charset=UTF-8');
        wp_mail($to, $subject, wpautop($message), $headers);
    }

    /**
     * 处理提现请求
     */
    public function process_withdraw() {
        check_ajax_referer('help_platform_withdraw', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('请先登录。', 'help-platform'));
        }

        $user_id = get_current_user_id();
        $amount = floatval($_POST['amount'] ?? 0);
        $bank_name = sanitize_text_field($_POST['bank_name'] ?? '');
        $bank_account = sanitize_text_field($_POST['bank_account'] ?? '');
        $bank_holder = sanitize_text_field($_POST['bank_holder'] ?? '');

        // 验证提现金额
        if ($amount <= 0) {
            wp_send_json_error(__('提现金额必须大于0。', 'help-platform'));
        }

        // 验证最低提现金额
        $min_withdraw = floatval(get_option('help_platform_min_withdraw', 100));
        if ($amount < $min_withdraw) {
            wp_send_json_error(sprintf(__('最低提现金额为%s元。', 'help-platform'), $min_withdraw));
        }

        // 验证用户余额
        $balance = $this->get_user_balance($user_id);
        if ($balance < $amount) {
            wp_send_json_error(__('余额不足。', 'help-platform'));
        }

        // 验证银行卡信息
        if (empty($bank_name) || empty($bank_account) || empty($bank_holder)) {
            wp_send_json_error(__('请填写完整的银行卡信息。', 'help-platform'));
        }

        // 计算手续费
        $fee_rate = floatval(get_option('help_platform_withdraw_fee_rate', 0.01));
        $fee = round($amount * $fee_rate, 2);
        $actual_amount = $amount - $fee;

        // 创建提现记录
        global $wpdb;
        $result = $wpdb->insert(
            $wpdb->prefix . 'help_withdrawals',
            array(
                'user_id' => $user_id,
                'amount' => $amount,
                'fee' => $fee,
                'bank_name' => $bank_name,
                'bank_account' => $bank_account,
                'bank_holder' => $bank_holder,
                'status' => 'pending'
            ),
            array('%d', '%f', '%f', '%s', '%s', '%s', '%s')
        );

        if (!$result) {
            wp_send_json_error(__('创建提现记录失败，请重试。', 'help-platform'));
        }

        $withdrawal_id = $wpdb->insert_id;

        // 冻结用户余额
        $this->update_user_balance($user_id, -$amount);

        // 发送通知
        $user = get_userdata($user_id);
        if ($user) {
            $subject = sprintf(__('提现申请已提交 - %s元', 'help-platform'), $amount);
            $message = sprintf(
                __('您的提现申请已提交，请等待审核。\n提现金额：%s元\n手续费：%s元\n实际到账：%s元\n申请时间：%s', 'help-platform'),
                $amount,
                $fee,
                $actual_amount,
                current_time('mysql')
            );
            $this->send_notification($user->user_email, $subject, $message);
        }

        // 通知管理员
        $admin_email = get_option('admin_email');
        $admin_subject = sprintf(__('新的提现申请 - %s元', 'help-platform'), $amount);
        $admin_message = sprintf(
            __('收到新的提现申请：\n用户：%s\n提现金额：%s元\n手续费：%s元\n实际到账：%s元\n银行卡信息：\n开户行：%s\n账户名：%s\n账号：%s\n申请时间：%s', 'help-platform'),
            $user->display_name,
            $amount,
            $fee,
            $actual_amount,
            $bank_name,
            $bank_holder,
            $bank_account,
            current_time('mysql')
        );
        $this->send_notification($admin_email, $admin_subject, $admin_message);

        wp_send_json_success(array(
            'message' => __('提现申请已提交，请等待审核。', 'help-platform'),
            'withdrawal_id' => $withdrawal_id
        ));
    }

    /**
     * 处理提现审核
     */
    public function process_withdraw_review() {
        check_ajax_referer('help_platform_withdraw_review', 'nonce');

        if (!current_user_can('manage_help_platform_finance')) {
            wp_send_json_error(__('您没有权限执行此操作。', 'help-platform'));
        }

        $withdrawal_id = intval($_POST['withdrawal_id'] ?? 0);
        $action = sanitize_text_field($_POST['action'] ?? '');
        $notes = sanitize_textarea_field($_POST['notes'] ?? '');

        if (!$withdrawal_id || !in_array($action, array('approve', 'reject'))) {
            wp_send_json_error(__('无效的请求。', 'help-platform'));
        }

        global $wpdb;
        $withdrawal = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}help_withdrawals WHERE id = %d AND status = 'pending'",
            $withdrawal_id
        ));

        if (!$withdrawal) {
            wp_send_json_error(__('提现记录不存在或状态不正确。', 'help-platform'));
        }

        // 开始事务
        $wpdb->query('START TRANSACTION');

        try {
            if ($action === 'approve') {
                // 更新提现记录状态
                $wpdb->update(
                    $wpdb->prefix . 'help_withdrawals',
                    array(
                        'status' => 'completed',
                        'notes' => $notes ?: __('提现申请已通过审核。', 'help-platform')
                    ),
                    array('id' => $withdrawal_id),
                    array('%s', '%s'),
                    array('%d')
                );

                // 发送通知给用户
                $user = get_userdata($withdrawal->user_id);
                if ($user) {
                    $subject = sprintf(__('提现申请已通过 - %s元', 'help-platform'), $withdrawal->amount);
                    $message = sprintf(
                        __('您的提现申请已通过审核。\n提现金额：%s元\n手续费：%s元\n实际到账：%s元\n到账时间：%s', 'help-platform'),
                        $withdrawal->amount,
                        $withdrawal->fee,
                        $withdrawal->amount - $withdrawal->fee,
                        current_time('mysql')
                    );
                    $this->send_notification($user->user_email, $subject, $message);
                }
            } else {
                // 更新提现记录状态
                $wpdb->update(
                    $wpdb->prefix . 'help_withdrawals',
                    array(
                        'status' => 'rejected',
                        'notes' => $notes ?: __('提现申请未通过审核。', 'help-platform')
                    ),
                    array('id' => $withdrawal_id),
                    array('%s', '%s'),
                    array('%d')
                );

                // 返还用户余额
                $this->update_user_balance($withdrawal->user_id, $withdrawal->amount);

                // 发送通知给用户
                $user = get_userdata($withdrawal->user_id);
                if ($user) {
                    $subject = sprintf(__('提现申请未通过 - %s元', 'help-platform'), $withdrawal->amount);
                    $message = sprintf(
                        __('您的提现申请未通过审核。\n提现金额：%s元\n原因：%s\n申请时间：%s', 'help-platform'),
                        $withdrawal->amount,
                        $notes ?: __('提现申请未通过审核。', 'help-platform'),
                        current_time('mysql')
                    );
                    $this->send_notification($user->user_email, $subject, $message);
                }
            }

            $wpdb->query('COMMIT');
            wp_send_json_success(array(
                'message' => $action === 'approve' ? 
                    __('提现申请已通过审核。', 'help-platform') : 
                    __('提现申请已拒绝。', 'help-platform')
            ));
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            wp_send_json_error(__('操作失败，请重试。', 'help-platform'));
        }
    }

    /**
     * 获取用户提现记录
     */
    public function get_user_withdrawals($user_id, $args = array()) {
        global $wpdb;

        $defaults = array(
            'status' => '',
            'per_page' => 20,
            'page' => 1
        );

        $args = wp_parse_args($args, $defaults);
        $where = array('user_id = %d');
        $params = array($user_id);

        if ($args['status']) {
            $where[] = 'status = %s';
            $params[] = $args['status'];
        }

        $where = implode(' AND ', $where);
        $offset = ($args['page'] - 1) * $args['per_page'];

        $query = $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}help_withdrawals 
            WHERE {$where}
            ORDER BY created_at DESC
            LIMIT %d OFFSET %d",
            array_merge($params, array($args['per_page'], $offset))
        );

        return $wpdb->get_results($query);
    }

    /**
     * 获取提现统计
     */
    public function get_withdrawal_stats($user_id = 0) {
        global $wpdb;

        $where = $user_id ? $wpdb->prepare('WHERE user_id = %d', $user_id) : '';

        $stats = $wpdb->get_row("
            SELECT 
                COUNT(*) as total_count,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_count,
                SUM(amount) as total_amount,
                SUM(fee) as total_fee,
                SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as completed_amount,
                SUM(CASE WHEN status = 'completed' THEN fee ELSE 0 END) as completed_fee
            FROM {$wpdb->prefix}help_withdrawals
            {$where}
        ");

        return $stats;
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