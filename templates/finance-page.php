<?php
/**
 * 用户财务页面模板
 */
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="help-platform-finance-page">
    <!-- 余额概览 -->
    <div class="finance-overview">
        <div class="balance-card">
            <h3><?php _e('账户余额', 'help-platform'); ?></h3>
            <div class="balance-amount"><?php echo number_format($balance, 2); ?> <?php _e('元', 'help-platform'); ?></div>
            <button class="button recharge-button" data-toggle="modal" data-target="#rechargeModal">
                <?php _e('立即充值', 'help-platform'); ?>
            </button>
            <button class="button withdraw-button" data-toggle="modal" data-target="#withdrawModal">
                <?php _e('申请提现', 'help-platform'); ?>
            </button>
        </div>

        <?php if (in_array('help_worker', wp_get_current_user()->roles)): ?>
        <div class="commission-card">
            <h3><?php _e('佣金统计', 'help-platform'); ?></h3>
            <div class="commission-stats">
                <div class="stat-item">
                    <span class="stat-label"><?php _e('总收入', 'help-platform'); ?></span>
                    <span class="stat-value"><?php echo number_format($commission->total_earned, 2); ?> <?php _e('元', 'help-platform'); ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label"><?php _e('已完成任务', 'help-platform'); ?></span>
                    <span class="stat-value"><?php echo $commission->completed_tasks; ?> <?php _e('个', 'help-platform'); ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label"><?php _e('平均收入', 'help-platform'); ?></span>
                    <span class="stat-value"><?php echo $commission->total_tasks > 0 ? number_format($commission->total_earned / $commission->total_tasks, 2) : '0.00'; ?> <?php _e('元/任务', 'help-platform'); ?></span>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- 交易记录 -->
    <div class="transaction-history">
        <h3><?php _e('交易记录', 'help-platform'); ?></h3>
        <table class="woocommerce-orders-table">
            <thead>
                <tr>
                    <th><?php _e('时间', 'help-platform'); ?></th>
                    <th><?php _e('类型', 'help-platform'); ?></th>
                    <th><?php _e('金额', 'help-platform'); ?></th>
                    <th><?php _e('手续费', 'help-platform'); ?></th>
                    <th><?php _e('状态', 'help-platform'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transactions as $transaction): ?>
                <tr>
                    <td><?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($transaction->created_at)); ?></td>
                    <td>
                        <?php
                        switch ($transaction->type) {
                            case 'recharge':
                                _e('充值', 'help-platform');
                                break;
                            case 'withdraw':
                                _e('提现', 'help-platform');
                                break;
                            case 'commission':
                                _e('佣金', 'help-platform');
                                break;
                        }
                        ?>
                    </td>
                    <td><?php echo number_format($transaction->amount, 2); ?> <?php _e('元', 'help-platform'); ?></td>
                    <td><?php echo $transaction->fee ? number_format($transaction->fee, 2) . ' ' . __('元', 'help-platform') : '-'; ?></td>
                    <td>
                        <?php
                        switch ($transaction->status) {
                            case 'pending':
                                _e('处理中', 'help-platform');
                                break;
                            case 'completed':
                                _e('已完成', 'help-platform');
                                break;
                            case 'failed':
                                _e('失败', 'help-platform');
                                break;
                            case 'cancelled':
                                _e('已取消', 'help-platform');
                                break;
                        }
                        ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- 充值模态框 -->
    <div id="rechargeModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2><?php _e('账户充值', 'help-platform'); ?></h2>
            <form id="rechargeForm" class="help-platform-form">
                <?php wp_nonce_field('help-platform-finance', 'recharge_nonce'); ?>
                <div class="form-row">
                    <label for="recharge_amount"><?php _e('充值金额', 'help-platform'); ?></label>
                    <input type="number" id="recharge_amount" name="amount" min="1" step="0.01" required>
                </div>
                <div class="form-row">
                    <label for="payment_method"><?php _e('支付方式', 'help-platform'); ?></label>
                    <select id="payment_method" name="payment_method" required>
                        <option value=""><?php _e('请选择支付方式', 'help-platform'); ?></option>
                        <option value="alipay"><?php _e('支付宝', 'help-platform'); ?></option>
                        <option value="wechat"><?php _e('微信支付', 'help-platform'); ?></option>
                        <option value="bank"><?php _e('银行转账', 'help-platform'); ?></option>
                    </select>
                </div>
                <div class="form-row">
                    <button type="submit" class="button button-primary"><?php _e('确认充值', 'help-platform'); ?></button>
                </div>
            </form>
        </div>
    </div>

    <!-- 提现模态框 -->
    <div id="withdrawModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2><?php _e('申请提现', 'help-platform'); ?></h2>
            <form id="withdrawForm" class="help-platform-form">
                <?php wp_nonce_field('help-platform-finance', 'withdraw_nonce'); ?>
                <div class="form-row">
                    <label for="withdraw_amount"><?php _e('提现金额', 'help-platform'); ?></label>
                    <input type="number" id="withdraw_amount" name="amount" min="<?php echo get_option('help_platform_finance_settings')['min_withdraw']; ?>" step="0.01" required>
                    <small><?php printf(__('最低提现金额：%s 元', 'help-platform'), get_option('help_platform_finance_settings')['min_withdraw']); ?></small>
                </div>
                <div class="form-row">
                    <label for="withdraw_method"><?php _e('提现方式', 'help-platform'); ?></label>
                    <select id="withdraw_method" name="withdraw_method" required>
                        <option value=""><?php _e('请选择提现方式', 'help-platform'); ?></option>
                        <option value="alipay"><?php _e('支付宝', 'help-platform'); ?></option>
                        <option value="wechat"><?php _e('微信', 'help-platform'); ?></option>
                        <option value="bank"><?php _e('银行账户', 'help-platform'); ?></option>
                    </select>
                </div>
                <div class="form-row">
                    <label for="account_info"><?php _e('收款账户信息', 'help-platform'); ?></label>
                    <textarea id="account_info" name="account_info" required></textarea>
                    <small><?php _e('请填写完整的收款账户信息，如支付宝账号、微信账号或银行账户信息', 'help-platform'); ?></small>
                </div>
                <div class="form-row">
                    <button type="submit" class="button button-primary"><?php _e('确认提现', 'help-platform'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.help-platform-finance-page {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.finance-overview {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.balance-card,
.commission-card {
    background: #fff;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.balance-amount {
    font-size: 32px;
    font-weight: bold;
    color: #2c3338;
    margin: 15px 0;
}

.commission-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    margin-top: 15px;
}

.stat-item {
    text-align: center;
}

.stat-label {
    display: block;
    color: #666;
    font-size: 14px;
    margin-bottom: 5px;
}

.stat-value {
    font-size: 18px;
    font-weight: bold;
    color: #2c3338;
}

.transaction-history {
    background: #fff;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.woocommerce-orders-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
}

.woocommerce-orders-table th,
.woocommerce-orders-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.woocommerce-orders-table th {
    background: #f8f9fa;
    font-weight: 600;
}

.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.modal-content {
    background-color: #fff;
    margin: 10% auto;
    padding: 20px;
    border-radius: 8px;
    max-width: 500px;
    position: relative;
}

.close {
    position: absolute;
    right: 20px;
    top: 10px;
    font-size: 24px;
    cursor: pointer;
}

.help-platform-form .form-row {
    margin-bottom: 15px;
}

.help-platform-form label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
}

.help-platform-form input,
.help-platform-form select,
.help-platform-form textarea {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.help-platform-form small {
    display: block;
    color: #666;
    margin-top: 5px;
}

.help-platform-form button {
    width: 100%;
    padding: 10px;
    background: #2271b1;
    color: #fff;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

.help-platform-form button:hover {
    background: #135e96;
}

@media (max-width: 768px) {
    .finance-overview {
        grid-template-columns: 1fr;
    }

    .woocommerce-orders-table {
        display: block;
        overflow-x: auto;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // 充值表单提交
    $('#rechargeForm').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        var submitButton = form.find('button[type="submit"]');
        
        submitButton.prop('disabled', true);
        
        $.ajax({
            url: helpPlatformFinance.ajaxurl,
            type: 'POST',
            data: {
                action: 'help_platform_process_recharge',
                nonce: $('#recharge_nonce').val(),
                amount: $('#recharge_amount').val(),
                payment_method: $('#payment_method').val()
            },
            success: function(response) {
                if (response.success) {
                    window.location.href = response.data.redirect;
                } else {
                    alert(response.data);
                }
            },
            error: function() {
                alert('<?php _e('请求失败，请稍后重试', 'help-platform'); ?>');
            },
            complete: function() {
                submitButton.prop('disabled', false);
            }
        });
    });

    // 提现表单提交
    $('#withdrawForm').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        var submitButton = form.find('button[type="submit"]');
        
        submitButton.prop('disabled', true);
        
        $.ajax({
            url: helpPlatformFinance.ajaxurl,
            type: 'POST',
            data: {
                action: 'help_platform_process_withdraw',
                nonce: $('#withdraw_nonce').val(),
                amount: $('#withdraw_amount').val(),
                withdraw_method: $('#withdraw_method').val(),
                account_info: $('#account_info').val()
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data);
                    location.reload();
                } else {
                    alert(response.data);
                }
            },
            error: function() {
                alert('<?php _e('请求失败，请稍后重试', 'help-platform'); ?>');
            },
            complete: function() {
                submitButton.prop('disabled', false);
            }
        });
    });

    // 模态框关闭
    $('.close').on('click', function() {
        $(this).closest('.modal').hide();
    });

    // 点击模态框外部关闭
    $(window).on('click', function(e) {
        if ($(e.target).hasClass('modal')) {
            $('.modal').hide();
        }
    });

    // 显示模态框
    $('[data-toggle="modal"]').on('click', function() {
        var target = $(this).data('target');
        $(target).show();
    });
});
</script> 