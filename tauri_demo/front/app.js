const { invoke } = window.__TAURI__.core;

// Универсальный обработчик команд
async function runCommand(button) {
    const section = button.closest('section');
    const cmd = button.dataset.cmd;
    const output = section.querySelector('pre');

    // Собираем параметры из inputs в секции
    const params = {};
    section.querySelectorAll('[data-param]').forEach(el => {
        const key = el.dataset.param;
        const value = el.type === 'number' ? Number(el.value) : el.value;
        params[key] = value;
    });

    try {
        if (output) output.textContent = 'Loading...';
        const result = await invoke(cmd, params);

        // Форматируем вывод
        if (output) {
            output.textContent = typeof result === 'object'
                ? JSON.stringify(result, null, 2)
                : result;
            output.style.color = '';
        }

        // Обновляем счетчик если это команда счетчика
        if (cmd.includes('counter')) {
            document.getElementById('counter').textContent = result;
        }
    } catch (error) {
        if (output) {
            output.textContent = `Error: ${error}`;
            output.style.color = '#e74c3c';
        }
    }
}

// Привязываем обработчик ко всем кнопкам с data-cmd
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-cmd]').forEach(button => {
        button.addEventListener('click', () => runCommand(button));
    });

    // Инициализируем счетчик
    const counterBtn = document.querySelector('[data-cmd="get_counter"]');
    if (counterBtn) runCommand(counterBtn);
});
