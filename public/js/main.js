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
        var script = document.createElement('script');
        
        // Определяем путь к table-height-adjuster.js
        var scriptPath = '/js/table-height-adjuster.js'; // Путь по умолчанию
        
        // Пытаемся найти скрипт main.js в DOM для определения базового пути
        var mainScript = document.querySelector('script[src*="main.js"]');
        if (mainScript && mainScript.src) {
            // Извлекаем базовый путь из src main.js
            var mainScriptPath = mainScript.src;
            var basePath = mainScriptPath.substring(0, mainScriptPath.lastIndexOf('/'));
            scriptPath = basePath + '/table-height-adjuster.js';
        }
        
        script.src = scriptPath;
        script.async = false; // Загружаем синхронно, чтобы функции были доступны сразу
        script.onload = function() {
            console.log('table-height-adjuster.js загружен глобально');
        };
        script.onerror = function() {
            console.error('Ошибка загрузки table-height-adjuster.js по пути: ' + scriptPath);
        };
        // Добавляем скрипт в head
        var head = document.head || document.getElementsByTagName('head')[0];
        head.appendChild(script);
    }
})();


