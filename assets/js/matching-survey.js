document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('#matchingSurvey');
    if (!form) return;

    const steps = [...form.querySelectorAll('[data-survey-step]')];
    const previous = document.querySelector('#surveyPrevious');
    const next = document.querySelector('#surveyNext');
    const submit = document.querySelector('#surveySubmit');
    const label = document.querySelector('#surveyStepLabel');
    const percent = document.querySelector('#surveyPercent');
    const bar = document.querySelector('#surveyProgressBar');
    const workInterestError = document.querySelector('#workInterestError');
    const preferenceError = document.querySelector('#preferenceError');
    const skillError = document.querySelector('#surveySkillError');
    let current = 0;

    const setGroupValidity = (inputs, valid) => {
        inputs.forEach((input) => {
            if (valid) input.removeAttribute('aria-invalid');
            else input.setAttribute('aria-invalid', 'true');
        });
    };

    function showStep(index) {
        current = Math.max(0, Math.min(steps.length - 1, index));
        steps.forEach((step, position) => { step.hidden = position !== current; });
        const progress = Math.round((current + 1) * 100 / steps.length);
        label.textContent = `ขั้นตอน ${current + 1} จาก ${steps.length}`;
        percent.textContent = `${progress}%`;
        bar.style.width = `${progress}%`;
        previous.hidden = current === 0;
        next.hidden = current === steps.length - 1;
        submit.hidden = current !== steps.length - 1;
        steps[current].querySelector('input, textarea')?.focus({ preventScroll: true });
        window.scrollTo({ top: form.offsetTop - 110, behavior: 'smooth' });
    }

    function currentStepIsValid() {
        if (current === 0) {
            const inputs = [...form.querySelectorAll('input[name="work_interests[]"]')];
            const count = inputs.filter((input) => input.checked).length;
            const valid = count >= 1 && count <= 5;
            workInterestError.hidden = valid;
            setGroupValidity(inputs, valid);
            return valid;
        }
        if (current === 1) {
            const inputs = [...form.querySelectorAll('input[name="job_preferences[]"]')];
            const valid = inputs.some((input) => input.checked);
            preferenceError.hidden = valid;
            setGroupValidity(inputs, valid);
            return valid;
        }
        if (current === 2) {
            const inputs = [...form.querySelectorAll('input[name="skill_ids[]"]')];
            const customInput = form.querySelector('input[name="custom_skills"]');
            const valid = inputs.some((input) => input.checked) || Boolean(customInput?.value.trim());
            skillError.hidden = valid;
            setGroupValidity(inputs, valid);
            if (customInput) {
                customInput.setAttribute('aria-describedby', 'surveySkillError');
                if (valid) customInput.removeAttribute('aria-invalid');
                else customInput.setAttribute('aria-invalid', 'true');
            }
            return valid;
        }

        const fields = [...steps[current].querySelectorAll('input, textarea, select')];
        const invalid = fields.find((field) => !field.checkValidity());
        if (invalid) {
            invalid.setAttribute('aria-invalid', 'true');
            invalid.reportValidity();
        }
        return !invalid;
    }

    next.addEventListener('click', () => {
        if (currentStepIsValid()) showStep(current + 1);
    });
    previous.addEventListener('click', () => showStep(current - 1));

    form.querySelectorAll('input[name="work_interests[]"]').forEach((input) => input.addEventListener('change', (event) => {
        const selected = form.querySelectorAll('input[name="work_interests[]"]:checked');
        if (selected.length > 5) {
            event.currentTarget.checked = false;
            workInterestError.textContent = 'เลือกได้สูงสุด 5 หมวด';
            workInterestError.hidden = false;
        } else {
            workInterestError.textContent = 'กรุณาเลือก 1–5 หมวด';
            workInterestError.hidden = true;
            setGroupValidity([...form.querySelectorAll('input[name="work_interests[]"]')], true);
        }
    }));
    form.querySelectorAll('input[name="job_preferences[]"], input[name="skill_ids[]"]').forEach((input) => input.addEventListener('change', () => {
        if (input.name === 'job_preferences[]') preferenceError.hidden = true;
        if (input.name === 'skill_ids[]' || input.name === 'custom_skills') skillError.hidden = true;
        input.removeAttribute('aria-invalid');
    }));
    form.querySelector('input[name="custom_skills"]')?.addEventListener('input', (event) => {
        skillError.hidden = true;
        event.currentTarget.removeAttribute('aria-invalid');
    });
    form.addEventListener('submit', (event) => {
        if (!currentStepIsValid()) event.preventDefault();
    });
    showStep(0);
});
