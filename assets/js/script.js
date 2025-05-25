jQuery(document).ready(function($) {
    'use strict';

    // 通用 AJAX 处理函数
    function handleAjaxRequest(url, data, successCallback, errorCallback) {
        $.ajax({
            url: url,
            type: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    if (successCallback) {
                        successCallback(response);
                    }
                } else {
                    if (errorCallback) {
                        errorCallback(response);
                    }
                }
            },
            error: function(xhr, status, error) {
                if (errorCallback) {
                    errorCallback({
                        success: false,
                        message: '请求失败，请稍后重试'
                    });
                }
            }
        });
    }

    // 表单验证函数
    function validateForm(form) {
        var isValid = true;
        form.find('[required]').each(function() {
            if (!$(this).val()) {
                isValid = false;
                $(this).addClass('error');
            } else {
                $(this).removeClass('error');
            }
        });
        return isValid;
    }

    // 显示消息提示
    function showMessage(message, type) {
        var messageClass = type === 'error' ? 'error' : 'success';
        var $message = $('<div class="help-platform-message ' + messageClass + '">' + message + '</div>');
        $('body').append($message);
        setTimeout(function() {
            $message.fadeOut(function() {
                $(this).remove();
            });
        }, 3000);
    }

    // 初始化所有表单
    $('.help-platform-form').each(function() {
        var $form = $(this);
        
        $form.on('submit', function(e) {
            e.preventDefault();
            
            if (!validateForm($form)) {
                showMessage('请填写所有必填字段', 'error');
                return;
            }

            var formData = new FormData(this);
            formData.append('action', $form.data('action'));
            formData.append('nonce', helpPlatform.nonce);

            handleAjaxRequest(
                helpPlatform.ajaxurl,
                formData,
                function(response) {
                    showMessage(response.message, 'success');
                    if (response.redirect) {
                        window.location.href = response.redirect;
                    }
                },
                function(response) {
                    showMessage(response.message || '操作失败，请稍后重试', 'error');
                }
            );
        });
    });

    // 文件上传处理
    $('.help-platform-file-upload').on('change', function() {
        var $input = $(this);
        var $preview = $input.siblings('.file-preview');
        var file = this.files[0];
        
        if (file) {
            if (file.type.match('image.*')) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    $preview.html('<img src="' + e.target.result + '" alt="预览图">');
                };
                reader.readAsDataURL(file);
            } else {
                $preview.html('<span class="file-name">' + file.name + '</span>');
            }
        }
    });

    // 移动端菜单切换
    $('.help-platform-mobile-menu-toggle').on('click', function() {
        $('.help-platform-mobile-menu').toggleClass('active');
    });

    // 响应式处理
    function handleResponsive() {
        if (window.innerWidth <= 768) {
            $('body').addClass('help-platform-mobile');
        } else {
            $('body').removeClass('help-platform-mobile');
        }
    }

    // 监听窗口大小变化
    $(window).on('resize', handleResponsive);
    handleResponsive();

    // 实名认证表单提交
    $('#help-platform-verify-form').on('submit', function(e) {
        e.preventDefault();
        var $form = $(this);
        var $submit = $form.find('button[type="submit"]');
        
        $submit.prop('disabled', true);
        
        $.ajax({
            url: helpPlatform.ajaxurl,
            type: 'POST',
            data: $form.serialize(),
            success: function(response) {
                if (response.success) {
                    $form.html('<div class="help-platform-message success">' + response.data + '</div>');
                } else {
                    $form.prepend('<div class="help-platform-message error">' + response.data + '</div>');
                    $submit.prop('disabled', false);
                }
            },
            error: function() {
                $form.prepend('<div class="help-platform-message error">' + helpPlatform.i18n.submitError + '</div>');
                $submit.prop('disabled', false);
            }
        });
    });

    // 任务发布表单提交
    $('#help-platform-job-form').on('submit', function(e) {
        e.preventDefault();
        var $form = $(this);
        var $submit = $form.find('button[type="submit"]');
        
        $submit.prop('disabled', true);
        
        $.ajax({
            url: helpPlatform.ajaxurl,
            type: 'POST',
            data: $form.serialize(),
            success: function(response) {
                if (response.success) {
                    $form.html('<div class="help-platform-message success">' + response.data + '</div>');
                } else {
                    $form.prepend('<div class="help-platform-message error">' + response.data + '</div>');
                    $submit.prop('disabled', false);
                }
            },
            error: function() {
                $form.prepend('<div class="help-platform-message error">' + helpPlatform.i18n.submitError + '</div>');
                $submit.prop('disabled', false);
            }
        });
    });

    // 添加表单验证
    $('.help-platform-verify-form, .help-platform-job-form').on('input', '[required]', function() {
        if ($(this).val()) {
            $(this).removeClass('error');
        }
    });
}); 