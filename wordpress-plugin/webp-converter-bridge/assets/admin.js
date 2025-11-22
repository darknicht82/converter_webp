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

                    // Show detailed per-file information
                    if (data.details && data.details.length > 0) {
                        data.details.forEach(function (detail) {
                            const icon = detail.status === 'success' ? '✓' : '✗';
                            const type = detail.status === 'success' ? 'success' : 'error';
                            logMessage(`${icon} ${detail.filename}`, type);
                        });
                    } else {
                        // Fallback to summary
                        logMessage(`Lote procesado: ${data.success} convertidos, ${data.failed} fallidos.`, 'success');
                    }
                } else {
                    logMessage('Error en lote: ' + (response.data.message || 'Desconocido'), 'error');
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
                // Increased to 1000ms for MAMP/local environments
                setTimeout(function () {
                    processBatch();
                }, 1000); // 1 second pause between images
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

})(jQuery);
