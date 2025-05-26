<?php

/**
 * 返回插件数据（示例：返回插件版本）
 *
 * @return string 插件版本
 */
function help_platform_get_plugin_data() {
	return HELP_PLATFORM_VERSION;
}

/**
 * 返回插件 URL（示例：返回插件 URL 常量）
 *
 * @return string 插件 URL
 */
function help_platform_get_plugin_url() {
	return HELP_PLATFORM_PLUGIN_URL;
}

// 在插件加载时引入该文件，确保函数定义生效
// require_once( __FILE__ ); 