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
        add_action('save_post_help_branch', array($this, 'save_branch_meta'));
        
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
        register_post_type('help_branch', array(
            'labels' => array(
                'name' => __('分公司管理', 'help-platform'),
                'singular_name' => __('分公司', 'help-platform'),
                'menu_name' => __('分公司管理', 'help-platform'),
                'all_items' => __('所有分公司', 'help-platform'),
                'add_new' => __('添加分公司', 'help-platform'),
                'add_new_item' => __('添加新分公司', 'help-platform'),
                'edit_item' => __('编辑分公司', 'help-platform'),
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'help-platform',
            'supports' => array('title', 'editor'),
            'capability_type' => 'post',
            'map_meta_cap' => true,
            'hierarchical' => false,
            'rewrite' => false,
            'query_var' => false,
        ));
    }

    /**
     * 添加分公司管理菜单
     */
    public function add_branch_menu() {
        add_submenu_page(
            'help-platform',
            __('分公司管理', 'help-platform'),
            __('分公司管理', 'help-platform'),
            'help_manage_branch',
            'edit.php?post_type=help_branch'
        );
    }

    /**
     * 添加分公司元数据框
     */
    public function add_branch_meta_boxes() {
        add_meta_box(
            'help_branch_details',
            __('分公司详情', 'help-platform'),
            array($this, 'render_branch_meta_box'),
            'help_branch',
            'normal',
            'high'
        );
    }

    /**
     * 渲染分公司元数据框
     */
    public function render_branch_meta_box($post) {
        wp_nonce_field('help_branch_meta_box', 'help_branch_meta_box_nonce');
        
        $branch_data = get_post_meta($post->ID, '_help_branch_data', true);
        $branch_data = wp_parse_args($branch_data, array(
            'country' => '',
            'currency' => '',
            'timezone' => '',
            'address' => '',
            'phone' => '',
            'email' => '',
            'manager_id' => '',
            'commission_rate' => '',
            'platform_fee' => '',
            'min_withdraw' => '',
            'withdraw_fee' => '',
        ));
        
        include HELP_PLATFORM_PLUGIN_DIR . 'templates/admin-branch-meta.php';
    }

    /**
     * 保存分公司元数据
     */
    public function save_branch_meta($post_id) {
        if (!isset($_POST['help_branch_meta_box_nonce']) || 
            !wp_verify_nonce($_POST['help_branch_meta_box_nonce'], 'help_branch_meta_box')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $branch_data = array(
            'country' => sanitize_text_field($_POST['branch_country']),
            'currency' => sanitize_text_field($_POST['branch_currency']),
            'timezone' => sanitize_text_field($_POST['branch_timezone']),
            'address' => sanitize_textarea_field($_POST['branch_address']),
            'phone' => sanitize_text_field($_POST['branch_phone']),
            'email' => sanitize_email($_POST['branch_email']),
            'manager_id' => intval($_POST['branch_manager_id']),
            'commission_rate' => floatval($_POST['branch_commission_rate']),
            'platform_fee' => floatval($_POST['branch_platform_fee']),
            'min_withdraw' => floatval($_POST['branch_min_withdraw']),
            'withdraw_fee' => floatval($_POST['branch_withdraw_fee']),
        );

        update_post_meta($post_id, '_help_branch_data', $branch_data);
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
}

// 初始化
function help_platform_branch_init() {
    return Help_Platform_Branch::get_instance();
}
add_action('plugins_loaded', 'help_platform_branch_init'); 