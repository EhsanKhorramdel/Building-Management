import { config, request } from './api';
import { closeDialog, openDialog } from '../ui';
import { mediaViewer } from '../media-viewer';

export function payments() {
    for (const [kind, field] of [['cost', 'incidentalCostId'], ['charge', 'monthlyChargeId']]) {
        const dialog = document.querySelector('.' + kind + '-pay-modal');
        document.querySelectorAll('.' + kind + '-pay-open-Btn').forEach(button => button.addEventListener('click', () => {
            dialog.querySelector('[name="' + field + '"], [name=payable_id]').value = button.dataset.paymentId;
            dialog.querySelector('.' + kind + '-pay-amount').textContent = button.dataset.amount;
            openDialog(dialog, button);
        }));
        dialog.querySelector('.' + kind + '-pay-close-Btn').addEventListener('click', closeDialog);
    }
    document.querySelectorAll('[data-payment-tab]').forEach(button => button.addEventListener('click', () => {
        document.querySelector('[data-tab="' + button.dataset.paymentTab + '"]').click();
    }));
    document.querySelectorAll('[data-invoice]').forEach(button => button.addEventListener('click', () => {
        mediaViewer().open({ id: 'cost', url: button.dataset.invoice, alt: 'تصویر فاکتور هزینه' }, button, 'cost');
    }));

    const status = document.querySelector('[data-payments-state]');
    const table = document.querySelector('[data-payments-table]');
    const body = table.querySelector('tbody');
    const cue = document.querySelector('[data-report-scroll-cue]');
    const frame = document.querySelector('[data-report-scroll-frame]');

    function updateReportOverflow() {
        const overflow = table.scrollWidth - table.clientWidth;
        cue.hidden = table.hidden || overflow <= 1;
        frame.classList.toggle('report-has-more', !cue.hidden && Math.abs(table.scrollLeft) < overflow - 1);
    }

    const reportSize = new ResizeObserver(updateReportOverflow);
    reportSize.observe(table);
    reportSize.observe(table.querySelector('table'));
    table.addEventListener('scroll', updateReportOverflow);
    const print = document.querySelector('[data-print]');
    print.addEventListener('click', () => window.print());
    const filters = document.querySelector('[data-report-filters]');
    const scope = document.querySelector('[data-report-scope]');
    const download = document.querySelector('[data-report-export]');
    let exportUrl;
    download.addEventListener('click', () => { if (exportUrl) window.location.assign(exportUrl); });

    function describeScope(parameters) {
        const labels = [scope.dataset.complexName, 'وضعیت: پرداخت‌شده'];
        const unit = parameters.get('unit_number');
        labels.push(unit ? 'واحد ' + unit : 'همه واحدها');
        const type = parameters.get('type');
        if (type) labels.push(type === 'charge' ? 'شارژ' : 'جانبی');
        const year = parameters.get('period_year');
        if (year) labels.push('دوره ' + year + '/' + parameters.get('period_month'));
        const from = parameters.get('paid_from');
        const to = parameters.get('paid_to');
        if (from) labels.push('از ' + from);
        if (to) labels.push('تا ' + to);
        scope.textContent = labels.join(' · ');
    }

    async function loadReport() {
        if (status.hasAttribute('data-loading')) return;
        const parameters = new URLSearchParams(new FormData(filters));
        for (const key of [...parameters.keys()]) {
            const value = parameters.get(key).trim();
            if (value) parameters.set(key, value);
            else parameters.delete(key);
        }
        parameters.set('complex_id', config.complexId);
        parameters.set('status', 'paid');
        const controls = [...filters.querySelectorAll('button')];
        controls.forEach(button => { button.disabled = true; });
        table.hidden = true;
        updateReportOverflow();
        print.disabled = true;
        download.disabled = true;
        exportUrl = null;
        status.hidden = false;
        status.dataset.loading = '';
        status.textContent = 'در حال دریافت پرداخت‌ها…';
        const rows = await request('/dashboard/building/payments', { method: 'POST', data: Object.fromEntries(parameters) });
        delete status.dataset.loading;
        controls.forEach(button => { button.disabled = false; });
        if (!rows) {
            status.textContent = 'پرداخت‌ها دریافت نشدند.';
            return;
        }
        describeScope(parameters);
        exportUrl = filters.dataset.exportUrl + '?' + parameters;
        download.disabled = false;
        if (!rows.length) {
            body.replaceChildren();
            status.textContent = 'پرداختی در این محدوده ثبت نشده است.';
            return;
        }
        body.replaceChildren(...rows.map(payment => {
            const row = document.createElement('tr');
            for (const field of ['user_name', 'unit_number', 'type', 'date', 'amount']) {
                const cell = document.createElement('td');
                cell.textContent = payment[field];
                row.append(cell);
            }
            const actions = document.createElement('td');
            actions.className = 'print:hidden';
            if (payment.receipt_url) {
                const receipt = document.createElement('a');
                receipt.className = 'action';
                receipt.href = payment.receipt_url;
                receipt.textContent = 'رسید';
                actions.append(receipt);
            }
            row.append(actions);
            return row;
        }));
        status.hidden = true;
        table.hidden = false;
        updateReportOverflow();
        print.disabled = false;
    }

    filters.addEventListener('submit', event => {
        event.preventDefault();
        void loadReport();
    });
    filters.querySelector('[data-report-reset]').addEventListener('click', () => {
        filters.reset();
        void loadReport();
    });
    document.addEventListener('panelchange', event => {
        if (event.detail === 'paymentInfo') void loadReport();
    });

    const more = document.querySelector('[data-more-payments]');
    more?.addEventListener('click', async () => {
        more.disabled = true;
        const history = await request(more.dataset.historyUrl + '?' + new URLSearchParams({ before_id: more.dataset.beforeId }));
        if (history) {
            const rows = document.querySelector('[data-online-payments] tbody');
            const range = document.createRange();
            range.selectNodeContents(rows);
            rows.append(range.createContextualFragment(history.html));
            if (history.cursor) more.dataset.beforeId = history.cursor;
            else more.hidden = true;
        }
        more.disabled = false;
    });
}
