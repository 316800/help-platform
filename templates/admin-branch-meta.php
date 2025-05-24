<?php
/**
 * 分公司元数据管理模板
 */
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="help-platform-branch-meta">
    <div class="form-row">
        <label for="branch_country"><?php _e('国家/地区', 'help-platform'); ?></label>
        <select id="branch_country" name="branch_country" required>
            <option value=""><?php _e('请选择国家/地区', 'help-platform'); ?></option>
            <option value="CN" <?php selected($branch_data['country'], 'CN'); ?>><?php _e('中国', 'help-platform'); ?></option>
            <option value="US" <?php selected($branch_data['country'], 'US'); ?>><?php _e('美国', 'help-platform'); ?></option>
            <option value="JP" <?php selected($branch_data['country'], 'JP'); ?>><?php _e('日本', 'help-platform'); ?></option>
            <option value="KR" <?php selected($branch_data['country'], 'KR'); ?>><?php _e('韩国', 'help-platform'); ?></option>
            <option value="SG" <?php selected($branch_data['country'], 'SG'); ?>><?php _e('新加坡', 'help-platform'); ?></option>
            <option value="AU" <?php selected($branch_data['country'], 'AU'); ?>><?php _e('澳大利亚', 'help-platform'); ?></option>
            <option value="CA" <?php selected($branch_data['country'], 'CA'); ?>><?php _e('加拿大', 'help-platform'); ?></option>
            <option value="GB" <?php selected($branch_data['country'], 'GB'); ?>><?php _e('英国', 'help-platform'); ?></option>
            <option value="DE" <?php selected($branch_data['country'], 'DE'); ?>><?php _e('德国', 'help-platform'); ?></option>
            <option value="FR" <?php selected($branch_data['country'], 'FR'); ?>><?php _e('法国', 'help-platform'); ?></option>
        </select>
    </div>

    <div class="form-row">
        <label for="branch_currency"><?php _e('货币', 'help-platform'); ?></label>
        <select id="branch_currency" name="branch_currency" required>
            <option value=""><?php _e('请选择货币', 'help-platform'); ?></option>
            <option value="CNY" <?php selected($branch_data['currency'], 'CNY'); ?>>CNY - <?php _e('人民币', 'help-platform'); ?></option>
            <option value="USD" <?php selected($branch_data['currency'], 'USD'); ?>>USD - <?php _e('美元', 'help-platform'); ?></option>
            <option value="JPY" <?php selected($branch_data['currency'], 'JPY'); ?>>JPY - <?php _e('日元', 'help-platform'); ?></option>
            <option value="KRW" <?php selected($branch_data['currency'], 'KRW'); ?>>KRW - <?php _e('韩元', 'help-platform'); ?></option>
            <option value="SGD" <?php selected($branch_data['currency'], 'SGD'); ?>>SGD - <?php _e('新加坡元', 'help-platform'); ?></option>
            <option value="AUD" <?php selected($branch_data['currency'], 'AUD'); ?>>AUD - <?php _e('澳元', 'help-platform'); ?></option>
            <option value="CAD" <?php selected($branch_data['currency'], 'CAD'); ?>>CAD - <?php _e('加元', 'help-platform'); ?></option>
            <option value="GBP" <?php selected($branch_data['currency'], 'GBP'); ?>>GBP - <?php _e('英镑', 'help-platform'); ?></option>
            <option value="EUR" <?php selected($branch_data['currency'], 'EUR'); ?>>EUR - <?php _e('欧元', 'help-platform'); ?></option>
        </select>
    </div>

    <div class="form-row">
        <label for="branch_timezone"><?php _e('时区', 'help-platform'); ?></label>
        <select id="branch_timezone" name="branch_timezone" required>
            <option value=""><?php _e('请选择时区', 'help-platform'); ?></option>
            <option value="Asia/Shanghai" <?php selected($branch_data['timezone'], 'Asia/Shanghai'); ?>><?php _e('中国标准时间 (UTC+8)', 'help-platform'); ?></option>
            <option value="America/New_York" <?php selected($branch_data['timezone'], 'America/New_York'); ?>><?php _e('美国东部时间 (UTC-5)', 'help-platform'); ?></option>
            <option value="Asia/Tokyo" <?php selected($branch_data['timezone'], 'Asia/Tokyo'); ?>><?php _e('日本标准时间 (UTC+9)', 'help-platform'); ?></option>
            <option value="Asia/Seoul" <?php selected($branch_data['timezone'], 'Asia/Seoul'); ?>><?php _e('韩国标准时间 (UTC+9)', 'help-platform'); ?></option>
            <option value="Asia/Singapore" <?php selected($branch_data['timezone'], 'Asia/Singapore'); ?>><?php _e('新加坡标准时间 (UTC+8)', 'help-platform'); ?></option>
            <option value="Australia/Sydney" <?php selected($branch_data['timezone'], 'Australia/Sydney'); ?>><?php _e('澳大利亚东部时间 (UTC+10)', 'help-platform'); ?></option>
            <option value="America/Toronto" <?php selected($branch_data['timezone'], 'America/Toronto'); ?>><?php _e('加拿大东部时间 (UTC-5)', 'help-platform'); ?></option>
            <option value="Europe/London" <?php selected($branch_data['timezone'], 'Europe/London'); ?>><?php _e('英国标准时间 (UTC+0)', 'help-platform'); ?></option>
            <option value="Europe/Berlin" <?php selected($branch_data['timezone'], 'Europe/Berlin'); ?>><?php _e('欧洲中部时间 (UTC+1)', 'help-platform'); ?></option>
            <option value="Europe/Paris" <?php selected($branch_data['timezone'], 'Europe/Paris'); ?>><?php _e('欧洲中部时间 (UTC+1)', 'help-platform'); ?></option>
        </select>
    </div>

    <div class="form-row">
        <label for="branch_address"><?php _e('地址', 'help-platform'); ?></label>
        <textarea id="branch_address" name="branch_address" rows="3" required><?php echo esc_textarea($branch_data['address']); ?></textarea>
    </div>

    <div class="form-row">
        <label for="branch_phone"><?php _e('电话', 'help-platform'); ?></label>
        <input type="tel" id="branch_phone" name="branch_phone" value="<?php echo esc_attr($branch_data['phone']); ?>" required>
    </div>

    <div class="form-row">
        <label for="branch_email"><?php _e('邮箱', 'help-platform'); ?></label>
        <input type="email" id="branch_email" name="branch_email" value="<?php echo esc_attr($branch_data['email']); ?>" required>
    </div>

    <div class="form-row">
        <label for="branch_manager_id"><?php _e('分公司管理员', 'help-platform'); ?></label>
        <select id="branch_manager_id" name="branch_manager_id">
            <option value=""><?php _e('请选择管理员', 'help-platform'); ?></option>
            <?php
            $users = get_users(array('role__in' => array('administrator', 'help_branch_manager')));
            foreach ($users as $user) {
                printf(
                    '<option value="%d" %s>%s</option>',
                    $user->ID,
                    selected($branch_data['manager_id'], $user->ID, false),
                    esc_html($user->display_name)
                );
            }
            ?>
        </select>
    </div>

    <div class="form-row">
        <label for="branch_commission_rate"><?php _e('佣金比例', 'help-platform'); ?></label>
        <input type="number" id="branch_commission_rate" name="branch_commission_rate" 
               value="<?php echo esc_attr($branch_data['commission_rate']); ?>" 
               min="0" max="1" step="0.01" required>
        <small><?php _e('请输入0-1之间的小数，例如：0.1 表示 10%', 'help-platform'); ?></small>
    </div>

    <div class="form-row">
        <label for="branch_platform_fee"><?php _e('平台服务费比例', 'help-platform'); ?></label>
        <input type="number" id="branch_platform_fee" name="branch_platform_fee" 
               value="<?php echo esc_attr($branch_data['platform_fee']); ?>" 
               min="0" max="1" step="0.01" required>
        <small><?php _e('请输入0-1之间的小数，例如：0.05 表示 5%', 'help-platform'); ?></small>
    </div>

    <div class="form-row">
        <label for="branch_min_withdraw"><?php _e('最低提现金额', 'help-platform'); ?></label>
        <input type="number" id="branch_min_withdraw" name="branch_min_withdraw" 
               value="<?php echo esc_attr($branch_data['min_withdraw']); ?>" 
               min="0" step="0.01" required>
        <small><?php _e('请输入最低提现金额', 'help-platform'); ?></small>
    </div>

    <div class="form-row">
        <label for="branch_withdraw_fee"><?php _e('提现手续费比例', 'help-platform'); ?></label>
        <input type="number" id="branch_withdraw_fee" name="branch_withdraw_fee" 
               value="<?php echo esc_attr($branch_data['withdraw_fee']); ?>" 
               min="0" max="1" step="0.01" required>
        <small><?php _e('请输入0-1之间的小数，例如：0.01 表示 1%', 'help-platform'); ?></small>
    </div>
</div>

<style>
.help-platform-branch-meta {
    padding: 12px;
}

.help-platform-branch-meta .form-row {
    margin-bottom: 15px;
}

.help-platform-branch-meta label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
}

.help-platform-branch-meta input[type="text"],
.help-platform-branch-meta input[type="email"],
.help-platform-branch-meta input[type="tel"],
.help-platform-branch-meta input[type="number"],
.help-platform-branch-meta select,
.help-platform-branch-meta textarea {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.help-platform-branch-meta small {
    display: block;
    color: #666;
    margin-top: 5px;
}

.help-platform-branch-meta input:focus,
.help-platform-branch-meta select:focus,
.help-platform-branch-meta textarea:focus {
    border-color: #2271b1;
    outline: none;
    box-shadow: 0 0 0 1px #2271b1;
}
</style>

<script>
jQuery(document).ready(function($) {
    // 国家/地区与货币联动
    $('#branch_country').on('change', function() {
        var country = $(this).val();
        var currencyMap = {
            'CN': 'CNY',
            'US': 'USD',
            'JP': 'JPY',
            'KR': 'KRW',
            'SG': 'SGD',
            'AU': 'AUD',
            'CA': 'CAD',
            'GB': 'GBP',
            'DE': 'EUR',
            'FR': 'EUR'
        };
        
        if (currencyMap[country]) {
            $('#branch_currency').val(currencyMap[country]);
        }
    });

    // 国家/地区与时区联动
    $('#branch_country').on('change', function() {
        var country = $(this).val();
        var timezoneMap = {
            'CN': 'Asia/Shanghai',
            'US': 'America/New_York',
            'JP': 'Asia/Tokyo',
            'KR': 'Asia/Seoul',
            'SG': 'Asia/Singapore',
            'AU': 'Australia/Sydney',
            'CA': 'America/Toronto',
            'GB': 'Europe/London',
            'DE': 'Europe/Berlin',
            'FR': 'Europe/Paris'
        };
        
        if (timezoneMap[country]) {
            $('#branch_timezone').val(timezoneMap[country]);
        }
    });
});
</script> 