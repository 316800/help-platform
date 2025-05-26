# HELP Platform WordPress插件

HELP Platform是一个帮助管理分公司、工作者和任务的WordPress插件。

## 功能特点

- 分公司管理
  - 分公司信息管理
  - 分公司佣金设置
  - 分公司数据统计

- 佣金管理
  - 平台佣金设置
  - 分公司佣金设置
  - 工作者佣金设置
  - 佣金计算
  - 佣金记录
  - 佣金统计
  - 数据导出

- 任务管理
  - 任务发布
  - 任务审核
  - 任务状态管理
  - 任务统计

- 用户管理
  - 实名认证
  - 角色权限
  - 消息通知

## 系统要求

- WordPress 5.0+
- PHP 7.2+
- MySQL 5.6+

## 安装说明

1. 下载插件压缩包
2. 在WordPress后台进入"插件 > 安装插件"
3. 点击"上传插件"按钮
4. 选择下载的压缩包并上传
5. 上传完成后点击"启用插件"

## 使用说明

### 分公司管理

1. 在后台菜单中点击"HELP Platform > 分公司管理"
2. 点击"添加分公司"创建新分公司
3. 填写分公司信息并保存
4. 在分公司列表中可以查看、编辑、删除分公司

### 佣金设置

1. 在分公司详情页面点击"佣金设置"
2. 设置平台佣金比例
3. 设置分公司佣金比例
4. 设置工作者佣金比例
5. 设置最低/最高佣金金额
6. 设置税率
7. 点击保存

### 佣金统计

1. 在后台菜单中点击"HELP Platform > 佣金统计"
2. 选择分公司、日期范围和状态进行筛选
3. 查看佣金统计数据
4. 点击"导出数据"下载CSV文件

### 任务管理

1. 在后台菜单中点击"HELP Platform > 任务管理"
2. 点击"发布任务"创建新任务
3. 填写任务信息并保存
4. 在任务列表中可以查看、编辑、删除任务

### 实名认证

1. 在页面中插入`[help_verify]`短代码
2. 用户填写认证信息并提交
3. 管理员在后台审核认证信息
4. 审核通过后用户可以发布任务

## 开发说明

### 目录结构

```
help-platform/
├── assets/
│   ├── css/
│   └── js/
├── includes/
│   ├── class-help-platform-branch.php
│   ├── class-help-platform-commission.php
│   └── class-help-platform-db.php
├── languages/
│   └── help-platform-zh_CN.po
├── templates/
│   ├── admin-branch-commission.php
│   ├── admin-branch-statistics.php
│   └── admin-commission.php
├── tests/
│   ├── bootstrap.php
│   └── class-help-platform-test.php
├── help-platform.php
├── phpunit.xml
└── README.md
```

### 运行测试

1. 安装PHPUnit
2. 运行测试安装脚本:
```bash
bin/install-wp-tests.sh wordpress_test root root localhost latest
```
3. 运行测试:
```bash
phpunit
```

### 添加新功能

1. 在`includes/`目录下创建新的类文件
2. 在`help-platform.php`中加载新类
3. 在`templates/`目录下创建新的模板文件
4. 在`languages/`目录下更新语言文件
5. 在`tests/`目录下添加新的测试用例

## 贡献指南

1. Fork本仓库
2. 创建新的功能分支
3. 提交更改
4. 推送到分支
5. 创建Pull Request

## 许可证

GPL v2 或更高版本

## 联系方式

- 网站: https://help-platform.com
- 邮箱: support@help-platform.com 