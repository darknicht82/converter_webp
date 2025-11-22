/**
 * Sistema de Búsqueda y Filtros en Galería
 */

function initGallerySearch() {
    // Crear barra de búsqueda si no existe
    let searchBar = document.getElementById('gallery-search');
    if (!searchBar) {
        const sourceGallery = document.getElementById('source-gallery-wrapper');
        if (sourceGallery) {
            const searchContainer = document.createElement('div');
            searchContainer.style.cssText = `
                margin: 20px 0;
                padding: 15px;
                background: #f8f9fa;
                border-radius: 8px;
                border: 1px solid #dee2e6;
            `;
            
            searchBar = document.createElement('input');
            searchBar.id = 'gallery-search';
            searchBar.type = 'text';
            searchBar.placeholder = '🔍 Buscar imágenes por nombre...';
            searchBar.style.cssText = `
                width: 100%;
                padding: 12px 15px;
                font-size: 14px;
                border: 2px solid #0066cc;
                border-radius: 6px;
                outline: none;
            `;
            
            const filterContainer = document.createElement('div');
            filterContainer.style.cssText = `
                display: flex;
                gap: 10px;
                margin-top: 10px;
                flex-wrap: wrap;
            `;
            
            const dateFilter = document.createElement('select');
            dateFilter.id = 'date-filter';
            dateFilter.innerHTML = `
                <option value="">📅 Todas las fechas</option>
                <option value="today">Hoy</option>
                <option value="yesterday">Ayer</option>
                <option value="this_week">Esta semana</option>
                <option value="this_month">Este mes</option>
            `;
            dateFilter.style.cssText = `
                padding: 8px 12px;
                border: 1px solid #ccc;
                border-radius: 4px;
                font-size: 13px;
            `;
            
            const clearBtn = document.createElement('button');
            clearBtn.textContent = 'Limpiar';
            clearBtn.style.cssText = `
                padding: 8px 15px;
                background: #6c757d;
                color: white;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                font-size: 13px;
            `;
            clearBtn.onclick = clearSearch;
            
            filterContainer.appendChild(dateFilter);
            filterContainer.appendChild(clearBtn);
            
            searchContainer.appendChild(searchBar);
            searchContainer.appendChild(filterContainer);
            sourceGallery.parentNode.insertBefore(searchContainer, sourceGallery);
        }
    }
    
    if (searchBar) {
        searchBar.addEventListener('input', performSearch);
        document.getElementById('date-filter')?.addEventListener('change', performSearch);
    }
}

function performSearch() {
    const searchTerm = document.getElementById('gallery-search')?.value.toLowerCase() || '';
    const dateFilter = document.getElementById('date-filter')?.value || '';
    
    // Buscar en galería de origen
    const sourceContainers = document.querySelectorAll('#source-gallery-wrapper .image-container');
    let visibleCount = 0;
    
    sourceContainers.forEach(container => {
        const filename = container.getAttribute('data-filename')?.toLowerCase() || '';
        const dateGroup = container.closest('.date-group')?.getAttribute('data-group') || '';
        
        let matches = true;
        
        // Filtro de texto
        if (searchTerm && !filename.includes(searchTerm)) {
            matches = false;
        }
        
        // Filtro de fecha
        if (dateFilter && dateGroup !== dateFilter) {
            matches = false;
        }
        
        if (matches) {
            container.style.display = '';
            visibleCount++;
        } else {
            container.style.display = 'none';
        }
    });
    
    // Ocultar grupos vacíos
    document.querySelectorAll('#source-gallery-wrapper .date-group').forEach(group => {
        const visibleInGroup = group.querySelectorAll('.image-container[style=""], .image-container:not([style*="display: none"])').length;
        if (visibleInGroup === 0) {
            group.style.display = 'none';
        } else {
            group.style.display = '';
        }
    });
    
    // Mostrar mensaje si no hay resultados
    let noResultsMsg = document.getElementById('no-search-results');
    if (visibleCount === 0 && (searchTerm || dateFilter)) {
        if (!noResultsMsg) {
            noResultsMsg = document.createElement('div');
            noResultsMsg.id = 'no-search-results';
            noResultsMsg.style.cssText = `
                text-align: center;
                padding: 40px;
                color: #666;
                font-size: 16px;
            `;
            noResultsMsg.textContent = 'No se encontraron imágenes que coincidan con los filtros.';
            document.getElementById('source-gallery-wrapper')?.appendChild(noResultsMsg);
        }
    } else if (noResultsMsg) {
        noResultsMsg.remove();
    }
}

function clearSearch() {
    const searchBar = document.getElementById('gallery-search');
    const dateFilter = document.getElementById('date-filter');
    
    if (searchBar) searchBar.value = '';
    if (dateFilter) dateFilter.value = '';
    
    performSearch();
}

// Inicializar cuando el DOM esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initGallerySearch);
} else {
    initGallerySearch();
}

// Exportar globalmente
window.initGallerySearch = initGallerySearch;
window.performSearch = performSearch;
window.clearSearch = clearSearch;





