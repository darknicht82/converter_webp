/**
 * Project: WebP Converter Bridge
 * Author: Christian Aguirre
 * Date: 2025-11-21
 */
(function ($) {
    'use strict';

    // --- Connection Test ---
    $(document).on('click', '#wcb-test-connection', function () {
        const $button = $(this);
        const $result = $('#wcb-test-result');

        $button.prop('disabled', true).text('Probando...');
        $result.removeClass('is-success is-error').hide().text('');

        $.post(wcbAdmin.ajaxUrl, {
            action: 'wcb_test_connection',
            nonce: wcbAdmin.nonce
        })
            .done(function (response) {
                if (response.success) {
                    $result
                        .addClass('is-success')
                        .text('Conexión Correcta')
                        .show();

                    // Parse response to show cost if available
                    if (response.data.body) {
                        try {
                            const body = JSON.parse(response.data.body);
                            if (body.client && body.client.cost_per_image) {
                                $result.append(' - Costo: $' + body.client.cost_per_image + ' por imagen');
                            }
                        } catch (e) {
                            console.error('Error parsing response body', e);
                        }
                    }

                    // Reload page after 1.5 seconds to show updated cost in settings
                    setTimeout(function () {
                        location.reload();
                    }, 1500);
                } else {
                    $result
                        .addClass('is-error')
                        .text('Fallo en Conexión: ' + (response.data && response.data.message ? response.data.message : 'Sin respuesta.'))
                        .show();
                }
            })
            .fail(function (xhr) {
                let message = 'Error desconocido.';
                if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                    message = xhr.responseJSON.data.message;
                } else if (xhr.statusText) {
                    message = xhr.statusText;
                }
                $result.addClass('is-error').text('Fallo en Conexión: ' + message).show();
            })
            .always(function () {
                $button.prop('disabled', false).text('Probar conexión');
            });
    });

    // --- Rewrite Rules ---
    function handleRewrite(action) {
        const $status = $('#wcb-rewrite-status');
        $status.text('Procesando...').css('color', '#666');

        $.post(wcbRewrite.ajaxUrl, {
            action: 'wcb_rewrite_rules',
            nonce: wcbRewrite.nonce,
            todo: action
        })
            .done(function (response) {
                if (response.success) {
                    $status.text(response.data.message).css('color', 'green');
                    // Reload to update UI state
                    setTimeout(function () {
                        location.reload();
                    }, 1000);
                } else {
                    $status.text('Error: ' + (response.data.message || 'Desconocido')).css('color', 'red');
                }
            })
            .fail(function (xhr) {
                let msg = 'Error de conexión';
                if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                    msg = xhr.responseJSON.data.message;
                } else if (xhr.status) {
                    msg += ' (' + xhr.status + ')';
                }
                $status.text(msg).css('color', 'red');
                console.error('Rewrite Rules Error:', xhr);
            });
    }

    $(document).on('click', '#wcb-insert-rewrite', function () {
        handleRewrite('insert');
    });

    $(document).on('click', '#wcb-remove-rewrite', function () {
        handleRewrite('remove');
    });

    // --- Bulk Conversion ---
    let bulkIds = [];
    let bulkTotal = 0;
    let bulkProcessed = 0;
    let isProcessing = false;
    const BATCH_SIZE = 1; // Reduced to 1 to prevent server timeouts (502)

    function updateProgress() {
        const percentage = Math.round((bulkProcessed / bulkTotal) * 100) || 0;
        $('#wcb-progress-bar').css('width', percentage + '%');
        $('#wcb-progress-text').text(percentage + '%');
        $('#wcb-progress-count').text(bulkProcessed + '/' + bulkTotal);
    }

    function logMessage(msg, type = 'info') {
        const color = type === 'error' ? 'red' : (type === 'success' ? 'green' : '#333');
        const $log = $('#wcb-bulk-log');
        $log.show().append('<div style="color:' + color + ';">' + msg + '</div>');
        $log.scrollTop($log[0].scrollHeight);
    }

    function processBatch() {
        if (!isProcessing || bulkIds.length === 0) {
            isProcessing = false;
            $('#wcb-start-bulk').prop('disabled', false);
            $('#wcb-stop-bulk').prop('disabled', true);
            logMessage('Proceso finalizado.', 'info');
            return;
        }

        const batch = bulkIds.splice(0, BATCH_SIZE);

        $.post(wcbBulk.ajaxUrl, {
            action: 'wcb_bulk_convert',
            nonce: wcbBulk.nonce,
            ids: batch
        })
            .done(function (response) {
                if (response.success) {
                    const data = response.data;
                    bulkProcessed += batch.length;
                    updateProgress();

                    // Update Health Stats
                    if (data.metrics) {
                        $('#wcb-stat-memory').text(data.metrics.memory);
                        $('#wcb-stat-time').text(data.metrics.time);
                        $('#wcb-stat-status').text('Procesando...').css('color', 'blue');
                    }

                    // Show detailed per-file information
                    if (data.details && data.details.length > 0) {
                        data.details.forEach(function (detail) {
                            const icon = detail.status === 'success' ? '✓' : '✗';
                            const type = detail.status === 'success' ? 'success' : 'error';
                            let msg = `${icon} ${detail.filename}`;
                            if (detail.error) {
                                msg += ` <small>(${detail.error})</small>`;
                            }
                            logMessage(msg, type);
                        });
                    } else {
                        // Fallback to summary
                        logMessage(`Lote procesado: ${data.success} convertidos, ${data.failed} fallidos.`, 'success');
                    }
                } else {
                    logMessage('Error en lote: ' + (response.data.message || 'Desconocido'), 'error');
                    $('#wcb-stat-status').text('Error en lote').css('color', 'red');
                    // Even if failed, we count as processed to move on
                    bulkProcessed += batch.length;
                    updateProgress();
                }
            })
            .fail(function (xhr, status, error) {
                let errorMsg = 'Error de red desconocido.';
                if (xhr.responseText) {
                    // Try to extract a meaningful message from HTML error pages (like 500 or 404)
                    const div = document.createElement('div');
                    div.innerHTML = xhr.responseText;
                    const title = div.querySelector('title');
                    if (title) {
                        errorMsg = title.innerText;
                    } else {
                        errorMsg = xhr.status + ' ' + xhr.statusText;
                    }
                } else {
                    errorMsg = error || status;
                }

                logMessage('Error crítico en lote: ' + errorMsg, 'error');
                console.error('Bulk Error Details:', xhr);

                // Stop processing on critical network error to allow debugging
                isProcessing = false;
                $('#wcb-start-bulk').prop('disabled', false);
                $('#wcb-stop-bulk').prop('disabled', true);
                logMessage('Proceso detenido automáticamente por errores. Revisa la consola para más detalles.', 'error');
            })
            .always(function () {
                // Pause before next batch to prevent server overload
                // Increased to 2000ms to prevent 502 Bad Gateway errors
                setTimeout(function () {
                    processBatch();
                }, 2000); // 2 seconds pause between images
            });
    }

    $(document).on('click', '#wcb-scan-images', function () {
        const $btn = $(this);
        $btn.prop('disabled', true).text('Escaneando...');
        $('#wcb-scan-status').text('');

        $.post(wcbBulk.ajaxUrl, {
            action: 'wcb_scan_images',
            nonce: wcbBulk.nonce
        })
            .done(function (response) {
                if (response.success) {
                    const data = response.data;
                    bulkIds = data.ids;
                    bulkTotal = data.pending_count;
                    bulkProcessed = 0;

                    let statusMsg = `Encontradas ${data.count} imágenes en total. `;
                    statusMsg += `✅ ${data.converted_count} ya convertidas. `;
                    statusMsg += `⏳ ${data.pending_count} pendientes.`;

                    $('#wcb-scan-status').html(statusMsg);
                    $('#wcb-bulk-progress-area').show();
                    updateProgress();
                    $('#wcb-bulk-log').empty().hide();
                } else {
                    $('#wcb-scan-status').text('Error: ' + response.data.message).css('color', 'red');
                }
            })
            .always(function () {
                $btn.prop('disabled', false).text('Escanear Imágenes');
            });
    });

    $(document).on('click', '#wcb-start-bulk', function () {
        if (bulkIds.length === 0) {
            alert('No hay imágenes para procesar. Escanea primero.');
            return;
        }
        isProcessing = true;
        $(this).prop('disabled', true);
        $('#wcb-stop-bulk').prop('disabled', false);
        logMessage('Iniciando conversión...', 'info');
        processBatch();
    });

    $(document).on('click', '#wcb-stop-bulk', function () {
        isProcessing = false;
        logMessage('Deteniendo proceso...', 'info');
        $(this).prop('disabled', true);
    });

    // Stop processing on page unload
    $(window).on('beforeunload', function () {
        if (isProcessing) {
            return 'La conversión está en progreso. ¿Seguro que quieres salir?';
        }
    });

    // Tab Handling
    $('.nav-tab-wrapper a').on('click', function (e) {
        // If it's a real link (page reload), let it be
        if ($(this).attr('href').indexOf('?page=') !== -1) {
            return;
        }
        e.preventDefault();

        // Internal tab switching (for sub-tabs like in File Manager)
        $(this).addClass('nav-tab-active').siblings().removeClass('nav-tab-active');
        const type = $(this).data('type');
        if (type) {
            loadFiles(type, 1);
        }
    });

    // File Manager Logic
    function loadFiles(type, page) {
        const $tableBody = $('#wcb-files-list');
        $tableBody.html('<tr><td colspan="5">Cargando...</td></tr>');

        $.post(wcbAdmin.ajaxUrl, {
            action: 'wcb_get_files',
            nonce: wcbAdmin.fileNonce,
            type: type,
            paged: page
        }, function (response) {
            if (response.success) {
                renderFilesTable(response.data.files, type);
                renderPagination(response.data, type);
            } else {
                $tableBody.html('<tr><td colspan="5" style="color:red;">Error: ' + (response.data ? response.data.message : 'Desconocido') + '</td></tr>');
            }
        }).fail(function (xhr) {
            $tableBody.html('<tr><td colspan="5" style="color:red;">Error de red: ' + xhr.status + ' ' + xhr.statusText + '</td></tr>');
        });
    }

    function renderFilesTable(files, type) {
        const $tableBody = $('#wcb-files-list');
        $tableBody.empty();

        if (files.length === 0) {
            $tableBody.html('<tr><td colspan="5">No se encontraron archivos.</td></tr>');
            return;
        }

        files.forEach(function (file) {
            let actions = '';
            if (type === 'webp') {
                actions = `<button class="button button-small wcb-delete-file" data-id="${file.id}">Borrar</button>`;
            } else {
                actions = `<button class="button button-small wcb-restore-backup" data-id="${file.id}">Restaurar</button> 
                           <button class="button button-small wcb-delete-backup" data-id="${file.id}" style="color: #a00;">Borrar Backup</button>`;
            }

            const row = `
                <tr>
                    <td><img src="${file.thumbnail}" width="50" height="50" style="object-fit:cover;"></td>
                    <td>
                        <strong>${file.filename}</strong><br>
                        <a href="${file.url}" target="_blank">Ver archivo</a>
                    </td>
                    <td>${file.size}</td>
                    <td>${file.date}</td>
                    <td>${actions}</td>
                </tr>
            `;
            $tableBody.append(row);
        });
    }

    function renderPagination(data, type) {
        const $pagination = $('.pagination-links');
        $pagination.empty();

        if (data.pages <= 1) return;

        if (data.current_page > 1) {
            $pagination.append(`<a class="button" href="#" onclick="loadFiles('${type}', ${data.current_page - 1}); return false;">&laquo; Anterior</a> `);
        }

        $pagination.append(`<span>Página ${data.current_page} de ${data.pages}</span> `);

        if (data.current_page < data.pages) {
            $pagination.append(`<a class="button" href="#" onclick="loadFiles('${type}', ${data.current_page + 1}); return false;">Siguiente &raquo;</a>`);
        }

        // Expose loadFiles globally for pagination clicks
        window.loadFiles = loadFiles;
    }

    // Action Handlers
    $(document).on('click', '.wcb-restore-backup', function () {
        if (!confirm('¿Estás seguro de restaurar el archivo original? Esto reemplazará la versión WebP.')) return;

        const id = $(this).data('id');
        const $btn = $(this);
        $btn.prop('disabled', true).text('Restaurando...');

        $.post(wcbAdmin.ajaxUrl, {
            action: 'wcb_restore_backup',
            nonce: wcbAdmin.fileNonce,
            id: id
        }, function (response) {
            if (response.success) {
                alert(response.data.message);
                loadFiles('backup', 1); // Reload list
            } else {
                alert('Error: ' + response.data.message);
                $btn.prop('disabled', false).text('Restaurar');
            }
        }).fail(function (xhr) {
            alert('Error de red: ' + xhr.status + ' ' + xhr.statusText);
            $btn.prop('disabled', false).text('Restaurar');
        });
    });

    $(document).on('click', '.wcb-delete-backup', function () {
        if (!confirm('¿Estás seguro de eliminar el backup? Esta acción es irreversible.')) return;

        const id = $(this).data('id');
        const $btn = $(this);
        $btn.prop('disabled', true).text('Borrando...');

        $.post(wcbAdmin.ajaxUrl, {
            action: 'wcb_delete_backup',
            nonce: wcbAdmin.fileNonce,
            id: id
        }, function (response) {
            if (response.success) {
                loadFiles('backup', 1);
            } else {
                alert('Error: ' + response.data.message);
                $btn.prop('disabled', false).text('Borrar Backup');
            }
        }).fail(function (xhr) {
            alert('Error de red: ' + xhr.status + ' ' + xhr.statusText);
            $btn.prop('disabled', false).text('Borrar Backup');
        });
    });

    $(document).on('click', '.wcb-delete-file', function () {
        if (!confirm('¿Estás seguro de eliminar este archivo? Se borrará de la biblioteca de medios.')) return;

        const id = $(this).data('id');
        const $btn = $(this);
        $btn.prop('disabled', true).text('Borrando...');

        $.post(wcbAdmin.ajaxUrl, {
            action: 'wcb_delete_file',
            nonce: wcbAdmin.fileNonce,
            id: id
        }, function (response) {
            if (response.success) {
                loadFiles('webp', 1);
            } else {
                alert('Error: ' + response.data.message);
                $btn.prop('disabled', false).text('Borrar');
            }
        }).fail(function (xhr) {
            alert('Error de red: ' + xhr.status + ' ' + xhr.statusText);
            $btn.prop('disabled', false).text('Borrar');
        });
    });

})(jQuery);
