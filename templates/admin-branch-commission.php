<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<table class="form-table">
    <tr>
        <th scope="row"><?php _e('平台佣金比例', 'help-platform'); ?></th>
        <td>
            <input type="number" name="platform_fee" value="<?php echo esc_attr($commission_data['platform_fee']); ?>" min="0" max="100" step="0.1" class="small-text"> %
            <p class="description"><?php _e('平台从每笔交易中抽取的佣金比例。', 'help-platform'); ?></p>
        </td>
    </tr>
    <tr>
        <th scope="row"><?php _e('分公司佣金比例', 'help-platform'); ?></th>
        <td>
            <input type="number" name="branch_fee" value="<?php echo esc_attr($commission_data['branch_fee']); ?>" min="0" max="100" step="0.1" class="small-text"> %
            <p class="description"><?php _e('分公司从每笔交易中抽取的佣金比例。', 'help-platform'); ?></p>
        </td>
    </tr>
    <tr>
        <th scope="row"><?php _e('工作者佣金比例', 'help-platform'); ?></th>
        <td>
            <input type="number" name="worker_fee" value="<?php echo esc_attr($commission_data['worker_fee']); ?>" min="0" max="100" step="0.1" class="small-text"> %
            <p class="description"><?php _e('工作者从每笔交易中获得的佣金比例。', 'help-platform'); ?></p>
        </td>
    </tr>
    <tr>
        <th scope="row"><?php _e('最低佣金金额', 'help-platform'); ?></th>
        <td>
            <input type="number" name="min_amount" value="<?php echo esc_attr($commission_data['min_amount']); ?>" min="0" step="0.01" class="regular-text">
            <p class="description"><?php _e('每笔交易的最低佣金金额。设为0表示不限制。', 'help-platform'); ?></p>
        </td>
    </tr>
    <tr>
        <th scope="row"><?php _e('最高佣金金额', 'help-platform'); ?></th>
        <td>
            <input type="number" name="max_amount" value="<?php echo esc_attr($commission_data['max_amount']); ?>" min="0" step="0.01" class="regular-text">
            <p class="description"><?php _e('每笔交易的最高佣金金额。设为0表示不限制。', 'help-platform'); ?></p>
        </td>
    </tr>
    <tr>
        <th scope="row"><?php _e('税率', 'help-platform'); ?></th>
        <td>
            <input type="number" name="tax_rate" value="<?php echo esc_attr($commission_data['tax_rate']); ?>" min="0" max="100" step="0.1" class="small-text"> %
            <p class="description"><?php _e('佣金收入的税率。', 'help-platform'); ?></p>
        </td>
    </tr>
</table>

<style>
.form-table input[type="number"] {
    width: 100px;
}
.form-table .description {
    color: #666;
    font-style: italic;
    margin: 5px 0 0;
}
</style> 