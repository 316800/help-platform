<?php
/**
 * HELP Platform AI 功能类
 */
class Help_Platform_AI {
    /**
     * 单例实例
     */
    private static $instance = null;

    /**
     * OpenAI API 密钥
     */
    private $api_key;

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
        $this->api_key = get_option('help_platform_openai_api_key', '');
        $this->init_hooks();
    }

    /**
     * 初始化钩子
     */
    private function init_hooks() {
        // 添加设置字段
        add_action('admin_init', array($this, 'register_settings'));
        
        // 任务审核前检查
        add_action('pre_post_update', array($this, 'check_job_content'), 10, 2);
        
        // 添加任务优化建议
        add_action('add_meta_boxes', array($this, 'add_ai_suggestions_meta_box'));
    }

    /**
     * 注册设置
     */
    public function register_settings() {
        register_setting('help_platform_settings', 'help_platform_openai_api_key');
        
        add_settings_section(
            'help_platform_ai_settings',
            __('AI 功能设置', 'help-platform'),
            array($this, 'render_ai_settings_section'),
            'help-platform'
        );

        add_settings_field(
            'help_platform_openai_api_key',
            __('OpenAI API Key', 'help-platform'),
            array($this, 'render_api_key_field'),
            'help-platform',
            'help_platform_ai_settings'
        );
    }

    /**
     * 渲染 AI 设置区域
     */
    public function render_ai_settings_section() {
        echo '<p>' . __('配置 OpenAI API 以启用智能审核和优化建议功能。', 'help-platform') . '</p>';
    }

    /**
     * 渲染 API Key 输入字段
     */
    public function render_api_key_field() {
        $api_key = get_option('help_platform_openai_api_key', '');
        echo '<input type="password" name="help_platform_openai_api_key" value="' . esc_attr($api_key) . '" class="regular-text">';
        echo '<p class="description">' . __('用于访问 OpenAI API 的密钥。', 'help-platform') . '</p>';
    }

    /**
     * 检查任务内容
     */
    public function check_job_content($post_id, $post) {
        if ($post->post_type !== 'help_job' || !$this->api_key) {
            return;
        }

        // 只检查新发布或更新的任务
        if ($post->post_status !== 'pending') {
            return;
        }

        $content = $post->post_title . "\n" . $post->post_content;
        $result = $this->analyze_content($content);

        if ($result) {
            update_post_meta($post_id, '_ai_analysis', $result);
            
            // 如果 AI 建议拒绝，自动设置为私有状态
            if (isset($result['suggested_action']) && $result['suggested_action'] === 'reject') {
                $post->post_status = 'private';
                wp_update_post($post);
                
                // 发送拒绝通知
                $user = get_userdata($post->post_author);
                if ($user) {
                    $subject = __('您的任务未通过 AI 审核', 'help-platform');
                    $message = $this->get_ai_rejection_email($post, $result);
                    Help_Platform::send_notification($user->user_email, $subject, $message);
                }
            }
        }
    }

    /**
     * 添加 AI 建议元数据框
     */
    public function add_ai_suggestions_meta_box() {
        add_meta_box(
            'help_job_ai_suggestions',
            __('AI 优化建议', 'help-platform'),
            array($this, 'render_ai_suggestions_meta_box'),
            'help_job',
            'side',
            'default'
        );
    }

    /**
     * 渲染 AI 建议元数据框
     */
    public function render_ai_suggestions_meta_box($post) {
        $analysis = get_post_meta($post->ID, '_ai_analysis', true);
        
        if (!$analysis) {
            echo '<p>' . __('暂无 AI 分析结果。', 'help-platform') . '</p>';
            return;
        }

        echo '<div class="ai-suggestions">';
        
        if (!empty($analysis['suggestions'])) {
            echo '<h4>' . __('优化建议：', 'help-platform') . '</h4>';
            echo '<ul>';
            foreach ($analysis['suggestions'] as $suggestion) {
                echo '<li>' . esc_html($suggestion) . '</li>';
            }
            echo '</ul>';
        }

        if (!empty($analysis['keywords'])) {
            echo '<h4>' . __('建议关键词：', 'help-platform') . '</h4>';
            echo '<p>' . esc_html(implode(', ', $analysis['keywords'])) . '</p>';
        }

        if (!empty($analysis['content_score'])) {
            echo '<h4>' . __('内容评分：', 'help-platform') . '</h4>';
            echo '<p>' . sprintf(__('完整性：%d%%', 'help-platform'), $analysis['content_score']['completeness']) . '</p>';
            echo '<p>' . sprintf(__('清晰度：%d%%', 'help-platform'), $analysis['content_score']['clarity']) . '</p>';
        }

        echo '</div>';
    }

    /**
     * 分析内容
     */
    private function analyze_content($content) {
        if (!$this->api_key) {
            return false;
        }

        $prompt = sprintf(
            "请分析以下任务描述，并提供审核建议：\n\n%s\n\n请从以下几个方面进行分析：\n1. 内容是否完整、清晰\n2. 是否存在违规内容\n3. 如何优化描述\n4. 建议的关键词\n5. 是否需要拒绝（如果内容明显违规）",
            $content
        );

        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode(array(
                'model' => 'gpt-3.5-turbo',
                'messages' => array(
                    array('role' => 'system', 'content' => '你是一个专业的任务审核助手，负责分析任务内容的合规性和质量。'),
                    array('role' => 'user', 'content' => $prompt)
                ),
                'temperature' => 0.7,
            )),
            'timeout' => 30,
        ));

        if (is_wp_error($response)) {
            error_log('OpenAI API Error: ' . $response->get_error_message());
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (empty($body['choices'][0]['message']['content'])) {
            return false;
        }

        // 解析 AI 返回的内容
        $content = $body['choices'][0]['message']['content'];
        return $this->parse_ai_response($content);
    }

    /**
     * 解析 AI 响应
     */
    private function parse_ai_response($content) {
        $result = array(
            'suggestions' => array(),
            'keywords' => array(),
            'content_score' => array(
                'completeness' => 0,
                'clarity' => 0
            ),
            'suggested_action' => 'approve'
        );

        // 使用正则表达式提取建议和关键词
        if (preg_match('/优化建议：\s*(.*?)(?=\n\n|$)/s', $content, $matches)) {
            $suggestions = explode("\n", trim($matches[1]));
            $result['suggestions'] = array_filter(array_map('trim', $suggestions));
        }

        if (preg_match('/建议关键词：\s*(.*?)(?=\n\n|$)/s', $content, $matches)) {
            $keywords = explode('、', trim($matches[1]));
            $result['keywords'] = array_filter(array_map('trim', $keywords));
        }

        // 检查是否需要拒绝
        if (stripos($content, '需要拒绝') !== false || stripos($content, '建议拒绝') !== false) {
            $result['suggested_action'] = 'reject';
        }

        // 提取评分
        if (preg_match('/完整性：(\d+)%/', $content, $matches)) {
            $result['content_score']['completeness'] = intval($matches[1]);
        }
        if (preg_match('/清晰度：(\d+)%/', $content, $matches)) {
            $result['content_score']['clarity'] = intval($matches[1]);
        }

        return $result;
    }

    /**
     * 获取 AI 拒绝邮件内容
     */
    private function get_ai_rejection_email($post, $analysis) {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title><?php _e('任务未通过 AI 审核', 'help-platform'); ?></title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    line-height: 1.6;
                    color: #333;
                    margin: 0;
                    padding: 20px;
                }
                .container {
                    max-width: 600px;
                    margin: 0 auto;
                    background: #fff;
                    padding: 20px;
                    border-radius: 4px;
                    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                }
                .header {
                    border-bottom: 1px solid #eee;
                    padding-bottom: 20px;
                    margin-bottom: 20px;
                    text-align: center;
                }
                .content {
                    margin-bottom: 20px;
                }
                .footer {
                    border-top: 1px solid #eee;
                    padding-top: 20px;
                    font-size: 12px;
                    color: #666;
                }
                .notice {
                    background: #fcf8e3;
                    border: 1px solid #faebcc;
                    color: #8a6d3b;
                    padding: 15px;
                    border-radius: 4px;
                    margin: 20px 0;
                }
                .suggestions {
                    background: #f9f9f9;
                    padding: 15px;
                    border-radius: 4px;
                    margin: 20px 0;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h2><?php _e('任务未通过 AI 审核', 'help-platform'); ?></h2>
                </div>

                <div class="content">
                    <p><?php printf(__('尊敬的 %s：', 'help-platform'), $user->display_name); ?></p>
                    <p><?php _e('您的任务未通过 AI 自动审核，原因如下：', 'help-platform'); ?></p>

                    <div class="notice">
                        <h3><?php _e('AI 审核结果', 'help-platform'); ?></h3>
                        <?php if (!empty($analysis['suggestions'])): ?>
                            <ul>
                                <?php foreach ($analysis['suggestions'] as $suggestion): ?>
                                    <li><?php echo esc_html($suggestion); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>

                    <div class="suggestions">
                        <h3><?php _e('优化建议', 'help-platform'); ?></h3>
                        <p><?php _e('请根据以上建议修改任务内容，确保：', 'help-platform'); ?></p>
                        <ul>
                            <li><?php _e('任务描述完整且清晰', 'help-platform'); ?></li>
                            <li><?php _e('不包含违规内容', 'help-platform'); ?></li>
                            <li><?php _e('位置信息准确', 'help-platform'); ?></li>
                        </ul>
                    </div>

                    <p><?php _e('修改完成后，您可以重新提交任务：', 'help-platform'); ?></p>
                    <a href="<?php echo home_url('/post-job'); ?>" class="button">
                        <?php _e('重新发布', 'help-platform'); ?>
                    </a>
                </div>

                <div class="footer">
                    <p><?php _e('此邮件由 HELP 平台自动发送，请勿直接回复。', 'help-platform'); ?></p>
                    <p><?php _e('如有任何问题，请联系平台客服。', 'help-platform'); ?></p>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
}

// 初始化
Help_Platform_AI::get_instance(); 