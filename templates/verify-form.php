<?php
if (!defined('ABSPATH')) {
    exit;
}
/**
 * 实名认证表单模板
 */
?>
<div class="help-platform-verify">
    <div class="form-header">
        <h2><?php _e('实名认证', 'help-platform'); ?></h2>
        <a href="<?php echo esc_url(add_query_arg('help', 'verify')); ?>" class="help-button" target="_blank">
            <span class="dashicons dashicons-editor-help"></span>
            <?php _e('帮助', 'help-platform'); ?>
        </a>
    </div>
    
    <form id="help-platform-verify-form" method="post">
        <?php wp_nonce_field('help_platform_nonce', 'help_platform_nonce'); ?>
        <input type="hidden" name="action" value="submit_verify">

        <div class="form-group">
            <label for="name"><?php _e('姓名', 'help-platform'); ?> <span class="required">*</span></label>
            <input type="text" id="name" name="name" required 
                   placeholder="<?php esc_attr_e('请输入您的真实姓名', 'help-platform'); ?>"
                   pattern="[\u4e00-\u9fa5]{2,20}"
                   title="<?php esc_attr_e('请输入2-20个汉字', 'help-platform'); ?>">
            <p class="description"><?php _e('请输入您的真实姓名，2-20个汉字', 'help-platform'); ?></p>
        </div>

        <div class="form-group">
            <label for="id_card"><?php _e('身份证号', 'help-platform'); ?> <span class="required">*</span></label>
            <input type="text" id="id_card" name="id_card" required 
                   placeholder="<?php esc_attr_e('请输入18位身份证号码', 'help-platform'); ?>"
                   pattern="[0-9Xx]{18}"
                   title="<?php esc_attr_e('请输入18位身份证号码', 'help-platform'); ?>">
            <p class="description"><?php _e('请输入18位身份证号码，用于身份验证', 'help-platform'); ?></p>
        </div>

        <div class="form-group">
            <label for="phone"><?php _e('手机号码', 'help-platform'); ?> <span class="required">*</span></label>
            <input type="tel" id="phone" name="phone" required 
                   placeholder="<?php esc_attr_e('请输入11位手机号码', 'help-platform'); ?>"
                   pattern="[0-9]{11}"
                   title="<?php esc_attr_e('请输入11位手机号码', 'help-platform'); ?>">
            <p class="description"><?php _e('请输入11位手机号码，用于接收验证码和通知', 'help-platform'); ?></p>
        </div>

        <div class="form-group">
            <label for="address"><?php _e('联系地址', 'help-platform'); ?> <span class="required">*</span></label>
            <textarea id="address" name="address" required 
                      placeholder="<?php esc_attr_e('请输入详细联系地址', 'help-platform'); ?>"
                      minlength="10" maxlength="200"></textarea>
            <p class="description"><?php _e('请输入详细联系地址，10-200个字符', 'help-platform'); ?></p>
        </div>

        <div class="form-group">
            <button type="submit" class="button button-primary">
                <span class="button-text"><?php _e('提交认证', 'help-platform'); ?></span>
                <span class="button-loading" style="display: none;">
                    <span class="spinner"></span>
                    <?php _e('提交中...', 'help-platform'); ?>
                </span>
            </button>
        </div>

        <div class="form-notice">
            <p><strong><?php _e('温馨提示：', 'help-platform'); ?></strong></p>
            <ul>
                <li><?php _e('请确保填写的信息真实有效，否则可能影响您的账号使用。', 'help-platform'); ?></li>
                <li><?php _e('您的个人信息将被严格保密，仅用于身份验证。', 'help-platform'); ?></li>
                <li><?php _e('认证信息提交后，我们将在1-3个工作日内完成审核。', 'help-platform'); ?></li>
            </ul>
        </div>
    </form>
</div>

<style>
.form-description {
    color: #666;
    margin-bottom: 25px;
    font-size: 15px;
}

.form-notice {
    margin-top: 30px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
    border-left: 4px solid #2196f3;
}

.form-notice ul {
    margin: 10px 0 0 20px;
    padding: 0;
}

.form-notice li {
    color: #666;
    margin-bottom: 8px;
    font-size: 14px;
}

.button-loading .spinner {
    display: inline-block;
    width: 16px;
    height: 16px;
    border: 2px solid #fff;
    border-radius: 50%;
    border-top-color: transparent;
    animation: spin 1s linear infinite;
    margin-right: 8px;
    vertical-align: middle;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.help-platform-verify .form-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.help-button {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 8px 15px;
    background: #e3f2fd;
    color: #2196f3;
    text-decoration: none;
    border-radius: 4px;
    transition: all 0.3s ease;
}

.help-button:hover {
    background: #bbdefb;
    color: #1976d2;
}

.help-button .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}
</style>

<script>
jQuery(document).ready(function($) {
    var $form = $('#help-platform-verify-form');
    var $submit = $form.find('button[type="submit"]');
    var $buttonText = $submit.find('.button-text');
    var $buttonLoading = $submit.find('.button-loading');

    // 身份证号验证
    function validateIdCard(idCard) {
        var reg = /(^\d{15}$)|(^\d{18}$)|(^\d{17}(\d|X|x)$)/;
        if (!reg.test(idCard)) {
            return false;
        }
        // 这里可以添加更详细的身份证验证逻辑
        return true;
    }

    // 手机号验证
    function validatePhone(phone) {
        return /^1[3-9]\d{9}$/.test(phone);
    }

    // 表单验证
    $form.on('submit', function(e) {
        e.preventDefault();
        
        var isValid = true;
        var $name = $('#name');
        var $idCard = $('#id_card');
        var $phone = $('#phone');
        var $address = $('#address');

        // 清除之前的错误提示
        $('.error-message').remove();
        $('.error').removeClass('error');

        // 验证姓名
        if (!$name.val().match(/^[\u4e00-\u9fa5]{2,20}$/)) {
            $name.addClass('error').after('<div class="error-message"><?php _e('请输入2-20个汉字的真实姓名', 'help-platform'); ?></div>');
            isValid = false;
        }

        // 验证身份证
        if (!validateIdCard($idCard.val())) {
            $idCard.addClass('error').after('<div class="error-message"><?php _e('请输入正确的18位身份证号码', 'help-platform'); ?></div>');
            isValid = false;
        }

        // 验证手机号
        if (!validatePhone($phone.val())) {
            $phone.addClass('error').after('<div class="error-message"><?php _e('请输入正确的11位手机号码', 'help-platform'); ?></div>');
            isValid = false;
        }

        // 验证地址
        if ($address.val().length < 10 || $address.val().length > 200) {
            $address.addClass('error').after('<div class="error-message"><?php _e('请输入10-200个字符的详细地址', 'help-platform'); ?></div>');
            isValid = false;
        }

        if (!isValid) {
            return;
        }

        // 显示加载状态
        $buttonText.hide();
        $buttonLoading.show();
        $submit.prop('disabled', true);

        // 提交表单
        $.ajax({
            url: helpPlatform.ajaxurl,
            type: 'POST',
            data: $form.serialize(),
            success: function(response) {
                if (response.success) {
                    $form.html('<div class="help-platform-message success">' + response.data + '</div>');
                } else {
                    $form.prepend('<div class="help-platform-message error">' + response.data + '</div>');
                    $buttonText.show();
                    $buttonLoading.hide();
                    $submit.prop('disabled', false);
                }
            },
            error: function() {
                $form.prepend('<div class="help-platform-message error">' + helpPlatform.i18n.submitError + '</div>');
                $buttonText.show();
                $buttonLoading.hide();
                $submit.prop('disabled', false);
            }
        });
    });

    // 实时验证
    $form.on('input', 'input, textarea', function() {
        var $field = $(this);
        $field.removeClass('error');
        $field.next('.error-message').remove();
    });
});
</script> 