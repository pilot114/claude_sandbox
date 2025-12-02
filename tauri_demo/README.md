# Tauri 2.2 Demo Application

Демонстрационное приложение, показывающее возможности Tauri 2.2 framework.

## Возможности приложения

Это приложение демонстрирует следующие возможности Tauri:

1. **Простые команды** - вызов Rust функций из JavaScript
2. **Обработка ошибок** - корректная обработка ошибок в Rust командах
3. **Структурированные данные** - передача сложных объектов между Rust и JavaScript
4. **Асинхронные операции** - использование async/await в Tauri
5. **State Management** - управление состоянием приложения на стороне Rust

## Технологии

- **Tauri 2.2** - основной framework
- **Rust** - backend логика
- **HTML/CSS/JavaScript** - frontend

## Структура проекта

```
tauri_demo/
├── back/                # Rust код
│   ├── src/
│   │   └── main.rs      # Основной файл с командами
│   ├── Cargo.toml       # Зависимости Rust
│   ├── build.rs         # Build скрипт
│   └── tauri.conf.json  # Конфигурация Tauri
└── front/               # Frontend файлы
    ├── index.html       # HTML
    ├── styles.css       # Стили
    └── app.js           # JavaScript логика
```

## Установка и запуск

### Предварительные требования

1. **Rust** (установить с https://rustup.rs/)
2. **Node.js** (опционально, для более сложных frontend проектов)

#### Системные зависимости для Linux

На Linux необходимо установить следующие библиотеки:

**Ubuntu/Debian:**
```bash
sudo apt update
sudo apt install libwebkit2gtk-4.1-dev \
    build-essential \
    curl \
    wget \
    file \
    libxdo-dev \
    libssl-dev \
    libayatana-appindicator3-dev \
    librsvg2-dev
```

**Arch Linux:**
```bash
sudo pacman -Syu
sudo pacman -S webkit2gtk-4.1 base-devel curl wget file openssl appmenu-gtk-module gtk3 libappindicator-gtk3 librsvg libvips
```

**Fedora:**
```bash
sudo dnf check-update
sudo dnf install webkit2gtk4.1-devel openssl-devel curl wget file libappindicator-gtk3-devel librsvg2-devel
sudo dnf group install "C Development Tools and Libraries"
```

#### macOS

На macOS системные зависимости обычно уже установлены. Может потребоваться Xcode Command Line Tools:
```bash
xcode-select --install
```

#### Windows

На Windows необходимо установить:
- Microsoft C++ Build Tools
- WebView2 (обычно уже установлен в Windows 10/11)

### Установка Tauri CLI

```bash
cargo install tauri-cli --version "^2.0.0"
```

### Запуск в режиме разработки

```bash
cd tauri_demo/back
cargo tauri dev
```

### Сборка для продакшена

```bash
cd tauri_demo/back
cargo tauri build
```

## Мобильные платформы (Android/iOS)

Tauri 2.x поддерживает сборку для Android и iOS.

### Android

#### Предварительные требования

1. **Android Studio** с Android SDK
2. **Java 17+** (OpenJDK или Oracle JDK)
3. **Android NDK** (устанавливается через Android Studio)

```bash
# Проверьте переменные окружения
echo $ANDROID_HOME
echo $NDK_HOME
```

#### Инициализация Android проекта

```bash
cd tauri_demo/back
cargo tauri android init
```

#### Запуск на эмуляторе/устройстве

```bash
# Запуск на эмуляторе
cargo tauri android dev

# Сборка APK для релиза
cargo tauri android build --apk

# Сборка AAB (для Google Play)
cargo tauri android build --bundle aab
```

#### Вспомогательные скрипты

Для упрощения работы с Android доступны готовые скрипты:

```bash
# Инициализация Android проекта
./android-init.sh

# Запуск в режиме разработки
./android-dev.sh

# Сборка APK
./android-build.sh apk

# Сборка AAB
./android-build.sh aab
```

### iOS

#### Предварительные требования

1. **macOS** (обязательно)
2. **Xcode** 13+
3. **Xcode Command Line Tools**

```bash
xcode-select --install
```

#### Инициализация iOS проекта

```bash
cd tauri_demo/back
cargo tauri ios init
```

#### Запуск на симуляторе/устройстве

```bash
# Запуск на симуляторе
cargo tauri ios dev

# Сборка для релиза
cargo tauri ios build
```

## Описание команд

### 1. `greet(name: string)`
Простая команда, возвращающая приветствие.

### 2. `calculate(a: number, b: number, operation: string)`
Калькулятор с обработкой ошибок (деление на ноль).

### 3. `get_system_info()`
Возвращает информацию о системе (ОС, архитектура, версия).

### 4. `async_operation(duration: number)`
Асинхронная операция с задержкой.

### 5. `get_counter()` / `increment_counter()`
Работа с состоянием на стороне Rust.

## Преимущества Tauri

- ⚡ **Легковесность** - приложения в разы меньше, чем Electron
- 🔒 **Безопасность** - встроенные механизмы безопасности
- 🚀 **Производительность** - использование нативных API
- 🎨 **Гибкость** - любой frontend framework (React, Vue, Svelte, etc.)
- 💻 **Кроссплатформенность** - Windows, macOS, Linux, Android, iOS

## Лицензия

MIT
