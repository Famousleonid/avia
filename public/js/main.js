// main.js


function showLoadingSpinner() {
    document.querySelector('#spinner-load').classList.remove('d-none');
}

function hideLoadingSpinner() {
    document.querySelector('#spinner-load').classList.add('d-none');
}


// Глобальная загрузка table-height-adjuster.js
(function() {
    // Проверяем, не загружен ли уже скрипт
    if (typeof adjustTableHeightToRange === 'undefined' && typeof calculateMaxTableRows === 'undefined') {
        // Проверяем, не загружается ли уже скрипт
        var existingScript = document.querySelector('script[src*="table-height-adjuster.js"]');
        if (existingScript) {
            console.log('table-height-adjuster.js уже загружается или загружен');
            return;
        }

        var script = document.createElement('script');

        // Определяем путь к table-height-adjuster.js
        // Файл всегда находится в /js/table-height-adjuster.js относительно корня приложения
        // Используем window.location.origin для получения базового URL
        var baseUrl = window.location.origin;
        var scriptPath = baseUrl + '/js/table-height-adjuster.js';

        script.src = scriptPath;
        script.async = false; // Загружаем синхронно, чтобы функции были доступны сразу
        script.onload = function() {
            // Генерируем событие для уведомления других скриптов
            window.dispatchEvent(new Event('tableHeightAdjusterLoaded'));
        };
        script.onerror = function() {
            console.error('Ошибка загрузки table-height-adjuster.js по пути: ' + scriptPath);
            console.error('Проверьте, существует ли файл по этому пути');
        };
        // Добавляем скрипт в head
        var head = document.head || document.getElementsByTagName('head')[0];
        head.appendChild(script);
    } else {
        console.log('table-height-adjuster.js уже загружен');
        // Генерируем событие сразу, если функция уже доступна
        window.dispatchEvent(new Event('tableHeightAdjusterLoaded'));
    }
})();


