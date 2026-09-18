(() => {
    const printButton = document.querySelector('[data-print-report]');
    if (!printButton) return;

    printButton.addEventListener('click', () => window.print());
    window.setTimeout(() => window.print(), 350);
})();
