import { ArcElement, Chart, DoughnutController, Legend, Tooltip } from 'chart.js';
import { request } from './api';
import { closeDialog, openDialog } from '../ui';

Chart.register(DoughnutController, ArcElement, Legend, Tooltip);

export function polls() {
    const options = document.querySelector('[data-options]');
    if (options) {
        let count = options.querySelectorAll('input').length;
        document.querySelector('[data-add-option]').addEventListener('click', () => {
            count++;
            const label = document.createElement('label');
            label.textContent = 'گزینه ' + count;
            const input = document.createElement('input');
            input.type = 'text';
            input.name = 'option' + count;
            label.append(input);
            options.append(label);
            input.focus();
        });
        document.querySelector('[data-remove-option]').addEventListener('click', () => {
            if (count <= 2) return;
            options.lastElementChild.remove();
            count--;
        });
    }
    const vote = document.querySelector('.modal-vote');
    const result = document.querySelector('.modal-vote-result');
    const wrapper = vote.querySelector('.option-wrapper');
    const status = result.querySelector('[data-chart-status]');
    let chart;

    document.querySelectorAll('.open-Btn-vote').forEach(button => button.addEventListener('click', () => {
        wrapper.replaceChildren();
        button.closest('.building-box').querySelectorAll('[data-option-id]').forEach(option => {
            const label = document.createElement('label');
            label.className = 'vote-choice';
            const input = document.createElement('input');
            input.type = 'radio';
            input.name = 'voteRadio';
            input.value = option.dataset.optionId;
            label.append(input, option.textContent.trim());
            wrapper.append(label);
        });
        openDialog(vote, button);
    }));

    function themeChart() {
        if (!chart) return;
        const style = getComputedStyle(document.documentElement);
        const dark = document.documentElement.classList.contains('dark');
        chart.options.plugins.legend.labels.color = style.getPropertyValue('--ink').trim();
        chart.data.datasets[0].borderColor = style.getPropertyValue('--surface').trim();
        chart.data.datasets[0].backgroundColor = chart.data.labels.map((_, index) => {
            const hue = (168 + index * 137.5) % 360;
            return 'hsl(' + hue + (dark ? ' 65% 62%)' : ' 58% 42%)');
        });
        chart.update();
    }

    document.querySelectorAll('.open-Btn-vote-result').forEach(button => button.addEventListener('click', async () => {
        chart?.destroy();
        chart = null;
        status.textContent = 'در حال دریافت نتیجه…';
        openDialog(result, button);
        const rows = await request('/dashboard/building/vote/Result', { method: 'POST', data: { poll_id: button.dataset.pollId } });
        if (!rows) {
            status.textContent = 'نتیجه دریافت نشد.';
            return;
        }
        if (!rows.some(row => row.voteCount)) {
            status.textContent = 'هنوز رأیی ثبت نشده است.';
            return;
        }
        status.textContent = '';
        chart = new Chart(document.querySelector('#voteChart'), {
            type: 'doughnut',
            data: {
                labels: rows.map(row => row.option),
                datasets: [{
                    data: rows.map(row => row.voteCount),
                }],
            },
            options: { maintainAspectRatio: false, plugins: { legend: { labels: {} } } },
        });
        themeChart();
    }));
    document.addEventListener('themechange', themeChart);
    document.querySelectorAll('.close-Btn-vote, .close-Btn-vote-result').forEach(button => button.addEventListener('click', closeDialog));
}
