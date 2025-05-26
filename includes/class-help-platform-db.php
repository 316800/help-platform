<?php
/**
 * HELP Platform 数据库管理类
 */
class Help_Platform_DB {
    /**
     * 数据库版本
     */
    private $db_version = '1.0';

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
        // 插件激活时创建/更新数据库表
        register_activation_hook(HELP_PLATFORM_PLUGIN_FILE, array($this, 'activate'));
        
        // 检查数据库版本
        add_action('plugins_loaded', array($this, 'check_db_version'));
    }

    /**
     * 插件激活时执行
     */
    public function activate() {
        $this->create_tables();
        update_option('help_platform_db_version', $this->db_version);
    }

    /**
     * 检查数据库版本
     */
    public function check_db_version() {
        $current_version = get_option('help_platform_db_version', '0');
        if (version_compare($current_version, $this->db_version, '<')) {
            $this->create_tables();
            update_option('help_platform_db_version', $this->db_version);
        }
    }

    /**
     * 创建数据库表
     */
    public function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // 佣金表
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}help_commission (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            branch_id bigint(20) NOT NULL,
            amount decimal(10,2) NOT NULL DEFAULT '0.00',
            platform_commission decimal(10,2) NOT NULL DEFAULT '0.00',
            branch_commission decimal(10,2) NOT NULL DEFAULT '0.00',
            worker_commission decimal(10,2) NOT NULL DEFAULT '0.00',
            tax_amount decimal(10,2) NOT NULL DEFAULT '0.00',
            status varchar(20) NOT NULL DEFAULT 'pending',
            notes text,
            date datetime NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY branch_id (branch_id),
            KEY status (status),
            KEY date (date)
        ) $charset_collate;";

        // 充值表
        $sql .= "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}help_recharges (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            amount decimal(10,2) NOT NULL DEFAULT '0.00',
            payment_method varchar(50) NOT NULL,
            transaction_id varchar(100),
            status varchar(20) NOT NULL DEFAULT 'pending',
            notes text,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY status (status),
            KEY transaction_id (transaction_id)
        ) $charset_collate;";

        // 提现表
        $sql .= "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}help_withdrawals (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            amount decimal(10,2) NOT NULL DEFAULT '0.00',
            fee decimal(10,2) NOT NULL DEFAULT '0.00',
            bank_name varchar(100),
            bank_account varchar(100),
            bank_holder varchar(100),
            status varchar(20) NOT NULL DEFAULT 'pending',
            notes text,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY status (status)
        ) $charset_collate;";

        // 任务表
        $sql .= "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}help_jobs (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            branch_id bigint(20) NOT NULL,
            title varchar(255) NOT NULL,
            description text,
            budget decimal(10,2) NOT NULL DEFAULT '0.00',
            location varchar(255),
            deadline datetime,
            status varchar(20) NOT NULL DEFAULT 'pending',
            worker_id bigint(20),
            rating decimal(2,1),
            review text,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY branch_id (branch_id),
            KEY worker_id (worker_id),
            KEY status (status),
            KEY deadline (deadline)
        ) $charset_collate;";

        // 申请表
        $sql .= "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}help_applications (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            job_id bigint(20) NOT NULL,
            worker_id bigint(20) NOT NULL,
            proposal text,
            price decimal(10,2) NOT NULL DEFAULT '0.00',
            status varchar(20) NOT NULL DEFAULT 'pending',
            notes text,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY job_id (job_id),
            KEY worker_id (worker_id),
            KEY status (status)
        ) $charset_collate;";

        // 通信表
        $sql .= "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}help_messages (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            job_id bigint(20) NOT NULL,
            sender_id bigint(20) NOT NULL,
            receiver_id bigint(20) NOT NULL,
            message_type varchar(20) NOT NULL DEFAULT 'text',
            content text NOT NULL,
            attachment_url varchar(255),
            is_read tinyint(1) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY job_id (job_id),
            KEY sender_id (sender_id),
            KEY receiver_id (receiver_id),
            KEY created_at (created_at)
        ) $charset_collate;";

        // 位置追踪表
        $sql .= "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}help_locations (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            worker_id bigint(20) NOT NULL,
            job_id bigint(20) NOT NULL,
            latitude decimal(10,8) NOT NULL,
            longitude decimal(11,8) NOT NULL,
            accuracy decimal(10,2),
            speed decimal(10,2),
            heading decimal(10,2),
            status varchar(20) NOT NULL DEFAULT 'active',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY worker_id (worker_id),
            KEY job_id (job_id),
            KEY created_at (created_at)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * 删除数据库表
     */
    public function drop_tables() {
        global $wpdb;
        
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}help_commission");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}help_recharges");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}help_withdrawals");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}help_jobs");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}help_applications");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}help_messages");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}help_locations");
        delete_option('help_platform_db_version');
    }
}

// 初始化
function help_platform_db_init() {
    return Help_Platform_DB::get_instance();
}
add_action('plugins_loaded', 'help_platform_db_init');

/**
 * 数据库迁移函数，在插件升级时执行，更新表结构、迁移选项等，保证数据不丢失。
 * 示例：如果数据库版本低于"1.0.1"，则执行迁移（例如，更新表字段、迁移选项等）。
 */
function help_platform_db_migrate() {
    global $wpdb;
    $db_version = get_option( 'help_platform_db_version', '1.0.0' );
    if ( version_compare( $db_version, '1.0.1', '<' ) ) {
        // 示例：如果数据库版本低于"1.0.1"，则执行迁移（例如，更新表字段、迁移选项等）
        // 例如，更新表字段（假设表名为 wp_help_platform_branch ）：
        $wpdb->query( "ALTER TABLE {$wpdb->prefix}help_platform_branch ADD COLUMN new_field VARCHAR( 255 ) DEFAULT '' AFTER old_field;" );
        // 或者迁移选项（例如，将旧选项"old_option"迁移到"new_option"）：
        $old_option = get_option( 'old_option', '' );
        if ( !empty( $old_option ) ) {
            update_option( 'new_option', $old_option );
            delete_option( 'old_option' );
        }
        // 其他迁移逻辑（例如，更新表结构、迁移数据等）...
    }
} 