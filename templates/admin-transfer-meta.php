<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="help-platform-transfer-meta">
    <div class="form-row">
        <label for="from_branch_id"><?php _e('转出分公司', 'help-platform'); ?></label>
        <select id="from_branch_id" name="from_branch_id" required>
            <option value=""><?php _e('请选择转出分公司', 'help-platform'); ?></option>
            <?php
            $branches = get_posts(array(
                'post_type' => 'help_branch',
                'posts_per_page' => -1,
                'orderby' => 'title',
                'order' => 'ASC'
            ));
            foreach ($branches as $branch) {
                $branch_data = get_post_meta($branch->ID, '_help_branch_data', true);
                $balance = Help_Platform_Finance::get_instance()->get_branch_balance($branch->ID);
                printf(
                    '<option value="%d" %s data-currency="%s" data-balance="%s">%s (余额: %s %s)</option>',
                    $branch->ID,
                    selected($transfer_data['from_branch_id'], $branch->ID, false),
                    esc_attr($branch_data['currency']),
                    esc_attr($balance),
                    esc_html($branch->post_title),
                    number_format($balance, 2),
                    esc_html($branch_data['currency'])
                );
            }
            ?>
        </select>
    </div>

    <div class="form-row">
        <label for="to_branch_id"><?php _e('转入分公司', 'help-platform'); ?></label>
        <select id="to_branch_id" name="to_branch_id" required>
            <option value=""><?php _e('请选择转入分公司', 'help-platform'); ?></option>
            <?php
            foreach ($branches as $branch) {
                printf(
                    '<option value="%d" %s>%s</option>',
                    $branch->ID,
                    selected($transfer_data['to_branch_id'], $branch->ID, false),
                    esc_html($branch->post_title)
                );
            }
            ?>
        </select>
    </div>

    <div class="form-row">
        <label for="amount"><?php _e('转账金额', 'help-platform'); ?></label>
        <input type="number" id="amount" name="amount" 
               value="<?php echo esc_attr($transfer_data['amount']); ?>" 
               min="0.01" step="0.01" required>
        <span class="currency-display"></span>
        <div class="balance-info"></div>
    </div>

    <div class="form-row">
        <label for="status"><?php _e('状态', 'help-platform'); ?></label>
        <select id="status" name="status" required>
            <option value="pending" <?php selected($transfer_data['status'], 'pending'); ?>><?php _e('待处理', 'help-platform'); ?></option>
            <option value="completed" <?php selected($transfer_data['status'], 'completed'); ?>><?php _e('已完成', 'help-platform'); ?></option>
            <option value="failed" <?php selected($transfer_data['status'], 'failed'); ?>><?php _e('失败', 'help-platform'); ?></option>
            <option value="cancelled" <?php selected($transfer_data['status'], 'cancelled'); ?>><?php _e('已取消', 'help-platform'); ?></option>
        </select>
    </div>

    <div class="form-row">
        <label for="note"><?php _e('备注', 'help-platform'); ?></label>
        <textarea id="note" name="note" rows="3"><?php echo esc_textarea($transfer_data['note']); ?></textarea>
    </div>

    <?php if (get_post_meta($post->ID, '_help_transfer_error', true)): ?>
        <div class="form-row error-message">
            <p class="error">
                <?php echo esc_html(get_post_meta($post->ID, '_help_transfer_error', true)); ?>
            </p>
        </div>
    <?php endif; ?>
</div>

<style>
.help-platform-transfer-meta {
    padding: 12px;
}

.help-platform-transfer-meta .form-row {
    margin-bottom: 15px;
}

.help-platform-transfer-meta label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
}

.help-platform-transfer-meta input[type="number"],
.help-platform-transfer-meta select,
.help-platform-transfer-meta textarea {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.help-platform-transfer-meta .currency-display {
    margin-left: 10px;
    color: #666;
}

.help-platform-transfer-meta .balance-info {
    margin-top: 5px;
    font-size: 12px;
    color: #666;
}

.help-platform-transfer-meta .error-message {
    margin-top: 15px;
    padding: 10px;
    background: #f8d7da;
    border: 1px solid #f5c6cb;
    border-radius: 4px;
}

.help-platform-transfer-meta .error-message p {
    margin: 0;
    color: #721c24;
}

.help-platform-transfer-meta input:focus,
.help-platform-transfer-meta select:focus,
.help-platform-transfer-meta textarea:focus {
    border-color: #2271b1;
    outline: none;
    box-shadow: 0 0 0 1px #2271b1;
}
</style>

<script>
jQuery(document).ready(function($) {
    // 更新货币显示
    function updateCurrencyDisplay() {
        var fromBranch = $('#from_branch_id option:selected');
        var currency = fromBranch.data('currency');
        var balance = fromBranch.data('balance');
        
        $('.currency-display').text(currency);
        $('.balance-info').text(
            '<?php _e('当前余额：', 'help-platform'); ?>' + 
            parseFloat(balance).toFixed(2) + ' ' + currency
        );
    }

    // 验证转账金额
    function validateAmount() {
        var amount = parseFloat($('#amount').val());
        var fromBranch = $('#from_branch_id option:selected');
        var balance = parseFloat(fromBranch.data('balance'));
        
        if (amount > balance) {
            $('.balance-info').addClass('error').text(
                '<?php _e('错误：转账金额不能大于当前余额', 'help-platform'); ?>'
            );
            return false;
        } else {
            $('.balance-info').removeClass('error');
            updateCurrencyDisplay();
            return true;
        }
    }

    // 验证转出和转入分公司不能相同
    function validateBranches() {
        var fromBranch = $('#from_branch_id').val();
        var toBranch = $('#to_branch_id').val();
        
        if (fromBranch && toBranch && fromBranch === toBranch) {
            $('#to_branch_id').addClass('error');
            alert('<?php _e('不能转账给自己', 'help-platform'); ?>');
            return false;
        } else {
            $('#to_branch_id').removeClass('error');
            return true;
        }
    }

    // 监听转出分公司选择
    $('#from_branch_id').on('change', function() {
        updateCurrencyDisplay();
        validateAmount();
    });

    // 监听转账金额输入
    $('#amount').on('input', function() {
        validateAmount();
    });

    // 监听转入分公司选择
    $('#to_branch_id').on('change', function() {
        validateBranches();
    });

    // 表单提交前验证
    $('form').on('submit', function(e) {
        if (!validateAmount() || !validateBranches()) {
            e.preventDefault();
            return false;
        }
    });

    // 初始化
    updateCurrencyDisplay();
});
</script> 