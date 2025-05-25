<?php
if (!defined('ABSPATH')) {
    exit;
}
/**
 * 任务发布表单模板
 */
?>
<div class="help-platform-job">
    <div class="form-header">
        <h2><?php _e('发布任务', 'help-platform'); ?></h2>
        <a href="<?php echo esc_url(add_query_arg('help', 'job')); ?>" class="help-button" target="_blank">
            <span class="dashicons dashicons-editor-help"></span>
            <?php _e('帮助', 'help-platform'); ?>
        </a>
    </div>
    
    <form id="help-platform-job-form" method="post">
        <?php wp_nonce_field('help_platform_nonce', 'help_platform_nonce'); ?>
        <input type="hidden" name="action" value="submit_job">

        <div class="form-group">
            <label for="title"><?php _e('任务标题', 'help-platform'); ?> <span class="required">*</span></label>
            <input type="text" id="title" name="title" required 
                   placeholder="<?php esc_attr_e('请输入任务标题，5-50个字符', 'help-platform'); ?>"
                   minlength="5" maxlength="50">
            <p class="description"><?php _e('请输入清晰的任务标题，5-50个字符', 'help-platform'); ?></p>
        </div>

        <div class="form-group">
            <label for="content"><?php _e('任务描述', 'help-platform'); ?> <span class="required">*</span></label>
            <textarea id="content" name="content" required 
                      placeholder="<?php esc_attr_e('请详细描述任务内容、要求和注意事项', 'help-platform'); ?>"
                      minlength="20" maxlength="2000"></textarea>
            <p class="description"><?php _e('请详细描述任务内容，20-2000个字符', 'help-platform'); ?></p>
            <div class="word-count">
                <span class="current">0</span>/<span class="max">2000</span> <?php _e('字符', 'help-platform'); ?>
            </div>
        </div>

        <div class="form-group">
            <label for="budget"><?php _e('任务预算', 'help-platform'); ?> <span class="required">*</span></label>
            <div class="budget-input">
                <input type="number" id="budget" name="budget" required 
                       min="1" max="100000" step="0.01"
                       placeholder="<?php esc_attr_e('请输入任务预算金额', 'help-platform'); ?>">
                <span class="currency"><?php _e('元', 'help-platform'); ?></span>
            </div>
            <p class="description"><?php _e('请输入合理的任务预算金额，1-100000元', 'help-platform'); ?></p>
        </div>

        <div class="form-group">
            <label for="location"><?php _e('任务地点', 'help-platform'); ?> <span class="required">*</span></label>
            <div class="location-input">
                <input type="text" id="location" name="location" required 
                       placeholder="<?php esc_attr_e('请输入或选择任务地点', 'help-platform'); ?>">
                <button type="button" id="get-location" class="button">
                    <span class="dashicons dashicons-location"></span>
                    <?php _e('获取当前位置', 'help-platform'); ?>
                </button>
            </div>
            <div id="map" style="height: 200px; margin-top: 10px;"></div>
            <input type="hidden" id="latitude" name="latitude">
            <input type="hidden" id="longitude" name="longitude">
            <p class="description"><?php _e('请选择或输入详细的任务地点', 'help-platform'); ?></p>
        </div>

        <div class="form-group">
            <label for="deadline"><?php _e('截止日期', 'help-platform'); ?> <span class="required">*</span></label>
            <input type="date" id="deadline" name="deadline" required 
                   min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>"
                   max="<?php echo date('Y-m-d', strtotime('+90 days')); ?>">
            <p class="description"><?php _e('请选择任务截止日期，1-90天内', 'help-platform'); ?></p>
        </div>

        <div class="form-group">
            <label><?php _e('任务图片', 'help-platform'); ?></label>
            <div class="image-upload">
                <input type="file" id="job_images" name="job_images[]" multiple 
                       accept="image/*" class="hidden">
                <button type="button" class="button upload-button">
                    <span class="dashicons dashicons-format-image"></span>
                    <?php _e('选择图片', 'help-platform'); ?>
                </button>
                <p class="description"><?php _e('可选，最多上传5张图片，每张不超过2MB', 'help-platform'); ?></p>
            </div>
            <div id="job_images_preview" class="image-preview"></div>
        </div>

        <div class="form-group">
            <button type="submit" class="button button-primary">
                <span class="button-text"><?php _e('发布任务', 'help-platform'); ?></span>
                <span class="button-loading" style="display: none;">
                    <span class="spinner"></span>
                    <?php _e('发布中...', 'help-platform'); ?>
                </span>
            </button>
        </div>

        <div class="form-notice">
            <p><strong><?php _e('温馨提示：', 'help-platform'); ?></strong></p>
            <ul>
                <li><?php _e('请确保任务描述清晰完整，便于接单者理解。', 'help-platform'); ?></li>
                <li><?php _e('任务预算请根据市场行情合理设置。', 'help-platform'); ?></li>
                <li><?php _e('任务发布后，我们将在24小时内完成审核。', 'help-platform'); ?></li>
                <li><?php _e('如有疑问，请联系客服。', 'help-platform'); ?></li>
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

.budget-input {
    position: relative;
    display: flex;
    align-items: center;
}

.budget-input .currency {
    position: absolute;
    left: 12px;
    color: #666;
}

.budget-input input {
    padding-left: 30px !important;
}

.location-input {
    display: flex;
    gap: 10px;
}

.location-input input {
    flex: 1;
}

.location-input .button {
    white-space: nowrap;
}

.image-upload {
    margin-bottom: 10px;
}

.image-upload .hidden {
    display: none;
}

.image-upload .upload-button {
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.image-preview {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 10px;
    margin-top: 10px;
}

.image-preview .preview-item {
    position: relative;
    aspect-ratio: 1;
    border-radius: 8px;
    overflow: hidden;
}

.image-preview .preview-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.image-preview .preview-item .remove {
    position: absolute;
    top: 5px;
    right: 5px;
    background: rgba(0,0,0,0.5);
    color: #fff;
    border: none;
    border-radius: 50%;
    width: 24px;
    height: 24px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}

.word-count {
    margin-top: 5px;
    text-align: right;
    color: #666;
    font-size: 13px;
}

.word-count .current {
    color: #2196f3;
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

@media screen and (max-width: 768px) {
    .location-input {
        flex-direction: column;
    }
    
    .location-input .button {
        width: 100%;
        margin-top: 10px;
    }
}

.help-platform-job .form-header {
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
    var $form = $('#help-platform-job-form');
    var $submit = $form.find('button[type="submit"]');
    var $buttonText = $submit.find('.button-text');
    var $buttonLoading = $submit.find('.button-loading');
    var $content = $('#content');
    var $wordCount = $('.word-count .current');

    // 字数统计
    $content.on('input', function() {
        var length = $(this).val().length;
        $wordCount.text(length);
        if (length > 2000) {
            $(this).addClass('error');
        } else {
            $(this).removeClass('error');
        }
    });

    // 图片上传
    $('.upload-button').on('click', function() {
        $('#job_images').click();
    });

    $('#job_images').on('change', function() {
        var files = this.files;
        var $preview = $('#job_images_preview');
        
        if (files.length > 5) {
            alert(helpPlatform.i18n.maxImages);
            this.value = '';
            return;
        }

        $preview.empty();
        
        for (var i = 0; i < files.length; i++) {
            var file = files[i];
            
            if (file.size > 2 * 1024 * 1024) {
                alert(helpPlatform.i18n.maxFileSize);
                continue;
            }

            if (!file.type.match('image.*')) {
                continue;
            }

            var reader = new FileReader();
            reader.onload = (function(file) {
                return function(e) {
                    var $item = $('<div class="preview-item">' +
                        '<img src="' + e.target.result + '" alt="预览图">' +
                        '<button type="button" class="remove" title="删除">&times;</button>' +
                        '</div>');
                    
                    $preview.append($item);
                };
            })(file);
            
            reader.readAsDataURL(file);
        }
    });

    // 删除图片
    $(document).on('click', '.preview-item .remove', function() {
        $(this).parent().remove();
    });

    // 表单验证
    $form.on('submit', function(e) {
        e.preventDefault();
        
        var isValid = true;
        var $title = $('#title');
        var $content = $('#content');
        var $budget = $('#budget');
        var $location = $('#location');
        var $deadline = $('#deadline');

        // 清除之前的错误提示
        $('.error-message').remove();
        $('.error').removeClass('error');

        // 验证标题
        if ($title.val().length < 5 || $title.val().length > 50) {
            $title.addClass('error').after('<div class="error-message"><?php _e('标题长度应为5-50个字符', 'help-platform'); ?></div>');
            isValid = false;
        }

        // 验证内容
        if ($content.val().length < 20 || $content.val().length > 2000) {
            $content.addClass('error').after('<div class="error-message"><?php _e('内容长度应为20-2000个字符', 'help-platform'); ?></div>');
            isValid = false;
        }

        // 验证预算
        var budget = parseFloat($budget.val());
        if (isNaN(budget) || budget < 1 || budget > 100000) {
            $budget.addClass('error').after('<div class="error-message"><?php _e('预算金额应为1-100000元', 'help-platform'); ?></div>');
            isValid = false;
        }

        // 验证地点
        if (!$location.val()) {
            $location.addClass('error').after('<div class="error-message"><?php _e('请选择或输入任务地点', 'help-platform'); ?></div>');
            isValid = false;
        }

        // 验证截止日期
        var deadline = new Date($deadline.val());
        var minDate = new Date();
        minDate.setDate(minDate.getDate() + 1);
        var maxDate = new Date();
        maxDate.setDate(maxDate.getDate() + 90);
        
        if (deadline < minDate || deadline > maxDate) {
            $deadline.addClass('error').after('<div class="error-message"><?php _e('截止日期应为1-90天内', 'help-platform'); ?></div>');
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
        var formData = new FormData(this);
        formData.append('action', 'help_platform_submit_job');

        $.ajax({
            url: helpPlatform.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
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

    // 初始化地图
    if (typeof google !== 'undefined' && google.maps) {
        initMap();
    } else {
        // 动态加载 Google Maps API
        var script = document.createElement('script');
        script.src = 'https://maps.googleapis.com/maps/api/js?key=' + helpPlatform.googleMapsApiKey + '&callback=initMap';
        script.async = true;
        script.defer = true;
        document.head.appendChild(script);
    }
});

// 地图相关函数
var map, marker, geocoder;

function initMap() {
    var defaultLocation = {lat: 39.9042, lng: 116.4074}; // 默认位置（北京）
    
    map = new google.maps.Map(document.getElementById('map'), {
        center: defaultLocation,
        zoom: 13,
        styles: [
            {
                "featureType": "all",
                "elementType": "geometry",
                "stylers": [{"color": "#f5f5f5"}]
            },
            {
                "featureType": "water",
                "elementType": "geometry",
                "stylers": [{"color": "#e9e9e9"}, {"lightness": 17}]
            }
        ]
    });

    geocoder = new google.maps.Geocoder();
    var $location = $('#location');
    var $latitude = $('#latitude');
    var $longitude = $('#longitude');

    // 点击地图添加标记
    map.addListener('click', function(e) {
        placeMarker(e.latLng);
        updateLocation(e.latLng);
    });

    // 获取当前位置
    $('#get-location').on('click', function() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position) {
                var pos = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude
                };
                map.setCenter(pos);
                placeMarker(pos);
                updateLocation(pos);
            }, function() {
                alert(helpPlatform.i18n.locationError);
            });
        } else {
            alert(helpPlatform.i18n.locationNotSupported);
        }
    });

    // 输入地址搜索
    $location.on('change', function() {
        var address = $(this).val();
        if (address) {
            geocoder.geocode({address: address}, function(results, status) {
                if (status === 'OK') {
                    var location = results[0].geometry.location;
                    map.setCenter(location);
                    placeMarker(location);
                    updateLocation(location);
                } else {
                    alert(helpPlatform.i18n.geocodeError);
                }
            });
        }
    });
}

function placeMarker(location) {
    if (marker) {
        marker.setPosition(location);
    } else {
        marker = new google.maps.Marker({
            position: location,
            map: map,
            draggable: true,
            animation: google.maps.Animation.DROP
        });

        marker.addListener('dragend', function(e) {
            updateLocation(e.latLng);
        });
    }
}

function updateLocation(location) {
    $('#latitude').val(location.lat());
    $('#longitude').val(location.lng());
    
    // 反向地理编码
    geocoder.geocode({location: location}, function(results, status) {
        if (status === 'OK' && results[0]) {
            $('#location').val(results[0].formatted_address);
        }
    });
}
</script> 