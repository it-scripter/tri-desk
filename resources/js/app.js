// Progressive enhancement of the public frontend. Every page works without
// JavaScript; this only adds comfort.

// After a failed submit the error summary takes the focus, so keyboard and
// screen reader users land on the list of problems (WCAG 3.3.1).
const summary = document.querySelector('[data-error-summary]');

if (summary instanceof HTMLElement) {
    summary.focus();
}
