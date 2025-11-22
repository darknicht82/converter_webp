<?php
require_once __DIR__ . '/config.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suite WebP Converter</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            background: linear-gradient(135deg, #0f172a, #1d4ed8);
            color: #f8fafc;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px 20px;
        }
        .dashboard {
            width: 100%;
            max-width: 1100px;
            background: rgba(15, 23, 42, 0.85);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 30px 80px rgba(15, 23, 42, 0.5);
            backdrop-filter: blur(12px);
        }
        h1 {
            font-size: 36px;
            margin-bottom: 10px;
            font-weight: 700;
            letter-spacing: -0.02em;
        }
        p.subtitle {
            font-size: 16px;
            color: rgba(226, 232, 240, 0.8);
            margin-bottom: 30px;
        }
        .grid {
            display: grid;
            gap: 24px;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        }
        .card {
            background: linear-gradient(160deg, rgba(59, 130, 246, 0.15), rgba(59, 130, 246, 0.05));
            border: 1px solid rgba(148, 163, 184, 0.2);
            border-radius: 16px;
            padding: 28px;
            transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
            position: relative;
            overflow: hidden;
        }
        .card::before {
            content: '';
            position: absolute;
            top: -40px;
            right: -40px;
            width: 140px;
            height: 140px;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.35), transparent 60%);
            opacity: 0;
            transition: opacity 0.25s ease;
        }
        .card:hover {
            transform: translateY(-6px);
            box-shadow: 0 25px 45px rgba(59, 130, 246, 0.25);
            border-color: rgba(59, 130, 246, 0.35);
        }
        .card:hover::before {
            opacity: 1;
        }
        .card h2 {
            font-size: 24px;
            margin-bottom: 12px;
        }
        .card p {
            font-size: 15px;
            color: rgba(226, 232, 240, 0.85);
            margin-bottom: 20px;
            line-height: 1.6;
        }
        .card a {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 22px;
            border-radius: 999px;
            background: linear-gradient(135deg, #38bdf8, #3b82f6);
            color: white;
            text-decoration: none;
            font-weight: 600;
            transition: background 0.2s ease;
            box-shadow: 0 10px 25px rgba(56, 189, 248, 0.35);
        }
        .card a:hover {
            background: linear-gradient(135deg, #0ea5e9, #2563eb);
        }
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            padding: 6px 14px;
            border-radius: 999px;
            background: rgba(148, 163, 184, 0.2);
            color: rgba(226, 232, 240, 0.9);
            margin-bottom: 16px;
        }
        .footer-note {
            margin-top: 30px;
            font-size: 13px;
            color: rgba(148, 163, 184, 0.75);
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
        }
        .footer-note span {
            display: inline-flex;
            gap: 8px;
            align-items: center;
        }
        .hero {
            display: flex;
            flex-direction: column;
            gap: 24px;
            margin-bottom: 36px;
        }
        .hero h1 {
            font-size: 44px;
            font-weight: 700;
            letter-spacing: -0.03em;
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .hero h1 span.badge-version {
            font-size: 18px;
            padding: 6px 18px;
            background: linear-gradient(135deg, #1e40af, #2563eb);
            border-radius: 999px;
            color: #e0f2fe;
            font-weight: 700;
            text-transform: uppercase;
        }
        .hero .subtitle {
            font-size: 16px;
            color: rgba(226, 232, 240, 0.85);
        }
        .hero .tag {
            display: none;
        }
        @media (max-width: 768px) {
            body {
                padding: 20px 16px;
            }
            .dashboard {
                padding: 28px 22px;
            }
            h1 {
                font-size: 28px;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <div class="hero">
            <h1>
                Conversor WebP
                <span class="badge-version">V2.0</span>
            </h1>
            <p class="subtitle">
                Convierte imágenes JPG/PNG a WebP con optimización avanzada.
            </p>
            <span class="tag">API REST • Integraciones • Métricas</span>
        </div>

        <div class="grid">
            <div class="card">
                <div class="badge">Conversor principal</div>
                <h2>Conversor WebP Online</h2>
                <p>
                    Interfaz visual para cargar, convertir y gestionar galerías WebP con herramientas de edición, lotes y descargas masivas.
                </p>
                <a href="webp-online/index.php">
                    Entrar al Conversor
                    <span>→</span>
                </a>
            </div>

            <div class="card">
                <div class="badge">Integración & métricas</div>
                <h2>Conversor WebP Online WordPress</h2>
                <p>
                    Acceso a tokens, dashboards y descargas del plugin para automatizar conversiones dentro de sitios WordPress.
                </p>
                <a href="webp-wordpress/index.php">
                    Gestionar WordPress
                    <span>→</span>
                </a>
            </div>

            <div class="card">
                <div class="badge">Creatividad</div>
                <h2>Social Media Designer</h2>
                <p>
                    Canvas tipo Canva con plantillas para redes sociales, IA de mejora de imagen y exportación directa a WebP.
                </p>
                <a href="social-designer/social-designer.php">
                    Abrir Designer
                    <span>→</span>
                </a>
            </div>
        </div>

        <div class="footer-note">
            <span>🌐 Base URL: <?php echo htmlspecialchars(BASE_URL); ?></span>
            <span>📂 Media: /media/upload · /media/convert</span>
        </div>
    </div>
</body>
</html>

