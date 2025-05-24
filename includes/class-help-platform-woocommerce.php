<?php
/**
 * HELP Platform WooCommerce 集成类
 */
class Help_Platform_WooCommerce {
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
        // 注册自定义产品类型
        add_filter('product_type_selector', array($this, 'add_task_product_type'));
        add_filter('woocommerce_product_data_tabs', array($this, 'add_task_product_tab'));
        add_action('woocommerce_product_data_panels', array($this, 'add_task_product_fields'));
        add_action('woocommerce_process_product_meta', array($this, 'save_task_product_fields'));

        // 处理任务支付
        add_action('woocommerce_checkout_order_processed', array($this, 'handle_task_payment'), 10, 3);
        add_action('woocommerce_order_status_completed', array($this, 'handle_task_completion'));

        // 添加自定义订单状态
        add_action('init', array($this, 'register_task_order_status'));
        add_filter('wc_order_statuses', array($this, 'add_task_order_status'));

        // 添加任务相关的订单操作
        add_action('woocommerce_order_status_changed', array($this, 'handle_order_status_change'), 10, 4);

        // 添加任务价格计算
        add_filter('woocommerce_product_get_price', array($this, 'calculate_task_price'), 10, 2);
        add_filter('woocommerce_product_get_regular_price', array($this, 'calculate_task_price'), 10, 2);

        // 添加任务相关的订单项元数据
        add_action('woocommerce_checkout_create_order_line_item', array($this, 'add_task_order_item_meta'), 10, 4);
    }

    /**
     * 注册自定义产品类型
     */
    public function add_task_product_type($types) {
        $types['help_task'] = __('HELP 任务', 'help-platform');
        return $types;
    }

    /**
     * 添加任务产品标签页
     */
    public function add_task_product_tab($tabs) {
        $tabs['help_task'] = array(
            'label' => __('任务设置', 'help-platform'),
            'target' => 'help_task_options',
            'class' => array('show_if_help_task'),
        );
        return $tabs;
    }

    /**
     * 添加任务产品字段
     */
    public function add_task_product_fields() {
        global $post;
        ?>
        <div id="help_task_options" class="panel woocommerce_options_panel">
            <?php
            woocommerce_wp_text_input(array(
                'id' => '_task_duration',
                'label' => __('任务时长（小时）', 'help-platform'),
                'type' => 'number',
                'custom_attributes' => array(
                    'step' => '0.5',
                    'min' => '0.5'
                )
            ));

            woocommerce_wp_select(array(
                'id' => '_task_category',
                'label' => __('任务类别', 'help-platform'),
                'options' => array(
                    'cleaning' => __('清洁服务', 'help-platform'),
                    'moving' => __('搬家服务', 'help-platform'),
                    'repair' => __('维修服务', 'help-platform'),
                    'other' => __('其他服务', 'help-platform')
                )
            ));

            woocommerce_wp_textarea_input(array(
                'id' => '_task_requirements',
                'label' => __('任务要求', 'help-platform'),
                'desc_tip' => true,
                'description' => __('详细描述任务的具体要求', 'help-platform')
            ));

            woocommerce_wp_checkbox(array(
                'id' => '_task_requires_verification',
                'label' => __('需要实名认证', 'help-platform'),
                'description' => __('勾选此项表示接单者需要完成实名认证', 'help-platform')
            ));
            ?>
        </div>
        <?php
    }

    /**
     * 保存任务产品字段
     */
    public function save_task_product_fields($post_id) {
        $fields = array(
            '_task_duration',
            '_task_category',
            '_task_requirements',
            '_task_requires_verification'
        );

        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
            }
        }
    }

    /**
     * 注册任务订单状态
     */
    public function register_task_order_status() {
        register_post_status('wc-task-in-progress', array(
            'label' => __('任务进行中', 'help-platform'),
            'public' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop('任务进行中 <span class="count">(%s)</span>', '任务进行中 <span class="count">(%s)</span>', 'help-platform')
        ));

        register_post_status('wc-task-completed', array(
            'label' => __('任务已完成', 'help-platform'),
            'public' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop('任务已完成 <span class="count">(%s)</span>', '任务已完成 <span class="count">(%s)</span>', 'help-platform')
        ));
    }

    /**
     * 添加任务订单状态
     */
    public function add_task_order_status($order_statuses) {
        $new_order_statuses = array();
        foreach ($order_statuses as $key => $status) {
            $new_order_statuses[$key] = $status;
            if ($key === 'wc-processing') {
                $new_order_statuses['wc-task-in-progress'] = __('任务进行中', 'help-platform');
                $new_order_statuses['wc-task-completed'] = __('任务已完成', 'help-platform');
            }
        }
        return $new_order_statuses;
    }

    /**
     * 处理任务支付
     */
    public function handle_task_payment($order_id, $posted_data, $order) {
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if ($product && $product->get_type() === 'help_task') {
                // 创建任务记录
                $task_id = wp_insert_post(array(
                    'post_title' => sprintf(__('任务 #%s', 'help-platform'), $order_id),
                    'post_type' => 'help_job',
                    'post_status' => 'publish',
                    'post_author' => $order->get_customer_id()
                ));

                if (!is_wp_error($task_id)) {
                    // 保存任务元数据
                    update_post_meta($task_id, '_order_id', $order_id);
                    update_post_meta($task_id, '_task_duration', $product->get_meta('_task_duration'));
                    update_post_meta($task_id, '_task_category', $product->get_meta('_task_category'));
                    update_post_meta($task_id, '_task_requirements', $product->get_meta('_task_requirements'));
                    update_post_meta($task_id, '_task_requires_verification', $product->get_meta('_task_requires_verification'));
                    update_post_meta($task_id, '_task_status', 'pending');

                    // 添加订单备注
                    $order->add_order_note(sprintf(__('已创建关联任务 #%s', 'help-platform'), $task_id));
                }
            }
        }
    }

    /**
     * 处理任务完成
     */
    public function handle_task_completion($order_id) {
        $order = wc_get_order($order_id);
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if ($product && $product->get_type() === 'help_task') {
                // 更新任务状态
                $task_id = get_posts(array(
                    'post_type' => 'help_job',
                    'meta_key' => '_order_id',
                    'meta_value' => $order_id,
                    'posts_per_page' => 1
                ));

                if (!empty($task_id)) {
                    update_post_meta($task_id[0]->ID, '_task_status', 'completed');
                    $order->add_order_note(sprintf(__('关联任务 #%s 已完成', 'help-platform'), $task_id[0]->ID));
                }
            }
        }
    }

    /**
     * 处理订单状态变更
     */
    public function handle_order_status_change($order_id, $old_status, $new_status, $order) {
        if ($new_status === 'task-in-progress') {
            // 更新任务状态为进行中
            $this->update_task_status($order_id, 'in_progress');
        } elseif ($new_status === 'task-completed') {
            // 更新任务状态为已完成
            $this->update_task_status($order_id, 'completed');
        }
    }

    /**
     * 更新任务状态
     */
    private function update_task_status($order_id, $status) {
        $task_id = get_posts(array(
            'post_type' => 'help_job',
            'meta_key' => '_order_id',
            'meta_value' => $order_id,
            'posts_per_page' => 1
        ));

        if (!empty($task_id)) {
            update_post_meta($task_id[0]->ID, '_task_status', $status);
        }
    }

    /**
     * 计算任务价格
     */
    public function calculate_task_price($price, $product) {
        if ($product->get_type() === 'help_task') {
            $duration = $product->get_meta('_task_duration');
            $base_rate = get_option('help_platform_base_rate', 50); // 基础时薪
            return $duration * $base_rate;
        }
        return $price;
    }

    /**
     * 添加任务订单项元数据
     */
    public function add_task_order_item_meta($item, $cart_item_key, $values, $order) {
        if (isset($values['data']) && $values['data']->get_type() === 'help_task') {
            $item->add_meta_data('_task_duration', $values['data']->get_meta('_task_duration'));
            $item->add_meta_data('_task_category', $values['data']->get_meta('_task_category'));
            $item->add_meta_data('_task_requirements', $values['data']->get_meta('_task_requirements'));
        }
    }
}

// 初始化
function help_platform_woocommerce_init() {
    if (class_exists('WooCommerce')) {
        return Help_Platform_WooCommerce::get_instance();
    }
    return null;
}
add_action('plugins_loaded', 'help_platform_woocommerce_init'); 