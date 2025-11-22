/**
 * WebP Converter - Inicializador Principal
 * Coordina todos los módulos
 */

document.addEventListener('DOMContentLoaded', () => {
    console.log('🚀 Inicializando WebP Converter...');
    
    // Inicializar módulos
    if (typeof initModals === 'function') {
        initModals();
    }
    
    if (typeof loadSavedTheme === 'function') {
        loadSavedTheme();
    }
    
    if (typeof initUpload === 'function') {
        initUpload();
    }
    
    if (typeof initGallery === 'function') {
        initGallery();
    }
    
    if (typeof initConverter === 'function') {
        initConverter();
    }
    
    console.log('✅ WebP Converter inicializado correctamente');
    console.log('📦 Módulos cargados:');
    console.log('   - Modals: Sistema de modales personalizados');
    console.log('   - Theme: Dark/Light mode');
    console.log('   - Upload: Drag & drop');
    console.log('   - Gallery: Galerías y refresh AJAX');
    console.log('   - Converter: Conversión y eliminación');
    console.log('   - Editor: Editor completo de imágenes');
});

// Información de la aplicación
window.WebPConverter = {
    version: '2.0',
    modules: ['modals', 'theme', 'upload', 'gallery', 'converter', 'editor'],
    initialized: true
};

console.log('✅ Main.js cargado');






