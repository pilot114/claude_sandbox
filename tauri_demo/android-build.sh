#!/bin/bash
# Скрипт для сборки Android приложения

set -e

BUILD_TYPE=${1:-apk}  # apk или aab

echo "🤖 Сборка Tauri Android приложения ($BUILD_TYPE)..."

# Переход в директорию back
cd "$(dirname "$0")/back"

# Проверка инициализации
if [ ! -d "gen/android" ]; then
    echo "❌ Ошибка: Android проект не инициализирован"
    echo "Выполните: ./android-init.sh"
    exit 1
fi

# Сборка
if [ "$BUILD_TYPE" = "aab" ]; then
    echo "📦 Сборка AAB (для Google Play)..."
    cargo tauri android build --bundle aab
else
    echo "📦 Сборка APK..."
    cargo tauri android build --apk
fi

echo "✅ Сборка завершена!"
echo ""
echo "APK/AAB находится в: back/gen/android/app/build/outputs/"
