/**
 * HELP Platform 财务页面脚本
 */
(function($) {
    'use strict';

    // 页面加载完成后初始化
    $(document).ready(function() {
        initModals();
        initForms();
        initTransactionFilters();
    });

    /**
     * 初始化模态框
     */
    function initModals() {
        // 关闭按钮点击事件
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
    }

    /**
     * 初始化表单
     */
    function initForms() {
        // 充值表单提交
        $('#rechargeForm').on('submit', function(e) {
            e.preventDefault();
            var form = $(this);
            var submitButton = form.find('button[type="submit"]');
            
            if (!validateRechargeForm(form)) {
                return;
            }

            submitForm(form, submitButton, 'help_platform_process_recharge', function(response) {
                if (response.success) {
                    window.location.href = response.data.redirect;
                } else {
                    showMessage('error', response.data);
                }
            });
        });

        // 提现表单提交
        $('#withdrawForm').on('submit', function(e) {
            e.preventDefault();
            var form = $(this);
            var submitButton = form.find('button[type="submit"]');
            
            if (!validateWithdrawForm(form)) {
                return;
            }

            if (!confirm(helpPlatformFinance.i18n.confirmWithdraw)) {
                return;
            }

            submitForm(form, submitButton, 'help_platform_process_withdraw', function(response) {
                if (response.success) {
                    showMessage('success', response.data);
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    showMessage('error', response.data);
                }
            });
        });

        // 金额输入格式化
        $('input[type="number"]').on('input', function() {
            var value = $(this).val();
            if (value) {
                value = parseFloat(value).toFixed(2);
                $(this).val(value);
            }
        });
    }

    /**
     * 初始化交易记录筛选
     */
    function initTransactionFilters() {
        // 日期范围筛选
        var dateRange = $('#transactionDateRange');
        if (dateRange.length) {
            dateRange.on('change', function() {
                filterTransactions();
            });
        }

        // 类型筛选
        var typeFilter = $('#transactionType');
        if (typeFilter.length) {
            typeFilter.on('change', function() {
                filterTransactions();
            });
        }

        // 状态筛选
        var statusFilter = $('#transactionStatus');
        if (statusFilter.length) {
            statusFilter.on('change', function() {
                filterTransactions();
            });
        }
    }

    /**
     * 验证充值表单
     */
    function validateRechargeForm(form) {
        var amount = form.find('#recharge_amount').val();
        var paymentMethod = form.find('#payment_method').val();

        if (!amount || amount <= 0) {
            showMessage('error', helpPlatformFinance.i18n.invalidAmount);
            return false;
        }

        if (!paymentMethod) {
            showMessage('error', helpPlatformFinance.i18n.selectPaymentMethod);
            return false;
        }

        return true;
    }

    /**
     * 验证提现表单
     */
    function validateWithdrawForm(form) {
        var amount = form.find('#withdraw_amount').val();
        var withdrawMethod = form.find('#withdraw_method').val();
        var accountInfo = form.find('#account_info').val();

        if (!amount || amount <= 0) {
            showMessage('error', helpPlatformFinance.i18n.invalidAmount);
            return false;
        }

        if (!withdrawMethod) {
            showMessage('error', helpPlatformFinance.i18n.selectWithdrawMethod);
            return false;
        }

        if (!accountInfo) {
            showMessage('error', helpPlatformFinance.i18n.enterAccountInfo);
            return false;
        }

        return true;
    }

    /**
     * 提交表单
     */
    function submitForm(form, submitButton, action, callback) {
        submitButton.prop('disabled', true).addClass('loading');
        
        $.ajax({
            url: helpPlatformFinance.ajaxurl,
            type: 'POST',
            data: {
                action: action,
                nonce: helpPlatformFinance.nonce,
                ...form.serializeArray().reduce(function(obj, item) {
                    obj[item.name] = item.value;
                    return obj;
                }, {})
            },
            success: callback,
            error: function() {
                showMessage('error', helpPlatformFinance.i18n.requestFailed);
            },
            complete: function() {
                submitButton.prop('disabled', false).removeClass('loading');
            }
        });
    }

    /**
     * 筛选交易记录
     */
    function filterTransactions() {
        var dateRange = $('#transactionDateRange').val();
        var type = $('#transactionType').val();
        var status = $('#transactionStatus').val();

        $.ajax({
            url: helpPlatformFinance.ajaxurl,
            type: 'POST',
            data: {
                action: 'help_platform_filter_transactions',
                nonce: helpPlatformFinance.nonce,
                date_range: dateRange,
                type: type,
                status: status
            },
            success: function(response) {
                if (response.success) {
                    updateTransactionTable(response.data);
                } else {
                    showMessage('error', response.data);
                }
            },
            error: function() {
                showMessage('error', helpPlatformFinance.i18n.requestFailed);
            }
        });
    }

    /**
     * 更新交易记录表格
     */
    function updateTransactionTable(data) {
        var tbody = $('.woocommerce-orders-table tbody');
        tbody.empty();

        if (data.length === 0) {
            tbody.append('<tr><td colspan="5" class="no-transactions">' + 
                helpPlatformFinance.i18n.noTransactions + '</td></tr>');
            return;
        }

        data.forEach(function(transaction) {
            var row = $('<tr>');
            row.append('<td>' + transaction.date + '</td>');
            row.append('<td>' + transaction.type + '</td>');
            row.append('<td>' + transaction.amount + '</td>');
            row.append('<td>' + (transaction.fee || '-') + '</td>');
            row.append('<td><span class="status-badge status-' + 
                transaction.status + '">' + transaction.status_text + '</span></td>');
            tbody.append(row);
        });
    }

    /**
     * 显示消息提示
     */
    function showMessage(type, message) {
        var messageClass = 'help-platform-' + type;
        var messageHtml = '<div class="' + messageClass + '">' + message + '</div>';
        
        // 移除现有消息
        $('.help-platform-error, .help-platform-success, .help-platform-notice').remove();
        
        // 添加新消息
        $('.help-platform-finance-page').prepend(messageHtml);
        
        // 3秒后自动移除
        setTimeout(function() {
            $('.' + messageClass).fadeOut(function() {
                $(this).remove();
            });
        }, 3000);
    }

})(jQuery); 