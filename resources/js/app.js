// Progressive enhancement of the public frontend. Every page works without
// JavaScript; this only adds comfort.

// After a failed submit the error summary takes the focus, so keyboard and
// screen reader users land on the list of problems (WCAG 3.3.1).
const summary = document.querySelector('[data-error-summary]');

if (summary instanceof HTMLElement) {
    summary.focus();
}

// Guardian consent (W-17): the block is shown only once the birth date makes
// the person a minor on race day. Same rule as the server - of age from the
// 18th birthday on the race day; without a race date by birth year. The
// server still decides; this only keeps the form short for adults.
const registrationForm = document.querySelector('form[data-race-date], form[data-race-year]');

if (registrationForm instanceof HTMLFormElement) {
    registrationForm.querySelectorAll('[data-guardian]').forEach((block) => {
        const input = document.getElementById(block.dataset.guardian);
        const notice = registrationForm.querySelector(`[data-guardian-notice="${block.dataset.guardian}"]`);

        if (!(input instanceof HTMLInputElement)) {
            return;
        }

        const update = () => {
            const minor = isMinor(input.value, registrationForm.dataset);

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
 * @param {DOMStringMap} race data-race-date or data-race-year of the form
 * @returns {boolean} false as long as the date is incomplete
 */
function isMinor(birthDate, race) {
    const birth = /^(\d{4})-(\d{2})-(\d{2})$/.exec(birthDate);

    if (birth === null) {
        return false;
    }

    const [year, month, day] = birth.slice(1).map(Number);

    if (race.raceDate === undefined) {
        return Number(race.raceYear) - year < 18;
    }

    const [raceYear, raceMonth, raceDay] = race.raceDate.split('-').map(Number);
    const birthdayPassed = raceMonth > month || (raceMonth === month && raceDay >= day);
    const age = raceYear - year - (birthdayPassed ? 0 : 1);

    return age < 18;
}
