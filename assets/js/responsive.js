/**
 * HELP Platform 响应式功能
 */
(function($) {
    'use strict';

    // 设备模式状态
    let deviceMode = {
        isMobile: false,
        isTablet: false,
        isDesktop: true
    };

    // 初始化
    function init() {
        // 添加设备切换按钮
        addDeviceSwitchButton();
        
        // 监听窗口大小变化
        $(window).on('resize', handleResize);
        
        // 初始检查设备类型
        checkDeviceType();
    }

    // 添加设备切换按钮
    function addDeviceSwitchButton() {
        const button = $('<div>', {
            class: 'help-platform-device-switch',
            title: '切换设备视图模式'
        }).append(
            $('<span>', {
                class: 'dashicons dashicons-smartphone'
            })
        );

        $('body').append(button);

        // 点击切换设备模式
        button.on('click', toggleDeviceMode);
    }

    // 切换设备模式
    function toggleDeviceMode() {
        const currentUrl = window.location.href;
        
        if (!deviceMode.isMobile) {
            // 切换到移动设备模式
            if (!$('.help-platform-mobile-mode').length) {
                const mobileContainer = $('<div>', {
                    class: 'help-platform-mobile-mode'
                }).append(
                    $('<iframe>', {
                        src: currentUrl,
                        id: 'help-platform-mobile-frame'
                    })
                );

                $('body').append(mobileContainer);
                $('body').addClass('help-platform-mobile-view');
                
                // 更新按钮图标
                $('.help-platform-device-switch .dashicons')
                    .removeClass('dashicons-smartphone')
                    .addClass('dashicons-desktop');
                
                deviceMode.isMobile = true;
                deviceMode.isDesktop = false;
            }
        } else {
            // 切换回桌面模式
            $('.help-platform-mobile-mode').remove();
            $('body').removeClass('help-platform-mobile-view');
            
            // 更新按钮图标
            $('.help-platform-device-switch .dashicons')
                .removeClass('dashicons-desktop')
                .addClass('dashicons-smartphone');
            
            deviceMode.isMobile = false;
            deviceMode.isDesktop = true;
        }

        // 保存设备模式状态
        localStorage.setItem('help_platform_device_mode', deviceMode.isMobile ? 'mobile' : 'desktop');
    }

    // 处理窗口大小变化
    function handleResize() {
        checkDeviceType();
    }

    // 检查设备类型
    function checkDeviceType() {
        const width = $(window).width();
        
        // 更新设备状态
        deviceMode.isMobile = width < 768;
        deviceMode.isTablet = width >= 768 && width < 992;
        deviceMode.isDesktop = width >= 992;

        // 根据设备类型添加相应的类
        $('body').removeClass('help-platform-mobile help-platform-tablet help-platform-desktop')
            .addClass(deviceMode.isMobile ? 'help-platform-mobile' :
                     deviceMode.isTablet ? 'help-platform-tablet' : 'help-platform-desktop');

        // 触发自定义事件
        $(document).trigger('help_platform_device_change', [deviceMode]);
    }

    // 在文档加载完成后初始化
    $(document).ready(function() {
        init();

        // 检查是否有保存的设备模式设置
        const savedMode = localStorage.getItem('help_platform_device_mode');
        if (savedMode === 'mobile' && !deviceMode.isMobile) {
            toggleDeviceMode();
        }
    });

    // 暴露公共方法
    window.HelpPlatformResponsive = {
        getDeviceMode: function() {
            return deviceMode;
        },
        toggleDeviceMode: toggleDeviceMode,
        isMobile: function() {
            return deviceMode.isMobile;
        },
        isTablet: function() {
            return deviceMode.isTablet;
        },
        isDesktop: function() {
            return deviceMode.isDesktop;
        }
    };

})(jQuery); 