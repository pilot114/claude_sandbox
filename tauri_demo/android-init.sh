#!/bin/bash
# Скрипт для инициализации Android проекта

set -e

echo "🤖 Инициализация Tauri Android проекта..."

# Проверка переменных окружения
if [ -z "$ANDROID_HOME" ]; then
    echo "❌ Ошибка: ANDROID_HOME не установлен"
    echo "Установите Android Studio и добавьте в ~/.bashrc или ~/.zshrc:"
    echo 'export ANDROID_HOME=$HOME/Android/Sdk'
    exit 1
fi

if [ -z "$NDK_HOME" ]; then
    echo "⚠️  Предупреждение: NDK_HOME не установлен"
    echo "Установите NDK через Android Studio SDK Manager"
    echo 'export NDK_HOME=$ANDROID_HOME/ndk/<version>'
fi

# Переход в директорию back
cd "$(dirname "$0")/back"

# Инициализация Android проекта
echo "📦 Запуск cargo tauri android init..."
cargo tauri android init

echo "✅ Android проект инициализирован!"
echo ""
echo "Следующие шаги:"
echo "  1. Запустить эмулятор или подключить устройство"
echo "  2. Выполнить: cd back && cargo tauri android dev"
