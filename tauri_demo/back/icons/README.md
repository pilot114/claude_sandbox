# Icons Directory

Для полноценной работы приложения нужны иконки в следующих форматах:

- 32x32.png
- 128x128.png
- 128x128@2x.png
- icon.icns (для macOS)
- icon.ico (для Windows)

## Генерация иконок

Вы можете использовать Tauri CLI для автоматической генерации иконок из одного PNG файла:

```bash
cargo tauri icon path/to/your/icon.png
```

Или использовать онлайн сервисы:
- https://icon.kitchen/
- https://www.img2go.com/convert-to-ico

## Временное решение

Для разработки можно запускать приложение без иконок. Tauri будет использовать иконки по умолчанию.
