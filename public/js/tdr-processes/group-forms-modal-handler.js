/**
 * GroupFormsModalHandler - модуль для обработки модального окна Group Process Forms
 * Отвечает за открытие модального окна при клике на кнопку
 */
class GroupFormsModalHandler {
    /**
     * Открывает модальное окно Group Process Forms
     * @param {Event} e - Событие клика
     */
    static openModal(e) {
        e.preventDefault();
        e.stopPropagation();
        const modalElement = document.getElementById('groupFormsModal');
        if (modalElement) {
            const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
            modal.show();
        } else {
            console.error('Modal #groupFormsModal not found!');
        }
    }

    /**
     * Инициализирует обработчики для открытия модального окна
     */
    static init() {
        // Вариант 1: По атрибуту data-bs-target
        document.querySelectorAll('[data-bs-target="#groupFormsModal"]').forEach(function(button) {
            button.addEventListener('click', GroupFormsModalHandler.openModal);
        });

        // Вариант 2: По классу paper-btn-multy и тексту (для компонента x-paper-button-multy)
        document.querySelectorAll('.paper-btn-multy').forEach(function(button) {
            // Проверяем, содержит ли кнопка или её родитель атрибут data-bs-target
            if (button.hasAttribute('data-bs-target') && button.getAttribute('data-bs-target') === '#groupFormsModal') {
                button.addEventListener('click', GroupFormsModalHandler.openModal);
            } else {
                // Проверяем по тексту внутри SVG
                const svg = button.querySelector('svg');
                if (svg) {
                    const foreignObject = svg.querySelector('foreignObject');
                    if (foreignObject) {
                        const text = foreignObject.textContent.trim();
                        if (text.includes('Group Process Forms')) {
                            button.addEventListener('click', GroupFormsModalHandler.openModal);
                        }
                    }
                }
            }
        });

        // Вариант 3: Обработчик на уровне документа для любых кликов по элементам внутри кнопки
        document.addEventListener('click', function(e) {
            // Проверяем, кликнули ли по элементу внутри кнопки с data-bs-target="#groupFormsModal"
            const button = e.target.closest('[data-bs-target="#groupFormsModal"]');
            if (button) {
                GroupFormsModalHandler.openModal(e);
                return;
            }

            // Проверяем, кликнули ли по SVG или его содержимому внутри paper-btn-multy
            const clickedElement = e.target;
            const paperButton = clickedElement.closest('.paper-btn-multy');
            if (paperButton) {
                // Проверяем атрибут или текст
                if (paperButton.hasAttribute('data-bs-target') &&
                    paperButton.getAttribute('data-bs-target') === '#groupFormsModal') {
                    GroupFormsModalHandler.openModal(e);
                    return;
                }

                // Проверяем по тексту
                const svg = paperButton.querySelector('svg');
                if (svg) {
                    const foreignObject = svg.querySelector('foreignObject');
                    if (foreignObject) {
                        const text = foreignObject.textContent.trim();
                        if (text.includes('Group Process Forms')) {
                            GroupFormsModalHandler.openModal(e);
                        }
                    }
                }
            }
        });
    }
}

// Экспорт для использования в других модулях
if (typeof module !== 'undefined' && module.exports) {
    module.exports = GroupFormsModalHandler;
}



