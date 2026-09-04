(() => {
    const statusForm = document.querySelector('#applicantStatusForm');
    const modalElement = document.querySelector('#completedRatingModal');
    const completedInput = statusForm?.querySelector('input[name="status"][value="completed"]');

    if (!statusForm || !modalElement || !completedInput || !window.bootstrap?.Modal) return;

    const ratingModal = window.bootstrap.Modal.getOrCreateInstance(modalElement);
    let previousStatus = statusForm.querySelector('input[name="status"]:checked')?.value ?? '';
    let openedForCompletion = false;
    let isSubmitting = false;

    statusForm.querySelectorAll('input[name="status"]').forEach((input) => {
        input.addEventListener('change', () => {
            if (input.value !== 'completed') {
                previousStatus = input.value;
                return;
            }

            openedForCompletion = true;
            ratingModal.show();
        });
    });

    modalElement.querySelector('#completedRatingForm')?.addEventListener('submit', () => {
        isSubmitting = true;
    });

    modalElement.addEventListener('hidden.bs.modal', () => {
        if (!openedForCompletion || isSubmitting) return;

        const previousInput = Array.from(statusForm.querySelectorAll('input[name="status"]'))
            .find((input) => input.value === previousStatus);
        if (previousInput) previousInput.checked = true;
        openedForCompletion = false;
    });
})();
