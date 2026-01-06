/**
 * PdfBadgeHandler - управление бейджем с количеством PDF файлов
 * Обновляет бейдж с количеством загруженных PDF файлов для рабочего заказа
 */

const PdfBadgeHandler = {
    /**
     * Обновляет бейдж с количеством PDF файлов
     * @param {number|string} workorderId - ID рабочего заказа
     */
    async updatePdfCountBadge(workorderId) {
        const pdfBadge = document.getElementById('pdfCountBadge');
        if (!pdfBadge) {
            return;
        }

        try {
            const response = await fetch(`/workorders/${workorderId}/pdfs`);
            if (!response.ok) {
                return;
            }

            const data = await response.json();
            const pdfCount = Array.isArray(data.pdfs) ? data.pdfs.length : 0;

            if (pdfCount > 0) {
                pdfBadge.textContent = pdfCount;
                pdfBadge.classList.remove('d-none');
            } else {
                pdfBadge.textContent = '';
                pdfBadge.classList.add('d-none');
            }
        } catch (error) {
            console.error('Failed to load PDF count:', error);
        }
    },

    /**
     * Инициализирует бейдж при загрузке страницы
     * @param {number|string} workorderId - ID рабочего заказа
     */
    init(workorderId) {
        if (workorderId) {
            this.updatePdfCountBadge(workorderId);
        }
    }
};


