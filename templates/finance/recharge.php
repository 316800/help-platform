<?php
/**
 * 充值页面模板
 */

if (!defined('ABSPATH')) {
    exit;
}

// 获取用户余额
$user_id = get_current_user_id();
$balance = get_user_meta($user_id, '_help_worker_balance', true);
$balance = floatval($balance);

// 获取充值记录
global $wpdb;
$recharge_records = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}help_recharges 
    WHERE user_id = %d 
    ORDER BY created_at DESC 
    LIMIT 10",
    $user_id
));
?>

<div class="wrap help-platform-recharge">
    <h1><?php _e('账户充值', 'help-platform'); ?></h1>

    <!-- 账户余额 -->
    <div class="balance-card">
        <div class="balance-title"><?php _e('当前余额', 'help-platform'); ?></div>
        <div class="balance-amount">¥<?php echo number_format($balance, 2); ?></div>
    </div>

    <!-- 充值表单 -->
    <div class="recharge-form">
        <h2><?php _e('充值金额', 'help-platform'); ?></h2>
        
        <form id="rechargeForm" method="post">
            <?php wp_nonce_field('help_platform_recharge', 'recharge_nonce'); ?>
            
            <div class="amount-options">
                <div class="amount-option">
                    <input type="radio" name="amount" id="amount_100" value="100">
                    <label for="amount_100">¥100</label>
                </div>
                <div class="amount-option">
                    <input type="radio" name="amount" id="amount_200" value="200">
                    <label for="amount_200">¥200</label>
                </div>
                <div class="amount-option">
                    <input type="radio" name="amount" id="amount_500" value="500">
                    <label for="amount_500">¥500</label>
                </div>
                <div class="amount-option">
                    <input type="radio" name="amount" id="amount_1000" value="1000">
                    <label for="amount_1000">¥1000</label>
                </div>
                <div class="amount-option custom">
                    <input type="radio" name="amount" id="amount_custom" value="custom">
                    <label for="amount_custom"><?php _e('其他金额', 'help-platform'); ?></label>
                    <input type="number" id="custom_amount" min="1" step="0.01" placeholder="<?php esc_attr_e('请输入金额', 'help-platform'); ?>" disabled>
                </div>
            </div>

            <div class="payment-methods">
                <h3><?php _e('支付方式', 'help-platform'); ?></h3>
                
                <div class="payment-option">
                    <input type="radio" name="payment_method" id="payment_alipay" value="alipay" checked>
                    <label for="payment_alipay">
                        <img src="<?php echo esc_url(plugins_url('assets/images/alipay.png', dirname(__FILE__))); ?>" alt="支付宝">
                        <?php _e('支付宝', 'help-platform'); ?>
                    </label>
                </div>
                
                <div class="payment-option">
                    <input type="radio" name="payment_method" id="payment_wechat" value="wechat">
                    <label for="payment_wechat">
                        <img src="<?php echo esc_url(plugins_url('assets/images/wechat.png', dirname(__FILE__))); ?>" alt="微信支付">
                        <?php _e('微信支付', 'help-platform'); ?>
                    </label>
                </div>
                
                <div class="payment-option">
                    <input type="radio" name="payment_method" id="payment_bank" value="bank">
                    <label for="payment_bank">
                        <img src="<?php echo esc_url(plugins_url('assets/images/bank.png', dirname(__FILE__))); ?>" alt="银行转账">
                        <?php _e('银行转账', 'help-platform'); ?>
                    </label>
                </div>
            </div>

            <div class="bank-info" style="display: none;">
                <h3><?php _e('银行账户信息', 'help-platform'); ?></h3>
                <div class="bank-details">
                    <p><strong><?php _e('开户行', 'help-platform'); ?>：</strong><?php echo esc_html(get_option('help_platform_bank_name')); ?></p>
                    <p><strong><?php _e('账户名', 'help-platform'); ?>：</strong><?php echo esc_html(get_option('help_platform_bank_account_name')); ?></p>
                    <p><strong><?php _e('账号', 'help-platform'); ?>：</strong><?php echo esc_html(get_option('help_platform_bank_account_number')); ?></p>
                </div>
                <div class="upload-proof">
                    <label for="proof_file"><?php _e('上传转账凭证', 'help-platform'); ?></label>
                    <input type="file" id="proof_file" name="proof_file" accept="image/*">
                    <p class="description"><?php _e('请上传转账成功的截图或照片', 'help-platform'); ?></p>
                </div>
            </div>

            <div class="form-submit">
                <button type="submit" class="button button-primary"><?php _e('立即充值', 'help-platform'); ?></button>
            </div>
        </form>
    </div>

    <!-- 充值记录 -->
    <div class="recharge-records">
        <h2><?php _e('充值记录', 'help-platform'); ?></h2>
        
        <?php if ($recharge_records) : ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('订单号', 'help-platform'); ?></th>
                    <th><?php _e('充值金额', 'help-platform'); ?></th>
                    <th><?php _e('支付方式', 'help-platform'); ?></th>
                    <th><?php _e('状态', 'help-platform'); ?></th>
                    <th><?php _e('创建时间', 'help-platform'); ?></th>
                    <th><?php _e('完成时间', 'help-platform'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recharge_records as $record) : ?>
                <tr>
                    <td><?php echo esc_html($record->order_no); ?></td>
                    <td>¥<?php echo number_format($record->amount, 2); ?></td>
                    <td>
                        <?php
                        switch ($record->payment_method) {
                            case 'alipay':
                                _e('支付宝', 'help-platform');
                                break;
                            case 'wechat':
                                _e('微信支付', 'help-platform');
                                break;
                            case 'bank':
                                _e('银行转账', 'help-platform');
                                break;
                        }
                        ?>
                    </td>
                    <td>
                        <?php
                        switch ($record->status) {
                            case 'pending':
                                echo '<span class="status-pending">' . __('待支付', 'help-platform') . '</span>';
                                break;
                            case 'processing':
                                echo '<span class="status-processing">' . __('处理中', 'help-platform') . '</span>';
                                break;
                            case 'completed':
                                echo '<span class="status-completed">' . __('已完成', 'help-platform') . '</span>';
                                break;
                            case 'failed':
                                echo '<span class="status-failed">' . __('失败', 'help-platform') . '</span>';
                                break;
                        }
                        ?>
                    </td>
                    <td><?php echo esc_html($record->created_at); ?></td>
                    <td><?php echo $record->completed_at ? esc_html($record->completed_at) : '-'; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else : ?>
        <div class="notice notice-info">
            <p><?php _e('暂无充值记录。', 'help-platform'); ?></p>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
.help-platform-recharge {
    margin: 20px;
}

.balance-card {
    background: #fff;
    padding: 30px;
    margin-bottom: 30px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,.1);
    text-align: center;
}

.balance-title {
    font-size: 16px;
    color: #666;
    margin-bottom: 10px;
}

.balance-amount {
    font-size: 36px;
    font-weight: bold;
    color: #2196F3;
}

.recharge-form {
    background: #fff;
    padding: 30px;
    margin-bottom: 30px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,.1);
}

.recharge-form h2 {
    margin-top: 0;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.amount-options {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 30px;
}

.amount-option {
    flex: 1;
    min-width: 120px;
}

.amount-option input[type="radio"] {
    display: none;
}

.amount-option label {
    display: block;
    padding: 15px;
    text-align: center;
    border: 2px solid #ddd;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s;
}

.amount-option input[type="radio"]:checked + label {
    border-color: #2196F3;
    background: #E3F2FD;
    color: #2196F3;
}

.amount-option.custom {
    position: relative;
}

.amount-option.custom input[type="number"] {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    padding: 15px;
    border: 2px solid #ddd;
    border-radius: 6px;
    text-align: center;
    font-size: 16px;
}

.amount-option.custom input[type="radio"]:checked + label + input[type="number"] {
    border-color: #2196F3;
}

.payment-methods {
    margin-bottom: 30px;
}

.payment-methods h3 {
    margin-bottom: 15px;
}

.payment-option {
    margin-bottom: 15px;
}

.payment-option input[type="radio"] {
    display: none;
}

.payment-option label {
    display: flex;
    align-items: center;
    padding: 15px;
    border: 2px solid #ddd;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s;
}

.payment-option label img {
    width: 24px;
    height: 24px;
    margin-right: 10px;
}

.payment-option input[type="radio"]:checked + label {
    border-color: #2196F3;
    background: #E3F2FD;
}

.bank-info {
    background: #f9f9f9;
    padding: 20px;
    border-radius: 6px;
    margin-bottom: 30px;
}

.bank-info h3 {
    margin-top: 0;
    margin-bottom: 15px;
}

.bank-details {
    margin-bottom: 20px;
}

.bank-details p {
    margin: 5px 0;
}

.upload-proof {
    margin-top: 20px;
}

.upload-proof label {
    display: block;
    margin-bottom: 10px;
    font-weight: 600;
}

.upload-proof .description {
    margin-top: 5px;
    color: #666;
    font-size: 13px;
}

.form-submit {
    text-align: center;
}

.form-submit button {
    padding: 12px 40px;
    font-size: 16px;
}

.recharge-records {
    background: #fff;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,.1);
}

.recharge-records h2 {
    margin-top: 0;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.recharge-records table {
    width: 100%;
    border-collapse: collapse;
}

.recharge-records th,
.recharge-records td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.recharge-records th {
    font-weight: 600;
    background: #f9f9f9;
}

.status-pending {
    color: #FFA000;
}

.status-processing {
    color: #2196F3;
}

.status-completed {
    color: #4CAF50;
}

.status-failed {
    color: #F44336;
}
</style>

<script>
jQuery(document).ready(function($) {
    // 自定义金额输入
    $('#amount_custom').on('change', function() {
        $('#custom_amount').prop('disabled', !$(this).is(':checked'));
    });

    $('#custom_amount').on('input', function() {
        if ($(this).val()) {
            $('#amount_custom').prop('checked', true);
        }
    });

    // 支付方式切换
    $('input[name="payment_method"]').on('change', function() {
        if ($(this).val() === 'bank') {
            $('.bank-info').slideDown();
        } else {
            $('.bank-info').slideUp();
        }
    });

    // 表单提交
    $('#rechargeForm').on('submit', function(e) {
        e.preventDefault();

        var amount = $('input[name="amount"]:checked').val();
        if (amount === 'custom') {
            amount = $('#custom_amount').val();
        }

        if (!amount || amount <= 0) {
            alert('<?php _e('请选择或输入充值金额', 'help-platform'); ?>');
            return;
        }

        var paymentMethod = $('input[name="payment_method"]:checked').val();
        if (paymentMethod === 'bank' && !$('#proof_file').val()) {
            alert('<?php _e('请上传转账凭证', 'help-platform'); ?>');
            return;
        }

        var formData = new FormData(this);
        formData.append('action', 'help_platform_recharge');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
                $('.form-submit button').prop('disabled', true).text('<?php _e('处理中...', 'help-platform'); ?>');
            },
            success: function(response) {
                if (response.success) {
                    if (response.data.redirect) {
                        window.location.href = response.data.redirect;
                    } else {
                        alert(response.data.message);
                        window.location.reload();
                    }
                } else {
                    alert(response.data.message);
                }
            },
            error: function() {
                alert('<?php _e('请求失败，请重试', 'help-platform'); ?>');
            },
            complete: function() {
                $('.form-submit button').prop('disabled', false).text('<?php _e('立即充值', 'help-platform'); ?>');
            }
        });
    });
});
</script> 