<?php
/**
 * HELP Platform 工人批量导入类
 */
class Help_Platform_Worker_Import {
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
        add_action('wp_ajax_help_platform_download_worker_template', array($this, 'download_template'));
    }

    /**
     * 下载导入模板
     */
    public function download_template() {
        check_ajax_referer('help_platform_download_template', 'nonce');

        if (!current_user_can('manage_help_platform_users')) {
            wp_die(__('您没有权限执行此操作。', 'help-platform'));
        }

        require_once HELP_PLATFORM_PLUGIN_DIR . 'vendor/autoload.php';

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // 设置表头
        $headers = array(
            'A1' => __('姓名 *', 'help-platform'),
            'B1' => __('手机号 *', 'help-platform'),
            'C1' => __('邮箱 *', 'help-platform'),
            'D1' => __('专业 *', 'help-platform'),
            'E1' => __('工作经验(年) *', 'help-platform'),
            'F1' => __('技能证书', 'help-platform'),
            'G1' => __('擅长领域', 'help-platform'),
            'H1' => __('自我介绍', 'help-platform'),
            'I1' => __('所属分公司', 'help-platform')
        );

        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }

        // 设置列宽
        $sheet->getColumnDimension('A')->setWidth(15);
        $sheet->getColumnDimension('B')->setWidth(15);
        $sheet->getColumnDimension('C')->setWidth(25);
        $sheet->getColumnDimension('D')->setWidth(20);
        $sheet->getColumnDimension('E')->setWidth(15);
        $sheet->getColumnDimension('F')->setWidth(20);
        $sheet->getColumnDimension('G')->setWidth(30);
        $sheet->getColumnDimension('H')->setWidth(40);
        $sheet->getColumnDimension('I')->setWidth(20);

        // 设置表头样式
        $headerStyle = array(
            'font' => array(
                'bold' => true,
                'color' => array('rgb' => 'FFFFFF')
            ),
            'fill' => array(
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => array('rgb' => '0073AA')
            ),
            'alignment' => array(
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
            )
        );
        $sheet->getStyle('A1:I1')->applyFromArray($headerStyle);

        // 添加分公司下拉列表
        $branches = get_posts(array(
            'post_type' => 'help_branch',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ));

        $branchNames = array();
        foreach ($branches as $branch) {
            $branchNames[] = $branch->post_title;
        }

        $validation = $sheet->getCell('I2')->getDataValidation();
        $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
        $validation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
        $validation->setAllowBlank(false);
        $validation->setShowInputMessage(true);
        $validation->setShowErrorMessage(true);
        $validation->setShowDropDown(true);
        $validation->setFormula1('"' . implode(',', $branchNames) . '"');

        // 复制验证到整列
        $sheet->setDataValidation('I2:I1000', $validation);

        // 添加示例数据
        $sheet->setCellValue('A2', '张三');
        $sheet->setCellValue('B2', '13800138000');
        $sheet->setCellValue('C2', 'zhangsan@example.com');
        $sheet->setCellValue('D2', '水电工');
        $sheet->setCellValue('E2', '5');
        $sheet->setCellValue('F2', '电工证,水工证');
        $sheet->setCellValue('G2', '水电安装,管道维修');
        $sheet->setCellValue('H2', '有5年水电工作经验，擅长各类水电安装和维修工作。');
        $sheet->setCellValue('I2', $branchNames[0]);

        // 设置示例行样式
        $exampleStyle = array(
            'font' => array(
                'italic' => true,
                'color' => array('rgb' => '666666')
            )
        );
        $sheet->getStyle('A2:I2')->applyFromArray($exampleStyle);

        // 输出文件
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="worker-import-template.xlsx"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * 导入工人数据
     */
    public function import_workers($file) {
        if (!current_user_can('manage_help_platform_users')) {
            return array(
                'success' => false,
                'message' => __('您没有权限执行此操作。', 'help-platform')
            );
        }

        require_once HELP_PLATFORM_PLUGIN_DIR . 'vendor/autoload.php';

        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file['tmp_name']);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            // 移除表头
            array_shift($rows);

            $results = array(
                'success' => true,
                'message' => '',
                'details' => array(),
                'total' => count($rows),
                'imported' => 0,
                'failed' => 0
            );

            foreach ($rows as $index => $row) {
                if (empty($row[0])) continue; // 跳过空行

                $result = $this->process_worker_row($row, $index + 2);
                if ($result['success']) {
                    $results['imported']++;
                } else {
                    $results['failed']++;
                    $results['details'][] = sprintf(
                        __('第 %d 行: %s', 'help-platform'),
                        $index + 2,
                        $result['message']
                    );
                }
            }

            $results['message'] = sprintf(
                __('导入完成。成功：%d，失败：%d', 'help-platform'),
                $results['imported'],
                $results['failed']
            );

            return $results;

        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => __('文件处理失败：', 'help-platform') . $e->getMessage()
            );
        }
    }

    /**
     * 处理单行工人数据
     */
    private function process_worker_row($row, $line_number) {
        // 验证必填字段
        if (empty($row[0]) || empty($row[1]) || empty($row[2]) || 
            empty($row[3]) || empty($row[4])) {
            return array(
                'success' => false,
                'message' => __('必填字段不能为空', 'help-platform')
            );
        }

        // 验证手机号
        if (!preg_match('/^1[3-9]\d{9}$/', $row[1])) {
            return array(
                'success' => false,
                'message' => __('手机号格式不正确', 'help-platform')
            );
        }

        // 验证邮箱
        if (!is_email($row[2])) {
            return array(
                'success' => false,
                'message' => __('邮箱格式不正确', 'help-platform')
            );
        }

        // 验证工作经验
        if (!is_numeric($row[4]) || $row[4] < 0) {
            return array(
                'success' => false,
                'message' => __('工作经验必须是大于等于0的数字', 'help-platform')
            );
        }

        // 检查手机号是否已存在
        $existing_user = get_user_by('login', $row[1]);
        if ($existing_user) {
            return array(
                'success' => false,
                'message' => __('手机号已被注册', 'help-platform')
            );
        }

        // 检查邮箱是否已存在
        $existing_user = get_user_by('email', $row[2]);
        if ($existing_user) {
            return array(
                'success' => false,
                'message' => __('邮箱已被注册', 'help-platform')
            );
        }

        // 获取分公司ID
        $branch_id = 0;
        if (!empty($row[8])) {
            $branch = get_page_by_title($row[8], OBJECT, 'help_branch');
            if ($branch) {
                $branch_id = $branch->ID;
            }
        }

        // 生成随机密码
        $password = wp_generate_password(12, true);

        // 创建用户
        $user_id = wp_create_user($row[1], $password, $row[2]);
        if (is_wp_error($user_id)) {
            return array(
                'success' => false,
                'message' => $user_id->get_error_message()
            );
        }

        // 设置用户角色
        $user = new WP_User($user_id);
        $user->set_role('help_worker');

        // 更新用户信息
        wp_update_user(array(
            'ID' => $user_id,
            'display_name' => $row[0],
            'first_name' => $row[0]
        ));

        // 保存工人信息
        update_user_meta($user_id, 'help_worker_data', array(
            'real_name' => $row[0],
            'phone' => $row[1],
            'profession' => $row[3],
            'experience' => floatval($row[4]),
            'certificates' => !empty($row[5]) ? explode(',', $row[5]) : array(),
            'skills' => !empty($row[6]) ? explode(',', $row[6]) : array(),
            'bio' => !empty($row[7]) ? $row[7] : '',
            'branch_id' => $branch_id
        ));

        // 如果需要自动通过实名认证
        if (isset($_POST['auto_verify']) && $_POST['auto_verify']) {
            update_user_meta($user_id, 'help_verify_status', 'verified');
            update_user_meta($user_id, 'help_verify_time', current_time('mysql'));
        }

        // 如果需要发送通知邮件
        if (isset($_POST['send_notification']) && $_POST['send_notification']) {
            $this->send_welcome_email($user_id, $password);
        }

        return array('success' => true);
    }

    /**
     * 发送欢迎邮件
     */
    private function send_welcome_email($user_id, $password) {
        $user = get_userdata($user_id);
        $site_name = get_bloginfo('name');
        $login_url = wp_login_url();

        $message = sprintf(
            __('您好 %s，<br><br>欢迎加入 %s！<br><br>您的账号信息如下：<br>登录名：%s<br>密码：%s<br><br>请登录后及时修改密码：<br>%s<br><br>如有任何问题，请联系客服。', 'help-platform'),
            $user->display_name,
            $site_name,
            $user->user_login,
            $password,
            $login_url
        );

        $headers = array('Content-Type: text/html; charset=UTF-8');
        wp_mail($user->user_email, sprintf(__('欢迎加入 %s', 'help-platform'), $site_name), $message, $headers);
    }
}

// 初始化
function help_platform_worker_import_init() {
    return Help_Platform_Worker_Import::get_instance();
}
add_action('plugins_loaded', 'help_platform_worker_import_init'); 