<?php
/**
 * 任务发布表单模板
 */
?>
<div class="help-platform-job-form">
    <form id="help-job-form" method="post" enctype="multipart/form-data">
        <?php wp_nonce_field('help-platform-nonce', 'nonce'); ?>
        
        <div class="form-group">
            <label for="title"><?php _e('任务标题', 'help-platform'); ?> <span class="required">*</span></label>
            <input type="text" id="title" name="title" required>
        </div>

        <div class="form-group">
            <label for="description"><?php _e('任务描述', 'help-platform'); ?> <span class="required">*</span></label>
            <textarea id="description" name="description" rows="5" required></textarea>
        </div>

        <div class="form-group">
            <label><?php _e('任务位置', 'help-platform'); ?></label>
            <div id="map" style="height: 300px; margin-bottom: 10px;"></div>
            <input type="hidden" id="latitude" name="latitude">
            <input type="hidden" id="longitude" name="longitude">
            <button type="button" id="get-location" class="button"><?php _e('获取当前位置', 'help-platform'); ?></button>
            <small class="form-text"><?php _e('点击地图选择位置，或点击按钮获取当前位置', 'help-platform'); ?></small>
        </div>

        <div class="form-group">
            <label for="job_images"><?php _e('任务图片', 'help-platform'); ?></label>
            <input type="file" id="job_images" name="job_images[]" accept="image/*" multiple>
            <small class="form-text"><?php _e('可以选择多张图片，第一张将作为任务封面', 'help-platform'); ?></small>
            <div id="job_images_preview" class="image-preview"></div>
        </div>

        <div class="form-group">
            <button type="submit" class="button button-primary"><?php _e('发布任务', 'help-platform'); ?></button>
        </div>

        <div id="job-message" class="message"></div>
    </form>
</div>

<script>
var map;
var marker;
var defaultLocation = {lat: 39.9042, lng: 116.4074}; // 默认位置（北京）

function initMap() {
    map = new google.maps.Map(document.getElementById('map'), {
        center: defaultLocation,
        zoom: 13
    });

    // 点击地图添加标记
    map.addListener('click', function(e) {
        placeMarker(e.latLng);
    });

    // 获取当前位置按钮
    document.getElementById('get-location').addEventListener('click', function() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position) {
                var pos = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude
                };
                map.setCenter(pos);
                placeMarker(pos);
            }, function() {
                alert(helpPlatform.i18n.locationError);
            });
        } else {
            alert(helpPlatform.i18n.locationError);
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
            draggable: true
        });
    }

    // 更新隐藏输入框的值
    document.getElementById('latitude').value = location.lat();
    document.getElementById('longitude').value = location.lng();

    // 拖动标记时更新位置
    marker.addListener('dragend', function(e) {
        document.getElementById('latitude').value = e.latLng.lat();
        document.getElementById('longitude').value = e.latLng.lng();
    });
}

jQuery(document).ready(function($) {
    // 图片预览
    $('#job_images').change(function() {
        var preview = $('#job_images_preview');
        preview.empty();

        if (this.files) {
            for (var i = 0; i < this.files.length; i++) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    preview.append('<div class="preview-item"><img src="' + e.target.result + '" style="max-width: 150px; margin: 5px;"></div>');
                }
                reader.readAsDataURL(this.files[i]);
            }
        }
    });

    // 表单提交
    $('#help-job-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $submit = $form.find('button[type="submit"]');
        var $message = $('#job-message');
        
        $submit.prop('disabled', true);
        $message.removeClass('success error').html('');

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
                    $message.addClass('success').html(response.data);
                    $form[0].reset();
                    $('#job_images_preview').empty();
                    if (marker) {
                        marker.setMap(null);
                        marker = null;
                    }
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