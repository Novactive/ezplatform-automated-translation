(function (global, doc, ibexa, bootstrap) {
    const containers = doc.querySelectorAll('.ibexa-auto-translation-actions-table');
    const showPopup = ({ currentTarget: btn }) => {
        const selector = `[data-action-logs-popup="${btn.dataset.uiComponent}-${btn.dataset.actionId}"]`;
        const modal = doc.querySelector(selector);
        bootstrap.Modal.getOrCreateInstance(modal).show();
    };

    containers.forEach((container) => {
        container.querySelectorAll('.ibexa-btn--translation-actions-chart').forEach((btn) => {
            btn.addEventListener('click', showPopup, false);
        });
    });
})(window, window.document, window.ibexa, window.bootstrap);
