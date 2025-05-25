<?php
if (!defined('ABSPATH')) {
    exit;
}

$user_id = get_current_user_id();
$user = get_userdata($user_id);
$verify_status = help_platform_get_verify_status($user_id);
$job_stats = help_platform_get_user_job_stats($user_id);
?>

<div class="help-platform-dashboard">
    <div class="dashboard-header">
        <div class="user-info">
            <div class="avatar">
                <?php echo get_avatar($user_id, 80); ?>
            </div>
            <div class="info">
                <h2><?php echo esc_html($user->display_name); ?></h2>
                <p class="user-meta">
                    <?php if ($verify_status['verified']): ?>
                        <span class="status verified">
                            <span class="dashicons dashicons-yes-alt"></span>
                            <?php _e('已实名认证', 'help-platform'); ?>
                        </span>
                    <?php else: ?>
                        <span class="status unverified">
                            <span class="dashicons dashicons-warning"></span>
                            <?php _e('未实名认证', 'help-platform'); ?>
                        </span>
                    <?php endif; ?>
                    <span class="join-date">
                        <span class="dashicons dashicons-calendar-alt"></span>
                        <?php printf(__('加入时间：%s', 'help-platform'), date_i18n(get_option('date_format'), strtotime($user->user_registered))); ?>
                    </span>
                </p>
            </div>
        </div>
        <div class="quick-actions">
            <a href="<?php echo esc_url(add_query_arg('tab', 'post-job', get_permalink())); ?>" class="button button-primary">
                <span class="dashicons dashicons-plus-alt"></span>
                <?php _e('发布任务', 'help-platform'); ?>
            </a>
            <?php if (!$verify_status['verified']): ?>
                <a href="<?php echo esc_url(add_query_arg('tab', 'verify', get_permalink())); ?>" class="button">
                    <span class="dashicons dashicons-id"></span>
                    <?php _e('实名认证', 'help-platform'); ?>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="dashboard-stats">
        <div class="stat-card">
            <div class="stat-icon">
                <span class="dashicons dashicons-list-view"></span>
            </div>
            <div class="stat-content">
                <h3><?php _e('发布任务', 'help-platform'); ?></h3>
                <div class="stat-number"><?php echo esc_html($job_stats['total']); ?></div>
                <div class="stat-detail">
                    <span class="pending"><?php echo esc_html($job_stats['pending']); ?> <?php _e('待审核', 'help-platform'); ?></span>
                    <span class="active"><?php echo esc_html($job_stats['active']); ?> <?php _e('进行中', 'help-platform'); ?></span>
                    <span class="completed"><?php echo esc_html($job_stats['completed']); ?> <?php _e('已完成', 'help-platform'); ?></span>
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <span class="dashicons dashicons-money-alt"></span>
            </div>
            <div class="stat-content">
                <h3><?php _e('账户余额', 'help-platform'); ?></h3>
                <div class="stat-number"><?php echo help_platform_format_money(help_platform_get_user_balance($user_id)); ?></div>
                <div class="stat-actions">
                    <a href="<?php echo esc_url(add_query_arg('tab', 'wallet', get_permalink())); ?>" class="button">
                        <?php _e('充值', 'help-platform'); ?>
                    </a>
                    <a href="<?php echo esc_url(add_query_arg('tab', 'transactions', get_permalink())); ?>" class="button">
                        <?php _e('交易记录', 'help-platform'); ?>
                    </a>
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <span class="dashicons dashicons-star-filled"></span>
            </div>
            <div class="stat-content">
                <h3><?php _e('信用评分', 'help-platform'); ?></h3>
                <div class="stat-number"><?php echo help_platform_get_user_rating($user_id); ?></div>
                <div class="rating-stars">
                    <?php help_platform_display_rating_stars(help_platform_get_user_rating($user_id)); ?>
                </div>
            </div>
        </div>
    </div>

    <div class="dashboard-tabs">
        <div class="tab-nav">
            <a href="#my-jobs" class="tab-link active"><?php _e('我的任务', 'help-platform'); ?></a>
            <a href="#my-bids" class="tab-link"><?php _e('我的投标', 'help-platform'); ?></a>
            <a href="#my-messages" class="tab-link"><?php _e('消息中心', 'help-platform'); ?></a>
            <a href="#my-settings" class="tab-link"><?php _e('账号设置', 'help-platform'); ?></a>
        </div>

        <div class="tab-content">
            <div id="my-jobs" class="tab-pane active">
                <?php
                $jobs = help_platform_get_user_jobs($user_id, array(
                    'posts_per_page' => 10,
                    'paged' => get_query_var('paged') ? get_query_var('paged') : 1
                ));

                if ($jobs->have_posts()):
                    while ($jobs->have_posts()): $jobs->the_post();
                        get_template_part('templates/job-item');
                    endwhile;
                    wp_reset_postdata();

                    // 分页
                    echo '<div class="pagination">';
                    echo paginate_links(array(
                        'total' => $jobs->max_num_pages,
                        'current' => get_query_var('paged') ? get_query_var('paged') : 1,
                        'prev_text' => '&laquo;',
                        'next_text' => '&raquo;',
                    ));
                    echo '</div>';
                else:
                    echo '<div class="no-items">';
                    echo '<p>' . __('您还没有发布过任务。', 'help-platform') . '</p>';
                    echo '<a href="' . esc_url(add_query_arg('tab', 'post-job', get_permalink())) . '" class="button">' . __('发布任务', 'help-platform') . '</a>';
                    echo '</div>';
                endif;
                ?>
            </div>

            <div id="my-bids" class="tab-pane">
                <?php
                $bids = help_platform_get_user_bids($user_id, array(
                    'posts_per_page' => 10,
                    'paged' => get_query_var('paged') ? get_query_var('paged') : 1
                ));

                if ($bids->have_posts()):
                    while ($bids->have_posts()): $bids->the_post();
                        get_template_part('templates/bid-item');
                    endwhile;
                    wp_reset_postdata();

                    // 分页
                    echo '<div class="pagination">';
                    echo paginate_links(array(
                        'total' => $bids->max_num_pages,
                        'current' => get_query_var('paged') ? get_query_var('paged') : 1,
                        'prev_text' => '&laquo;',
                        'next_text' => '&raquo;',
                    ));
                    echo '</div>';
                else:
                    echo '<div class="no-items">';
                    echo '<p>' . __('您还没有投标记录。', 'help-platform') . '</p>';
                    echo '</div>';
                endif;
                ?>
            </div>

            <div id="my-messages" class="tab-pane">
                <?php
                $messages = help_platform_get_user_messages($user_id, array(
                    'posts_per_page' => 20,
                    'paged' => get_query_var('paged') ? get_query_var('paged') : 1
                ));

                if ($messages->have_posts()):
                    while ($messages->have_posts()): $messages->the_post();
                        get_template_part('templates/message-item');
                    endwhile;
                    wp_reset_postdata();

                    // 分页
                    echo '<div class="pagination">';
                    echo paginate_links(array(
                        'total' => $messages->max_num_pages,
                        'current' => get_query_var('paged') ? get_query_var('paged') : 1,
                        'prev_text' => '&laquo;',
                        'next_text' => '&raquo;',
                    ));
                    echo '</div>';
                else:
                    echo '<div class="no-items">';
                    echo '<p>' . __('暂无消息。', 'help-platform') . '</p>';
                    echo '</div>';
                endif;
                ?>
            </div>

            <div id="my-settings" class="tab-pane">
                <form id="user-settings-form" method="post">
                    <?php wp_nonce_field('help_platform_user_settings', 'help_platform_user_settings_nonce'); ?>
                    <input type="hidden" name="action" value="update_user_settings">

                    <div class="form-group">
                        <label for="display_name"><?php _e('显示名称', 'help-platform'); ?></label>
                        <input type="text" id="display_name" name="display_name" value="<?php echo esc_attr($user->display_name); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="user_email"><?php _e('电子邮箱', 'help-platform'); ?></label>
                        <input type="email" id="user_email" name="user_email" value="<?php echo esc_attr($user->user_email); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="user_phone"><?php _e('手机号码', 'help-platform'); ?></label>
                        <input type="tel" id="user_phone" name="user_phone" value="<?php echo esc_attr(get_user_meta($user_id, 'phone', true)); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="user_address"><?php _e('联系地址', 'help-platform'); ?></label>
                        <textarea id="user_address" name="user_address" required><?php echo esc_textarea(get_user_meta($user_id, 'address', true)); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="current_password"><?php _e('当前密码', 'help-platform'); ?></label>
                        <input type="password" id="current_password" name="current_password">
                        <p class="description"><?php _e('如需修改密码，请填写当前密码', 'help-platform'); ?></p>
                    </div>

                    <div class="form-group password-fields" style="display: none;">
                        <label for="new_password"><?php _e('新密码', 'help-platform'); ?></label>
                        <input type="password" id="new_password" name="new_password">
                    </div>

                    <div class="form-group password-fields" style="display: none;">
                        <label for="confirm_password"><?php _e('确认新密码', 'help-platform'); ?></label>
                        <input type="password" id="confirm_password" name="confirm_password">
                    </div>

                    <div class="form-group">
                        <button type="submit" class="button button-primary">
                            <?php _e('保存设置', 'help-platform'); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.help-platform-dashboard {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding: 20px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.user-info {
    display: flex;
    align-items: center;
    gap: 20px;
}

.avatar img {
    border-radius: 50%;
    border: 3px solid #fff;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.info h2 {
    margin: 0 0 10px;
    font-size: 24px;
    color: #333;
}

.user-meta {
    display: flex;
    gap: 15px;
    color: #666;
    font-size: 14px;
}

.user-meta .status {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 8px;
    border-radius: 4px;
    font-weight: 500;
}

.status.verified {
    background: #e8f5e9;
    color: #2e7d32;
}

.status.unverified {
    background: #fff3e0;
    color: #ef6c00;
}

.quick-actions {
    display: flex;
    gap: 10px;
}

.quick-actions .button {
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.dashboard-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: #fff;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    display: flex;
    gap: 20px;
}

.stat-icon {
    width: 60px;
    height: 60px;
    background: #f8f9fa;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.stat-icon .dashicons {
    font-size: 30px;
    width: 30px;
    height: 30px;
    color: #2196f3;
}

.stat-content {
    flex: 1;
}

.stat-content h3 {
    margin: 0 0 10px;
    font-size: 16px;
    color: #666;
}

.stat-number {
    font-size: 24px;
    font-weight: 600;
    color: #333;
    margin-bottom: 10px;
}

.stat-detail {
    display: flex;
    gap: 15px;
    font-size: 13px;
    color: #666;
}

.stat-detail span {
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.stat-detail .pending { color: #ff9800; }
.stat-detail .active { color: #4caf50; }
.stat-detail .completed { color: #2196f3; }

.stat-actions {
    display: flex;
    gap: 10px;
}

.stat-actions .button {
    padding: 6px 12px;
    font-size: 13px;
}

.rating-stars {
    color: #ffc107;
    font-size: 18px;
}

.dashboard-tabs {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    overflow: hidden;
}

.tab-nav {
    display: flex;
    border-bottom: 1px solid #eee;
    background: #f8f9fa;
}

.tab-link {
    padding: 15px 20px;
    color: #666;
    text-decoration: none;
    font-weight: 500;
    border-bottom: 2px solid transparent;
    transition: all 0.3s ease;
}

.tab-link:hover {
    color: #2196f3;
    background: #fff;
}

.tab-link.active {
    color: #2196f3;
    border-bottom-color: #2196f3;
    background: #fff;
}

.tab-content {
    padding: 20px;
}

.tab-pane {
    display: none;
}

.tab-pane.active {
    display: block;
}

.no-items {
    text-align: center;
    padding: 40px 20px;
    color: #666;
}

.no-items p {
    margin-bottom: 20px;
}

.pagination {
    margin-top: 20px;
    text-align: center;
}

.pagination .page-numbers {
    display: inline-block;
    padding: 8px 12px;
    margin: 0 5px;
    border: 1px solid #ddd;
    border-radius: 4px;
    color: #666;
    text-decoration: none;
    transition: all 0.3s ease;
}

.pagination .page-numbers.current {
    background: #2196f3;
    border-color: #2196f3;
    color: #fff;
}

.pagination .page-numbers:hover:not(.current) {
    background: #f8f9fa;
    border-color: #2196f3;
    color: #2196f3;
}

@media screen and (max-width: 768px) {
    .dashboard-header {
        flex-direction: column;
        gap: 20px;
        text-align: center;
    }

    .user-info {
        flex-direction: column;
    }

    .quick-actions {
        width: 100%;
        justify-content: center;
    }

    .dashboard-stats {
        grid-template-columns: 1fr;
    }

    .tab-nav {
        flex-wrap: wrap;
    }

    .tab-link {
        flex: 1;
        text-align: center;
        padding: 12px;
        font-size: 14px;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // 标签页切换
    $('.tab-link').on('click', function(e) {
        e.preventDefault();
        var target = $(this).attr('href');
        
        $('.tab-link').removeClass('active');
        $(this).addClass('active');
        
        $('.tab-pane').removeClass('active');
        $(target).addClass('active');
    });

    // 密码修改
    $('#current_password').on('input', function() {
        if ($(this).val()) {
            $('.password-fields').slideDown();
        } else {
            $('.password-fields').slideUp();
        }
    });

    // 设置表单提交
    $('#user-settings-form').on('submit', function(e) {
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
                    alert(response.data);
                    location.reload();
                } else {
                    alert(response.data);
                }
            },
            error: function() {
                alert(helpPlatform.i18n.submitError);
            },
            complete: function() {
                $submit.prop('disabled', false);
            }
        });
    });
});
</script> 