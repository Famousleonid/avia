/**
 * PackageModalHandler - модуль для обработки модального окна Package Process Forms
 * Отвечает за загрузку процессов, отображение в таблице и формирование многостраничной страницы
 */
class PackageModalHandler {
    /**
     * Инициализирует обработчики для модального окна Package
     */
    static init() {
        const packageModal = document.getElementById('packageModal');
        if (!packageModal) {
            console.warn('Package modal not found');
            return;
        }

        // Обработчик открытия модального окна
        packageModal.addEventListener('show.bs.modal', function() {
            PackageModalHandler.loadProcesses();
        });

        // Обработчик кнопки Package
        const packageButton = document.getElementById('packageButton');
        if (packageButton) {
            packageButton.addEventListener('click', PackageModalHandler.handlePackage);
        }
    }

    /**
     * Загружает все процессы для текущего компонента
     */
    static loadProcesses() {
        const tbody = document.getElementById('packageProcessesTableBody');
        if (!tbody) {
            console.error('Package processes table body not found');
            return;
        }

        // Получаем tdrId из URL или из данных на странице
        const tdrId = PackageModalHandler.getTdrId();
        if (!tdrId) {
            console.error('TDR ID not found');
            tbody.innerHTML = '<tr><td colspan="4" class="text-center">Error: TDR ID not found</td></tr>';
            return;
        }

        // Получаем данные о процессах из таблицы на странице
        const processes = PackageModalHandler.extractProcessesFromPage();
        
        if (processes.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center">No processes found for this component</td></tr>';
            return;
        }

        // Очищаем таблицу
        tbody.innerHTML = '';

        // Получаем список vendors
        const vendors = PackageModalHandler.getVendors();

        // Заполняем таблицу
        processes.forEach((process, index) => {
            const row = PackageModalHandler.createProcessRow(process, vendors, index);
            tbody.appendChild(row);
        });
    }

    /**
     * Извлекает процессы из таблицы на странице
     */
    static extractProcessesFromPage() {
        const processes = [];
        const tableRows = document.querySelectorAll('#sortable-tbody tr');
        
        tableRows.forEach(row => {
            const processNameCell = row.querySelector('td:first-child');
            const processCell = row.querySelector('td:nth-child(2)');
            const formLink = row.querySelector('.form-link');
            
            if (processNameCell && processCell && formLink) {
                const processName = processNameCell.textContent.trim();
                const processText = processCell.textContent.trim();
                const tdrProcessId = formLink.getAttribute('data-tdr-process-id');
                const processId = formLink.getAttribute('data-process');
                
                if (processName && tdrProcessId && processId) {
                    processes.push({
                        processName: processName,
                        processText: processText,
                        tdrProcessId: tdrProcessId,
                        processId: processId
                    });
                }
            }
        });

        return processes;
    }

    /**
     * Получает список vendors из селекта на странице
     */
    static getVendors() {
        const vendors = [];
        const vendorSelect = document.querySelector('.vendor-select');
        if (vendorSelect) {
            vendorSelect.querySelectorAll('option').forEach(option => {
                if (option.value) {
                    vendors.push({
                        id: option.value,
                        name: option.textContent.trim()
                    });
                }
            });
        }
        return vendors;
    }

    /**
     * Создает строку таблицы для процесса
     */
    static createProcessRow(process, vendors, index) {
        const row = document.createElement('tr');
        row.setAttribute('data-tdr-process-id', process.tdrProcessId);
        row.setAttribute('data-process-id', process.processId);

        // Колонка Process Name
        const processNameCell = document.createElement('td');
        processNameCell.className = 'align-middle';
        processNameCell.textContent = process.processName;
        row.appendChild(processNameCell);

        // Колонка Process
        const processCell = document.createElement('td');
        processCell.className = 'align-middle';
        processCell.textContent = process.processText;
        row.appendChild(processCell);

        // Колонка Vendor
        const vendorCell = document.createElement('td');
        vendorCell.className = 'align-middle';
        const vendorSelect = document.createElement('select');
        vendorSelect.className = 'form-select form-select-sm package-vendor-select';
        vendorSelect.setAttribute('data-tdr-process-id', process.tdrProcessId);
        vendorSelect.setAttribute('data-process-id', process.processId);
        
        const defaultOption = document.createElement('option');
        defaultOption.value = '';
        defaultOption.textContent = 'Select Vendor';
        vendorSelect.appendChild(defaultOption);

        vendors.forEach(vendor => {
            const option = document.createElement('option');
            option.value = vendor.id;
            option.textContent = vendor.name;
            vendorSelect.appendChild(option);
        });

        vendorCell.appendChild(vendorSelect);
        row.appendChild(vendorCell);

        // Колонка Select
        const selectCell = document.createElement('td');
        selectCell.className = 'text-center align-middle';
        const checkbox = document.createElement('input');
        checkbox.type = 'checkbox';
        checkbox.className = 'form-check-input package-process-checkbox';
        checkbox.setAttribute('data-tdr-process-id', process.tdrProcessId);
        checkbox.setAttribute('data-process-id', process.processId);
        checkbox.id = `package-checkbox-${index}`;
        
        const label = document.createElement('label');
        label.className = 'form-check-label';
        label.setAttribute('for', checkbox.id);
        label.style.cursor = 'pointer';
        label.style.marginLeft = '0.5rem';

        selectCell.appendChild(checkbox);
        selectCell.appendChild(label);
        row.appendChild(selectCell);

        return row;
    }

    /**
     * Получает TDR ID из URL
     */
    static getTdrId() {
        // Пытаемся получить из URL
        const urlMatch = window.location.pathname.match(/\/tdr\/(\d+)\/processes/);
        if (urlMatch) {
            return urlMatch[1];
        }
        
        // Пытаемся получить из данных на странице
        const tdrIdElement = document.querySelector('[data-tdr-id]');
        if (tdrIdElement) {
            return tdrIdElement.getAttribute('data-tdr-id');
        }

        return null;
    }

    /**
     * Обрабатывает нажатие на кнопку Package
     */
    static handlePackage() {
        const selectedProcesses = PackageModalHandler.getSelectedProcesses();
        
        if (selectedProcesses.length === 0) {
            alert('Please select at least one process');
            return;
        }

        const tdrId = PackageModalHandler.getTdrId();
        if (!tdrId) {
            alert('Error: TDR ID not found');
            return;
        }

        // Формируем URL для генерации многостраничной страницы
        const params = new URLSearchParams();
        params.append('processes', JSON.stringify(selectedProcesses));
        
        const url = `/tdr/${tdrId}/package-forms?${params.toString()}`;
        
        // Открываем в новом окне
        window.open(url, '_blank');
        
        // Закрываем модальное окно
        const packageModal = bootstrap.Modal.getInstance(document.getElementById('packageModal'));
        if (packageModal) {
            packageModal.hide();
        }
    }

    /**
     * Получает выбранные процессы с их vendor
     */
    static getSelectedProcesses() {
        const selectedProcesses = [];
        const checkboxes = document.querySelectorAll('.package-process-checkbox:checked');
        
        checkboxes.forEach(checkbox => {
            const tdrProcessId = checkbox.getAttribute('data-tdr-process-id');
            const processId = checkbox.getAttribute('data-process-id');
            
            // Находим соответствующий select vendor
            const vendorSelect = document.querySelector(
                `.package-vendor-select[data-tdr-process-id="${tdrProcessId}"][data-process-id="${processId}"]`
            );
            
            const vendorId = vendorSelect ? vendorSelect.value : '';
            
            selectedProcesses.push({
                tdr_process_id: tdrProcessId,
                process_id: processId,
                vendor_id: vendorId
            });
        });

        return selectedProcesses;
    }
}

// Инициализация при загрузке DOM
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        PackageModalHandler.init();
    });
} else {
    PackageModalHandler.init();
}

// Экспорт для использования в других модулях
if (typeof module !== 'undefined' && module.exports) {
    module.exports = PackageModalHandler;
}

