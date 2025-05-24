<?php
/**
 * HELP Platform 分公司资金转账管理类
 */
class Help_Platform_Transfer {
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
        add_action('admin_menu', array($this, 'add_transfer_menu'));
        
        // 处理转账请求
        add_action('wp_ajax_help_platform_process_transfer', array($this, 'process_transfer'));
        
        // 添加转账记录表
        add_action('admin_init', array($this, 'add_transfer_columns'));
        add_filter('manage_help_transfer_posts_columns', array($this, 'transfer_columns'));
        add_action('manage_help_transfer_posts_custom_column', array($this, 'transfer_column_content'), 10, 2);
    }

    /**
     * 注册转账记录文章类型
     */
    public function register_transfer_post_type() {
        register_post_type('help_transfer', array(
            'labels' => array(
                'name' => __('资金转账', 'help-platform'),
                'singular_name' => __('转账记录', 'help-platform'),
                'menu_name' => __('资金转账', 'help-platform'),
                'all_items' => __('所有转账', 'help-platform'),
                'add_new' => __('发起转账', 'help-platform'),
                'add_new_item' => __('发起新转账', 'help-platform'),
                'edit_item' => __('编辑转账', 'help-platform'),
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'help-platform',
            'supports' => array('title'),
            'capability_type' => 'post',
            'map_meta_cap' => true,
            'hierarchical' => false,
            'rewrite' => false,
            'query_var' => false,
        ));
    }

    /**
     * 添加转账管理菜单
     */
    public function add_transfer_menu() {
        add_submenu_page(
            'help-platform',
            __('资金转账', 'help-platform'),
            __('资金转账', 'help-platform'),
            'manage_help_platform_finance',
            'edit.php?post_type=help_transfer'
        );
    }

    /**
     * 添加转账元数据框
     */
    public function add_transfer_meta_boxes() {
        add_meta_box(
            'help_transfer_details',
            __('转账详情', 'help-platform'),
            array($this, 'render_transfer_meta_box'),
            'help_transfer',
            'normal',
            'high'
        );
    }

    /**
     * 渲染转账元数据框
     */
    public function render_transfer_meta_box($post) {
        wp_nonce_field('help_transfer_meta_box', 'help_transfer_meta_box_nonce');
        
        $transfer_data = get_post_meta($post->ID, '_help_transfer_data', true);
        $transfer_data = wp_parse_args($transfer_data, array(
            'from_branch_id' => '',
            'to_branch_id' => '',
            'amount' => '',
            'currency' => '',
            'status' => 'pending',
            'note' => '',
        ));
        
        include HELP_PLATFORM_PLUGIN_DIR . 'templates/admin-transfer-meta.php';
    }

    /**
     * 保存转账元数据
     */
    public function save_transfer_meta($post_id) {
        if (!isset($_POST['help_transfer_meta_box_nonce']) || 
            !wp_verify_nonce($_POST['help_transfer_meta_box_nonce'], 'help_transfer_meta_box')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $transfer_data = array(
            'from_branch_id' => intval($_POST['from_branch_id']),
            'to_branch_id' => intval($_POST['to_branch_id']),
            'amount' => floatval($_POST['amount']),
            'currency' => sanitize_text_field($_POST['currency']),
            'status' => sanitize_text_field($_POST['status']),
            'note' => sanitize_textarea_field($_POST['note']),
        );

        update_post_meta($post_id, '_help_transfer_data', $transfer_data);

        // 如果状态更新为已完成，执行转账
        if ($transfer_data['status'] === 'completed' && get_post_meta($post_id, '_help_transfer_status', true) !== 'completed') {
            $this->execute_transfer($post_id, $transfer_data);
        }
    }

    /**
     * 执行转账
     */
    private function execute_transfer($transfer_id, $transfer_data) {
        global $wpdb;

        // 开始事务
        $wpdb->query('START TRANSACTION');

        try {
            // 检查转出分公司余额
            $from_balance = Help_Platform_Finance::get_instance()->get_branch_balance($transfer_data['from_branch_id']);
            if ($from_balance < $transfer_data['amount']) {
                throw new Exception(__('转出分公司余额不足', 'help-platform'));
            }

            // 扣除转出分公司余额
            $wpdb->insert(
                $wpdb->prefix . 'help_withdrawals',
                array(
                    'branch_id' => $transfer_data['from_branch_id'],
                    'amount' => $transfer_data['amount'],
                    'fee' => 0,
                    'final_amount' => $transfer_data['amount'],
                    'method' => 'transfer',
                    'account_info' => sprintf(__('转账至分公司 #%d', 'help-platform'), $transfer_data['to_branch_id']),
                    'status' => 'completed',
                    'created_at' => current_time('mysql')
                ),
                array('%d', '%f', '%f', '%f', '%s', '%s', '%s', '%s')
            );

            // 增加转入分公司余额
            $wpdb->insert(
                $wpdb->prefix . 'help_recharges',
                array(
                    'branch_id' => $transfer_data['to_branch_id'],
                    'amount' => $transfer_data['amount'],
                    'payment_method' => 'transfer',
                    'status' => 'completed',
                    'created_at' => current_time('mysql')
                ),
                array('%d', '%f', '%s', '%s', '%s')
            );

            // 更新转账状态
            update_post_meta($transfer_id, '_help_transfer_status', 'completed');
            update_post_meta($transfer_id, '_help_transfer_completed_at', current_time('mysql'));

            // 提交事务
            $wpdb->query('COMMIT');

            return true;
        } catch (Exception $e) {
            // 回滚事务
            $wpdb->query('ROLLBACK');
            
            // 更新转账状态为失败
            update_post_meta($transfer_id, '_help_transfer_status', 'failed');
            update_post_meta($transfer_id, '_help_transfer_error', $e->getMessage());
            
            return false;
        }
    }

    /**
     * 处理转账请求
     */
    public function process_transfer() {
        check_ajax_referer('help-platform-transfer', 'nonce');

        if (!current_user_can('manage_help_platform_finance')) {
            wp_send_json_error(__('您没有权限执行此操作', 'help-platform'));
        }

        $from_branch_id = intval($_POST['from_branch_id']);
        $to_branch_id = intval($_POST['to_branch_id']);
        $amount = floatval($_POST['amount']);
        $note = sanitize_textarea_field($_POST['note']);

        if (!$from_branch_id || !$to_branch_id || $amount <= 0) {
            wp_send_json_error(__('参数错误', 'help-platform'));
        }

        if ($from_branch_id === $to_branch_id) {
            wp_send_json_error(__('不能转账给自己', 'help-platform'));
        }

        // 获取分公司数据
        $from_branch = get_post($from_branch_id);
        $to_branch = get_post($to_branch_id);

        if (!$from_branch || !$to_branch || 
            $from_branch->post_type !== 'help_branch' || 
            $to_branch->post_type !== 'help_branch') {
            wp_send_json_error(__('分公司不存在', 'help-platform'));
        }

        // 检查转出分公司余额
        $from_balance = Help_Platform_Finance::get_instance()->get_branch_balance($from_branch_id);
        if ($from_balance < $amount) {
            wp_send_json_error(__('转出分公司余额不足', 'help-platform'));
        }

        // 创建转账记录
        $transfer_id = wp_insert_post(array(
            'post_title' => sprintf(
                __('从 %s 转账至 %s', 'help-platform'),
                $from_branch->post_title,
                $to_branch->post_title
            ),
            'post_type' => 'help_transfer',
            'post_status' => 'publish'
        ));

        if (is_wp_error($transfer_id)) {
            wp_send_json_error(__('创建转账记录失败', 'help-platform'));
        }

        // 保存转账数据
        $transfer_data = array(
            'from_branch_id' => $from_branch_id,
            'to_branch_id' => $to_branch_id,
            'amount' => $amount,
            'currency' => get_post_meta($from_branch_id, '_help_branch_data', true)['currency'],
            'status' => 'pending',
            'note' => $note
        );

        update_post_meta($transfer_id, '_help_transfer_data', $transfer_data);
        update_post_meta($transfer_id, '_help_transfer_status', 'pending');
        update_post_meta($transfer_id, '_help_transfer_created_at', current_time('mysql'));

        wp_send_json_success(array(
            'message' => __('转账申请已创建', 'help-platform'),
            'transfer_id' => $transfer_id
        ));
    }

    /**
     * 添加转账记录列
     */
    public function add_transfer_columns() {
        add_filter('manage_help_transfer_posts_columns', array($this, 'transfer_columns'));
        add_action('manage_help_transfer_posts_custom_column', array($this, 'transfer_column_content'), 10, 2);
    }

    /**
     * 定义转账记录列
     */
    public function transfer_columns($columns) {
        $new_columns = array();
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            if ($key === 'title') {
                $new_columns['transfer_amount'] = __('转账金额', 'help-platform');
                $new_columns['transfer_from'] = __('转出分公司', 'help-platform');
                $new_columns['transfer_to'] = __('转入分公司', 'help-platform');
                $new_columns['transfer_status'] = __('状态', 'help-platform');
                $new_columns['transfer_date'] = __('转账时间', 'help-platform');
            }
        }
        return $new_columns;
    }

    /**
     * 显示转账记录列内容
     */
    public function transfer_column_content($column, $post_id) {
        if (!in_array($column, array('transfer_amount', 'transfer_from', 'transfer_to', 'transfer_status', 'transfer_date'))) {
            return;
        }

        $transfer_data = get_post_meta($post_id, '_help_transfer_data', true);
        $status = get_post_meta($post_id, '_help_transfer_status', true);
        $created_at = get_post_meta($post_id, '_help_transfer_created_at', true);
        $completed_at = get_post_meta($post_id, '_help_transfer_completed_at', true);

        switch ($column) {
            case 'transfer_amount':
                echo number_format($transfer_data['amount'], 2) . ' ' . $transfer_data['currency'];
                break;

            case 'transfer_from':
                $from_branch = get_post($transfer_data['from_branch_id']);
                echo $from_branch ? esc_html($from_branch->post_title) : '-';
                break;

            case 'transfer_to':
                $to_branch = get_post($transfer_data['to_branch_id']);
                echo $to_branch ? esc_html($to_branch->post_title) : '-';
                break;

            case 'transfer_status':
                $status_class = '';
                $status_text = '';
                switch ($status) {
                    case 'pending':
                        $status_class = 'pending';
                        $status_text = __('待处理', 'help-platform');
                        break;
                    case 'completed':
                        $status_class = 'completed';
                        $status_text = __('已完成', 'help-platform');
                        break;
                    case 'failed':
                        $status_class = 'failed';
                        $status_text = __('失败', 'help-platform');
                        break;
                    case 'cancelled':
                        $status_class = 'cancelled';
                        $status_text = __('已取消', 'help-platform');
                        break;
                }
                printf(
                    '<span class="help-platform-status-badge status-%s">%s</span>',
                    esc_attr($status_class),
                    esc_html($status_text)
                );
                break;

            case 'transfer_date':
                if ($completed_at) {
                    echo date_i18n('Y-m-d H:i:s', strtotime($completed_at));
                } else {
                    echo date_i18n('Y-m-d H:i:s', strtotime($created_at));
                }
                break;
        }
    }
}

// 初始化
function help_platform_transfer_init() {
    return Help_Platform_Transfer::get_instance();
}
add_action('plugins_loaded', 'help_platform_transfer_init'); 