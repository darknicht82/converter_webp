/**
 * Sistema de Temas (Dark/Light Mode)
 */

function toggleTheme() {
    const body = document.body;
    const icon = document.getElementById('theme-icon');
    
    body.classList.toggle('dark-mode');
    
    if (body.classList.contains('dark-mode')) {
        if (icon) icon.textContent = '☀️';
        localStorage.setItem('theme', 'dark');
    } else {
        if (icon) icon.textContent = '🌙';
        localStorage.setItem('theme', 'light');
    }
}

function loadSavedTheme() {
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'dark') {
        document.body.classList.add('dark-mode');
        const themeIcon = document.getElementById('theme-icon');
        if (themeIcon) themeIcon.textContent = '☀️';
    }
}

function setQuality(value) {
    const input = document.getElementById('quality');
    if (!input) return;
    
    input.value = value;
    
    // Resaltar botón activo
    document.querySelectorAll('.preset-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    if (event && event.target) event.target.classList.add('active');
    
    // Feedback visual
    input.style.background = '#d4edda';
    setTimeout(() => {
        input.style.background = '';
    }, 300);
}

// Exportar globalmente
window.toggleTheme = toggleTheme;
window.setQuality = setQuality;

console.log('✅ Theme.js cargado');









