document.addEventListener('DOMContentLoaded', () => {
    const inputs = [...document.querySelectorAll('input[name="work_interests[]"]')];
    const error = document.querySelector('#editInterestError');
    if (!inputs.length || !error) return;

    inputs.forEach(input => input.addEventListener('change', event => {
        if (inputs.filter(item => item.checked).length > 5) {
            event.currentTarget.checked = false;
            error.hidden = false;
            return;
        }
        error.hidden = true;
    }));
});