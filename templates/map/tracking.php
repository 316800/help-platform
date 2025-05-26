<?php
/**
 * 地图追踪页面模板
 */

if (!defined('ABSPATH')) {
    exit;
}

// 获取任务信息
$job_id = isset($_GET['job_id']) ? intval($_GET['job_id']) : 0;
if (!$job_id) {
    wp_die(__('无效的任务ID', 'help-platform'));
}

$job = get_post($job_id);
if (!$job || $job->post_type !== 'help_job') {
    wp_die(__('任务不存在', 'help-platform'));
}

// 获取工作者信息
$worker_id = get_post_meta($job_id, '_worker_id', true);
if (!$worker_id) {
    wp_die(__('该任务尚未分配工作者', 'help-platform'));
}

$worker = get_userdata($worker_id);

// 获取最新位置
global $wpdb;
$latest_location = $wpdb->get_row($wpdb->prepare(
    "SELECT *
    FROM {$wpdb->prefix}help_locations
    WHERE job_id = %d
    ORDER BY created_at DESC
    LIMIT 1",
    $job_id
));

// 获取历史轨迹
$locations = $wpdb->get_results($wpdb->prepare(
    "SELECT *
    FROM {$wpdb->prefix}help_locations
    WHERE job_id = %d
    AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ORDER BY created_at ASC",
    $job_id
));

// 准备轨迹数据
$track_points = array();
if ($locations) {
    foreach ($locations as $location) {
        $track_points[] = array(
            'lat' => floatval($location->latitude),
            'lng' => floatval($location->longitude),
            'time' => strtotime($location->created_at),
            'speed' => floatval($location->speed),
            'heading' => floatval($location->heading)
        );
    }
}
?>

<div class="wrap help-platform-map">
    <div class="map-container">
        <!-- 地图头部 -->
        <div class="map-header">
            <div class="map-title">
                <h2><?php printf(__('追踪 %s 的位置', 'help-platform'), esc_html($worker->display_name)); ?></h2>
                <div class="job-info">
                    <?php printf(__('任务：%s', 'help-platform'), esc_html($job->post_title)); ?>
                </div>
            </div>
            <div class="map-actions">
                <button type="button" class="button refresh-location">
                    <span class="dashicons dashicons-update"></span>
                    <?php _e('刷新', 'help-platform'); ?>
                </button>
                <button type="button" class="button button-primary toggle-track">
                    <span class="dashicons dashicons-visibility"></span>
                    <span class="track-text"><?php _e('显示轨迹', 'help-platform'); ?></span>
                </button>
            </div>
        </div>

        <!-- 地图主体 -->
        <div id="map" class="map-body"></div>

        <!-- 地图信息 -->
        <div class="map-info">
            <div class="info-item">
                <span class="label"><?php _e('最后更新：', 'help-platform'); ?></span>
                <span class="value" id="lastUpdate">
                    <?php echo $latest_location ? date_i18n('Y-m-d H:i:s', strtotime($latest_location->created_at)) : __('暂无数据', 'help-platform'); ?>
                </span>
            </div>
            <div class="info-item">
                <span class="label"><?php _e('当前速度：', 'help-platform'); ?></span>
                <span class="value" id="currentSpeed">
                    <?php echo $latest_location ? sprintf(__('%.1f km/h', 'help-platform'), $latest_location->speed) : __('暂无数据', 'help-platform'); ?>
                </span>
            </div>
            <div class="info-item">
                <span class="label"><?php _e('方向：', 'help-platform'); ?></span>
                <span class="value" id="currentHeading">
                    <?php echo $latest_location ? sprintf(__('%.1f°', 'help-platform'), $latest_location->heading) : __('暂无数据', 'help-platform'); ?>
                </span>
            </div>
        </div>
    </div>
</div>

<style>
.help-platform-map {
    margin: 20px;
}

.map-container {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,.1);
    display: flex;
    flex-direction: column;
    height: calc(100vh - 100px);
}

.map-header {
    padding: 20px;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.map-title h2 {
    margin: 0;
    font-size: 18px;
}

.job-info {
    color: #666;
    font-size: 14px;
    margin-top: 5px;
}

.map-actions {
    display: flex;
    gap: 10px;
}

.map-body {
    flex: 1;
    min-height: 500px;
    position: relative;
}

.map-info {
    padding: 15px 20px;
    border-top: 1px solid #eee;
    display: flex;
    gap: 20px;
    background: #f8f9fa;
}

.info-item {
    display: flex;
    align-items: center;
    gap: 5px;
}

.info-item .label {
    color: #666;
    font-size: 13px;
}

.info-item .value {
    font-weight: 500;
    color: #333;
}

.refresh-location,
.toggle-track {
    display: flex;
    align-items: center;
    gap: 5px;
}

.refresh-location .dashicons,
.toggle-track .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}

.toggle-track.active {
    background: #2271b1;
    border-color: #2271b1;
    color: #fff;
}
</style>

<script>
jQuery(document).ready(function($) {
    var map;
    var marker;
    var trackPolyline;
    var isTrackingVisible = false;
    var trackPoints = <?php echo json_encode($track_points); ?>;
    var lastUpdate = $('#lastUpdate');
    var currentSpeed = $('#currentSpeed');
    var currentHeading = $('#currentHeading');

    // 初始化地图
    function initMap() {
        var defaultLocation = { lat: 39.9042, lng: 116.4074 }; // 默认位置（北京）
        var initialLocation = trackPoints.length > 0 ? 
            { lat: trackPoints[trackPoints.length - 1].lat, lng: trackPoints[trackPoints.length - 1].lng } : 
            defaultLocation;

        map = new google.maps.Map(document.getElementById('map'), {
            center: initialLocation,
            zoom: 15,
            mapTypeId: 'roadmap',
            mapTypeControl: false,
            streetViewControl: false,
            fullscreenControl: false
        });

        // 创建标记
        marker = new google.maps.Marker({
            position: initialLocation,
            map: map,
            icon: {
                path: google.maps.SymbolPath.CIRCLE,
                scale: 8,
                fillColor: '#2196F3',
                fillOpacity: 1,
                strokeColor: '#fff',
                strokeWeight: 2
            }
        });

        // 创建轨迹线
        trackPolyline = new google.maps.Polyline({
            path: trackPoints.map(function(point) {
                return { lat: point.lat, lng: point.lng };
            }),
            geodesic: true,
            strokeColor: '#2196F3',
            strokeOpacity: 0.8,
            strokeWeight: 3,
            map: isTrackingVisible ? map : null
        });

        // 添加方向指示器
        if (trackPoints.length > 0) {
            var lastPoint = trackPoints[trackPoints.length - 1];
            var heading = lastPoint.heading;
            var headingIcon = {
                path: google.maps.SymbolPath.FORWARD_CLOSED_ARROW,
                scale: 3,
                fillColor: '#2196F3',
                fillOpacity: 1,
                strokeColor: '#fff',
                strokeWeight: 1,
                rotation: heading
            };
            marker.setIcon(headingIcon);
        }
    }

    // 更新位置
    function updateLocation(location) {
        if (!location) return;

        var position = { lat: parseFloat(location.latitude), lng: parseFloat(location.longitude) };
        marker.setPosition(position);
        map.panTo(position);

        // 更新方向
        var headingIcon = {
            path: google.maps.SymbolPath.FORWARD_CLOSED_ARROW,
            scale: 3,
            fillColor: '#2196F3',
            fillOpacity: 1,
            strokeColor: '#fff',
            strokeWeight: 1,
            rotation: parseFloat(location.heading)
        };
        marker.setIcon(headingIcon);

        // 更新信息
        lastUpdate.text(moment(location.created_at).format('YYYY-MM-DD HH:mm:ss'));
        currentSpeed.text(location.speed + ' km/h');
        currentHeading.text(location.heading + '°');

        // 更新轨迹
        trackPoints.push({
            lat: parseFloat(location.latitude),
            lng: parseFloat(location.longitude),
            time: moment(location.created_at).unix(),
            speed: parseFloat(location.speed),
            heading: parseFloat(location.heading)
        });

        if (isTrackingVisible) {
            trackPolyline.setPath(trackPoints.map(function(point) {
                return { lat: point.lat, lng: point.lng };
            }));
        }
    }

    // 刷新位置
    function refreshLocation() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'help_platform_get_location',
                job_id: <?php echo $job_id; ?>,
                _wpnonce: '<?php echo wp_create_nonce('help_platform_get_location'); ?>'
            },
            success: function(response) {
                if (response.success && response.data.location) {
                    updateLocation(response.data.location);
                }
            }
        });
    }

    // 切换轨迹显示
    $('.toggle-track').on('click', function() {
        isTrackingVisible = !isTrackingVisible;
        trackPolyline.setMap(isTrackingVisible ? map : null);
        $(this).toggleClass('active');
        $('.track-text').text(isTrackingVisible ? '<?php _e('隐藏轨迹', 'help-platform'); ?>' : '<?php _e('显示轨迹', 'help-platform'); ?>');
    });

    // 手动刷新
    $('.refresh-location').on('click', refreshLocation);

    // 定期刷新
    setInterval(refreshLocation, 30000);

    // 初始化地图
    initMap();
});
</script>

<!-- 加载 Google Maps API -->
<script src="https://maps.googleapis.com/maps/api/js?key=<?php echo esc_attr(get_option('help_platform_google_maps_api_key')); ?>&callback=initMap" async defer></script> 