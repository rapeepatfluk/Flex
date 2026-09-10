(() => {
    const statusForm = document.querySelector('#applicantStatusForm');
    if (!statusForm) return;

    const statusInputs = Array.from(statusForm.querySelectorAll('input[name="status"]'));
    const submitButton = statusForm.querySelector('button[type="submit"]');
    const syncSubmitButton = () => {
        if (!submitButton) return;
        const selected = statusInputs.find((input) => input.checked);
        submitButton.disabled = !selected;
        submitButton.textContent = selected ? 'บันทึกสถานะที่เลือก' : 'เลือกสถานะถัดไป';
    };

    statusInputs.forEach((input) => input.addEventListener('change', syncSubmitButton));
    syncSubmitButton();

    const modalElement = document.querySelector('#completedRatingModal');
    const completedInput = statusForm?.querySelector('input[name="status"][value="completed"]');

    if (!modalElement || !completedInput || !window.bootstrap?.Modal) return;

    const ratingModal = window.bootstrap.Modal.getOrCreateInstance(modalElement);
    let previousStatus = statusForm.querySelector('input[name="status"]:checked')?.value ?? '';
    let openedForCompletion = false;
    let isSubmitting = false;

    statusInputs.forEach((input) => {
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
        completedInput.checked = false;
        if (previousInput && previousInput !== completedInput) previousInput.checked = true;
        syncSubmitButton();
        openedForCompletion = false;
    });
})();
