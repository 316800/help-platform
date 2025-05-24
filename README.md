# HELP 全球生活服务平台

HELP 全球生活服务平台是一个功能强大的 WordPress 插件，为生活服务平台提供完整的解决方案。

## 功能特点

- 实名认证系统
- 任务发布与管理
- 工人管理
- 财务管理
- 分公司管理
- 佣金系统
- 批量导入工人
- 转账管理

## 系统要求

- WordPress 5.0 或更高版本
- PHP 7.4 或更高版本
- MySQL 5.7 或更高版本
- WooCommerce 5.0 或更高版本

## 安装说明

1. 下载插件压缩包
2. 在 WordPress 后台进入"插件 > 安装插件"
3. 点击"上传插件"按钮
4. 选择下载的压缩包并上传
5. 激活插件
6. 在"设置 > HELP 平台"中配置插件

## 使用说明

### 实名认证
- 使用短代码 `[help_verify]` 显示认证表单
- 支持身份证照片和自拍照片上传
- 后台审核认证申请

### 任务管理
- 使用短代码 `[help_job]` 显示任务发布表单
- 支持任务分类、地点标记
- 工人可申请接单
- 完整的任务状态管理

### 财务管理
- 支持充值、提现
- 佣金自动计算
- 分公司独立财务
- 转账管理
- 财务报表导出

### 工人管理
- 批量导入工人信息
- 工人资质管理
- 技能标签
- 工作经验记录

## 开发说明

### 目录结构
```
help-platform/
├── assets/          # CSS、JS 和图片资源
├── includes/        # PHP 类文件
├── languages/       # 翻译文件
├── templates/       # 模板文件
├── vendor/          # Composer 依赖
└── help-platform.php # 主插件文件
```

### 开发环境设置
1. 克隆仓库
```bash
git clone https://github.com/your-username/help-platform.git
```

2. 安装依赖
```bash
composer install
```

3. 配置开发环境
- 复制 `.env.example` 为 `.env`
- 修改配置参数

### 贡献指南
1. Fork 项目
2. 创建特性分支 (`git checkout -b feature/AmazingFeature`)
3. 提交更改 (`git commit -m 'Add some AmazingFeature'`)
4. 推送到分支 (`git push origin feature/AmazingFeature`)
5. 创建 Pull Request

## 版本历史

- 1.0.0
  - 初始版本发布
  - 基础功能实现
  - 实名认证系统
  - 任务管理
  - 财务管理
  - 工人管理

## 许可证

本项目采用 GPL v2 或更高版本许可证 - 查看 [LICENSE](LICENSE) 文件了解详情

## 联系方式

- 项目维护者：HELP Team
- 项目链接：https://github.com/your-username/help-platform
- 官方网站：https://help-platform.com 