<?php
/**
 * HELP Platform 主类
 */
class Help_Platform {
    /**
     * 插件版本
     */
    const VERSION = '1.0.0';

    /**
     * 数据库版本
     */
    const DB_VERSION = '1.0.0';

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
        $this->check_upgrade();
    }

    /**
     * 初始化钩子
     */
    private function init_hooks() {
        // 添加管理菜单
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // 添加设置链接
        add_filter('plugin_action_links_' . plugin_basename(HELP_PLATFORM_PLUGIN_DIR . 'help-platform.php'), 
            array($this, 'add_settings_link'));

        // 注册AJAX处理函数
        $this->register_ajax_handlers();

        // WooCommerce 集成
        add_action('woocommerce_loaded', array($this, 'init_woocommerce'));
        add_action('woocommerce_order_status_changed', array($this, 'handle_order_status_change'), 10, 3);
        add_action('woocommerce_order_details_after_order_table', array($this, 'display_job_info_in_order'));
        add_action('woocommerce_admin_order_data_after_billing_address', array($this, 'display_job_info_in_admin_order'));

        // 添加升级相关钩子
        add_action('admin_init', array($this, 'check_upgrade'));
        add_action('admin_notices', array($this, 'display_upgrade_notices'));
        register_activation_hook(HELP_PLATFORM_PLUGIN_DIR . 'help-platform.php', array($this, 'activate'));
        register_deactivation_hook(HELP_PLATFORM_PLUGIN_DIR . 'help-platform.php', array($this, 'deactivate'));
    }

    /**
     * 注册AJAX处理函数
     */
    private function register_ajax_handlers() {
        $ajax = new Help_Platform_Ajax();

        // 消息相关
        add_action('wp_ajax_help_platform_send_message', array($ajax, 'send_message'));
        add_action('wp_ajax_help_platform_upload_image', array($ajax, 'upload_image'));
        add_action('wp_ajax_help_platform_get_messages', array($ajax, 'get_messages'));

        // 位置相关
        add_action('wp_ajax_help_platform_update_location', array($ajax, 'update_location'));
        add_action('wp_ajax_help_platform_get_location', array($ajax, 'get_location'));

        // 验证相关
        add_action('wp_ajax_help_platform_submit_verify', array($ajax, 'submit_verify'));
    }

    /**
     * 添加管理菜单
     */
    public function add_admin_menu() {
        add_menu_page(
            __('HELP 平台设置', 'help-platform'),
            __('HELP 平台', 'help-platform'),
            'manage_options',
            'help-platform',
            array($this, 'render_settings_page'),
            'dashicons-store',
            30
        );

        add_submenu_page(
            'help-platform',
            __('平台设置', 'help-platform'),
            __('平台设置', 'help-platform'),
            'manage_options',
            'help-platform',
            array($this, 'render_settings_page')
        );
    }

    /**
     * 添加设置链接
     */
    public function add_settings_link($links) {
        $settings_link = '<a href="admin.php?page=help-platform">' . __('设置', 'help-platform') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    /**
     * 渲染设置页面
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        // 保存设置
        if (isset($_POST['help_platform_settings_nonce']) && 
            wp_verify_nonce($_POST['help_platform_settings_nonce'], 'help_platform_settings')) {
            
            $settings = array(
                'enable_registration' => isset($_POST['enable_registration']),
                'enable_job_posting' => isset($_POST['enable_job_posting']),
                'notification_email' => sanitize_email($_POST['notification_email']),
            );
            
            update_option('help_platform_settings', $settings);
            echo '<div class="notice notice-success"><p>' . __('设置已保存', 'help-platform') . '</p></div>';
        }

        // 获取当前设置
        $settings = get_option('help_platform_settings', array(
            'enable_registration' => true,
            'enable_job_posting' => true,
            'notification_email' => get_option('admin_email'),
        ));

        // 加载设置页面模板
        include HELP_PLATFORM_PLUGIN_DIR . 'templates/admin-settings.php';
    }

    /**
     * 获取设置
     */
    public static function get_settings() {
        return get_option('help_platform_settings', array(
            'enable_registration' => true,
            'enable_job_posting' => true,
            'notification_email' => get_option('admin_email'),
        ));
    }

    /**
     * 发送邮件通知
     */
    public static function send_notification($to, $subject, $message) {
        $headers = array('Content-Type: text/html; charset=UTF-8');
        return wp_mail($to, $subject, $message, $headers);
    }

    /**
     * 初始化 WooCommerce 集成
     */
    public function init_woocommerce() {
        if (!class_exists('WooCommerce')) {
            return;
        }

        // 添加自定义订单状态
        add_filter('woocommerce_register_shop_order_post_statuses', array($this, 'register_custom_order_status'));
        add_filter('wc_order_statuses', array($this, 'add_custom_order_status'));
        
        // 添加任务产品类型
        add_filter('product_type_selector', array($this, 'add_job_product_type'));
        add_filter('woocommerce_product_data_tabs', array($this, 'add_job_product_data_tab'));
        add_action('woocommerce_product_data_panels', array($this, 'add_job_product_data_fields'));
        add_action('woocommerce_process_product_meta', array($this, 'save_job_product_data'));
        
        // 添加任务相关字段到结账页面
        add_action('woocommerce_after_order_notes', array($this, 'add_job_fields_to_checkout'));
        add_action('woocommerce_checkout_process', array($this, 'validate_job_fields'));
        add_action('woocommerce_checkout_update_order_meta', array($this, 'save_job_fields'));
    }

    /**
     * 注册自定义订单状态
     */
    public function register_custom_order_status($order_statuses) {
        $order_statuses['wc-job-in-progress'] = array(
            'label' => __('任务进行中', 'help-platform'),
            'public' => true,
            'exclude_from_search' => false,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop('任务进行中 <span class="count">(%s)</span>', '任务进行中 <span class="count">(%s)</span>', 'help-platform')
        );
        $order_statuses['wc-job-completed'] = array(
            'label' => __('任务已完成', 'help-platform'),
            'public' => true,
            'exclude_from_search' => false,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop('任务已完成 <span class="count">(%s)</span>', '任务已完成 <span class="count">(%s)</span>', 'help-platform')
        );
        return $order_statuses;
    }

    /**
     * 添加自定义订单状态到订单状态列表
     */
    public function add_custom_order_status($order_statuses) {
        $new_order_statuses = array();
        foreach ($order_statuses as $key => $status) {
            $new_order_statuses[$key] = $status;
            if ($key === 'wc-processing') {
                $new_order_statuses['wc-job-in-progress'] = __('任务进行中', 'help-platform');
                $new_order_statuses['wc-job-completed'] = __('任务已完成', 'help-platform');
            }
        }
        return $new_order_statuses;
    }

    /**
     * 添加任务产品类型
     */
    public function add_job_product_type($types) {
        $types['job'] = __('任务服务', 'help-platform');
        return $types;
    }

    /**
     * 添加任务产品数据标签页
     */
    public function add_job_product_data_tab($tabs) {
        $tabs['job'] = array(
            'label' => __('任务设置', 'help-platform'),
            'target' => 'job_product_data',
            'class' => array('show_if_job')
        );
        return $tabs;
    }

    /**
     * 添加任务产品数据字段
     */
    public function add_job_product_data_fields() {
        global $post;
        ?>
        <div id="job_product_data" class="panel woocommerce_options_panel">
            <?php
            woocommerce_wp_text_input(array(
                'id' => '_job_duration',
                'label' => __('预计时长（小时）', 'help-platform'),
                'type' => 'number',
                'custom_attributes' => array(
                    'step' => '0.5',
                    'min' => '0.5'
                )
            ));
            
            woocommerce_wp_textarea_input(array(
                'id' => '_job_requirements',
                'label' => __('工作要求', 'help-platform'),
                'desc_tip' => true,
                'description' => __('描述完成此任务所需的具体要求', 'help-platform')
            ));
            
            woocommerce_wp_checkbox(array(
                'id' => '_job_requires_location',
                'label' => __('需要位置追踪', 'help-platform'),
                'description' => __('启用此选项将允许客户追踪工作者的位置', 'help-platform')
            ));
            ?>
        </div>
        <?php
    }

    /**
     * 保存任务产品数据
     */
    public function save_job_product_data($post_id) {
        $job_duration = isset($_POST['_job_duration']) ? wc_clean($_POST['_job_duration']) : '';
        $job_requirements = isset($_POST['_job_requirements']) ? wp_kses_post($_POST['_job_requirements']) : '';
        $job_requires_location = isset($_POST['_job_requires_location']) ? 'yes' : 'no';

        update_post_meta($post_id, '_job_duration', $job_duration);
        update_post_meta($post_id, '_job_requirements', $job_requirements);
        update_post_meta($post_id, '_job_requires_location', $job_requires_location);
    }

    /**
     * 添加任务字段到结账页面
     */
    public function add_job_fields_to_checkout($checkout) {
        $cart = WC()->cart;
        $has_job_product = false;

        foreach ($cart->get_cart() as $cart_item) {
            $product = $cart_item['data'];
            if ($product->get_type() === 'job') {
                $has_job_product = true;
                break;
            }
        }

        if (!$has_job_product) {
            return;
        }

        echo '<div id="job_fields">';
        echo '<h3>' . __('任务详情', 'help-platform') . '</h3>';

        woocommerce_form_field('job_title', array(
            'type' => 'text',
            'class' => array('form-row-wide'),
            'label' => __('任务标题', 'help-platform'),
            'required' => true,
        ), $checkout->get_value('job_title'));

        woocommerce_form_field('job_description', array(
            'type' => 'textarea',
            'class' => array('form-row-wide'),
            'label' => __('任务描述', 'help-platform'),
            'required' => true,
        ), $checkout->get_value('job_description'));

        woocommerce_form_field('job_address', array(
            'type' => 'textarea',
            'class' => array('form-row-wide'),
            'label' => __('任务地址', 'help-platform'),
            'required' => true,
        ), $checkout->get_value('job_address'));

        woocommerce_form_field('job_date', array(
            'type' => 'date',
            'class' => array('form-row-wide'),
            'label' => __('期望日期', 'help-platform'),
            'required' => true,
            'custom_attributes' => array(
                'min' => date('Y-m-d')
            )
        ), $checkout->get_value('job_date'));

        echo '</div>';
    }

    /**
     * 验证任务字段
     */
    public function validate_job_fields() {
        $cart = WC()->cart;
        $has_job_product = false;

        foreach ($cart->get_cart() as $cart_item) {
            $product = $cart_item['data'];
            if ($product->get_type() === 'job') {
                $has_job_product = true;
                break;
            }
        }

        if (!$has_job_product) {
            return;
        }

        if (empty($_POST['job_title'])) {
            wc_add_notice(__('请输入任务标题', 'help-platform'), 'error');
        }
        if (empty($_POST['job_description'])) {
            wc_add_notice(__('请输入任务描述', 'help-platform'), 'error');
        }
        if (empty($_POST['job_address'])) {
            wc_add_notice(__('请输入任务地址', 'help-platform'), 'error');
        }
        if (empty($_POST['job_date'])) {
            wc_add_notice(__('请选择期望日期', 'help-platform'), 'error');
        }
    }

    /**
     * 保存任务字段
     */
    public function save_job_fields($order_id) {
        if (!empty($_POST['job_title'])) {
            update_post_meta($order_id, '_job_title', sanitize_text_field($_POST['job_title']));
        }
        if (!empty($_POST['job_description'])) {
            update_post_meta($order_id, '_job_description', sanitize_textarea_field($_POST['job_description']));
        }
        if (!empty($_POST['job_address'])) {
            update_post_meta($order_id, '_job_address', sanitize_textarea_field($_POST['job_address']));
        }
        if (!empty($_POST['job_date'])) {
            update_post_meta($order_id, '_job_date', sanitize_text_field($_POST['job_date']));
        }

        // 创建任务
        $this->create_job_from_order($order_id);
    }

    /**
     * 从订单创建任务
     */
    private function create_job_from_order($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        // 检查订单是否包含任务产品
        $has_job_product = false;
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if ($product && $product->get_type() === 'job') {
                $has_job_product = true;
                break;
            }
        }

        if (!$has_job_product) {
            return;
        }

        // 创建任务
        $job_data = array(
            'post_title' => get_post_meta($order_id, '_job_title', true),
            'post_content' => get_post_meta($order_id, '_job_description', true),
            'post_type' => 'help_job',
            'post_status' => 'publish',
            'post_author' => $order->get_customer_id()
        );

        $job_id = wp_insert_post($job_data);
        if (is_wp_error($job_id)) {
            return;
        }

        // 保存任务元数据
        update_post_meta($job_id, '_order_id', $order_id);
        update_post_meta($job_id, '_job_address', get_post_meta($order_id, '_job_address', true));
        update_post_meta($job_id, '_job_date', get_post_meta($order_id, '_job_date', true));
        update_post_meta($job_id, '_job_status', 'pending');

        // 更新订单元数据
        update_post_meta($order_id, '_job_id', $job_id);
    }

    /**
     * 处理订单状态变更
     */
    public function handle_order_status_change($order_id, $old_status, $new_status) {
        $job_id = get_post_meta($order_id, '_job_id', true);
        if (!$job_id) {
            return;
        }

        $job = get_post($job_id);
        if (!$job || $job->post_type !== 'help_job') {
            return;
        }

        switch ($new_status) {
            case 'job-in-progress':
                update_post_meta($job_id, '_job_status', 'in_progress');
                break;
            case 'job-completed':
                update_post_meta($job_id, '_job_status', 'completed');
                break;
            case 'cancelled':
                update_post_meta($job_id, '_job_status', 'cancelled');
                break;
            case 'refunded':
                update_post_meta($job_id, '_job_status', 'refunded');
                break;
        }
    }

    /**
     * 在订单详情页面显示任务信息
     */
    public function display_job_info_in_order($order) {
        $job_id = get_post_meta($order->get_id(), '_job_id', true);
        if (!$job_id) {
            return;
        }

        $job = get_post($job_id);
        if (!$job || $job->post_type !== 'help_job') {
            return;
        }

        $job_status = get_post_meta($job_id, '_job_status', true);
        $job_address = get_post_meta($job_id, '_job_address', true);
        $job_date = get_post_meta($job_id, '_job_date', true);
        $worker_id = get_post_meta($job_id, '_worker_id', true);
        $worker = $worker_id ? get_userdata($worker_id) : null;

        ?>
        <h2><?php _e('任务信息', 'help-platform'); ?></h2>
        <table class="woocommerce-table shop_table job_details">
            <tbody>
                <tr>
                    <th><?php _e('任务状态：', 'help-platform'); ?></th>
                    <td><?php echo esc_html($this->get_job_status_label($job_status)); ?></td>
                </tr>
                <tr>
                    <th><?php _e('任务地址：', 'help-platform'); ?></th>
                    <td><?php echo esc_html($job_address); ?></td>
                </tr>
                <tr>
                    <th><?php _e('期望日期：', 'help-platform'); ?></th>
                    <td><?php echo esc_html($job_date); ?></td>
                </tr>
                <?php if ($worker) : ?>
                <tr>
                    <th><?php _e('工作者：', 'help-platform'); ?></th>
                    <td><?php echo esc_html($worker->display_name); ?></td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <?php
    }

    /**
     * 在管理订单页面显示任务信息
     */
    public function display_job_info_in_admin_order($order) {
        $job_id = get_post_meta($order->get_id(), '_job_id', true);
        if (!$job_id) {
            return;
        }

        $job = get_post($job_id);
        if (!$job || $job->post_type !== 'help_job') {
            return;
        }

        $job_status = get_post_meta($job_id, '_job_status', true);
        $job_address = get_post_meta($job_id, '_job_address', true);
        $job_date = get_post_meta($job_id, '_job_date', true);
        $worker_id = get_post_meta($job_id, '_worker_id', true);
        $worker = $worker_id ? get_userdata($worker_id) : null;

        ?>
        <div class="job-info">
            <h3><?php _e('任务信息', 'help-platform'); ?></h3>
            <p>
                <strong><?php _e('任务状态：', 'help-platform'); ?></strong>
                <?php echo esc_html($this->get_job_status_label($job_status)); ?>
            </p>
            <p>
                <strong><?php _e('任务地址：', 'help-platform'); ?></strong>
                <?php echo esc_html($job_address); ?>
            </p>
            <p>
                <strong><?php _e('期望日期：', 'help-platform'); ?></strong>
                <?php echo esc_html($job_date); ?>
            </p>
            <?php if ($worker) : ?>
            <p>
                <strong><?php _e('工作者：', 'help-platform'); ?></strong>
                <?php echo esc_html($worker->display_name); ?>
            </p>
            <?php endif; ?>
            <p>
                <a href="<?php echo esc_url(admin_url('admin.php?page=help-platform-jobs&action=edit&job_id=' . $job_id)); ?>" class="button">
                    <?php _e('查看任务详情', 'help-platform'); ?>
                </a>
            </p>
        </div>
        <?php
    }

    /**
     * 获取任务状态标签
     */
    private function get_job_status_label($status) {
        $labels = array(
            'pending' => __('待接单', 'help-platform'),
            'in_progress' => __('进行中', 'help-platform'),
            'completed' => __('已完成', 'help-platform'),
            'cancelled' => __('已取消', 'help-platform'),
            'refunded' => __('已退款', 'help-platform')
        );
        return isset($labels[$status]) ? $labels[$status] : $status;
    }

    /**
     * 检查是否需要升级
     */
    public function check_upgrade() {
        $current_version = get_option('help_platform_version', '0');
        $current_db_version = get_option('help_platform_db_version', '0');

        if (version_compare($current_version, self::VERSION, '<')) {
            // 创建升级前的备份
            $this->create_backup('pre_upgrade_' . $current_version);

            // 执行版本升级
            $this->upgrade_version($current_version);

            // 更新版本号
            update_option('help_platform_version', self::VERSION);
        }

        if (version_compare($current_db_version, self::DB_VERSION, '<')) {
            // 创建数据库升级前的备份
            $this->create_backup('pre_db_upgrade_' . $current_db_version);

            // 执行数据库升级
            $this->upgrade_database($current_db_version);

            // 更新数据库版本号
            update_option('help_platform_db_version', self::DB_VERSION);
        }
    }

    /**
     * 执行版本升级
     */
    private function upgrade_version($from_version) {
        // 根据版本号执行相应的升级操作
        switch ($from_version) {
            case '0':
                // 首次安装，初始化数据
                $this->init_data();
                break;
            case '0.9.0':
                // 从 0.9.0 升级到 1.0.0
                $this->upgrade_to_1_0_0();
                break;
            // 添加更多版本升级处理
        }
    }

    /**
     * 执行数据库升级
     */
    private function upgrade_database($from_version) {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // 根据数据库版本执行相应的升级操作
        switch ($from_version) {
            case '0':
                // 首次安装，创建所有表
                $this->create_tables();
                break;
            case '0.9.0':
                // 从 0.9.0 升级到 1.0.0
                // 例如：添加新列
                $wpdb->query("ALTER TABLE {$wpdb->prefix}help_jobs ADD COLUMN new_column VARCHAR(255) AFTER existing_column");
                break;
            // 添加更多数据库升级处理
        }
    }

    /**
     * 创建数据库表
     */
    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // 创建任务表
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}help_jobs (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            order_id bigint(20) NOT NULL,
            worker_id bigint(20) DEFAULT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY post_id (post_id),
            KEY order_id (order_id),
            KEY worker_id (worker_id),
            KEY status (status)
        ) $charset_collate;";

        // 创建消息表
        $sql .= "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}help_messages (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            job_id bigint(20) NOT NULL,
            sender_id bigint(20) NOT NULL,
            receiver_id bigint(20) NOT NULL,
            message_type varchar(20) NOT NULL DEFAULT 'text',
            content text,
            attachment_url varchar(255),
            is_read tinyint(1) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY job_id (job_id),
            KEY sender_id (sender_id),
            KEY receiver_id (receiver_id)
        ) $charset_collate;";

        // 创建位置表
        $sql .= "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}help_locations (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            worker_id bigint(20) NOT NULL,
            job_id bigint(20) NOT NULL,
            latitude decimal(10,8) NOT NULL,
            longitude decimal(11,8) NOT NULL,
            accuracy float,
            speed float,
            heading float,
            status varchar(20) NOT NULL DEFAULT 'active',
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY worker_id (worker_id),
            KEY job_id (job_id),
            KEY created_at (created_at)
        ) $charset_collate;";

        // 创建备份记录表
        $sql .= "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}help_backups (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            backup_type varchar(20) NOT NULL,
            version varchar(20) NOT NULL,
            file_path varchar(255) NOT NULL,
            file_size bigint(20) NOT NULL,
            created_at datetime NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'success',
            notes text,
            PRIMARY KEY  (id),
            KEY backup_type (backup_type),
            KEY version (version),
            KEY created_at (created_at)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * 创建备份目录
     */
    private function create_backup_directory() {
        $upload_dir = wp_upload_dir();
        $backup_dir = $upload_dir['basedir'] . '/help-platform-backups';
        
        if (!file_exists($backup_dir)) {
            wp_mkdir_p($backup_dir);
        }

        // 创建 .htaccess 文件保护备份目录
        $htaccess_file = $backup_dir . '/.htaccess';
        if (!file_exists($htaccess_file)) {
            $htaccess_content = "Order deny,allow\nDeny from all";
            file_put_contents($htaccess_file, $htaccess_content);
        }

        // 创建 index.php 文件防止目录列表
        $index_file = $backup_dir . '/index.php';
        if (!file_exists($index_file)) {
            file_put_contents($index_file, '<?php // Silence is golden');
        }
    }

    /**
     * 创建数据备份
     */
    private function create_backup($type = 'manual') {
        global $wpdb;
        $upload_dir = wp_upload_dir();
        $backup_dir = $upload_dir['basedir'] . '/help-platform-backups';
        $timestamp = current_time('Y-m-d_H-i-s');
        $filename = "backup_{$type}_{$timestamp}.sql";
        $filepath = $backup_dir . '/' . $filename;

        // 获取需要备份的表
        $tables = array(
            $wpdb->prefix . 'help_jobs',
            $wpdb->prefix . 'help_messages',
            $wpdb->prefix . 'help_locations',
            $wpdb->prefix . 'help_backups'
        );

        // 创建备份文件
        $backup_content = "-- HELP Platform Backup\n";
        $backup_content .= "-- Version: " . self::VERSION . "\n";
        $backup_content .= "-- Date: " . current_time('mysql') . "\n\n";

        foreach ($tables as $table) {
            // 获取表结构
            $create_table = $wpdb->get_row("SHOW CREATE TABLE $table", ARRAY_N);
            $backup_content .= "\n" . $create_table[1] . ";\n\n";

            // 获取表数据
            $rows = $wpdb->get_results("SELECT * FROM $table", ARRAY_A);
            foreach ($rows as $row) {
                $values = array_map(array($wpdb, '_real_escape'), $row);
                $backup_content .= "INSERT INTO $table VALUES ('" . implode("','", $values) . "');\n";
            }
        }

        // 保存备份文件
        if (file_put_contents($filepath, $backup_content)) {
            // 记录备份信息
            $wpdb->insert(
                $wpdb->prefix . 'help_backups',
                array(
                    'backup_type' => $type,
                    'version' => self::VERSION,
                    'file_path' => $filepath,
                    'file_size' => filesize($filepath),
                    'created_at' => current_time('mysql'),
                    'status' => 'success',
                    'notes' => "Backup created for {$type}"
                ),
                array('%s', '%s', '%s', '%d', '%s', '%s', '%s')
            );

            // 清理旧备份
            $this->cleanup_old_backups();
        }
    }

    /**
     * 清理旧备份
     */
    private function cleanup_old_backups() {
        global $wpdb;
        
        // 保留最近30天的备份
        $retention_days = 30;
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$retention_days} days"));
        
        $old_backups = $wpdb->get_results($wpdb->prepare(
            "SELECT id, file_path FROM {$wpdb->prefix}help_backups 
            WHERE created_at < %s AND backup_type != 'pre_upgrade'",
            $cutoff_date
        ));

        foreach ($old_backups as $backup) {
            if (file_exists($backup->file_path)) {
                unlink($backup->file_path);
            }
            $wpdb->delete(
                $wpdb->prefix . 'help_backups',
                array('id' => $backup->id),
                array('%d')
            );
        }
    }

    /**
     * 显示升级通知
     */
    public function display_upgrade_notices() {
        $current_version = get_option('help_platform_version', '0');
        if (version_compare($current_version, self::VERSION, '<')) {
            ?>
            <div class="notice notice-warning">
                <p>
                    <?php printf(
                        __('HELP Platform 插件需要升级。当前版本：%s，最新版本：%s。', 'help-platform'),
                        $current_version,
                        self::VERSION
                    ); ?>
                </p>
                <p>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=help-platform&action=upgrade')); ?>" class="button button-primary">
                        <?php _e('立即升级', 'help-platform'); ?>
                    </a>
                </p>
            </div>
            <?php
        }
    }

    /**
     * 初始化设置
     */
    private function init_settings() {
        $default_settings = array(
            'enable_registration' => true,
            'enable_job_posting' => true,
            'notification_email' => get_option('admin_email'),
            'backup_retention_days' => 30,
            'auto_backup_enabled' => true,
            'backup_schedule' => 'daily'
        );

        $current_settings = get_option('help_platform_settings', array());
        $settings = wp_parse_args($current_settings, $default_settings);
        update_option('help_platform_settings', $settings);

        // 设置定时备份任务
        if ($settings['auto_backup_enabled']) {
            if (!wp_next_scheduled('help_platform_daily_backup')) {
                wp_schedule_event(time(), $settings['backup_schedule'], 'help_platform_daily_backup');
            }
        }
    }

    /**
     * 初始化数据
     */
    private function init_data() {
        // 初始化必要的数据
        // 例如：创建默认角色、设置默认选项等
    }

    /**
     * 升级到 1.0.0 版本
     */
    private function upgrade_to_1_0_0() {
        // 执行 1.0.0 版本特定的升级操作
        // 例如：更新数据结构、迁移数据等
    }

    /**
     * 插件激活时的处理
     */
    public function activate() {
        // 创建必要的数据库表
        $this->create_tables();
        
        // 保存当前版本
        update_option('help_platform_version', self::VERSION);
        update_option('help_platform_db_version', self::DB_VERSION);
        
        // 创建备份目录
        $this->create_backup_directory();
        
        // 初始化设置
        $this->init_settings();
    }

    /**
     * 插件停用时的处理
     */
    public function deactivate() {
        // 创建数据备份
        $this->create_backup();
        
        // 清理定时任务
        wp_clear_scheduled_hook('help_platform_daily_backup');
    }
}

// 初始化插件
function help_platform_init() {
    return Help_Platform::get_instance();
}
add_action('plugins_loaded', 'help_platform_init'); 