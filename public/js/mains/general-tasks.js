document.addEventListener('DOMContentLoaded', () => {
    const panel = document.querySelector('.main-tasks-notes-window');
    const buttons = Array.from(panel?.querySelectorAll('.js-gt-btn') || []);
    if (buttons.length === 0) return;

    const workorderId = panel.closest('[data-main-tabs]').dataset.woId;
    function activateStage(stageId) {
        document.documentElement.dataset.mainGt = stageId;
        buttons.forEach(button => {
            const active = button.dataset.gtId === stageId;
            button.classList.toggle('active', active);
            button.setAttribute('aria-pressed', String(active));
        });
    }

    activateStage(document.documentElement.dataset.mainGt);
    panel.addEventListener('click', event => {
        const button = event.target.closest('.js-gt-btn');
        if (!button) return;

        const stageId = button.dataset.gtId;
        activateStage(stageId);
        window.__mainsInitDatePickers?.();
        window.UserUiSettings.set('mains.show', `generalTaskTab:${workorderId}`, stageId);
    });
});
