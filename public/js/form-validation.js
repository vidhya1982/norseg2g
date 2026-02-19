window.FormValidator = (function () {

    const rules = {
        required: v => v.trim() !== "",
        email: v => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v),
        min: (v, len) => v.trim().length >= Number(len),
        numeric: v => /^\d+$/.test(v),
    };

    function showError(input, message) {
        input.classList.add("is-invalid");

        let fb = input.nextElementSibling;
        if (!fb || !fb.classList.contains("invalid-feedback")) {
            fb = document.createElement("div");
            fb.className = "invalid-feedback";
            input.after(fb);
        }
        fb.innerText = message;
    }

    function clearError(input) {
        input.classList.remove("is-invalid");
        let fb = input.nextElementSibling;
        if (fb && fb.classList.contains("invalid-feedback")) fb.remove();
    }

    function validateInput(input) {
        clearError(input);

        const validations = input.dataset.validate?.split("|") || [];

        for (let rule of validations) {
            let [name, param] = rule.split(":");

            if (!rules[name]) continue;

            if (!rules[name](input.value, param)) {
                showError(
                    input,
                    input.dataset.message || "Invalid field"
                );
                return false;
            }
        }
        return true;
    }

    function validateForm(form) {
        let ok = true;
        form.querySelectorAll("[data-validate]").forEach(inp => {
            if (!validateInput(inp)) ok = false;
        });
        return ok;
    }

    function attach(form) {
        form.querySelectorAll("[data-validate]").forEach(inp => {
            inp.addEventListener("input", () => validateInput(inp));
        });
    }

    return { attach, validateForm };

})();

window.formHandler = () => ({
    handleSubmit(event) {
        event.preventDefault();

        const form = event.target;

        // attach live validation once
        FormValidator.attach(form);

        // validate form
        const isValid = FormValidator.validateForm(form);
        if (!isValid) {
            return false; // STOP EVERYTHING
        }

        // Livewire or normal submit
        const livewireMethod = form.dataset.livewireSubmit;

        if (livewireMethod && window.Livewire) {
            const component = form.closest("[wire\\:id]");
            if (!component) return false;

            Livewire
                .find(component.getAttribute("wire:id"))
                .call(livewireMethod);
        } else {
            form.submit(); // normal HTML submit
        }
    },
});

document.addEventListener('DOMContentLoaded', function () {

    /* ===============================
       SHOW / HIDE PASSWORD
    =============================== */
    document.querySelectorAll('.toggle-password').forEach(toggle => {
        toggle.addEventListener('click', function () {

            const wrapper = this.closest('.password-field');
            if (!wrapper) return;

            const input = wrapper.querySelector('.password-input');
            const icon  = this.querySelector('i');

            if (!input || !icon) return;

            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        });
    });

    /* ===============================
      CONFIRM PASSWORD CHECK
    =============================== */
    const form = document.querySelector(
        'form[wire\\:submit\\.prevent="updatePassword"]'
    );

    if (!form) return;

    form.addEventListener('submit', function (e) {

        const password = document.getElementById('new_password');
        const confirm  = document.getElementById('confirm_password');

        if (!password || !confirm) return;

        const passWrap = password.closest('.floating-input');
        const confWrap = confirm.closest('.floating-input');

        passWrap?.classList.remove('input-error', 'shake');
        confWrap?.classList.remove('input-error', 'shake');

        if (password.value !== confirm.value) {

            e.preventDefault(); // stop Livewire submit

            passWrap?.classList.add('input-error', 'shake');
            confWrap?.classList.add('input-error', 'shake');

            window.dispatchEvent(new CustomEvent('toast', {
                detail: {
                    type: 'error',
                    message: 'Password and confirm password do not match'
                }
            }));
        }
    });
});