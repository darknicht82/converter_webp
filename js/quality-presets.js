/**
 * Presets de Calidad para Conversión WebP
 */

document.addEventListener('DOMContentLoaded', function() {
    const qualityInput = document.getElementById('quality');
    const presetButtons = document.querySelectorAll('.quality-preset');
    
    if (!qualityInput) return;
    
    presetButtons.forEach(button => {
        button.addEventListener('click', function() {
            const quality = parseInt(this.getAttribute('data-quality'));
            qualityInput.value = quality;
            
            // Resaltar el botón seleccionado
            presetButtons.forEach(btn => {
                btn.style.opacity = '0.7';
                btn.style.transform = 'scale(1)';
            });
            this.style.opacity = '1';
            this.style.transform = 'scale(1.05)';
            
            // Feedback visual
            this.style.transition = 'all 0.2s';
            setTimeout(() => {
                this.style.transform = 'scale(1)';
            }, 200);
        });
    });
    
    // Actualizar estado de botones según el valor actual
    function updatePresetButtons() {
        const currentQuality = parseInt(qualityInput.value);
        presetButtons.forEach(btn => {
            const btnQuality = parseInt(btn.getAttribute('data-quality'));
            if (btnQuality === currentQuality) {
                btn.style.opacity = '1';
                btn.style.transform = 'scale(1.05)';
            } else {
                btn.style.opacity = '0.7';
                btn.style.transform = 'scale(1)';
            }
        });
    }
    
    // Actualizar cuando cambia el input manualmente
    qualityInput.addEventListener('input', updatePresetButtons);
    updatePresetButtons();
});





