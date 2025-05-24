#!/bin/bash

# 确保 msgfmt 命令可用
if ! command -v msgfmt &> /dev/null; then
    echo "错误：未找到 msgfmt 命令。请安装 gettext 工具包。"
    exit 1
fi

# 编译所有 .po 文件
for po_file in languages/*.po; do
    if [ -f "$po_file" ]; then
        mo_file="${po_file%.po}.mo"
        echo "编译 $po_file 到 $mo_file"
        msgfmt -o "$mo_file" "$po_file"
    fi
done

echo "翻译文件编译完成！" 