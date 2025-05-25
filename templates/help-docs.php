<?php
if (!defined('ABSPATH')) {
    exit;
}

$section = isset($_GET['section']) ? sanitize_text_field($_GET['section']) : '';
$help_docs = array(
    'verify' => array(
        'title' => __('实名认证帮助', 'help-platform'),
        'content' => array(
            array(
                'title' => __('什么是实名认证？', 'help-platform'),
                'content' => __('实名认证是为了确保平台用户身份的真实性，保护用户权益的重要措施。通过实名认证后，您将获得更多平台功能的使用权限。', 'help-platform')
            ),
            array(
                'title' => __('如何进行实名认证？', 'help-platform'),
                'content' => __('1. 点击"实名认证"按钮进入认证页面
2. 填写您的真实姓名
3. 输入您的身份证号码
4. 填写您的手机号码
5. 输入您的详细地址
6. 点击"提交认证"按钮
7. 等待管理员审核（通常1-2个工作日）', 'help-platform')
            ),
            array(
                'title' => __('认证信息会保密吗？', 'help-platform'),
                'content' => __('是的，您的认证信息将被严格保密，仅用于身份验证，不会用于其他用途。', 'help-platform')
            ),
            array(
                'title' => __('认证失败怎么办？', 'help-platform'),
                'content' => __('如果认证失败，请检查：
1. 信息是否填写正确
2. 身份证号码是否有效
3. 手机号码是否正确
如有疑问，请联系客服。', 'help-platform')
            )
        )
    ),
    'job' => array(
        'title' => __('任务发布帮助', 'help-platform'),
        'content' => array(
            array(
                'title' => __('如何发布任务？', 'help-platform'),
                'content' => __('1. 点击"发布任务"按钮
2. 填写任务标题
3. 详细描述任务内容
4. 设置任务预算
5. 选择任务地点
6. 设置截止时间
7. 点击"发布"按钮', 'help-platform')
            ),
            array(
                'title' => __('任务预算如何设置？', 'help-platform'),
                'content' => __('任务预算应该根据：
1. 任务难度
2. 所需时间
3. 市场行情
合理设置预算可以提高任务接单率。', 'help-platform')
            ),
            array(
                'title' => __('任务发布后可以修改吗？', 'help-platform'),
                'content' => __('可以修改，但有以下限制：
1. 只能修改未接单的任务
2. 修改后需要重新审核
3. 已接单的任务不能修改', 'help-platform')
            ),
            array(
                'title' => __('如何选择合适的接单人？', 'help-platform'),
                'content' => __('建议参考以下因素：
1. 接单人的信用评分
2. 历史完成情况
3. 用户评价
4. 沟通响应速度', 'help-platform')
            )
        )
    ),
    'message' => array(
        'title' => __('消息中心帮助', 'help-platform'),
        'content' => array(
            array(
                'title' => __('消息类型说明', 'help-platform'),
                'content' => __('系统消息：平台通知、系统公告等
任务消息：任务状态更新、接单通知等
支付消息：支付成功、退款通知等', 'help-platform')
            ),
            array(
                'title' => __('如何管理消息？', 'help-platform'),
                'content' => __('1. 查看消息列表
2. 点击消息查看详情
3. 使用筛选功能查找特定消息
4. 标记消息已读
5. 删除不需要的消息', 'help-platform')
            ),
            array(
                'title' => __('消息通知设置', 'help-platform'),
                'content' => __('您可以设置：
1. 邮件通知
2. 站内消息
3. 短信通知（可选）
在个人设置中管理通知偏好。', 'help-platform')
            ),
            array(
                'title' => __('消息保存时间', 'help-platform'),
                'content' => __('系统消息：永久保存
任务消息：任务完成后保存30天
支付消息：保存90天
重要消息建议及时保存。', 'help-platform')
            )
        )
    ),
    'payment' => array(
        'title' => __('支付帮助', 'help-platform'),
        'content' => array(
            array(
                'title' => __('支持哪些支付方式？', 'help-platform'),
                'content' => __('目前支持：
1. 支付宝
2. 微信支付
3. 银行卡支付
更多支付方式陆续开通中。', 'help-platform')
            ),
            array(
                'title' => __('如何充值？', 'help-platform'),
                'content' => __('1. 进入"我的钱包"
2. 点击"充值"按钮
3. 选择充值金额
4. 选择支付方式
5. 完成支付', 'help-platform')
            ),
            array(
                'title' => __('如何提现？', 'help-platform'),
                'content' => __('1. 进入"我的钱包"
2. 点击"提现"按钮
3. 输入提现金额
4. 选择提现方式
5. 确认提现
提现将在1-3个工作日内到账。', 'help-platform')
            ),
            array(
                'title' => __('支付安全说明', 'help-platform'),
                'content' => __('平台采用：
1. SSL加密传输
2. 资金托管
3. 实名认证
4. 交易密码
保障您的资金安全。', 'help-platform')
            )
        )
    )
);

if (!isset($help_docs[$section])) {
    $section = 'verify';
}

$current_doc = $help_docs[$section];
?>

<div class="help-docs-container">
    <div class="help-docs-sidebar">
        <h3><?php _e('帮助文档', 'help-platform'); ?></h3>
        <ul class="help-docs-menu">
            <?php foreach ($help_docs as $key => $doc): ?>
                <li class="<?php echo $key === $section ? 'active' : ''; ?>">
                    <a href="<?php echo esc_url(add_query_arg('section', $key)); ?>">
                        <?php echo esc_html($doc['title']); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    
    <div class="help-docs-content">
        <h2><?php echo esc_html($current_doc['title']); ?></h2>
        <div class="help-docs-list">
            <?php foreach ($current_doc['content'] as $item): ?>
                <div class="help-doc-item">
                    <h3><?php echo esc_html($item['title']); ?></h3>
                    <div class="help-doc-content">
                        <?php echo wpautop(esc_html($item['content'])); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<style>
.help-docs-container {
    display: grid;
    grid-template-columns: 250px 1fr;
    gap: 30px;
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.help-docs-sidebar {
    border-right: 1px solid #eee;
    padding-right: 20px;
}

.help-docs-sidebar h3 {
    margin: 0 0 20px;
    padding-bottom: 10px;
    border-bottom: 2px solid #2196f3;
    color: #333;
    font-size: 18px;
}

.help-docs-menu {
    list-style: none;
    margin: 0;
    padding: 0;
}

.help-docs-menu li {
    margin-bottom: 10px;
}

.help-docs-menu a {
    display: block;
    padding: 10px 15px;
    color: #666;
    text-decoration: none;
    border-radius: 4px;
    transition: all 0.3s ease;
}

.help-docs-menu li.active a,
.help-docs-menu a:hover {
    background: #e3f2fd;
    color: #2196f3;
}

.help-docs-content h2 {
    margin: 0 0 30px;
    color: #333;
    font-size: 24px;
}

.help-doc-item {
    margin-bottom: 30px;
    padding-bottom: 30px;
    border-bottom: 1px solid #eee;
}

.help-doc-item:last-child {
    margin-bottom: 0;
    padding-bottom: 0;
    border-bottom: none;
}

.help-doc-item h3 {
    margin: 0 0 15px;
    color: #2196f3;
    font-size: 18px;
}

.help-doc-content {
    color: #666;
    line-height: 1.6;
}

.help-doc-content p {
    margin: 0 0 15px;
}

.help-doc-content p:last-child {
    margin-bottom: 0;
}

@media screen and (max-width: 768px) {
    .help-docs-container {
        grid-template-columns: 1fr;
    }

    .help-docs-sidebar {
        border-right: none;
        border-bottom: 1px solid #eee;
        padding-right: 0;
        padding-bottom: 20px;
        margin-bottom: 20px;
    }
}
</style> 