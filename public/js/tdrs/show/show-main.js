/**
 * ShowMain - главный файл инициализации для страницы tdrs.show
 * Координирует работу всех модулей
 */

/**
 * Инициализирует все модули при загрузке страницы
 */
function initTdrsShow() {
    // Инициализация обработчика формы Work Order
    if (typeof WorkOrderFormHandler !== 'undefined') {
        WorkOrderFormHandler.init();
    }

    // Инициализация обработчика PDF библиотеки
    if (typeof PdfViewerHandler !== 'undefined') {
        PdfViewerHandler.init();
    }

    // Инициализация обработчика загрузки PDF
    if (typeof PdfUploadHandler !== 'undefined') {
        PdfUploadHandler.init();
    }

    // Инициализация обработчика удаления PDF
    if (typeof PdfDeleteHandler !== 'undefined') {
        PdfDeleteHandler.init();
    }

    // Инициализация бейджа PDF при загрузке страницы
    const workorderId = window.currentWorkorderId || 
                       document.querySelector('[data-workorder-id]')?.dataset.workorderId ||
                       null;
    
    if (workorderId && typeof PdfBadgeHandler !== 'undefined') {
        PdfBadgeHandler.init(workorderId);
    }

    // Обработка открытия модального окна PDF библиотеки
    document.querySelectorAll('.open-pdf-modal').forEach(button => {
        button.addEventListener('click', async function () {
            const workorderId = this.dataset.id;
            const workorderNumber = this.dataset.number;
            
            window.currentPdfWorkorderId = workorderId;
            window.currentPdfWorkorderNumber = workorderNumber;

            const modalWorkorderNumber = document.getElementById('pdfModalWorkorderNumber');
            if (modalWorkorderNumber) {
                modalWorkorderNumber.textContent = workorderNumber;
            }

            if (typeof PdfLibraryHandler !== 'undefined') {
                await PdfLibraryHandler.loadPdfLibrary(workorderId);
            }

            const pdfModal = document.getElementById('pdfModal');
            if (pdfModal) {
                const modal = new bootstrap.Modal(pdfModal);
                modal.show();
            }
        });
    });

    // Очистка при закрытии основного модального окна PDF Library
    const pdfModal = document.getElementById('pdfModal');
    if (pdfModal) {
        pdfModal.addEventListener('hidden.bs.modal', function () {
            // Очищаем iframe просмотра PDF, если он был открыт
            if (typeof PdfViewerHandler !== 'undefined') {
                PdfViewerHandler.closePdfViewer();
            }
        });
    }
}

// Инициализация при загрузке DOM
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initTdrsShow);
} else {
    // DOM уже загружен
    initTdrsShow();
}


