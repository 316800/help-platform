<?php
/**
 * HELP Platform 用户角色管理类
 */
class Help_Platform_Roles {
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
        // 添加用户角色选择字段到注册表单
        add_action('register_form', array($this, 'add_role_field'));
        add_action('user_register', array($this, 'save_role_field'));
        
        // 添加用户角色管理页面
        add_action('admin_menu', array($this, 'add_role_management_page'));
        
        // 根据用户角色限制功能访问
        add_action('template_redirect', array($this, 'check_user_access'));
        
        // 添加用户角色切换功能（仅管理员可用）
        add_action('admin_init', array($this, 'handle_role_switch'));

        // 新增钩子
        add_action('admin_menu', array($this, 'add_role_statistics_page'));
        add_action('admin_init', array($this, 'handle_role_actions'));
        add_action('user_register', array($this, 'send_welcome_email'));
        add_action('set_user_role', array($this, 'handle_role_change'), 10, 3);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_dashboard_setup', array($this, 'add_dashboard_widgets'));
    }

    /**
     * 添加角色选择字段到注册表单
     */
    public function add_role_field() {
        ?>
        <p>
            <label for="help_user_role"><?php _e('注册为', 'help-platform'); ?></label>
            <select name="help_user_role" id="help_user_role" required>
                <option value=""><?php _e('请选择角色', 'help-platform'); ?></option>
                <option value="help_customer"><?php _e('客户', 'help-platform'); ?></option>
                <option value="help_worker"><?php _e('工人', 'help-platform'); ?></option>
            </select>
        </p>
        <?php
    }

    /**
     * 保存用户角色
     */
    public function save_role_field($user_id) {
        if (!empty($_POST['help_user_role'])) {
            $role = sanitize_text_field($_POST['help_user_role']);
            if (in_array($role, array('help_customer', 'help_worker'))) {
                $user = new WP_User($user_id);
                $user->set_role($role);
            }
        }
    }

    /**
     * 添加角色管理页面
     */
    public function add_role_management_page() {
        add_submenu_page(
            'help-platform',
            __('用户角色管理', 'help-platform'),
            __('用户角色管理', 'help-platform'),
            'help_manage_users',
            'help-role-management',
            array($this, 'render_role_management_page')
        );
    }

    /**
     * 渲染角色管理页面
     */
    public function render_role_management_page() {
        if (!current_user_can('help_manage_users')) {
            wp_die(__('您没有权限访问此页面。', 'help-platform'));
        }

        // 获取所有用户
        $users = get_users(array(
            'role__in' => array('help_customer', 'help_worker'),
            'orderby' => 'registered',
            'order' => 'DESC'
        ));

        ?>
        <div class="wrap">
            <h1><?php _e('用户角色管理', 'help-platform'); ?></h1>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('用户名', 'help-platform'); ?></th>
                        <th><?php _e('邮箱', 'help-platform'); ?></th>
                        <th><?php _e('当前角色', 'help-platform'); ?></th>
                        <th><?php _e('注册时间', 'help-platform'); ?></th>
                        <th><?php _e('操作', 'help-platform'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo esc_html($user->display_name); ?></td>
                            <td><?php echo esc_html($user->user_email); ?></td>
                            <td>
                                <?php
                                $roles = array_map(function($role) {
                                    switch ($role) {
                                        case 'help_customer':
                                            return __('客户', 'help-platform');
                                        case 'help_worker':
                                            return __('工人', 'help-platform');
                                        default:
                                            return $role;
                                    }
                                }, $user->roles);
                                echo esc_html(implode(', ', $roles));
                                ?>
                            </td>
                            <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($user->user_registered))); ?></td>
                            <td>
                                <form method="post" style="display:inline;">
                                    <?php wp_nonce_field('help_switch_role', 'help_role_nonce'); ?>
                                    <input type="hidden" name="user_id" value="<?php echo esc_attr($user->ID); ?>">
                                    <select name="new_role">
                                        <option value="help_customer" <?php selected(in_array('help_customer', $user->roles)); ?>>
                                            <?php _e('客户', 'help-platform'); ?>
                                        </option>
                                        <option value="help_worker" <?php selected(in_array('help_worker', $user->roles)); ?>>
                                            <?php _e('工人', 'help-platform'); ?>
                                        </option>
                                    </select>
                                    <input type="submit" name="switch_role" class="button" value="<?php esc_attr_e('更新角色', 'help-platform'); ?>">
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * 处理角色切换
     */
    public function handle_role_switch() {
        if (!current_user_can('help_manage_users')) {
            return;
        }

        if (isset($_POST['switch_role']) && isset($_POST['user_id']) && isset($_POST['new_role'])) {
            if (!wp_verify_nonce($_POST['help_role_nonce'], 'help_switch_role')) {
                wp_die(__('安全验证失败。', 'help-platform'));
            }

            $user_id = intval($_POST['user_id']);
            $new_role = sanitize_text_field($_POST['new_role']);

            if (in_array($new_role, array('help_customer', 'help_worker'))) {
                $user = new WP_User($user_id);
                $user->set_role($new_role);
                
                // 添加管理员通知
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-success is-dismissible"><p>' . 
                         __('用户角色已更新。', 'help-platform') . 
                         '</p></div>';
                });
            }
        }
    }

    /**
     * 检查用户访问权限
     */
    public function check_user_access() {
        // 获取当前页面
        $current_page = get_post_type();
        
        // 检查用户是否已登录
        if (!is_user_logged_in()) {
            // 未登录用户只能访问登录和注册页面
            if (!in_array($current_page, array('', 'wp-login.php', 'wp-register.php'))) {
                wp_redirect(wp_login_url(get_permalink()));
                exit;
            }
            return;
        }

        $user = wp_get_current_user();
        
        // 根据用户角色和页面类型检查权限
        switch ($current_page) {
            case 'help_job':
                // 检查任务发布权限
                if (is_singular('help_job')) {
                    // 查看任务详情
                    if (!in_array('help_customer', $user->roles) && 
                        !in_array('help_worker', $user->roles) && 
                        !in_array('help_manager', $user->roles) && 
                        !in_array('administrator', $user->roles)) {
                        wp_die(__('您没有权限查看任务详情。', 'help-platform'));
                    }
                } else {
                    // 发布任务
                    if (!current_user_can('help_post_job') && 
                        !in_array('help_manager', $user->roles) && 
                        !in_array('administrator', $user->roles)) {
                        wp_die(__('您没有权限发布任务。', 'help-platform'));
                    }
                }
                break;

            case 'help_verify':
                // 检查实名认证权限
                if (!current_user_can('help_verify') && 
                    !in_array('help_manager', $user->roles) && 
                    !in_array('administrator', $user->roles)) {
                    wp_die(__('您没有权限进行实名认证。', 'help-platform'));
                }
                break;
        }
    }

    /**
     * 添加角色统计页面
     */
    public function add_role_statistics_page() {
        add_submenu_page(
            'help-platform',
            __('角色统计', 'help-platform'),
            __('角色统计', 'help-platform'),
            'help_manage_users',
            'help-role-statistics',
            array($this, 'render_role_statistics_page')
        );
    }

    /**
     * 渲染角色统计页面
     */
    public function render_role_statistics_page() {
        if (!current_user_can('help_manage_users')) {
            wp_die(__('您没有权限访问此页面。', 'help-platform'));
        }

        // 获取统计数据
        $stats = $this->get_role_statistics();
        ?>
        <div class="wrap">
            <h1><?php _e('角色统计', 'help-platform'); ?></h1>

            <div class="help-role-stats-grid">
                <!-- 总用户数 -->
                <div class="help-stat-box">
                    <h3><?php _e('总用户数', 'help-platform'); ?></h3>
                    <div class="stat-number"><?php echo esc_html($stats['total_users']); ?></div>
                </div>

                <!-- 客户数量 -->
                <div class="help-stat-box">
                    <h3><?php _e('客户数量', 'help-platform'); ?></h3>
                    <div class="stat-number"><?php echo esc_html($stats['customer_count']); ?></div>
                    <div class="stat-trend <?php echo $stats['customer_trend'] >= 0 ? 'positive' : 'negative'; ?>">
                        <?php echo $stats['customer_trend']; ?>%
                    </div>
                </div>

                <!-- 工人数量 -->
                <div class="help-stat-box">
                    <h3><?php _e('工人数量', 'help-platform'); ?></h3>
                    <div class="stat-number"><?php echo esc_html($stats['worker_count']); ?></div>
                    <div class="stat-trend <?php echo $stats['worker_trend'] >= 0 ? 'positive' : 'negative'; ?>">
                        <?php echo $stats['worker_trend']; ?>%
                    </div>
                </div>

                <!-- 已认证用户 -->
                <div class="help-stat-box">
                    <h3><?php _e('已认证用户', 'help-platform'); ?></h3>
                    <div class="stat-number"><?php echo esc_html($stats['verified_count']); ?></div>
                    <div class="stat-percentage">
                        <?php echo round(($stats['verified_count'] / $stats['total_users']) * 100); ?>%
                    </div>
                </div>
            </div>

            <!-- 用户增长图表 -->
            <div class="help-role-chart-container">
                <h2><?php _e('用户增长趋势', 'help-platform'); ?></h2>
                <canvas id="helpRoleGrowthChart"></canvas>
            </div>

            <!-- 角色分布图表 -->
            <div class="help-role-chart-container">
                <h2><?php _e('角色分布', 'help-platform'); ?></h2>
                <canvas id="helpRoleDistributionChart"></canvas>
            </div>

            <!-- 活跃度统计 -->
            <div class="help-activity-stats">
                <h2><?php _e('用户活跃度', 'help-platform'); ?></h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('角色', 'help-platform'); ?></th>
                            <th><?php _e('平均登录次数/月', 'help-platform'); ?></th>
                            <th><?php _e('平均在线时长', 'help-platform'); ?></th>
                            <th><?php _e('任务完成率', 'help-platform'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><?php _e('客户', 'help-platform'); ?></td>
                            <td><?php echo esc_html($stats['customer_activity']['logins']); ?></td>
                            <td><?php echo esc_html($stats['customer_activity']['online_time']); ?></td>
                            <td><?php echo esc_html($stats['customer_activity']['task_completion']); ?>%</td>
                        </tr>
                        <tr>
                            <td><?php _e('工人', 'help-platform'); ?></td>
                            <td><?php echo esc_html($stats['worker_activity']['logins']); ?></td>
                            <td><?php echo esc_html($stats['worker_activity']['online_time']); ?></td>
                            <td><?php echo esc_html($stats['worker_activity']['task_completion']); ?>%</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }

    /**
     * 获取角色统计数据
     */
    private function get_role_statistics() {
        global $wpdb;

        // 基础统计数据
        $stats = array(
            'total_users' => count_users()['total_users'],
            'customer_count' => count_users()['avail_roles']['help_customer'] ?? 0,
            'worker_count' => count_users()['avail_roles']['help_worker'] ?? 0,
            'verified_count' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE meta_key = 'help_verified' AND meta_value = '1'"),
        );

        // 计算趋势（与上月相比）
        $last_month = date('Y-m', strtotime('-1 month'));
        $this_month = date('Y-m');
        
        $stats['customer_trend'] = $this->calculate_growth_trend('help_customer', $last_month, $this_month);
        $stats['worker_trend'] = $this->calculate_growth_trend('help_worker', $last_month, $this_month);

        // 获取活跃度数据
        $stats['customer_activity'] = $this->get_role_activity('help_customer');
        $stats['worker_activity'] = $this->get_role_activity('help_worker');

        return $stats;
    }

    /**
     * 计算用户增长趋势
     */
    private function calculate_growth_trend($role, $last_month, $this_month) {
        global $wpdb;
        
        $last_month_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->users} u 
            JOIN {$wpdb->usermeta} um ON u.ID = um.user_id 
            WHERE um.meta_key = '{$wpdb->prefix}capabilities' 
            AND um.meta_value LIKE %s 
            AND DATE_FORMAT(u.user_registered, '%%Y-%%m') = %s",
            '%' . $role . '%',
            $last_month
        ));

        $this_month_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->users} u 
            JOIN {$wpdb->usermeta} um ON u.ID = um.user_id 
            WHERE um.meta_key = '{$wpdb->prefix}capabilities' 
            AND um.meta_value LIKE %s 
            AND DATE_FORMAT(u.user_registered, '%%Y-%%m') = %s",
            '%' . $role . '%',
            $this_month
        ));

        if ($last_month_count == 0) {
            return $this_month_count > 0 ? 100 : 0;
        }

        return round((($this_month_count - $last_month_count) / $last_month_count) * 100);
    }

    /**
     * 获取角色活跃度数据
     */
    private function get_role_activity($role) {
        global $wpdb;

        // 获取最近30天的数据
        $thirty_days_ago = date('Y-m-d', strtotime('-30 days'));

        // 平均登录次数
        $logins = $wpdb->get_var($wpdb->prepare(
            "SELECT AVG(login_count) FROM (
                SELECT user_id, COUNT(*) as login_count 
                FROM {$wpdb->usermeta} 
                WHERE meta_key = 'help_last_login' 
                AND meta_value >= %s 
                AND user_id IN (
                    SELECT user_id FROM {$wpdb->usermeta} 
                    WHERE meta_key = '{$wpdb->prefix}capabilities' 
                    AND meta_value LIKE %s
                )
                GROUP BY user_id
            ) as login_stats",
            $thirty_days_ago,
            '%' . $role . '%'
        ));

        // 平均在线时长（分钟）
        $online_time = $wpdb->get_var($wpdb->prepare(
            "SELECT AVG(online_time) FROM (
                SELECT user_id, SUM(meta_value) as online_time 
                FROM {$wpdb->usermeta} 
                WHERE meta_key = 'help_online_time' 
                AND user_id IN (
                    SELECT user_id FROM {$wpdb->usermeta} 
                    WHERE meta_key = '{$wpdb->prefix}capabilities' 
                    AND meta_value LIKE %s
                )
                GROUP BY user_id
            ) as time_stats",
            '%' . $role . '%'
        ));

        // 任务完成率
        $task_completion = $wpdb->get_var($wpdb->prepare(
            "SELECT AVG(completion_rate) FROM (
                SELECT user_id, 
                    (COUNT(CASE WHEN status = 'completed' THEN 1 END) * 100.0 / COUNT(*)) as completion_rate 
                FROM {$wpdb->prefix}help_jobs 
                WHERE user_id IN (
                    SELECT user_id FROM {$wpdb->usermeta} 
                    WHERE meta_key = '{$wpdb->prefix}capabilities' 
                    AND meta_value LIKE %s
                )
                GROUP BY user_id
            ) as completion_stats",
            '%' . $role . '%'
        ));

        return array(
            'logins' => round($logins ?? 0, 1),
            'online_time' => round(($online_time ?? 0) / 60, 1) . ' 小时',
            'task_completion' => round($task_completion ?? 0, 1)
        );
    }

    /**
     * 发送欢迎邮件
     */
    public function send_welcome_email($user_id) {
        $user = get_userdata($user_id);
        if (!$user) {
            return;
        }

        $role = reset($user->roles);
        $template = '';

        switch ($role) {
            case 'help_customer':
                $template = 'welcome-customer';
                break;
            case 'help_worker':
                $template = 'welcome-worker';
                break;
            default:
                return;
        }

        $subject = sprintf(
            __('欢迎加入 HELP 平台 - %s', 'help-platform'),
            $role === 'help_customer' ? __('客户', 'help-platform') : __('工人', 'help-platform')
        );

        ob_start();
        include HELP_PLATFORM_PLUGIN_DIR . "templates/emails/{$template}.php";
        $message = ob_get_clean();

        Help_Platform::send_notification($user->user_email, $subject, $message);
    }

    /**
     * 处理角色变更
     */
    public function handle_role_change($user_id, $role, $old_roles) {
        $user = get_userdata($user_id);
        if (!$user) {
            return;
        }

        // 发送角色变更通知
        $subject = __('您的账户角色已更新', 'help-platform');
        
        ob_start();
        include HELP_PLATFORM_PLUGIN_DIR . 'templates/emails/role-changed.php';
        $message = ob_get_clean();

        Help_Platform::send_notification($user->user_email, $subject, $message);

        // 记录角色变更日志
        $this->log_role_change($user_id, $role, $old_roles);
    }

    /**
     * 记录角色变更日志
     */
    private function log_role_change($user_id, $new_role, $old_roles) {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'help_role_logs',
            array(
                'user_id' => $user_id,
                'old_roles' => implode(',', $old_roles),
                'new_role' => $new_role,
                'changed_by' => get_current_user_id(),
                'changed_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%d', '%s')
        );
    }

    /**
     * 添加仪表盘小工具
     */
    public function add_dashboard_widgets() {
        if (current_user_can('help_manage_users')) {
            wp_add_dashboard_widget(
                'help_role_stats_widget',
                __('HELP 平台用户统计', 'help-platform'),
                array($this, 'render_dashboard_widget')
            );
        }
    }

    /**
     * 渲染仪表盘小工具
     */
    public function render_dashboard_widget() {
        $stats = $this->get_role_statistics();
        ?>
        <div class="help-dashboard-stats">
            <div class="stat-item">
                <span class="stat-label"><?php _e('总用户数', 'help-platform'); ?></span>
                <span class="stat-value"><?php echo esc_html($stats['total_users']); ?></span>
            </div>
            <div class="stat-item">
                <span class="stat-label"><?php _e('客户数量', 'help-platform'); ?></span>
                <span class="stat-value"><?php echo esc_html($stats['customer_count']); ?></span>
            </div>
            <div class="stat-item">
                <span class="stat-label"><?php _e('工人数量', 'help-platform'); ?></span>
                <span class="stat-value"><?php echo esc_html($stats['worker_count']); ?></span>
            </div>
            <div class="stat-item">
                <span class="stat-label"><?php _e('已认证用户', 'help-platform'); ?></span>
                <span class="stat-value"><?php echo esc_html($stats['verified_count']); ?></span>
            </div>
        </div>
        <?php
    }

    /**
     * 加载管理界面脚本
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'help-role') === false) {
            return;
        }

        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '3.7.0', true);
        wp_enqueue_style(
            'help-platform-admin',
            HELP_PLATFORM_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            HELP_PLATFORM_VERSION
        );

        // 添加图表数据
        $stats = $this->get_role_statistics();
        wp_localize_script('chart-js', 'helpRoleStats', array(
            'growthData' => $this->get_growth_chart_data(),
            'distributionData' => array(
                'customers' => $stats['customer_count'],
                'workers' => $stats['worker_count']
            )
        ));
    }

    /**
     * 获取增长图表数据
     */
    private function get_growth_chart_data() {
        global $wpdb;
        
        $data = array();
        $months = 6; // 显示最近6个月的数据

        for ($i = $months - 1; $i >= 0; $i--) {
            $month = date('Y-m', strtotime("-{$i} months"));
            
            $data['labels'][] = date_i18n('Y年m月', strtotime($month));
            
            // 获取客户数量
            $data['customers'][] = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->users} u 
                JOIN {$wpdb->usermeta} um ON u.ID = um.user_id 
                WHERE um.meta_key = '{$wpdb->prefix}capabilities' 
                AND um.meta_value LIKE %s 
                AND DATE_FORMAT(u.user_registered, '%%Y-%%m') <= %s",
                '%help_customer%',
                $month
            ));

            // 获取工人数量
            $data['workers'][] = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->users} u 
                JOIN {$wpdb->usermeta} um ON u.ID = um.user_id 
                WHERE um.meta_key = '{$wpdb->prefix}capabilities' 
                AND um.meta_value LIKE %s 
                AND DATE_FORMAT(u.user_registered, '%%Y-%%m') <= %s",
                '%help_worker%',
                $month
            ));
        }

        return $data;
    }
}

// 初始化
Help_Platform_Roles::get_instance(); 