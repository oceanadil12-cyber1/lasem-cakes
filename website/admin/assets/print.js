/* La Sem admin — the Print button on bare pages (invoice), where inline scripts are blocked by the CSP. */
document.addEventListener('click', function (e) { if (e.target.closest('[data-print]')) window.print(); });
