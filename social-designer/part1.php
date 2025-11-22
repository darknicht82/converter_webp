<?php
/**
 * Social Media Designer - WebP Converter
 * Versión PHP con integración de config.php
 */

require_once __DIR__ . '/../config.php';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Social Media Designer - WebP Converter</title>
    
    <!-- Fabric.js para Canvas -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.0/fabric.min.js"></script>
    
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            background: linear-gradient(135deg, #f5f7fb 0%, #e2e8f0 100%);
            min-height: 100vh;
            margin: 0;
            padding: 24px 24px 0;
            transition: background-color 0.3s, color 0.3s;
        }
        .top-nav {
            position: sticky;
            top: 0;
            z-index: 1000;
            background: linear-gradient(135deg, rgba(30, 41, 59, 0.96), rgba(15, 23, 42, 0.96));
            backdrop-filter: blur(14px);
            border-bottom: 1px solid rgba(148, 163, 184, 0.14);
            margin: -24px -24px 24px -24px;
            padding: 0 24px;
            box-shadow: 0 18px 36px rgba(15, 23, 42, 0.3);
        }
        .top-nav-inner {
            max-width: 1400px;
            margin: 0 auto;
            padding: 18px 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
        }
        .top-nav .brand {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            font-weight: 700;
            font-size: 20px;
            color: #f8fafc;
            letter-spacing: -0.01em;
        }
        .top-nav .nav-links {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }
        .top-nav .nav-links a {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: rgba(226, 232, 240, 0.85);
            text-decoration: none;
            padding: 9px 18px;
            border-radius: 999px;
            transition: background 0.2s ease, color 0.2s ease, box-shadow 0.2s ease;
            font-weight: 500;
            font-size: 15px;
        }
        .top-nav .nav-links a:hover {
            background: rgba(148, 163, 184, 0.22);
            color: #f1f5f9;
        }
        .top-nav .nav-links a.active {
            background: linear-gradient(135deg, #9333ea, #ec4899);
            color: #fff;
            box-shadow: 0 12px 24px rgba(147, 51, 234, 0.32);
        }
        
        body.dark-mode {
            background: #1a1a1a;
            color: #e0e0e0;
        }
        
        body.dark-mode .container {
            background: #1a1a1a;
        }
        
        body.dark-mode .header {
            background: #1e1e1e;
            border-bottom-color: #333;
        }
        body.dark-mode .top-nav {
            background: linear-gradient(135deg, rgba(12, 18, 31, 0.96), rgba(8, 12, 24, 0.96));
            border-bottom-color: rgba(148, 163, 184, 0.18);
        }
        body.dark-mode .top-nav .brand {
            color: #f8fafc;
        }
        body.dark-mode .top-nav .nav-links a:hover {
            background: rgba(148, 163, 184, 0.28);
            color: #e0f2fe;
        }
        body.dark-mode .top-nav .nav-links a.active {
            background: linear-gradient(135deg, #6366f1, #ec4899);
        }
        @media (max-width: 900px) {
            .top-nav-inner {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            .top-nav .nav-links {
                width: 100%;
                justify-content: space-between;
            }
        }
        
        body.dark-mode .header h1 {
            color: #e0e0e0;
        }
        
        body.dark-mode .btn-secondary {
            background: #2a2a2a;
            color: #e0e0e0;
            border-color: #444;
        }
        
        body.dark-mode .btn-secondary:hover {
            background: #333;
            border-color: #555;
        }
        
        body.dark-mode .left-panel,
        body.dark-mode .right-panel {
            background: #1e1e1e;
            border-color: #333;
        }
        
        body.dark-mode .canvas-panel {
            background: #161616;
        }
        
        body.dark-mode .template-item,
        body.dark-mode .tool-section,
        body.dark-mode .layer-item,
        body.dark-mode .tool-btn {
            background: #2a2a2a;
            border-color: #444;
            color: #e0e0e0;
        }
        
        body.dark-mode .template-item:hover,
        body.dark-mode .tool-btn:hover,
        body.dark-mode .layer-item:hover {
            background: #333;
            border-color: #555;
        }
        
        body.dark-mode .template-item.active {
            background: #0d3d66;
            border-color: #0066cc;
        }
        
        body.dark-mode .tool-section-header {
            background: #2a2a2a;
        }
        
        body.dark-mode .tool-section-header:hover {
            background: #333;
        }
        
        body.dark-mode .template-category h3,
        body.dark-mode .tool-section h3,
        body.dark-mode label {
            color: #999;
        }
        
        body.dark-mode .template-name,
        body.dark-mode .layer-name {
            color: #e0e0e0;
        }
        
        body.dark-mode .template-size {
            color: #888;
        }
        
        body.dark-mode input[type="text"],
        body.dark-mode input[type="number"],
        body.dark-mode textarea,
        body.dark-mode select {
            background: #2a2a2a;
            border-color: #444;
            color: #e0e0e0;
        }
        
        body.dark-mode .zoom-controls {
            background: #2a2a2a;
            border-color: #444;
        }
        
        body.dark-mode .zoom-btn {
            background: #2a2a2a;
            border-color: #444;
            color: #e0e0e0;
        }
        
        body.dark-mode .zoom-btn:hover {
            background: #333;
        }
        
        body.dark-mode .tool-section-content {
            border-top-color: #333;
        }
        
        body.dark-mode .canvas-container {
            box-shadow: 0 4px 20px rgba(0,0,0,0.5);
        }
        
        body.dark-mode .drag-handle {
            color: #666;
        }
        
        body.dark-mode .tool-section.drag-over {
            border-top-color: #0066cc;
        }
        
        /* HUD Moderno y Original */
        .floating-toolbar {
            position: fixed;
            top: 70px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            padding: 8px;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.15);
            display: flex;
            gap: 4px;
            z-index: 1000;
            transition: all 0.3s;
        }
        
        .floating-toolbar:hover {
            box-shadow: 0 6px 32px rgba(0, 0, 0, 0.2);
        }
        
        .toolbar-btn {
            width: 40px;
            height: 40px;
            border: none;
            background: white;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            transition: all 0.2s;
            position: relative;
        }
        
        .toolbar-btn:hover {
            background: #0066cc;
            color: white;
            transform: translateY(-2px);
        }
        
        .toolbar-btn.active {
            background: #0066cc;
            color: white;
        }
        
        .toolbar-separator {
            width: 1px;
            background: #ddd;
            margin: 0 4px;
        }
        
        .quick-action {
            position: fixed;
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: linear-gradient(135deg, #0066cc, #0052a3);
            color: white;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            box-shadow: 0 4px 16px rgba(0, 102, 204, 0.4);
            transition: all 0.3s;
            z-index: 999;
        }
        
        .quick-action:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 24px rgba(0, 102, 204, 0.6);
        }
        
        .quick-export {
            bottom: 24px;
            right: 24px;
        }
        
        .quick-undo {
            bottom: 24px;
            left: 240px;
        }
        
        .mini-preview {
            position: fixed;
            bottom: 24px;
            left: 240px;
            width: 180px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            padding: 12px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
            z-index: 998;
            display: none;
        }
        
        .mini-preview.show {
            display: block;
        }
        
        .mini-preview-canvas {
            width: 100%;
            height: auto;
            border-radius: 6px;
            border: 1px solid #ddd;
        }
        
        .mini-preview-label {
            font-size: 11px;
            color: #666;
            margin-top: 8px;
            text-align: center;
        }
        
        .command-palette {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 600px;
            max-width: 90vw;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border: 1px solid #e0e0e0;
            border-radius: 16px;
            box-shadow: 0 8px 48px rgba(0, 0, 0, 0.2);
            z-index: 2000;
            display: none;
            overflow: hidden;
        }
        
        .command-palette.show {
            display: block;
            animation: commandPaletteIn 0.2s ease-out;
        }
        
        @keyframes commandPaletteIn {
            from {
                opacity: 0;
                transform: translate(-50%, -45%);
            }
            to {
                opacity: 1;
                transform: translate(-50%, -50%);
            }
        }
        
        .command-input {
            width: 100%;
            padding: 20px 24px;
            border: none;
            font-size: 16px;
            background: transparent;
            outline: none;
        }
        
        .command-results {
            max-height: 400px;
            overflow-y: auto;
            border-top: 1px solid #e8e8e8;
        }
        
        .command-item {
            padding: 12px 24px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.15s;
        }
        
        .command-item:hover,
        .command-item.selected {
            background: #f8f9fa;
        }
        
        .command-icon {
            font-size: 20px;
            width: 32px;
            text-align: center;
        }
        
        .command-text {
            flex: 1;
        }
        
        .command-name {
            font-size: 14px;
            font-weight: 500;
            color: #333;
        }
        
        .command-desc {
            font-size: 12px;
            color: #999;
        }
        
        .command-shortcut {
            font-size: 11px;
            color: #999;
            background: #f0f0f0;
            padding: 3px 8px;
            border-radius: 4px;
        }
        
        /* Dark Mode para HUD */
        body.dark-mode .floating-toolbar {
            background: rgba(42, 42, 42, 0.95);
            border-color: #444;
        }
        
        body.dark-mode .toolbar-btn {
            background: #2a2a2a;
            color: #e0e0e0;
        }
        
        body.dark-mode .toolbar-btn:hover {
            background: #0066cc;
        }
        
        body.dark-mode .mini-preview {
            background: rgba(42, 42, 42, 0.95);
            border-color: #444;
        }
        
        body.dark-mode .command-palette {
            background: rgba(30, 30, 30, 0.98);
            border-color: #444;
        }
        
        body.dark-mode .command-input {
            color: #e0e0e0;
        }
        
        body.dark-mode .command-item:hover,
        body.dark-mode .command-item.selected {
            background: #333;
        }
        
        body.dark-mode .command-name {
            color: #e0e0e0;
        }
        
        /* Info section scrollable */
        .info-content {
            max-height: 200px;
            overflow-y: auto;
        }
        
        /* Paneles Colapsables estilo Photoshop/Canva */
        .left-panel,
        .right-panel {
            transition: all 0.3s ease;
        }
        
        .left-panel.collapsed {
            width: 0 !important;
            min-width: 0 !important;
            padding: 0 !important;
            border: none !important;
            overflow: hidden;
        }
        
        .right-panel.collapsed {
            width: 0 !important;
            min-width: 0 !important;
            padding: 0 !important;
            border: none !important;
            overflow: hidden;
        }
        
        .main-content.left-collapsed {
            grid-template-columns: 0 1fr 280px !important;
        }
        
        .main-content.right-collapsed {
            grid-template-columns: 220px 1fr 0 !important;
        }
        
        .main-content.both-collapsed {
            grid-template-columns: 0 1fr 0 !important;
        }
        
        /* Botones de Toggle para Paneles */
        .panel-toggle {
            position: fixed;
            width: 32px;
            height: 80px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 1001;
            transition: all 0.3s;
            font-size: 16px;
            color: #666;
        }
        
        .panel-toggle:hover {
            background: white;
            color: #0066cc;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.15);
        }
        
        .panel-toggle-left {
            top: 50%;
            left: 220px;
            transform: translateY(-50%);
            border-radius: 0 8px 8px 0;
            border-left: none;
        }
        
        .panel-toggle-left.collapsed {
            left: 0;
        }
        
        .panel-toggle-right {
            top: 50%;
            right: 280px;
            transform: translateY(-50%);
            border-radius: 8px 0 0 8px;
            border-right: none;
        }
        
        .panel-toggle-right.collapsed {
            right: 0;
        }
        
        /* Dark Mode para paneles colapsables */
        body.dark-mode .panel-toggle {
            background: rgba(42, 42, 42, 0.95);
            border-color: #444;
            color: #999;
        }
        
        body.dark-mode .panel-toggle:hover {
            background: #2a2a2a;
            color: #0066cc;
        }
        
        body.dark-mode .info-content,
        body.dark-mode #template-info {
            color: #999 !important;
        }
        
        body.dark-mode #template-info strong {
            color: #e0e0e0;
        }
        
        .container {
            width: 100%;
            height: calc(100vh - 76px);
            margin-top: 52px;
            background: white;
            display: flex;
            flex-direction: column;
        }
        
        .header {
            background: white;
            border-bottom: 1px solid #e0e0e0;
            padding: 12px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        
        .header h1 {
            font-size: 18px;
            font-weight: 600;
            color: #333;
        }
        
        .header h1::before {
            content: "🎨";
            margin-right: 8px;
        }
        
        .header .actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .theme-toggle {
            width: 36px;
            height: 36px;
            border: 1px solid #ddd;
            border-radius: 6px;
            background: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            transition: all 0.2s;
            margin-right: 10px;
        }
        
        .theme-toggle:hover {
            background: #f8f9fa;
            border-color: #999;
        }
        
        body.dark-mode .theme-toggle {
            background: #2a2a2a;
            border-color: #444;
        }
        
        body.dark-mode .theme-toggle:hover {
            background: #333;
        }
        
        .btn {
            padding: 8px 16px;
            border: 1px solid #ddd;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            font-size: 13px;
            transition: all 0.2s;
            background: white;
        }
        
        .btn-primary {
            background: #0066cc;
            color: white;
            border-color: #0066cc;
        }
        
        .btn-primary:hover {
            background: #0052a3;
        }
        
        .btn-secondary {
            background: white;
            color: #666;
            border-color: #ddd;
        }
        
        .btn-secondary:hover {
            background: #f8f9fa;
            border-color: #999;
        }
        
        .main-content {
            display: grid;
            grid-template-columns: 220px 1fr 280px;
            grid-template-areas: "left center right";
            height: calc(100vh - 60px);
            flex: 1;
        }
        
        .left-panel {
            grid-area: left;
        }
        
        .canvas-panel {
            grid-area: center;
        }
        
        .right-panel {
            grid-area: right;
        }
        
        /* Panel Izquierdo - Plantillas */
        .left-panel {
            background: #fafafa;
            border-right: 1px solid #e0e0e0;
            padding: 16px;
            overflow-y: auto;
        }
        
        .template-category {
            margin-bottom: 20px;
        }
        
        .template-category h3 {
            font-size: 11px;
            color: #999;
            margin-bottom: 8px;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        
        .template-item {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            padding: 10px;
            margin-bottom: 6px;
            cursor: pointer;
            transition: all 0.15s;
        }
        
        .template-item:hover {
            border-color: #0066cc;
            background: #f8f9fa;
        }
        
        .template-item.active {
            border-color: #0066cc;
            background: #e6f2ff;
        }
        
        .template-name {
            font-weight: 500;
            font-size: 12px;
            margin-bottom: 3px;
            color: #333;
        }
        
        .template-size {
            font-size: 10px;
            color: #999;
        }
        
        /* Panel Central - Canvas */
        .canvas-panel {
            background: #fafafa;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
            height: 100%;
        }
        
        .canvas-wrapper {
            width: 100%;
            height: 100%;
            overflow: auto;
            position: relative;
            cursor: grab;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .canvas-wrapper:active {
            cursor: grabbing;
        }
        
        .canvas-container {
            display: flex;
            align-items: center;
            justify-content: center;
            background: white;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            border-radius: 4px;
            margin: auto;
        }

        #canvas {
            border: 1px solid #ddd;
            display: block;
        }
        
        /* Controles de Zoom */
        .zoom-controls {
            position: absolute;
            bottom: 16px;
            right: 16px;
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            padding: 6px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            z-index: 100;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .zoom-btn {
            width: 28px;
            height: 28px;
            border: 1px solid #ddd;
            background: white;
            color: #666;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.15s;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .zoom-btn:hover {
            background: #f8f9fa;
            border-color: #999;
        }
        
        .zoom-level {
            font-size: 11px;
            font-weight: 500;
            color: #666;
            min-width: 40px;
            text-align: center;
        }
        
        .zoom-hint {
            position: absolute;
            top: 16px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(51,51,51,0.95);
            color: white;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 500;
            z-index: 100;
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .zoom-hint.show {
            opacity: 1;
        }
        
        /* Panel Derecho - Herramientas */
        .right-panel {
            background: #fafafa;
            border-left: 1px solid #e0e0e0;
            padding: 16px;
            overflow-y: auto;
            height: 100%;
        }
        
        .tool-section {
            margin-bottom: 16px;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            background: white;
            overflow: hidden;
            cursor: move;
            transition: all 0.2s;
        }
        
        .tool-section.dragging {
            opacity: 0.5;
            transform: rotate(2deg);
        }
        
        .tool-section.drag-over {
            border-top: 3px solid #0066cc;
            margin-top: 20px;
        }
        
        .tool-section-header {
            padding: 12px;
            background: white;
            cursor: grab;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.15s;
            border-bottom: 1px solid transparent;
        }
        
        .tool-section-header:active {
            cursor: grabbing;
        }
        
        .tool-section-header:hover {
            background: #f8f9fa;
        }
        
        .tool-section-header.collapsed {
            border-bottom: none;
        }
        
        .drag-handle {
            color: #999;
            font-size: 14px;
            margin-right: 8px;
            cursor: grab;
        }
        
        .drag-handle:active {
            cursor: grabbing;
        }
        
        .tool-section h3 {
            font-size: 12px;
            color: #333;
            margin: 0;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        
        .collapse-icon {
            font-size: 16px;
            color: #999;
            transition: transform 0.2s;
        }
        
        .collapse-icon.collapsed {
            transform: rotate(-90deg);
        }
        
        .tool-section-content {
            padding: 12px;
            display: block;
            border-top: 1px solid #e8e8e8;
        }
        
        .tool-section-content.hidden {
            display: none;
        }
        
        .tool-btn {
            width: 100%;
            padding: 10px;
            margin-bottom: 6px;
            background: white;
            color: #333;
            border: 1px solid #ddd;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            font-size: 12px;
            transition: all 0.15s;
        }
        
        .tool-btn:hover {
            background: #0066cc;
            color: white;
            border-color: #0066cc;
        }
        
        .tool-btn-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin-bottom: 10px;
        }
        
        .tool-btn-small {
            padding: 7px;
            background: white;
            color: #666;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
            font-size: 11px;
            font-weight: 500;
            transition: all 0.15s;
        }
        
        .tool-btn-small:hover {
            background: #0066cc;
            color: white;
            border-color: #0066cc;
        }
        
        input[type="text"],
        input[type="number"],
        textarea,
        select {
            width: 100%;
            padding: 8px;
            margin-bottom: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 12px;
            background: white;
        }
        
        input[type="text"]:focus,
        textarea:focus,
        select:focus {
            border-color: #0066cc;
            outline: none;
            box-shadow: 0 0 0 2px rgba(0,102,204,0.1);
        }
        
        input[type="color"] {
            width: 100%;
            height: 36px;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
        }
        
        input[type="range"] {
            width: 100%;
        }
        
        label {
            display: block;
            font-size: 11px;
            color: #999;
            margin-bottom: 4px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        
        .color-presets {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 5px;
            margin-bottom: 10px;
        }
        
        .color-preset {
            width: 100%;
            height: 35px;
            border: 2px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .color-preset:hover {
            transform: scale(1.1);
            border-color: #0066cc;
        }
        
        .layers-list {
            margin-top: 10px;
        }
        
        .layer-item {
            background: white;
            border: 1px solid #e8e8e8;
            border-radius: 4px;
            padding: 8px;
            margin-bottom: 4px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.15s;
        }
        
        .layer-item:hover {
            border-color: #0066cc;
            background: #f8f9fa;
        }
        
        .layer-item.active {
            border-color: #0066cc;
            background: #e6f2ff;
        }
        
        .layer-name {
            font-size: 11px;
            font-weight: 500;
            color: #333;
        }
        
        .layer-actions {
            display: flex;
            gap: 5px;
        }
        
        .layer-btn {
            padding: 3px 6px;
            border: 1px solid #ddd;
            border-radius: 3px;
            cursor: pointer;
            font-size: 10px;
            background: white;
            color: #666;
        }
        
        .layer-btn:hover {
            background: #f0f0f0;
            border-color: #999;
        }
        
        /* Modal de Confirmación Personalizado */
        .confirm-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.6);
            z-index: 10000;
            align-items: center;
            justify-content: center;
        }
        
        .confirm-modal.show {
            display: flex;
        }
        
        .confirm-content {
            background: white;
            padding: 30px;
            border-radius: 12px;
            max-width: 450px;
            width: 90%;
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
            animation: modalSlideIn 0.2s ease-out;
        }
        
        @keyframes modalSlideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .confirm-content h3 {
            margin: 0 0 15px 0;
            color: #dc3545;
            font-size: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .confirm-content p {
            margin: 0 0 20px 0;
            color: #333;
            font-size: 15px;
            line-height: 1.5;
            white-space: pre-line;
        }
        
        .confirm-buttons {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        
        .confirm-btn {
            padding: 10px 24px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .confirm-btn.cancel {
            background: #6c757d;
            color: white;
        }
        
        .confirm-btn.cancel:hover {
            background: #5a6268;
        }
        
        .confirm-btn.confirm {
            background: #dc3545;
            color: white;
        }
        
        .confirm-btn.confirm:hover {
            background: #c82333;
        }
        
        body.dark-mode .confirm-content {
            background: #2d2d3a;
        }
        
        body.dark-mode .confirm-content h3 {
            color: #ff6b6b;
        }
        
        body.dark-mode .confirm-content p {
            color: #e0e0e0;
        }
        
        /* Modal de Alerta/Notificación */
        .alert-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.6);
            z-index: 10001;
            align-items: center;
            justify-content: center;
        }
        
        .alert-modal.show {
            display: flex;
        }
        
        .alert-content {
            background: white;
            padding: 30px;
            border-radius: 12px;
            max-width: 450px;
            width: 90%;
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
            animation: modalSlideIn 0.2s ease-out;
        }
        
        .alert-content.success h3 { color: #28a745; }
        .alert-content.error h3 { color: #dc3545; }
        .alert-content.warning h3 { color: #ffc107; }
        .alert-content.info h3 { color: #0066cc; }
        
        .alert-content h3 {
            margin: 0 0 15px 0;
            font-size: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-content p {
            margin: 0 0 20px 0;
            color: #333;
            font-size: 15px;
            line-height: 1.5;
            white-space: pre-line;
        }
        
        .alert-content .btn-close {
            width: 100%;
            padding: 10px 24px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            background: #0066cc;
            color: white;
            transition: all 0.2s;
        }
        
        .alert-content .btn-close:hover {
            background: #0052a3;
        }
        
        body.dark-mode .alert-content {
            background: #2d2d3a;
        }
        
        body.dark-mode .alert-content p {
            color: #e0e0e0;
        }
    </style>
</head>
<body>
<nav class="top-nav">
    <div class="top-nav-inner">
        <div class="brand">🎨 Social Designer</div>
        <div class="nav-links">
            <a href="../index.php">🏠 <span>Inicio</span></a>
            <a href="../webp-online/index.php">⚙️ <span>Conversor</span></a>
            <a href="../webp-wordpress/index.php">🔗 <span>WordPress</span></a>
            <a href="social-designer.php" class="active">🎨 <span>Social Designer</span></a>
        </div>
    </div>
</nav>

<!-- Modal de Confirmación Personalizado -->
<div class="confirm-modal" id="confirm-modal">
    <div class="confirm-content">
        <h3>
            <span>⚠️</span>
            <span id="confirm-title">Confirmar Acción</span>
        </h3>
        <p id="confirm-message">¿Estás seguro?</p>
        <div class="confirm-buttons">
            <button class="confirm-btn cancel" onclick="closeConfirm(false)">Cancelar</button>
            <button class="confirm-btn confirm" onclick="closeConfirm(true)">Confirmar</button>
        </div>
    </div>
</div>

<!-- Modal de Alerta/Notificación -->
<div class="alert-modal" id="alert-modal">
    <div class="alert-content" id="alert-content">
        <h3 id="alert-title">
            <span id="alert-icon">ℹ️</span>
            <span id="alert-title-text">Información</span>
        </h3>
        <p id="alert-message">Mensaje...</p>
        <button class="btn-close" onclick="closeAlert()">Aceptar</button>
    </div>
</div>

<div class="container">
    <!-- Header -->
    <div class="header">
        <h1>Social Media Designer</h1>
        <div class="actions">
            <button class="theme-toggle" onclick="toggleTheme()" title="Cambiar tema">
                <span id="theme-icon">☀️</span>
            </button>
            <button class="btn btn-secondary" onclick="clearCanvas()">
                Limpiar
            </button>
            <button class="btn btn-primary" onclick="exportDesign()">
                Exportar
            </button>
        </div>
    </div>
    
    <!-- Floating Toolbar (HUD Moderno) -->
    <div class="floating-toolbar">
        <button class="toolbar-btn" onclick="toggleCommandPalette()" title="Comando Rápido (Ctrl+K)">⚡</button>
        <div class="toolbar-separator"></div>
        <button class="toolbar-btn" onclick="addText('heading')" title="Agregar Título">T</button>
        <button class="toolbar-btn" onclick="uploadBackground()" title="Imagen de Fondo">🖼️</button>
        <button class="toolbar-btn" onclick="addShape('rect')" title="Rectángulo">▭</button>
        <button class="toolbar-btn" onclick="addShape('circle')" title="Círculo">●</button>
        <div class="toolbar-separator"></div>
        <button class="toolbar-btn" id="view-mode-btn" onclick="toggleViewMode()" title="Modo Vista">👁️</button>
    </div>
    
    <!-- Quick Actions (Botones Flotantes) -->
    <button class="quick-action quick-export" onclick="exportDesign()" title="Exportar (Ctrl+S)">💾</button>
    
    <!-- Mini Preview (opcional, se muestra al activar) -->
    <div class="mini-preview" id="mini-preview">
        <canvas class="mini-preview-canvas" id="mini-preview-canvas"></canvas>
        <div class="mini-preview-label">Vista Previa</div>
    </div>
    
    <!-- Command Palette (Spotlight-style) -->
    <div class="command-palette" id="command-palette">
        <input type="text" class="command-input" id="command-input" placeholder="Buscar acción... (Ctrl+K para abrir)">
        <div class="command-results" id="command-results"></div>
    </div>
    
    <!-- Botones Toggle para Paneles -->
    <div class="panel-toggle panel-toggle-left" id="toggle-left" onclick="toggleLeftPanel()" title="Mostrar/Ocultar Plantillas">
        ◀
    </div>
    <div class="panel-toggle panel-toggle-right" id="toggle-right" onclick="toggleRightPanel()" title="Mostrar/Ocultar Herramientas">
        ▶
    </div>
    
    <!-- Contenido Principal -->
    <div class="main-content" id="main-content">
        <!-- Panel Izquierdo: Plantillas -->
        <div class="left-panel" id="left-panel">
            <h2 style="font-size: 13px; margin-bottom: 16px; color: #333; font-weight: 600;">Plantillas</h2>
            
            <div class="template-category">
                <h3>Instagram</h3>
                <div class="template-item" onclick="loadTemplate('instagram-post')">
                    <div class="template-name">Post Cuadrado</div>
                    <div class="template-size">1080 x 1080</div>
                </div>
                <div class="template-item" onclick="loadTemplate('instagram-story')">
                    <div class="template-name">Story / Reels</div>
                    <div class="template-size">1080 x 1920</div>
                </div>
                <div class="template-item" onclick="loadTemplate('instagram-portrait')">
                    <div class="template-name">Post Retrato</div>
                    <div class="template-size">1080 x 1350</div>
                </div>
                <div class="template-item" onclick="loadTemplate('instagram-highlight')">
                    <div class="template-name">Highlight Cover</div>
                    <div class="template-size">1080 x 1920</div>
                </div>
            </div>
            
            <div class="template-category">
                <h3>📘 Facebook</h3>
                <div class="template-item" onclick="loadTemplate('facebook-cover')">
                    <div class="template-name">Portada</div>
                    <div class="template-size">820 x 312</div>
                </div>
                <div class="template-item" onclick="loadTemplate('facebook-post')">
                    <div class="template-name">Post</div>
                    <div class="template-size">1200 x 630</div>
                </div>
                <div class="template-item" onclick="loadTemplate('facebook-cover-hd')">
                    <div class="template-name">Portada HD</div>
                    <div class="template-size">1640 x 624</div>
                </div>
                <div class="template-item" onclick="loadTemplate('facebook-story')">
                    <div class="template-name">Story</div>
                    <div class="template-size">1080 x 1920</div>
                </div>
            </div>
            
            <div class="template-category">
                <h3>▶️ YouTube</h3>
                <div class="template-item" onclick="loadTemplate('youtube-thumb')">
                    <div class="template-name">Thumbnail</div>
                    <div class="template-size">1280 x 720</div>
                </div>
                <div class="template-item" onclick="loadTemplate('youtube-banner')">
                    <div class="template-name">Banner</div>
                    <div class="template-size">2560 x 1440</div>
                </div>
                <div class="template-item" onclick="loadTemplate('youtube-thumb-hd')">
                    <div class="template-name">Thumbnail HD</div>
                    <div class="template-size">1920 x 1080</div>
                </div>
                <div class="template-item" onclick="loadTemplate('youtube-short')">
                    <div class="template-name">Short Vertical</div>
                    <div class="template-size">1080 x 1920</div>
                </div>
            </div>
            
            <div class="template-category">
                <h3>🐦 Twitter/X</h3>
                <div class="template-item" onclick="loadTemplate('twitter-header')">
                    <div class="template-name">Header</div>
                    <div class="template-size">1500 x 500</div>
                </div>
                <div class="template-item" onclick="loadTemplate('twitter-post')">
                    <div class="template-name">Post</div>
                    <div class="template-size">1200 x 675</div>
                </div>
                <div class="template-item" onclick="loadTemplate('twitter-header-hd')">
                    <div class="template-name">Header HD</div>
                    <div class="template-size">3000 x 1000</div>
                </div>
            </div>
            
            <div class="template-category">
                <h3>💼 LinkedIn</h3>
                <div class="template-item" onclick="loadTemplate('linkedin-banner')">
                    <div class="template-name">Banner</div>
                    <div class="template-size">1584 x 396</div>
                </div>
                <div class="template-item" onclick="loadTemplate('linkedin-post')">
                    <div class="template-name">Post</div>
                    <div class="template-size">1200 x 627</div>
                </div>
                <div class="template-item" onclick="loadTemplate('linkedin-square')">
                    <div class="template-name">Post Cuadrado</div>
                    <div class="template-size">1200 x 1200</div>
                </div>
            </div>
            
            <div class="template-category">
                <h3>TikTok</h3>
                <div class="template-item" onclick="loadTemplate('tiktok-cover')">
                    <div class="template-name">Video Cover</div>
                    <div class="template-size">1080 x 1920</div>
                </div>
            </div>
            
            <div class="template-category">
                <h3>🎮 Twitch</h3>
                <div class="template-item" onclick="loadTemplate('twitch-banner')">
                    <div class="template-name">Banner</div>
                    <div class="template-size">1200 x 480</div>
                </div>
                <div class="template-item" onclick="loadTemplate('twitch-offline')">
                    <div class="template-name">Pantalla Offline</div>
                    <div class="template-size">1920 x 1080</div>
                </div>
            </div>

            <div class="template-category">
                <h3>📌 Pinterest</h3>
                <div class="template-item" onclick="loadTemplate('pinterest-pin')">
                    <div class="template-name">Pin Estándar</div>
                    <div class="template-size">1000 x 1500</div>
                </div>
                <div class="template-item" onclick="loadTemplate('pinterest-pin-long')">
                    <div class="template-name">Pin Largo</div>
                    <div class="template-size">1000 x 2100</div>
                </div>
            </div>

            <div class="template-category">
                <h3>💬 WhatsApp</h3>
                <div class="template-item" onclick="loadTemplate('whatsapp-status')">
                    <div class="template-name">Estado</div>
                    <div class="template-size">1080 x 1920</div>
                </div>
            </div>

            <div class="template-category">
                <h3>🎮 Discord</h3>
                <div class="template-item" onclick="loadTemplate('discord-server')">
                    <div class="template-name">Icono de Servidor</div>
                    <div class="template-size">512 x 512</div>
                </div>
            </div>

            <div class="template-category">
                <h3>🖼️ Wallpapers</h3>
                <div class="template-item" onclick="loadTemplate('wallpaper-4k')">
                    <div class="template-name">Wallpaper 4K</div>
                    <div class="template-size">3840 x 2160</div>
                </div>
                <div class="template-item" onclick="loadTemplate('wallpaper-2k')">
                    <div class="template-name">Wallpaper 2K</div>
                    <div class="template-size">2560 x 1440</div>
                </div>
                <div class="template-item" onclick="loadTemplate('wallpaper-ultrawide')">
                    <div class="template-name">Wallpaper Ultrawide</div>
                    <div class="template-size">3440 x 1440</div>
                </div>
            </div>

            <div class="template-category">
                <h3>📱 Mobile</h3>
                <div class="template-item" onclick="loadTemplate('iphone-15pro')">
                    <div class="template-name">iPhone 15 Pro</div>
                    <div class="template-size">1290 x 2796</div>
                </div>
            </div>

            <div class="template-category">
                <h3>🖨️ Print / Posters</h3>
                <div class="template-item" onclick="loadTemplate('poster-digital')">
                    <div class="template-name">Poster Digital</div>
                    <div class="template-size">3000 x 4000</div>
                </div>
            </div>

            <div class="template-category">
                <h3>Web</h3>
                <div class="template-item" onclick="loadTemplate('web-banner')">
                    <div class="template-name">Banner</div>
                    <div class="template-size">1920 x 400</div>
                </div>
                <div class="template-item" onclick="loadTemplate('web-hero')">
                    <div class="template-name">Hero</div>
                    <div class="template-size">1920 x 600</div>
                </div>
                <div class="template-item" onclick="loadTemplate('square')">
                    <div class="template-name">Cuadrado</div>
                    <div class="template-size">1000 x 1000</div>
                </div>
            </div>
        </div>
