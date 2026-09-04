document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-password-toggle]').forEach((button) => {
        const input = document.getElementById(button.getAttribute('aria-controls'));
        if (!input) return;
        button.addEventListener('click', () => {
            const reveal = input.type === 'password';
            input.type = reveal ? 'text' : 'password';
            button.setAttribute('aria-pressed', String(reveal));
            button.querySelector('span:not(.visually-hidden)').textContent = reveal ? 'ซ่อน' : 'แสดง';
        });
    });

    const companyField = document.querySelector('[data-company-field]');
    const companyInput = companyField?.querySelector('input');
    const syncCompanyField = () => {
        const isEmployer = document.querySelector('input[name="role"]:checked')?.value === 'employer';
        if (!companyField || !companyInput) return;
        companyField.hidden = !isEmployer;
        companyInput.required = isEmployer;
    };
    document.querySelectorAll('input[name="role"]').forEach((input) => input.addEventListener('change', syncCompanyField));
    syncCompanyField();
});
