import './bootstrap';

/*
 * Login screen: demo-account autofill buttons (dev only). The buttons are
 * only rendered server-side in local environments (AuthController::create),
 * and their emails/passwords come from the server-rendered data attributes.
 */
document.querySelectorAll('[data-fill-account]').forEach((button) => {
    button.addEventListener('click', () => {
        const form = button.closest('form');

        if (!form) {
            return;
        }

        const email = form.querySelector('input[name="email"]');
        const password = form.querySelector('input[name="password"]');

        if (!email || !password) {
            return;
        }

        email.value = button.dataset.email ?? '';
        password.value = button.dataset.password ?? '';

        email.dispatchEvent(new Event('input', { bubbles: true }));
        password.dispatchEvent(new Event('input', { bubbles: true }));
    });
});

/*
 * Decision confirmation dialogs (design system 4): approve/reject open a
 * native <dialog> (elevation-2), never firing instantly on click.
 */
document.addEventListener('click', (event) => {
    const opener = event.target.closest('[data-modal-open]');

    if (opener) {
        event.preventDefault();
        const dialog = document.getElementById(opener.getAttribute('data-modal-open'));

        if (dialog && typeof dialog.showModal === 'function') {
            dialog.showModal();
        }

        return;
    }

    const closer = event.target.closest('[data-modal-close]');

    if (closer) {
        event.preventDefault();
        closer.closest('dialog')?.close();
    }
});

// Clicking the dialog backdrop (target is the dialog element itself) closes it.
document.addEventListener('click', (event) => {
    if (event.target instanceof HTMLDialogElement && event.target.open) {
        event.target.close();
    }
});

/*
 * New Request form: live working-day count + balance preview (FR-REQ-02,
 * design system 5.9). Only Monday–Friday count (weekends excluded), mirroring
 * App\Support\WorkingDayCalculator. All figures come from the server-rendered
 * balances.
 */
const requestForm = document.querySelector('[data-request-form]');

if (requestForm) {
    const startInput = requestForm.querySelector('[name="start_date"]');
    const endInput = requestForm.querySelector('[name="end_date"]');
    const typeSelect = requestForm.querySelector('[name="leave_type_id"]');
    const daysOutput = requestForm.querySelector('[data-days-output]');
    const preview = requestForm.querySelector('[data-balance-preview]');
    const balanceScript = document.getElementById('balance-data');
    const balances = balanceScript ? JSON.parse(balanceScript.textContent || '{}') : {};

    const dayCount = () => {
        if (!startInput?.value || !endInput?.value) {
            return null;
        }

        const start = new Date(`${startInput.value}T00:00:00`);
        const end = new Date(`${endInput.value}T00:00:00`);

        if (Number.isNaN(start.getTime()) || Number.isNaN(end.getTime()) || end < start) {
            return null;
        }

        let days = 0;

        for (const cursor = new Date(start); cursor <= end; cursor.setDate(cursor.getDate() + 1)) {
            const day = cursor.getDay();

            if (day !== 0 && day !== 6) {
                days++;
            }
        }

        return days;
    };

    const render = () => {
        const days = dayCount();

        if (daysOutput) {
            daysOutput.value = days === null ? '—' : String(days);
        }

        if (!preview) {
            return;
        }

        const balance = typeSelect?.value ? balances[typeSelect.value] : null;

        if (days === null || !balance) {
            preview.innerHTML = '<p class="text-neutral-600">Select a leave type and a valid date range to preview how this request affects your balance.</p>';

            return;
        }

        if (days < 1) {
            preview.innerHTML = '<p class="text-amber-800">This range contains no working days (weekends are not counted). Pick at least one weekday.</p>';

            return;
        }

        const remaining = Number(balance.remaining);
        const after = remaining - days;
        const over = after < 0;
        const tone = over ? 'text-amber-800' : 'text-[#1f4a3a]';
        const warning = over
            ? ' This request exceeds your remaining balance and may be rejected.'
            : '';

        preview.innerHTML = `<p class="${tone}">You have <strong>${remaining}</strong> day(s) remaining. This request uses <strong>${days}</strong> day(s), leaving <strong>${after}</strong>.${warning}</p>`;
    };

    [startInput, endInput, typeSelect].forEach((input) => {
        input?.addEventListener('change', render);
        input?.addEventListener('input', render);
    });

    render();
}
