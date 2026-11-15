// Progressive enhancement of the public frontend. Every page works without
// JavaScript; this only adds comfort.

// After a failed submit the error summary takes the focus, so keyboard and
// screen reader users land on the list of problems (WCAG 3.3.1).
const summary = document.querySelector('[data-error-summary]');

if (summary instanceof HTMLElement) {
    summary.focus();
}

// Guardian consent (W-17): the block is shown only once the birth date makes
// the person a minor at the moment of registration - that is when the
// contract is made. Same rule as the server: of age from the 18th birthday
// on. The server still decides; this only keeps the form short for adults.
const registrationForm = document.querySelector('form[data-registration-date]');

if (registrationForm instanceof HTMLFormElement) {
    const registeredOn = registrationForm.dataset.registrationDate ?? '';

    registrationForm.querySelectorAll('[data-guardian]').forEach((block) => {
        const input = document.getElementById(block.dataset.guardian);
        const notice = registrationForm.querySelector(`[data-guardian-notice="${block.dataset.guardian}"]`);

        if (!(input instanceof HTMLInputElement)) {
            return;
        }

        const update = () => {
            const minor = isMinor(input.value, registeredOn);

            // Kept open where something was entered or the server reported
            // an error, so nothing filled in disappears.
            block.hidden = !(minor || block.hasAttribute('data-keep-open'));

            if (notice instanceof HTMLElement) {
                notice.textContent = minor ? notice.dataset.message ?? '' : '';
            }
        };

        input.addEventListener('input', update);
        input.addEventListener('change', update);
        update();
    });
}

/**
 * @param {string} birthDate as sent by a date input, YYYY-MM-DD
 * @param {string} on the day of registration, YYYY-MM-DD
 * @returns {boolean} false as long as a date is incomplete
 */
function isMinor(birthDate, on) {
    const birth = /^(\d{4})-(\d{2})-(\d{2})$/.exec(birthDate);
    const day = /^(\d{4})-(\d{2})-(\d{2})$/.exec(on);

    if (birth === null || day === null) {
        return false;
    }

    const [year, month, date] = birth.slice(1).map(Number);
    const [onYear, onMonth, onDate] = day.slice(1).map(Number);
    const birthdayPassed = onMonth > month || (onMonth === month && onDate >= date);

    return onYear - year - (birthdayPassed ? 0 : 1) < 18;
}
