import { config, request } from './api';
import { closeDialog, openDialog } from '../ui';

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
    const invoice = document.querySelector('.cost-modal');
    document.querySelectorAll('[data-invoice]').forEach(button => button.addEventListener('click', () => {
        invoice.querySelector('img').src = button.dataset.invoice;
        openDialog(invoice, button);
    }));
    invoice?.querySelector('.cost-close-Btn')?.addEventListener('click', closeDialog);

    const status = document.querySelector('[data-payments-state]');
    const table = document.querySelector('[data-payments-table]');
    const body = table.querySelector('tbody');
    const print = document.querySelector('[data-print]');
    print.addEventListener('click', () => window.print());
    document.addEventListener('panelchange', async event => {
        if (event.detail !== 'paymentInfo' || status.hasAttribute('data-loading')) return;
        table.hidden = true;
        print.disabled = true;
        status.hidden = false;
        status.dataset.loading = '';
        status.textContent = 'در حال دریافت پرداخت‌ها…';
        const rows = await request('/dashboard/building/payments', { method: 'POST', data: { complex_id: config.complexId } });
        delete status.dataset.loading;
        if (!rows) {
            status.textContent = 'پرداخت‌ها دریافت نشدند.';
            return;
        }
        if (!rows.length) {
            status.textContent = 'پرداختی ثبت نشده است.';
            return;
        }
        body.replaceChildren(...rows.map(payment => {
            const row = document.createElement('tr');
            for (const field of ['user_name', 'unit_number', 'type', 'date', 'amount']) {
                const cell = document.createElement('td');
                cell.textContent = payment[field];
                row.append(cell);
            }
            return row;
        }));
        status.hidden = true;
        table.hidden = false;
        print.disabled = false;
    });
}
