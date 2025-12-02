// Tauri API will be available via window.__TAURI__
const { invoke } = window.__TAURI__.core;

// Helper function to display results
function displayResult(elementId, message, isError = false) {
    const element = document.getElementById(elementId);
    element.textContent = message;
    element.className = 'result ' + (isError ? 'error' : 'success');
}

// 1. Greet function
async function greetUser() {
    try {
        const name = document.getElementById('name-input').value || 'Мир';
        const result = await invoke('greet', { name });
        displayResult('greet-result', result);
    } catch (error) {
        displayResult('greet-result', `Ошибка: ${error}`, true);
    }
}

// 2. Calculator function
async function calculateNumbers() {
    try {
        const a = parseInt(document.getElementById('calc-a').value);
        const b = parseInt(document.getElementById('calc-b').value);
        const operation = document.getElementById('calc-op').value;

        const result = await invoke('calculate', { a, b, operation });
        displayResult('calc-result', `Результат: ${result}`);
    } catch (error) {
        displayResult('calc-result', `Ошибка: ${error}`, true);
    }
}

// 3. System Info function
async function fetchSystemInfo() {
    try {
        const info = await invoke('get_system_info');
        const message = `
            Операционная система: ${info.os}
            Архитектура: ${info.arch}
            Версия приложения: ${info.version}
        `.trim().replace(/\n\s+/g, '\n');
        displayResult('system-info', message);
    } catch (error) {
        displayResult('system-info', `Ошибка: ${error}`, true);
    }
}

// 4. Async operation
async function runAsyncOperation() {
    const duration = parseInt(document.getElementById('async-duration').value);
    const resultElement = document.getElementById('async-result');

    try {
        displayResult('async-result', `Выполняется операция на ${duration} секунд...`);
        const result = await invoke('async_operation', { duration });
        displayResult('async-result', result);
    } catch (error) {
        displayResult('async-result', `Ошибка: ${error}`, true);
    }
}

// 5. Counter functions
async function getCounterValue() {
    try {
        const value = await invoke('get_counter');
        document.getElementById('counter-value').textContent = value;
    } catch (error) {
        console.error('Ошибка получения счётчика:', error);
    }
}

async function incrementCounter() {
    try {
        const value = await invoke('increment_counter');
        document.getElementById('counter-value').textContent = value;
    } catch (error) {
        console.error('Ошибка увеличения счётчика:', error);
    }
}

// Initialize counter on page load
window.addEventListener('DOMContentLoaded', () => {
    getCounterValue();
});

// Allow Enter key to trigger greet
document.addEventListener('DOMContentLoaded', () => {
    const nameInput = document.getElementById('name-input');
    if (nameInput) {
        nameInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                greetUser();
            }
        });
    }
});
