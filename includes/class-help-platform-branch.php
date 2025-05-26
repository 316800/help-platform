<?php
/**
 * HELP Platform 分公司管理类
 */
class Help_Platform_Branch {
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
        add_action('admin_menu', array($this, 'add_branch_menu'));
        
        // 注册分公司文章类型
        add_action('init', array($this, 'register_branch_post_type'));
        
        // 添加分公司元数据
        add_action('add_meta_boxes', array($this, 'add_branch_meta_boxes'));
        add_action('save_post', array($this, 'save_branch_meta'));
        
        // 添加分公司管理员角色
        add_action('init', array($this, 'add_branch_manager_role'));
        
        // 处理分公司管理员分配
        add_action('wp_ajax_help_platform_assign_branch_manager', array($this, 'assign_branch_manager'));

        // 添加财务页面链接
        add_filter('post_row_actions', array($this, 'add_finance_action'), 10, 2);
        add_action('admin_menu', array($this, 'add_finance_submenu'));
    }

    /**
     * 注册分公司文章类型
     */
    public function register_branch_post_type() {
        $labels = array(
            'name' => __('分公司', 'help-platform'),
            'singular_name' => __('分公司', 'help-platform'),
            'add_new' => __('添加分公司', 'help-platform'),
            'add_new_item' => __('添加新分公司', 'help-platform'),
            'edit_item' => __('编辑分公司', 'help-platform'),
            'view_item' => __('查看分公司', 'help-platform'),
            'search_items' => __('搜索分公司', 'help-platform'),
            'not_found' => __('未找到分公司', 'help-platform'),
            'not_found_in_trash' => __('回收站中未找到分公司', 'help-platform'),
        );

        $args = array(
            'labels' => $labels,
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'help-platform',
            'menu_icon' => 'dashicons-store',
            'supports' => array('title'),
            'capability_type' => 'post',
            'map_meta_cap' => true,
        );

        register_post_type('help_branch', $args);
    }

    /**
     * 添加分公司管理菜单
     */
    public function add_branch_menu() {
        add_submenu_page(
            'help-platform',
            __('分公司管理', 'help-platform'),
            __('分公司管理', 'help-platform'),
            'manage_options',
            'edit.php?post_type=help_branch'
        );

        add_submenu_page(
            'help-platform',
            __('佣金统计', 'help-platform'),
            __('佣金统计', 'help-platform'),
            'manage_options',
            'help-platform-commission',
            array($this, 'render_commission_page')
        );
    }

    /**
     * 添加分公司元数据框
     */
    public function add_branch_meta_boxes() {
        add_meta_box(
            'branch_details',
            __('分公司详情', 'help-platform'),
            array($this, 'render_branch_meta_box'),
            'help_branch',
            'normal',
            'high'
        );

        add_meta_box(
            'branch_commission',
            __('佣金设置', 'help-platform'),
            array($this, 'render_commission_meta_box'),
            'help_branch',
            'normal',
            'high'
        );

        add_meta_box(
            'branch_statistics',
            __('统计数据', 'help-platform'),
            array($this, 'render_statistics_meta_box'),
            'help_branch',
            'side',
            'default'
        );
    }

    /**
     * 渲染分公司详情元数据框
     */
    public function render_branch_meta_box($post) {
        wp_nonce_field('branch_meta_box', 'branch_meta_box_nonce');

        $branch_data = get_post_meta($post->ID, '_branch_data', true);
        $branch_data = wp_parse_args($branch_data, array(
            'country' => '',
            'city' => '',
            'address' => '',
            'phone' => '',
            'email' => '',
            'manager' => '',
            'currency' => 'USD',
            'timezone' => 'UTC',
            'status' => 'active',
        ));

        include HELP_PLATFORM_PLUGIN_DIR . 'templates/admin-branch-meta.php';
    }

    /**
     * 渲染佣金设置元数据框
     */
    public function render_commission_meta_box($post) {
        $commission_data = get_post_meta($post->ID, '_commission_data', true);
        $commission_data = wp_parse_args($commission_data, array(
            'platform_fee' => 10, // 平台佣金比例
            'branch_fee' => 20,   // 分公司佣金比例
            'worker_fee' => 70,   // 工作者佣金比例
            'min_amount' => 0,    // 最低佣金金额
            'max_amount' => 0,    // 最高佣金金额
            'tax_rate' => 0,      // 税率
        ));

        include HELP_PLATFORM_PLUGIN_DIR . 'templates/admin-branch-commission.php';
    }

    /**
     * 渲染统计数据元数据框
     */
    public function render_statistics_meta_box($post) {
        $statistics = $this->get_branch_statistics($post->ID);
        include HELP_PLATFORM_PLUGIN_DIR . 'templates/admin-branch-statistics.php';
    }

    /**
     * 保存分公司元数据
     */
    public function save_branch_meta($post_id) {
        if (!isset($_POST['branch_meta_box_nonce']) || 
            !wp_verify_nonce($_POST['branch_meta_box_nonce'], 'branch_meta_box')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // 保存分公司基本信息
        $branch_data = array(
            'country' => sanitize_text_field($_POST['branch_country'] ?? ''),
            'city' => sanitize_text_field($_POST['branch_city'] ?? ''),
            'address' => sanitize_textarea_field($_POST['branch_address'] ?? ''),
            'phone' => sanitize_text_field($_POST['branch_phone'] ?? ''),
            'email' => sanitize_email($_POST['branch_email'] ?? ''),
            'manager' => intval($_POST['branch_manager'] ?? 0),
            'currency' => sanitize_text_field($_POST['branch_currency'] ?? 'USD'),
            'timezone' => sanitize_text_field($_POST['branch_timezone'] ?? 'UTC'),
            'status' => sanitize_text_field($_POST['branch_status'] ?? 'active'),
        );
        update_post_meta($post_id, '_branch_data', $branch_data);

        // 保存佣金设置
        $commission_data = array(
            'platform_fee' => floatval($_POST['platform_fee'] ?? 10),
            'branch_fee' => floatval($_POST['branch_fee'] ?? 20),
            'worker_fee' => floatval($_POST['worker_fee'] ?? 70),
            'min_amount' => floatval($_POST['min_amount'] ?? 0),
            'max_amount' => floatval($_POST['max_amount'] ?? 0),
            'tax_rate' => floatval($_POST['tax_rate'] ?? 0),
        );
        update_post_meta($post_id, '_commission_data', $commission_data);
    }

    /**
     * 添加分公司管理员角色
     */
    public function add_branch_manager_role() {
        add_role(
            'help_branch_manager',
            __('HELP 分公司管理员', 'help-platform'),
            array(
                'read' => true,
                'edit_posts' => true,
                'edit_published_posts' => true,
                'publish_posts' => true,
                'delete_posts' => true,
                'upload_files' => true,
                'help_manage_branch_finance' => true,
                'help_manage_branch_users' => true,
                'help_manage_branch_tasks' => true,
            )
        );
    }

    /**
     * 分配分公司管理员
     */
    public function assign_branch_manager() {
        check_ajax_referer('help-platform-branch', 'nonce');

        if (!current_user_can('help_manage_branch')) {
            wp_send_json_error(__('您没有权限执行此操作', 'help-platform'));
        }

        $branch_id = intval($_POST['branch_id']);
        $manager_id = intval($_POST['manager_id']);

        if (!$branch_id || !$manager_id) {
            wp_send_json_error(__('参数错误', 'help-platform'));
        }

        $branch_data = get_post_meta($branch_id, '_help_branch_data', true);
        $branch_data['manager_id'] = $manager_id;
        update_post_meta($branch_id, '_help_branch_data', $branch_data);

        // 更新用户角色
        $user = get_user_by('id', $manager_id);
        if ($user) {
            $user->set_role('help_branch_manager');
            update_user_meta($manager_id, '_help_branch_id', $branch_id);
        }

        wp_send_json_success(__('分公司管理员已更新', 'help-platform'));
    }

    /**
     * 获取分公司列表
     */
    public function get_branches($args = array()) {
        $defaults = array(
            'post_type' => 'help_branch',
            'post_status' => 'publish',
            'posts_per_page' => -1,
        );

        $args = wp_parse_args($args, $defaults);
        $branches = get_posts($args);

        $result = array();
        foreach ($branches as $branch) {
            $branch_data = get_post_meta($branch->ID, '_help_branch_data', true);
            $result[] = array(
                'id' => $branch->ID,
                'title' => $branch->post_title,
                'data' => $branch_data,
            );
        }

        return $result;
    }

    /**
     * 获取用户所属分公司
     */
    public function get_user_branch($user_id) {
        $branch_id = get_user_meta($user_id, '_help_branch_id', true);
        if (!$branch_id) {
            return null;
        }

        $branch = get_post($branch_id);
        if (!$branch || $branch->post_type !== 'help_branch') {
            return null;
        }

        $branch_data = get_post_meta($branch_id, '_help_branch_data', true);
        return array(
            'id' => $branch->ID,
            'title' => $branch->post_title,
            'data' => $branch_data,
        );
    }

    /**
     * 获取分公司货币设置
     */
    public function get_branch_currency($branch_id) {
        $branch_data = get_post_meta($branch_id, '_help_branch_data', true);
        return isset($branch_data['currency']) ? $branch_data['currency'] : 'CNY';
    }

    /**
     * 获取分公司时区设置
     */
    public function get_branch_timezone($branch_id) {
        $branch_data = get_post_meta($branch_id, '_help_branch_data', true);
        return isset($branch_data['timezone']) ? $branch_data['timezone'] : 'Asia/Shanghai';
    }

    /**
     * 添加财务页面链接到分公司列表
     */
    public function add_finance_action($actions, $post) {
        if ($post->post_type === 'help_branch' && current_user_can('manage_help_platform_finance')) {
            $actions['finance'] = sprintf(
                '<a href="%s">%s</a>',
                admin_url('admin.php?page=help-platform-branch-finance&post=' . $post->ID),
                __('财务', 'help-platform')
            );
        }
        return $actions;
    }

    /**
     * 添加财务子菜单
     */
    public function add_finance_submenu() {
        add_submenu_page(
            null,
            __('分公司财务', 'help-platform'),
            __('分公司财务', 'help-platform'),
            'manage_help_platform_finance',
            'help-platform-branch-finance',
            array($this, 'render_finance_page')
        );
    }

    /**
     * 渲染财务页面
     */
    public function render_finance_page() {
        if (!current_user_can('manage_help_platform_finance')) {
            wp_die(__('您没有权限访问此页面', 'help-platform'));
        }

        include HELP_PLATFORM_PLUGIN_DIR . 'templates/admin-branch-finance.php';
    }

    /**
     * 获取分公司统计数据
     */
    public function get_branch_statistics($branch_id) {
        global $wpdb;

        $today = date('Y-m-d');
        $month_start = date('Y-m-01');
        $year_start = date('Y-01-01');

        // 获取任务统计
        $task_stats = $wpdb->get_row($wpdb->prepare("
            SELECT 
                COUNT(*) as total_tasks,
                SUM(CASE WHEN post_date >= %s THEN 1 ELSE 0 END) as today_tasks,
                SUM(CASE WHEN post_date >= %s THEN 1 ELSE 0 END) as month_tasks,
                SUM(CASE WHEN post_date >= %s THEN 1 ELSE 0 END) as year_tasks
            FROM {$wpdb->posts} p
            JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = 'help_job'
            AND p.post_status = 'publish'
            AND pm.meta_key = '_branch_id'
            AND pm.meta_value = %d
        ", $today, $month_start, $year_start, $branch_id));

        // 获取佣金统计
        $commission_stats = $wpdb->get_row($wpdb->prepare("
            SELECT 
                SUM(amount) as total_commission,
                SUM(CASE WHEN date >= %s THEN amount ELSE 0 END) as today_commission,
                SUM(CASE WHEN date >= %s THEN amount ELSE 0 END) as month_commission,
                SUM(CASE WHEN date >= %s THEN amount ELSE 0 END) as year_commission
            FROM {$wpdb->prefix}help_commission
            WHERE branch_id = %d
        ", $today, $month_start, $year_start, $branch_id));

        return array(
            'tasks' => $task_stats,
            'commission' => $commission_stats,
        );
    }

    /**
     * 渲染佣金统计页面
     */
    public function render_commission_page() {
        include HELP_PLATFORM_PLUGIN_DIR . 'templates/admin-commission.php';
    }
}

// 初始化
function help_platform_branch_init() {
    return Help_Platform_Branch::get_instance();
}
add_action('plugins_loaded', 'help_platform_branch_init'); 