# Инструкция по загрузке компонентов через CSV

## Что добавлено

В столбец "Action" страницы components.index добавлена кнопка загрузки CSV файла (📤) для каждого manual.

## Как использовать

1. **Подготовка CSV файла**:
   - Скачайте шаблон: нажмите кнопку "Download Template" в модальном окне
   - Или используйте готовый пример: `public/data/components_example.csv`

2. **Загрузка файла**:
   - Нажмите кнопку 📤 в столбце Action для нужного manual
   - Выберите CSV файл
   - Нажмите "Upload Components"

## Формат CSV файла

### Обязательные поля:
- `part_number` - номер детали
- `name` - название компонента  
- `ipl_num` - IPL номер

**Важно**: Один и тот же компонент (part_number) может иметь разные IPL номера (ipl_num) в одном manual. Система проверяет точные дубликаты по всем полям.

### Опциональные поля:
- `assy_part_number` - номер сборки
- `assy_ipl_num` - IPL номер сборки
- `log_card` - флаг карточки (0 или 1)
- `repair` - флаг ремонта (0 или 1)
- `is_bush` - флаг втулки (0 или 1)
- `bush_ipl_num` - IPL номер втулки

## Пример CSV

```csv
part_number,assy_part_number,name,ipl_num,assy_ipl_num,log_card,repair,is_bush,bush_ipl_num
ABC123,ABC123-ASSY,Landing Gear Actuator,123-456,123-456A,1,0,0,
XYZ789,,Hydraulic Pump,789-012,,0,1,0,
```

## Особенности

- **Дублирование**: Если компонент с точно такими же данными уже существует, он будет пропущен
- **Валидация**: Система проверяет обязательные поля и формат данных
- **Обработка ошибок**: Показывает количество новых компонентов и пропущенных дубликатов
- **Автообновление**: Страница автоматически перезагружается после успешной загрузки

## Файлы для изучения

- `resources/views/admin/components/index.blade.php` - обновленный view
- `app/Http/Controllers/Admin/ComponentController.php` - методы uploadCsv и downloadCsvTemplate
- `routes/web.php` - новые маршруты
- `public/data/components_example.csv` - пример файла
- `public/data/README_CSV_Components.md` - подробная документация
