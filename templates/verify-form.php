<?php
if (!defined('ABSPATH')) {
    exit;
}
/**
 * 实名认证表单模板
 */

$current_user = wp_get_current_user();
$verify_data = get_user_meta($current_user->ID, 'help_verify_data', true);
$verify_status = get_user_meta($current_user->ID, 'help_verify_status', true);
$verify_reason = get_user_meta($current_user->ID, 'help_verify_reason', true);

// 获取当前语言
$current_language = get_locale();
$is_chinese = strpos($current_language, 'zh') === 0;

// 获取服务类别数据
$service_categories = array(
    'home_service' => array(
        'name' => __('家居服务', 'help-platform'),
        'subcategories' => array(
            'cleaning' => array(
                'name' => __('清洁服务', 'help-platform'),
                'services' => array(
                    'regular_cleaning' => __('日常保洁', 'help-platform'),
                    'deep_cleaning' => __('深度保洁', 'help-platform'),
                    'window_cleaning' => __('擦玻璃', 'help-platform'),
                    'carpet_cleaning' => __('地毯清洗', 'help-platform'),
                    'post_renovation' => __('开荒保洁', 'help-platform')
                ),
                'skills' => array(
                    __('专业清洁工具使用', 'help-platform'),
                    __('清洁剂使用', 'help-platform'),
                    __('特殊材质清洁', 'help-platform'),
                    __('高空作业', 'help-platform'),
                    __('垃圾分类处理', 'help-platform')
                )
            ),
            'maintenance' => array(
                'name' => __('维修服务', 'help-platform'),
                'services' => array(
                    'plumbing' => __('水电维修', 'help-platform'),
                    'electrical' => __('电器维修', 'help-platform'),
                    'carpentry' => __('木工维修', 'help-platform'),
                    'painting' => __('墙面维修', 'help-platform'),
                    'appliance' => __('家电维修', 'help-platform')
                ),
                'skills' => array(
                    __('水电安装维修', 'help-platform'),
                    __('电器故障诊断', 'help-platform'),
                    __('木工手艺', 'help-platform'),
                    __('家电维修', 'help-platform'),
                    __('工具使用', 'help-platform')
                )
            ),
            'moving' => array(
                'name' => __('搬家服务', 'help-platform'),
                'services' => array(
                    'home_moving' => __('家庭搬家', 'help-platform'),
                    'office_moving' => __('办公室搬迁', 'help-platform'),
                    'piano_moving' => __('钢琴搬运', 'help-platform'),
                    'furniture_moving' => __('家具搬运', 'help-platform')
                ),
                'skills' => array(
                    __('物品打包', 'help-platform'),
                    __('家具拆装', 'help-platform'),
                    __('钢琴搬运', 'help-platform'),
                    __('贵重物品搬运', 'help-platform'),
                    __('空间规划', 'help-platform')
                )
            )
        )
    ),
    'personal_care' => array(
        'name' => __('个人护理', 'help-platform'),
        'subcategories' => array(
            'elderly_care' => array(
                'name' => __('老人照护', 'help-platform'),
                'services' => array(
                    'daily_care' => __('日常照护', 'help-platform'),
                    'medical_care' => __('医疗照护', 'help-platform'),
                    'companionship' => __('陪伴服务', 'help-platform'),
                    'rehabilitation' => __('康复护理', 'help-platform')
                ),
                'skills' => array(
                    __('基础护理技能', 'help-platform'),
                    __('急救知识', 'help-platform'),
                    __('心理疏导', 'help-platform'),
                    __('康复护理', 'help-platform'),
                    __('营养搭配', 'help-platform')
                )
            ),
            'childcare' => array(
                'name' => __('儿童照护', 'help-platform'),
                'services' => array(
                    'babysitting' => __('临时照看', 'help-platform'),
                    'tutoring' => __('作业辅导', 'help-platform'),
                    'early_education' => __('早教服务', 'help-platform'),
                    'special_needs' => __('特殊儿童照护', 'help-platform')
                ),
                'skills' => array(
                    __('儿童照护', 'help-platform'),
                    __('教育辅导', 'help-platform'),
                    __('儿童心理', 'help-platform'),
                    __('急救知识', 'help-platform'),
                    __('早教技能', 'help-platform')
                )
            )
        )
    ),
    'education' => array(
        'name' => __('教育培训', 'help-platform'),
        'subcategories' => array(
            'academic' => array(
                'name' => __('学科辅导', 'help-platform'),
                'services' => array(
                    'math' => __('数学辅导', 'help-platform'),
                    'language' => __('语言辅导', 'help-platform'),
                    'science' => __('理科辅导', 'help-platform'),
                    'art' => __('艺术辅导', 'help-platform'),
                    'music' => __('音乐辅导', 'help-platform')
                ),
                'skills' => array(
                    __('学科专业知识', 'help-platform'),
                    __('教学方法', 'help-platform'),
                    __('课程设计', 'help-platform'),
                    __('学生心理辅导', 'help-platform'),
                    __('考试技巧', 'help-platform')
                )
            ),
            'skills' => array(
                'name' => __('技能培训', 'help-platform'),
                'services' => array(
                    'music' => __('音乐培训', 'help-platform'),
                    'sports' => __('体育培训', 'help-platform'),
                    'cooking' => __('烹饪培训', 'help-platform'),
                    'computer' => __('电脑培训', 'help-platform'),
                    'language' => __('语言培训', 'help-platform')
                ),
                'skills' => array(
                    __('专业技能', 'help-platform'),
                    __('教学经验', 'help-platform'),
                    __('课程规划', 'help-platform'),
                    __('实践指导', 'help-platform'),
                    __('个性化教学', 'help-platform')
                )
            )
        )
    )
);
?>

<div class="help-verify-form">
    <?php if ($verify_status === 'pending'): ?>
        <div class="help-notice help-notice-info">
            <?php _e('您的认证申请正在审核中，请耐心等待。', 'help-platform'); ?>
        </div>
    <?php elseif ($verify_status === 'rejected'): ?>
        <div class="help-notice help-notice-error">
            <?php _e('您的认证申请未通过，原因：', 'help-platform'); ?>
            <p><?php echo esc_html($verify_reason); ?></p>
            <a href="#" class="help-button help-button-primary" id="help-verify-edit">
                <?php _e('重新提交', 'help-platform'); ?>
            </a>
        </div>
    <?php endif; ?>

    <form id="help-verify-form" method="post" enctype="multipart/form-data" class="<?php echo $verify_status === 'pending' ? 'hidden' : ''; ?>">
        <?php wp_nonce_field('help_verify_submit', 'help_verify_nonce'); ?>

        <div class="help-form-section">
            <h3><?php _e('基本信息', 'help-platform'); ?></h3>
            
            <div class="help-form-row">
                <div class="help-form-group">
                    <label for="verify_name"><?php _e('姓名', 'help-platform'); ?> *</label>
                    <input type="text" id="verify_name" name="verify_name" required 
                           value="<?php echo esc_attr($verify_data['name'] ?? ''); ?>">
                </div>

                <div class="help-form-group">
                    <label for="verify_country"><?php _e('国家/地区', 'help-platform'); ?> *</label>
                    <select id="verify_country" name="verify_country" required>
                        <option value=""><?php _e('请选择', 'help-platform'); ?></option>
                        <option value="CN" <?php selected($verify_data['country'] ?? '', 'CN'); ?>><?php _e('中国', 'help-platform'); ?></option>
                        <option value="US" <?php selected($verify_data['country'] ?? '', 'US'); ?>><?php _e('美国', 'help-platform'); ?></option>
                        <option value="ES" <?php selected($verify_data['country'] ?? '', 'ES'); ?>><?php _e('西班牙', 'help-platform'); ?></option>
                        <!-- 可以添加更多国家 -->
                    </select>
                </div>
            </div>

            <div class="help-form-row">
                <div class="help-form-group">
                    <label for="verify_id_type"><?php _e('证件类型', 'help-platform'); ?> *</label>
                    <select id="verify_id_type" name="verify_id_type" required>
                        <option value=""><?php _e('请选择', 'help-platform'); ?></option>
                        <option value="id_card" <?php selected($verify_data['id_type'] ?? '', 'id_card'); ?>><?php _e('身份证', 'help-platform'); ?></option>
                        <option value="passport" <?php selected($verify_data['id_type'] ?? '', 'passport'); ?>><?php _e('护照', 'help-platform'); ?></option>
                        <option value="driver_license" <?php selected($verify_data['id_type'] ?? '', 'driver_license'); ?>><?php _e('驾驶证', 'help-platform'); ?></option>
                        <option value="other" <?php selected($verify_data['id_type'] ?? '', 'other'); ?>><?php _e('其他', 'help-platform'); ?></option>
                    </select>
                </div>

                <div class="help-form-group">
                    <label for="verify_id_number"><?php _e('证件号码', 'help-platform'); ?> *</label>
                    <input type="text" id="verify_id_number" name="verify_id_number" required 
                           value="<?php echo esc_attr($verify_data['id_number'] ?? ''); ?>">
                </div>
            </div>

            <div class="help-form-row">
                <div class="help-form-group">
                    <label for="verify_phone"><?php _e('联系电话', 'help-platform'); ?> *</label>
                    <div class="help-phone-input">
                        <select id="verify_phone_code" name="verify_phone_code" required>
                            <option value="+86" <?php selected($verify_data['phone_code'] ?? '', '+86'); ?>>+86</option>
                            <option value="+1" <?php selected($verify_data['phone_code'] ?? '', '+1'); ?>>+1</option>
                            <option value="+34" <?php selected($verify_data['phone_code'] ?? '', '+34'); ?>>+34</option>
                            <!-- 可以添加更多国家代码 -->
                        </select>
                        <input type="tel" id="verify_phone" name="verify_phone" required 
                               value="<?php echo esc_attr($verify_data['phone'] ?? ''); ?>">
                    </div>
                </div>

                <div class="help-form-group">
                    <label for="verify_email"><?php _e('电子邮箱', 'help-platform'); ?> *</label>
                    <input type="email" id="verify_email" name="verify_email" required 
                           value="<?php echo esc_attr($verify_data['email'] ?? $current_user->user_email); ?>">
                </div>
            </div>
        </div>

        <div class="help-form-section">
            <h3><?php _e('专业信息', 'help-platform'); ?></h3>
            
            <div class="help-form-group">
                <label for="verify_profession_category"><?php _e('服务大类', 'help-platform'); ?> *</label>
                <select id="verify_profession_category" name="verify_profession_category" required>
                    <option value=""><?php _e('请选择服务大类', 'help-platform'); ?></option>
                    <?php
                    $saved_category = isset($verify_data['profession']['category']) ? $verify_data['profession']['category'] : '';
                    foreach ($service_categories as $key => $category) {
                        printf(
                            '<option value="%s" %s>%s</option>',
                            esc_attr($key),
                            selected($saved_category, $key, false),
                            esc_html($category['name'])
                        );
                    }
                    ?>
                </select>
            </div>

            <div class="help-form-group" id="verify_profession_subcategory_group" style="display: none;">
                <label for="verify_profession_subcategory"><?php _e('服务小类', 'help-platform'); ?> *</label>
                <select id="verify_profession_subcategory" name="verify_profession_subcategory" required>
                    <option value=""><?php _e('请选择服务小类', 'help-platform'); ?></option>
                </select>
            </div>

            <div class="help-form-group" id="verify_profession_specific_group" style="display: none;">
                <label for="verify_profession_specific"><?php _e('具体服务', 'help-platform'); ?> *</label>
                <select id="verify_profession_specific" name="verify_profession_specific" required>
                    <option value=""><?php _e('请选择具体服务', 'help-platform'); ?></option>
                </select>
            </div>

            <div class="help-form-group" id="verify_profession_skills_group" style="display: none;">
                <label><?php _e('专业技能', 'help-platform'); ?> *</label>
                <div class="help-skills-container">
                    <!-- 技能选项将通过 JavaScript 动态加载 -->
                </div>
            </div>

            <div class="help-form-group">
                <label for="verify_experience"><?php _e('工作经验', 'help-platform'); ?> *</label>
                <select id="verify_experience" name="verify_experience" required>
                    <option value=""><?php _e('请选择', 'help-platform'); ?></option>
                    <option value="0-1" <?php selected($verify_data['experience'] ?? '', '0-1'); ?>><?php _e('1年以下', 'help-platform'); ?></option>
                    <option value="1-3" <?php selected($verify_data['experience'] ?? '', '1-3'); ?>><?php _e('1-3年', 'help-platform'); ?></option>
                    <option value="3-5" <?php selected($verify_data['experience'] ?? '', '3-5'); ?>><?php _e('3-5年', 'help-platform'); ?></option>
                    <option value="5+" <?php selected($verify_data['experience'] ?? '', '5+'); ?>><?php _e('5年以上', 'help-platform'); ?></option>
                </select>
            </div>

            <div class="help-form-group">
                <label for="verify_introduction"><?php _e('专业介绍', 'help-platform'); ?> *</label>
                <textarea id="verify_introduction" name="verify_introduction" rows="5" required 
                          placeholder="<?php _e('请详细介绍您的专业技能、服务经验等', 'help-platform'); ?>"><?php echo esc_textarea($verify_data['introduction'] ?? ''); ?></textarea>
            </div>

            <div class="help-form-group">
                <label><?php _e('技能证书', 'help-platform'); ?></label>
                <div class="help-certificate-upload">
                    <div class="help-upload-item">
                        <input type="file" name="verify_certificates[]" accept=".pdf,.jpg,.jpeg,.png" multiple>
                        <p class="help-upload-hint"><?php _e('支持PDF、JPG、PNG格式，最多5个文件', 'help-platform'); ?></p>
                    </div>
                    <?php if (!empty($verify_data['certificates'])): ?>
                        <div class="help-certificate-list">
                            <?php foreach ($verify_data['certificates'] as $cert): ?>
                                <div class="help-certificate-item">
                                    <a href="<?php echo esc_url($cert['url']); ?>" target="_blank">
                                        <?php echo esc_html($cert['name']); ?>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="help-form-section">
            <h3><?php _e('身份验证', 'help-platform'); ?></h3>
            
            <div class="help-form-group">
                <label><?php _e('证件照片', 'help-platform'); ?> *</label>
                <div class="help-id-photos">
                    <div class="help-upload-item">
                        <label><?php _e('证件正面', 'help-platform'); ?></label>
                        <input type="file" name="verify_id_front" accept="image/*" required>
                        <?php if (!empty($verify_data['id_front'])): ?>
                            <img src="<?php echo esc_url($verify_data['id_front']); ?>" class="help-id-preview">
                        <?php endif; ?>
                    </div>
                    <div class="help-upload-item">
                        <label><?php _e('证件背面', 'help-platform'); ?></label>
                        <input type="file" name="verify_id_back" accept="image/*" required>
                        <?php if (!empty($verify_data['id_back'])): ?>
                            <img src="<?php echo esc_url($verify_data['id_back']); ?>" class="help-id-preview">
                        <?php endif; ?>
                    </div>
                    <div class="help-upload-item">
                        <label><?php _e('手持证件照片', 'help-platform'); ?></label>
                        <input type="file" name="verify_id_selfie" accept="image/*" required>
                        <?php if (!empty($verify_data['id_selfie'])): ?>
                            <img src="<?php echo esc_url($verify_data['id_selfie']); ?>" class="help-id-preview">
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="help-form-section">
            <div class="help-form-group">
                <label class="help-checkbox-label">
                    <input type="checkbox" name="verify_agreement" required>
                    <?php _e('我已阅读并同意', 'help-platform'); ?>
                    <a href="<?php echo esc_url(get_privacy_policy_url()); ?>" target="_blank">
                        <?php _e('隐私政策', 'help-platform'); ?>
                    </a>
                    <?php _e('和', 'help-platform'); ?>
                    <a href="<?php echo esc_url(home_url('/terms')); ?>" target="_blank">
                        <?php _e('服务条款', 'help-platform'); ?>
                    </a>
                </label>
            </div>
        </div>

        <div class="help-form-submit">
            <button type="submit" class="help-button help-button-primary">
                <?php _e('提交认证', 'help-platform'); ?>
            </button>
        </div>
    </form>
</div>

<style>
.help-verify-form {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
}

.help-form-section {
    background: #fff;
    padding: 20px;
    margin-bottom: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.help-form-section h3 {
    margin-top: 0;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.help-form-row {
    display: flex;
    gap: 20px;
    margin-bottom: 15px;
}

.help-form-group {
    flex: 1;
    margin-bottom: 15px;
}

.help-form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
}

.help-form-group input[type="text"],
.help-form-group input[type="email"],
.help-form-group input[type="tel"],
.help-form-group select,
.help-form-group textarea {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.help-phone-input {
    display: flex;
    gap: 10px;
}

.help-phone-input select {
    width: 100px;
}

.help-certificate-upload,
.help-id-photos {
    display: grid;
    gap: 15px;
}

.help-upload-item {
    border: 2px dashed #ddd;
    padding: 15px;
    border-radius: 4px;
    text-align: center;
}

.help-upload-hint {
    font-size: 0.9em;
    color: #666;
    margin: 5px 0 0;
}

.help-id-preview {
    max-width: 200px;
    margin-top: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.help-certificate-list {
    margin-top: 10px;
}

.help-certificate-item {
    padding: 5px 0;
}

.help-checkbox-label {
    display: flex;
    align-items: center;
    gap: 5px;
}

.help-form-submit {
    text-align: center;
    margin-top: 30px;
}

.help-button {
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 16px;
}

.help-button-primary {
    background: #0073aa;
    color: #fff;
}

.help-button-primary:hover {
    background: #005177;
}

.help-notice {
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 4px;
}

.help-notice-info {
    background: #e5f5fa;
    border: 1px solid #bce0f3;
    color: #0073aa;
}

.help-notice-error {
    background: #fbeaea;
    border: 1px solid #f1d4d4;
    color: #d63638;
}

.hidden {
    display: none;
}

@media (max-width: 768px) {
    .help-form-row {
        flex-direction: column;
        gap: 0;
    }
    
    .help-phone-input {
        flex-direction: column;
    }
    
    .help-phone-input select {
        width: 100%;
    }
}

.help-skills-container {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 10px;
    margin-top: 10px;
}

.help-skill-checkbox {
    display: flex;
    align-items: center;
    gap: 5px;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    cursor: pointer;
}

.help-skill-checkbox:hover {
    background-color: #f5f5f5;
}

.help-skill-checkbox input[type="checkbox"] {
    margin: 0;
}

.help-form-group input[type="text"]:focus,
.help-form-group input[type="email"]:focus,
.help-form-group input[type="tel"]:focus,
.help-form-group select:focus,
.help-form-group textarea:focus {
    border-color: #0073aa;
    box-shadow: 0 0 0 1px #0073aa;
    outline: none;
}

.help-form-group input.error,
.help-form-group select.error,
.help-form-group textarea.error {
    border-color: #d63638;
}

.help-form-group .error-message {
    color: #d63638;
    font-size: 0.9em;
    margin-top: 5px;
    display: none;
}

.help-form-group input.error + .error-message,
.help-form-group select.error + .error-message,
.help-form-group textarea.error + .error-message {
    display: block;
}

.help-upload-item {
    position: relative;
    transition: all 0.3s ease;
}

.help-upload-item:hover {
    border-color: #0073aa;
    background-color: #f8f9fa;
}

.help-upload-item input[type="file"] {
    position: absolute;
    width: 100%;
    height: 100%;
    top: 0;
    left: 0;
    opacity: 0;
    cursor: pointer;
}

.help-upload-item .upload-placeholder {
    padding: 20px;
    text-align: center;
    color: #666;
}

.help-upload-item .upload-placeholder i {
    font-size: 24px;
    margin-bottom: 10px;
    color: #0073aa;
}

.help-skill-checkbox {
    transition: all 0.2s ease;
}

.help-skill-checkbox:hover {
    background-color: #f0f7fb;
    border-color: #0073aa;
}

.help-skill-checkbox input[type="checkbox"]:checked + span {
    color: #0073aa;
    font-weight: 500;
}

.help-form-submit button {
    transition: all 0.3s ease;
    min-width: 200px;
}

.help-form-submit button:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.help-form-submit button:active {
    transform: translateY(0);
}

.help-notice {
    position: relative;
    padding-left: 40px;
}

.help-notice:before {
    content: '';
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    width: 20px;
    height: 20px;
    background-size: contain;
    background-repeat: no-repeat;
}

.help-notice-info:before {
    background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="%230073aa"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>');
}

.help-notice-error:before {
    background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="%23d63638"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>');
}
</style>

<script>
jQuery(document).ready(function($) {
    // 显示/隐藏表单
    $('#help-verify-edit').on('click', function(e) {
        e.preventDefault();
        $('#help-verify-form').removeClass('hidden');
        $(this).closest('.help-notice').addClass('hidden');
    });

    // 文件上传预览
    $('input[type="file"]').on('change', function(e) {
        const file = e.target.files[0];
        if (file && file.type.startsWith('image/')) {
            const reader = new FileReader();
            const $uploadItem = $(this).closest('.help-upload-item');
            const $preview = $uploadItem.find('.help-id-preview');
            
            reader.onload = function(e) {
                if ($preview.length) {
                    $preview.attr('src', e.target.result);
                } else {
                    $('<img>')
                        .addClass('help-id-preview')
                        .attr('src', e.target.result)
                        .insertAfter($uploadItem.find('input[type="file"]'));
                }
                $uploadItem.addClass('has-preview');
            };
            
            reader.readAsDataURL(file);
        }
    });

    // 定义服务类别数据
    const serviceCategories = <?php echo json_encode($service_categories); ?>;
    
    // 获取已保存的数据
    const savedData = <?php echo json_encode($verify_data['profession'] ?? array()); ?>;
    
    // 初始化表单
    function initializeForm() {
        if (savedData.category) {
            $('#verify_profession_category').val(savedData.category).trigger('change');
            
            if (savedData.subcategory) {
                setTimeout(() => {
                    $('#verify_profession_subcategory').val(savedData.subcategory).trigger('change');
                    
                    if (savedData.specific) {
                        setTimeout(() => {
                            $('#verify_profession_specific').val(savedData.specific).trigger('change');
                            
                            if (savedData.skills && savedData.skills.length > 0) {
                                setTimeout(() => {
                                    savedData.skills.forEach(skill => {
                                        $(`input[name="verify_skills[]"][value="${skill}"]`).prop('checked', true);
                                    });
                                }, 100);
                            }
                        }, 100);
                    }
                }, 100);
            }
        }
    }

    // 处理大类选择
    $('#verify_profession_category').on('change', function() {
        const category = $(this).val();
        const $subcategoryGroup = $('#verify_profession_subcategory_group');
        const $subcategory = $('#verify_profession_subcategory');
        const $specificGroup = $('#verify_profession_specific_group');
        const $specific = $('#verify_profession_specific');
        const $skillsGroup = $('#verify_profession_skills_group');

        // 清空并隐藏下级选项
        $subcategory.empty().append('<option value=""><?php _e('请选择服务小类', 'help-platform'); ?></option>');
        $specific.empty().append('<option value=""><?php _e('请选择具体服务', 'help-platform'); ?></option>');
        $('.help-skills-container').empty();
        
        if (category && category !== 'other') {
            // 显示并填充小类选项
            const subcategories = serviceCategories[category].subcategories;
            for (const key in subcategories) {
                $subcategory.append(`<option value="${key}">${subcategories[key].name}</option>`);
            }
            $subcategoryGroup.show();
            $specificGroup.hide();
            $skillsGroup.hide();
        } else {
            $subcategoryGroup.hide();
            $specificGroup.hide();
            $skillsGroup.hide();
        }
    });

    // 处理小类选择
    $('#verify_profession_subcategory').on('change', function() {
        const category = $('#verify_profession_category').val();
        const subcategory = $(this).val();
        const $specificGroup = $('#verify_profession_specific_group');
        const $specific = $('#verify_profession_specific');
        const $skillsGroup = $('#verify_profession_skills_group');

        // 清空并隐藏下级选项
        $specific.empty().append('<option value=""><?php _e('请选择具体服务', 'help-platform'); ?></option>');
        $('.help-skills-container').empty();

        if (subcategory) {
            // 显示并填充具体服务选项
            const services = serviceCategories[category].subcategories[subcategory].services;
            for (const key in services) {
                $specific.append(`<option value="${key}">${services[key]}</option>`);
            }
            $specificGroup.show();
            $skillsGroup.hide();
        } else {
            $specificGroup.hide();
            $skillsGroup.hide();
        }
    });

    // 处理具体服务选择
    $('#verify_profession_specific').on('change', function() {
        const category = $('#verify_profession_category').val();
        const subcategory = $('#verify_profession_subcategory').val();
        const $skillsGroup = $('#verify_profession_skills_group');
        const $skillsContainer = $('.help-skills-container');

        // 清空技能选项
        $skillsContainer.empty();

        if ($(this).val()) {
            // 显示并填充技能选项
            const skills = serviceCategories[category].subcategories[subcategory].skills;
            skills.forEach(skill => {
                $skillsContainer.append(`
                    <label class="help-skill-checkbox">
                        <input type="checkbox" name="verify_skills[]" value="${skill}">
                        ${skill}
                    </label>
                `);
            });
            $skillsGroup.show();
        } else {
            $skillsGroup.hide();
        }
    });

    // 表单验证函数
    function validateForm() {
        let isValid = true;
        const $form = $('#help-verify-form');

        // 验证姓名
        const $name = $('#verify_name');
        if ($name.val().trim().length < 2) {
            $name.addClass('error').after('<div class="error-message"><?php _e('请输入至少2个字符的姓名', 'help-platform'); ?></div>');
            isValid = false;
        } else {
            $name.removeClass('error').next('.error-message').remove();
        }

        // 验证证件号码
        const $idNumber = $('#verify_id_number');
        const idType = $('#verify_id_type').val();
        let idPattern;
        switch (idType) {
            case 'id_card':
                idPattern = /(^\d{15}$)|(^\d{18}$)|(^\d{17}(\d|X|x)$)/;
                break;
            case 'passport':
                idPattern = /^[A-Z0-9]{6,10}$/;
                break;
            case 'driver_license':
                idPattern = /^[A-Z0-9]{15}$/;
                break;
            default:
                idPattern = /^.+$/;
        }
        if (!idPattern.test($idNumber.val())) {
            $idNumber.addClass('error').after('<div class="error-message"><?php _e('请输入有效的证件号码', 'help-platform'); ?></div>');
            isValid = false;
        } else {
            $idNumber.removeClass('error').next('.error-message').remove();
        }

        // 验证手机号
        const $phone = $('#verify_phone');
        const phoneCode = $('#verify_phone_code').val();
        let phonePattern;
        switch (phoneCode) {
            case '+86':
                phonePattern = /^1[3-9]\d{9}$/;
                break;
            case '+1':
                phonePattern = /^[2-9]\d{9}$/;
                break;
            case '+34':
                phonePattern = /^[6-9]\d{8}$/;
                break;
            default:
                phonePattern = /^\d{6,15}$/;
        }
        if (!phonePattern.test($phone.val())) {
            $phone.addClass('error').after('<div class="error-message"><?php _e('请输入有效的电话号码', 'help-platform'); ?></div>');
            isValid = false;
        } else {
            $phone.removeClass('error').next('.error-message').remove();
        }

        // 验证邮箱
        const $email = $('#verify_email');
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailPattern.test($email.val())) {
            $email.addClass('error').after('<div class="error-message"><?php _e('请输入有效的电子邮箱', 'help-platform'); ?></div>');
            isValid = false;
        } else {
            $email.removeClass('error').next('.error-message').remove();
        }

        // 验证专业介绍
        const $introduction = $('#verify_introduction');
        if ($introduction.val().trim().length < 50) {
            $introduction.addClass('error').after('<div class="error-message"><?php _e('专业介绍至少需要50个字符', 'help-platform'); ?></div>');
            isValid = false;
        } else {
            $introduction.removeClass('error').next('.error-message').remove();
        }

        // 验证文件上传
        const $idFront = $('input[name="verify_id_front"]');
        const $idBack = $('input[name="verify_id_back"]');
        const $idSelfie = $('input[name="verify_id_selfie"]');
        const fileTypes = ['image/jpeg', 'image/png', 'image/jpg'];
        const maxSize = 5 * 1024 * 1024; // 5MB

        function validateFile($input) {
            if ($input[0].files.length > 0) {
                const file = $input[0].files[0];
                if (!fileTypes.includes(file.type)) {
                    $input.addClass('error').after('<div class="error-message"><?php _e('请上传JPG或PNG格式的图片', 'help-platform'); ?></div>');
                    return false;
                }
                if (file.size > maxSize) {
                    $input.addClass('error').after('<div class="error-message"><?php _e('图片大小不能超过5MB', 'help-platform'); ?></div>');
                    return false;
                }
                $input.removeClass('error').next('.error-message').remove();
                return true;
            }
            return true;
        }

        if (!validateFile($idFront) || !validateFile($idBack) || !validateFile($idSelfie)) {
            isValid = false;
        }

        return isValid;
    }

    // 表单提交时验证
    $('#help-verify-form').on('submit', function(e) {
        if (!validateForm()) {
            e.preventDefault();
            return false;
        }

        // 显示提交中状态
        const $submitButton = $(this).find('button[type="submit"]');
        const originalText = $submitButton.text();
        $submitButton.prop('disabled', true).text('<?php _e('提交中...', 'help-platform'); ?>');

        // 提交表单
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: new FormData(this),
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message || '<?php _e('提交失败，请重试', 'help-platform'); ?>');
                    $submitButton.prop('disabled', false).text(originalText);
                }
            },
            error: function() {
                alert('<?php _e('提交失败，请重试', 'help-platform'); ?>');
                $submitButton.prop('disabled', false).text(originalText);
            }
        });

        return false;
    });

    // 实时验证
    $('input, select, textarea').on('change blur', function() {
        validateForm();
    });

    // 初始化表单
    initializeForm();
});
</script> 