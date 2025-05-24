<?php
/**
 * 实名认证表单模板
 */
?>
<div class="help-platform-verify-form">
    <form id="help-verify-form" method="post" enctype="multipart/form-data">
        <?php wp_nonce_field('help-platform-nonce', 'nonce'); ?>
        
        <div class="form-group">
            <label for="real_name"><?php _e('真实姓名', 'help-platform'); ?> <span class="required">*</span></label>
            <input type="text" id="real_name" name="real_name" required>
        </div>

        <div class="form-group">
            <label for="id_number"><?php _e('身份证号码', 'help-platform'); ?> <span class="required">*</span></label>
            <input type="text" id="id_number" name="id_number" required pattern="\d{17}[\dXx]">
            <small class="form-text"><?php _e('请输入18位身份证号码', 'help-platform'); ?></small>
        </div>

        <div class="form-group">
            <label for="id_photo"><?php _e('身份证照片', 'help-platform'); ?> <span class="required">*</span></label>
            <input type="file" id="id_photo" name="id_photo" accept="image/*" required>
            <small class="form-text"><?php _e('请上传清晰的身份证正反面照片', 'help-platform'); ?></small>
            <div id="id_photo_preview" class="image-preview"></div>
        </div>

        <div class="form-group">
            <label for="selfie_photo"><?php _e('自拍照片', 'help-platform'); ?></label>
            <input type="file" id="selfie_photo" name="selfie_photo" accept="image/*">
            <small class="form-text"><?php _e('请上传手持身份证的自拍照片（选填）', 'help-platform'); ?></small>
            <div id="selfie_photo_preview" class="image-preview"></div>
        </div>

        <div class="form-group">
            <button type="submit" class="button button-primary"><?php _e('提交认证', 'help-platform'); ?></button>
        </div>

        <div id="verify-message" class="message"></div>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    // 图片预览
    function previewImage(input, previewId) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                $('#' + previewId).html('<img src="' + e.target.result + '" style="max-width: 200px;">');
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    $('#id_photo').change(function() {
        previewImage(this, 'id_photo_preview');
    });

    $('#selfie_photo').change(function() {
        previewImage(this, 'selfie_photo_preview');
    });

    // 表单提交
    $('#help-verify-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $submit = $form.find('button[type="submit"]');
        var $message = $('#verify-message');
        
        $submit.prop('disabled', true);
        $message.removeClass('success error').html('');

        var formData = new FormData(this);
        formData.append('action', 'help_platform_submit_verify');

        $.ajax({
            url: helpPlatform.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $message.addClass('success').html(response.data);
                    $form[0].reset();
                    $('#id_photo_preview, #selfie_photo_preview').empty();
                } else {
                    $message.addClass('error').html(response.data);
                }
            },
            error: function() {
                $message.addClass('error').html(helpPlatform.i18n.submitError);
            },
            complete: function() {
                $submit.prop('disabled', false);
            }
        });
    });
});
</script> 