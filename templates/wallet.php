<?php
if (!defined('ABSPATH')) {
    exit;
}

$user_id = get_current_user_id();
$balance = help_platform_get_user_balance($user_id);
$transactions = help_platform_get_user_transactions($user_id, array(
    'posts_per_page' => 20,
    'paged' => get_query_var('paged') ? get_query_var('paged') : 1
));
?>

<div class="help-platform-wallet">
    <div class="wallet-header">
        <div class="balance-card">
            <div class="balance-title"><?php _e('账户余额', 'help-platform'); ?></div>
            <div class="balance-amount"><?php echo help_platform_format_money($balance); ?></div>
            <div class="balance-actions">
                <button type="button" class="button button-primary" id="recharge-button">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <?php _e('充值', 'help-platform'); ?>
                </button>
                <button type="button" class="button" id="withdraw-button">
                    <span class="dashicons dashicons-money-alt"></span>
                    <?php _e('提现', 'help-platform'); ?>
                </button>
            </div>
        </div>
    </div>

    <div class="wallet-content">
        <div class="section-header">
            <h2><?php _e('交易记录', 'help-platform'); ?></h2>
            <div class="filter-actions">
                <select id="transaction-type">
                    <option value=""><?php _e('全部类型', 'help-platform'); ?></option>
                    <option value="recharge"><?php _e('充值', 'help-platform'); ?></option>
                    <option value="withdraw"><?php _e('提现', 'help-platform'); ?></option>
                    <option value="job_payment"><?php _e('任务支付', 'help-platform'); ?></option>
                    <option value="job_income"><?php _e('任务收入', 'help-platform'); ?></option>
                    <option value="refund"><?php _e('退款', 'help-platform'); ?></option>
                </select>
                <input type="date" id="transaction-date" placeholder="<?php esc_attr_e('选择日期', 'help-platform'); ?>">
            </div>
        </div>

        <div class="transaction-list">
            <?php if ($transactions->have_posts()): ?>
                <table class="transaction-table">
                    <thead>
                        <tr>
                            <th><?php _e('交易时间', 'help-platform'); ?></th>
                            <th><?php _e('交易类型', 'help-platform'); ?></th>
                            <th><?php _e('交易金额', 'help-platform'); ?></th>
                            <th><?php _e('交易状态', 'help-platform'); ?></th>
                            <th><?php _e('备注', 'help-platform'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($transactions->have_posts()): $transactions->the_post(); 
                            $transaction_type = get_post_meta(get_the_ID(), 'transaction_type', true);
                            $amount = get_post_meta(get_the_ID(), 'amount', true);
                            $status = get_post_meta(get_the_ID(), 'status', true);
                        ?>
                            <tr>
                                <td><?php echo get_the_date('Y-m-d H:i:s'); ?></td>
                                <td>
                                    <?php
                                    $type_labels = array(
                                        'recharge' => __('充值', 'help-platform'),
                                        'withdraw' => __('提现', 'help-platform'),
                                        'job_payment' => __('任务支付', 'help-platform'),
                                        'job_income' => __('任务收入', 'help-platform'),
                                        'refund' => __('退款', 'help-platform')
                                    );
                                    echo isset($type_labels[$transaction_type]) ? $type_labels[$transaction_type] : $transaction_type;
                                    ?>
                                </td>
                                <td class="<?php echo $amount >= 0 ? 'amount-positive' : 'amount-negative'; ?>">
                                    <?php echo ($amount >= 0 ? '+' : '') . help_platform_format_money($amount); ?>
                                </td>
                                <td>
                                    <?php
                                    $status_labels = array(
                                        'pending' => __('处理中', 'help-platform'),
                                        'completed' => __('已完成', 'help-platform'),
                                        'failed' => __('失败', 'help-platform'),
                                        'cancelled' => __('已取消', 'help-platform')
                                    );
                                    $status_class = array(
                                        'pending' => 'status-pending',
                                        'completed' => 'status-completed',
                                        'failed' => 'status-failed',
                                        'cancelled' => 'status-cancelled'
                                    );
                                    ?>
                                    <span class="status-badge <?php echo isset($status_class[$status]) ? $status_class[$status] : ''; ?>">
                                        <?php echo isset($status_labels[$status]) ? $status_labels[$status] : $status; ?>
                                    </span>
                                </td>
                                <td><?php echo get_the_excerpt(); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>

                <?php
                // 分页
                echo '<div class="pagination">';
                echo paginate_links(array(
                    'total' => $transactions->max_num_pages,
                    'current' => get_query_var('paged') ? get_query_var('paged') : 1,
                    'prev_text' => '&laquo;',
                    'next_text' => '&raquo;',
                ));
                echo '</div>';
                ?>

            <?php else: ?>
                <div class="no-items">
                    <p><?php _e('暂无交易记录。', 'help-platform'); ?></p>
                </div>
            <?php endif; ?>
            wp_reset_postdata();
        </div>
    </div>
</div>

<!-- 充值弹窗 -->
<div id="recharge-modal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3><?php _e('账户充值', 'help-platform'); ?></h3>
            <button type="button" class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <form id="recharge-form" method="post">
                <?php wp_nonce_field('help_platform_recharge', 'help_platform_recharge_nonce'); ?>
                <input type="hidden" name="action" value="help_platform_recharge">

                <div class="form-group">
                    <label for="recharge_amount"><?php _e('充值金额', 'help-platform'); ?></label>
                    <div class="amount-input">
                        <span class="currency"><?php echo get_woocommerce_currency_symbol(); ?></span>
                        <input type="number" id="recharge_amount" name="amount" min="1" step="0.01" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="payment_method"><?php _e('支付方式', 'help-platform'); ?></label>
                    <select id="payment_method" name="payment_method" required>
                        <option value=""><?php _e('请选择支付方式', 'help-platform'); ?></option>
                        <option value="alipay"><?php _e('支付宝', 'help-platform'); ?></option>
                        <option value="wechat"><?php _e('微信支付', 'help-platform'); ?></option>
                        <option value="bank"><?php _e('银行转账', 'help-platform'); ?></option>
                    </select>
                </div>

                <div class="form-group payment-fields" id="alipay-fields" style="display: none;">
                    <div class="qr-code">
                        <img src="<?php echo esc_url(help_platform_get_payment_qrcode('alipay')); ?>" alt="支付宝二维码">
                    </div>
                    <p class="description"><?php _e('请使用支付宝扫描二维码完成支付', 'help-platform'); ?></p>
                </div>

                <div class="form-group payment-fields" id="wechat-fields" style="display: none;">
                    <div class="qr-code">
                        <img src="<?php echo esc_url(help_platform_get_payment_qrcode('wechat')); ?>" alt="微信支付二维码">
                    </div>
                    <p class="description"><?php _e('请使用微信扫描二维码完成支付', 'help-platform'); ?></p>
                </div>

                <div class="form-group payment-fields" id="bank-fields" style="display: none;">
                    <div class="bank-info">
                        <p><strong><?php _e('开户行：', 'help-platform'); ?></strong> <?php echo esc_html(help_platform_get_bank_info('bank_name')); ?></p>
                        <p><strong><?php _e('账户名：', 'help-platform'); ?></strong> <?php echo esc_html(help_platform_get_bank_info('account_name')); ?></p>
                        <p><strong><?php _e('账号：', 'help-platform'); ?></strong> <?php echo esc_html(help_platform_get_bank_info('account_number')); ?></p>
                    </div>
                    <p class="description"><?php _e('请使用银行转账，转账时请备注您的用户名', 'help-platform'); ?></p>
                </div>

                <div class="form-group">
                    <button type="submit" class="button button-primary">
                        <?php _e('确认充值', 'help-platform'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 提现弹窗 -->
<div id="withdraw-modal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3><?php _e('申请提现', 'help-platform'); ?></h3>
            <button type="button" class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <form id="withdraw-form" method="post">
                <?php wp_nonce_field('help_platform_withdraw', 'help_platform_withdraw_nonce'); ?>
                <input type="hidden" name="action" value="help_platform_withdraw">

                <div class="form-group">
                    <label for="withdraw_amount"><?php _e('提现金额', 'help-platform'); ?></label>
                    <div class="amount-input">
                        <span class="currency"><?php echo get_woocommerce_currency_symbol(); ?></span>
                        <input type="number" id="withdraw_amount" name="amount" min="1" max="<?php echo esc_attr($balance); ?>" step="0.01" required>
                    </div>
                    <p class="description"><?php printf(__('可提现余额：%s', 'help-platform'), help_platform_format_money($balance)); ?></p>
                </div>

                <div class="form-group">
                    <label for="withdraw_method"><?php _e('提现方式', 'help-platform'); ?></label>
                    <select id="withdraw_method" name="withdraw_method" required>
                        <option value=""><?php _e('请选择提现方式', 'help-platform'); ?></option>
                        <option value="alipay"><?php _e('支付宝', 'help-platform'); ?></option>
                        <option value="wechat"><?php _e('微信', 'help-platform'); ?></option>
                        <option value="bank"><?php _e('银行卡', 'help-platform'); ?></option>
                    </select>
                </div>

                <div class="form-group withdraw-fields" id="alipay-withdraw-fields" style="display: none;">
                    <label for="alipay_account"><?php _e('支付宝账号', 'help-platform'); ?></label>
                    <input type="text" id="alipay_account" name="alipay_account">
                </div>

                <div class="form-group withdraw-fields" id="wechat-withdraw-fields" style="display: none;">
                    <label for="wechat_account"><?php _e('微信账号', 'help-platform'); ?></label>
                    <input type="text" id="wechat_account" name="wechat_account">
                </div>

                <div class="form-group withdraw-fields" id="bank-withdraw-fields" style="display: none;">
                    <label for="bank_name"><?php _e('开户行', 'help-platform'); ?></label>
                    <input type="text" id="bank_name" name="bank_name">
                    
                    <label for="bank_account"><?php _e('银行卡号', 'help-platform'); ?></label>
                    <input type="text" id="bank_account" name="bank_account">
                    
                    <label for="bank_holder"><?php _e('开户人姓名', 'help-platform'); ?></label>
                    <input type="text" id="bank_holder" name="bank_holder">
                </div>

                <div class="form-group">
                    <button type="submit" class="button button-primary">
                        <?php _e('申请提现', 'help-platform'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.help-platform-wallet {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.wallet-header {
    margin-bottom: 30px;
}

.balance-card {
    background: linear-gradient(135deg, #2196f3, #1976d2);
    color: #fff;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.balance-title {
    font-size: 16px;
    opacity: 0.9;
    margin-bottom: 10px;
}

.balance-amount {
    font-size: 36px;
    font-weight: 600;
    margin-bottom: 20px;
}

.balance-actions {
    display: flex;
    gap: 10px;
}

.balance-actions .button {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
}

.balance-actions .button-primary {
    background: #fff;
    color: #2196f3;
}

.balance-actions .button-primary:hover {
    background: #f8f9fa;
    transform: translateY(-1px);
}

.balance-actions .button:not(.button-primary) {
    background: rgba(255,255,255,0.2);
    color: #fff;
}

.balance-actions .button:not(.button-primary):hover {
    background: rgba(255,255,255,0.3);
}

.wallet-content {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    overflow: hidden;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #eee;
}

.section-header h2 {
    margin: 0;
    font-size: 20px;
    color: #333;
}

.filter-actions {
    display: flex;
    gap: 10px;
}

.filter-actions select,
.filter-actions input {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.transaction-list {
    padding: 20px;
}

.transaction-table {
    width: 100%;
    border-collapse: collapse;
}

.transaction-table th,
.transaction-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.transaction-table th {
    font-weight: 600;
    color: #666;
    background: #f8f9fa;
}

.transaction-table td {
    color: #333;
}

.amount-positive {
    color: #4caf50;
}

.amount-negative {
    color: #f44336;
}

.status-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 13px;
    font-weight: 500;
}

.status-pending {
    background: #fff3e0;
    color: #ef6c00;
}

.status-completed {
    background: #e8f5e9;
    color: #2e7d32;
}

.status-failed {
    background: #ffebee;
    color: #c62828;
}

.status-cancelled {
    background: #f5f5f5;
    color: #757575;
}

/* 弹窗样式 */
.modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.modal-content {
    background: #fff;
    border-radius: 12px;
    width: 90%;
    max-width: 500px;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #eee;
}

.modal-header h3 {
    margin: 0;
    font-size: 20px;
    color: #333;
}

.modal-close {
    background: none;
    border: none;
    font-size: 24px;
    color: #666;
    cursor: pointer;
    padding: 0;
    line-height: 1;
}

.modal-body {
    padding: 20px;
}

.amount-input {
    position: relative;
    display: flex;
    align-items: center;
}

.amount-input .currency {
    position: absolute;
    left: 12px;
    color: #666;
}

.amount-input input {
    padding-left: 30px !important;
}

.qr-code {
    text-align: center;
    margin: 20px 0;
}

.qr-code img {
    max-width: 200px;
    border: 1px solid #ddd;
    padding: 10px;
    border-radius: 4px;
}

.bank-info {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 4px;
    margin: 10px 0;
}

.bank-info p {
    margin: 5px 0;
    color: #666;
}

.bank-info strong {
    color: #333;
}

@media screen and (max-width: 768px) {
    .section-header {
        flex-direction: column;
        gap: 15px;
    }

    .filter-actions {
        width: 100%;
    }

    .filter-actions select,
    .filter-actions input {
        flex: 1;
    }

    .transaction-table {
        display: block;
        overflow-x: auto;
    }

    .balance-actions {
        flex-direction: column;
    }

    .balance-actions .button {
        width: 100%;
        justify-content: center;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // 充值弹窗
    $('#recharge-button').on('click', function() {
        $('#recharge-modal').fadeIn();
    });

    // 提现弹窗
    $('#withdraw-button').on('click', function() {
        $('#withdraw-modal').fadeIn();
    });

    // 关闭弹窗
    $('.modal-close').on('click', function() {
        $(this).closest('.modal').fadeOut();
    });

    // 点击弹窗外部关闭
    $('.modal').on('click', function(e) {
        if ($(e.target).is('.modal')) {
            $(this).fadeOut();
        }
    });

    // 支付方式切换
    $('#payment_method').on('change', function() {
        $('.payment-fields').hide();
        var method = $(this).val();
        if (method) {
            $('#' + method + '-fields').show();
        }
    });

    // 提现方式切换
    $('#withdraw_method').on('change', function() {
        $('.withdraw-fields').hide();
        var method = $(this).val();
        if (method) {
            $('#' + method + '-withdraw-fields').show();
        }
    });

    // 交易类型筛选
    $('#transaction-type').on('change', function() {
        filterTransactions();
    });

    // 日期筛选
    $('#transaction-date').on('change', function() {
        filterTransactions();
    });

    function filterTransactions() {
        var type = $('#transaction-type').val();
        var date = $('#transaction-date').val();
        
        $.ajax({
            url: helpPlatform.ajaxurl,
            type: 'POST',
            data: {
                action: 'help_platform_filter_transactions',
                type: type,
                date: date,
                nonce: helpPlatform.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('.transaction-list').html(response.data);
                }
            }
        });
    }

    // 充值表单提交
    $('#recharge-form').on('submit', function(e) {
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

    // 提现表单提交
    $('#withdraw-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $submit = $form.find('button[type="submit"]');
        var amount = parseFloat($('#withdraw_amount').val());
        var balance = parseFloat('<?php echo $balance; ?>');
        
        if (amount > balance) {
            alert(helpPlatform.i18n.insufficientBalance);
            return;
        }
        
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