<?php

// Lightweight root welcome screen. Laravel starts only after the user chooses
// to continue, so this page remains available before first-run files exist.

$installed = is_file(__DIR__.'/storage/app/installed.lock');
$scriptDirectory = str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/')));
$scriptDirectory = $scriptDirectory === '/' ? '' : rtrim($scriptDirectory, '/');
$publicUrl = $scriptDirectory.'/public/';
$setupUrl = $scriptDirectory.'/public/setup';
$assetBase = $scriptDirectory.'/public/assets';
$version = trim((string) @file_get_contents(__DIR__.'/VERSION')) ?: '1.0.74';

if ($installed) {
    header('Location: '.$publicUrl);
    exit;
}

header('Content-Type: text/html; charset=UTF-8');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Aptoria <?= htmlspecialchars($version, ENT_QUOTES, 'UTF-8') ?> Setup</title>
    <link rel="icon" href="<?= htmlspecialchars($assetBase.'/aptoria/img/favicon.ico', ENT_QUOTES, 'UTF-8') ?>">
    <style>
        * { box-sizing: border-box; }
        html, body { min-height: 100%; }
        body {
            margin: 0;
            background: #f1f3f6;
            color: #6a6c6f;
            font-family: Arial, "Helvetica Neue", Helvetica, sans-serif;
        }
        .color-line {
            height: 6px;
            background: linear-gradient(to right, #34495e 0 25%, #9b59b6 25% 35%, #3498db 35% 45%, #62cb31 45% 55%, #ffb606 55% 65%, #e67e22 65% 75%, #e74c3c 75% 85%, #c0392b 85% 100%);
        }
        .topbar {
            min-height: 76px;
            padding: 14px 28px;
            border-bottom: 1px solid #e4e5e7;
            background: #fff;
            box-shadow: 0 1px 3px rgba(0, 0, 0, .04);
        }
        .topbar-inner, .page {
            width: min(1120px, calc(100% - 32px));
            margin: 0 auto;
        }
        .topbar-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
        }
        .brand { height: 48px; max-width: 230px; object-fit: contain; }
        .version {
            padding: 6px 10px;
            border: 1px solid #e4e5e7;
            border-radius: 3px;
            background: #fafbfc;
            color: #9d9fa2;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .page { padding: 42px 0 56px; }
        .hero {
            display: grid;
            grid-template-columns: minmax(0, 1.2fr) minmax(330px, .8fr);
            gap: 24px;
            align-items: stretch;
        }
        .hpanel {
            overflow: hidden;
            border: 1px solid #e4e5e7;
            border-radius: 4px;
            background: #fff;
            box-shadow: 0 1px 2px rgba(0, 0, 0, .03);
        }
        .panel-heading {
            padding: 13px 18px;
            border-bottom: 1px solid #e4e5e7;
            background: #f7f9fa;
            color: #34495e;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
        }
        .panel-body { padding: 28px; }
        .hero-copy .panel-body { padding: 40px; }
        .eyebrow {
            margin-bottom: 14px;
            color: #3498db;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
        }
        h1, h2, h3 { color: #34495e; font-weight: 300; }
        h1 { margin: 0 0 15px; font-size: clamp(34px, 5vw, 52px); line-height: 1.08; }
        h2 { margin: 0 0 12px; font-size: 25px; }
        h3 { margin: 0 0 5px; font-size: 17px; font-weight: 600; }
        p { margin: 0; line-height: 1.7; }
        .lead { max-width: 650px; color: #7b7d80; font-size: 17px; }
        .actions { display: flex; flex-wrap: wrap; gap: 12px; align-items: center; margin-top: 28px; }
        .btn {
            display: inline-block;
            padding: 12px 21px;
            border: 1px solid #58b62c;
            border-radius: 3px;
            background: #62cb31;
            color: #fff;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
        }
        .btn:hover { background: #74d348; }
        .muted { color: #9d9fa2; font-size: 12px; }
        .signal-list { display: grid; gap: 12px; margin: 0; padding: 0; list-style: none; }
        .signal {
            display: grid;
            grid-template-columns: 10px 1fr auto;
            gap: 11px;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #f1f3f6;
        }
        .signal:last-child { border-bottom: 0; }
        .dot { width: 9px; height: 9px; border-radius: 50%; }
        .green { background: #62cb31; }
        .blue { background: #3498db; }
        .orange { background: #ffb606; }
        .red { background: #e74c3c; }
        .label {
            padding: 4px 7px;
            border-radius: 3px;
            color: #fff;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .label-success { background: #62cb31; }
        .label-info { background: #3498db; }
        .label-warning { background: #ffb606; }
        .label-danger { background: #e74c3c; }
        .workflow {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
            margin-top: 24px;
        }
        .step { display: flex; gap: 14px; align-items: flex-start; }
        .step-number {
            flex: 0 0 38px;
            width: 38px;
            height: 38px;
            border-radius: 12px;
            background: linear-gradient(135deg, #62cb31, #3498db);
            color: #fff;
            font-weight: 800;
            line-height: 38px;
            text-align: center;
        }
        .footer-note { margin-top: 24px; color: #9d9fa2; font-size: 12px; text-align: center; }
        @media (max-width: 900px) {
            .hero { grid-template-columns: 1fr; }
            .workflow { grid-template-columns: 1fr; }
        }
        @media (max-width: 560px) {
            .topbar { padding: 12px 0; }
            .brand { max-width: 190px; }
            .page { padding-top: 20px; }
            .hero-copy .panel-body, .panel-body { padding: 24px; }
            .btn { width: 100%; text-align: center; }
        }
    </style>
</head>
<body>
    <div class="color-line"></div>
    <header class="topbar">
        <div class="topbar-inner">
            <img class="brand" src="<?= htmlspecialchars($assetBase.'/aptoria/img/aptoria-logo-horizontal.png', ENT_QUOTES, 'UTF-8') ?>" alt="Aptoria">
            <span class="version">Server setup · v<?= htmlspecialchars($version, ENT_QUOTES, 'UTF-8') ?></span>
        </div>
    </header>

    <main class="page">
        <section class="hero">
            <article class="hpanel hero-copy">
                <div class="panel-body">
                    <div class="eyebrow">Self-hosted Aptoria UI API QA workspace</div>
                    <h1>Welcome to Aptoria</h1>
                    <p class="lead">
                        Start the guided installer to prepare the environment, create the
                        database and administrator, then optionally import a comprehensive
                        simulated QA review covering the full application workflow.
                    </p>
                    <div class="actions">
                        <a class="btn" href="<?= htmlspecialchars($setupUrl, ENT_QUOTES, 'UTF-8') ?>">Start installation</a>
                        <span class="muted">Laravel starts only after you continue.</span>
                    </div>
                </div>
            </article>

            <aside class="hpanel">
                <div class="panel-heading">Demo QA review preview</div>
                <div class="panel-body">
                    <ul class="signal-list">
                        <li class="signal"><span class="dot green"></span><span><strong>Endpoint inventory</strong><br><span class="muted">GET, HEAD and reviewed destructive methods</span></span><span class="label label-success">Ready</span></li>
                        <li class="signal"><span class="dot blue"></span><span><strong>Safe scan evidence</strong><br><span class="muted">Stored responses without live external requests</span></span><span class="label label-info">Included</span></li>
                        <li class="signal"><span class="dot orange"></span><span><strong>Regression signals</strong><br><span class="muted">Slow response and content-type drift</span></span><span class="label label-warning">Review</span></li>
                        <li class="signal"><span class="dot red"></span><span><strong>Release gate</strong><br><span class="muted">Realistic blockers, findings and evidence</span></span><span class="label label-danger">Blocked</span></li>
                    </ul>
                </div>
            </aside>
        </section>

        <section class="workflow">
            <article class="hpanel"><div class="panel-body step"><span class="step-number">1</span><div><h3>Prepare</h3><p>Create first-run files and verify server requirements.</p></div></div></article>
            <article class="hpanel"><div class="panel-body step"><span class="step-number">2</span><div><h3>Import demo</h3><p>Optionally load a complete simulated API QA project.</p></div></div></article>
            <article class="hpanel"><div class="panel-body step"><span class="step-number">3</span><div><h3>Review</h3><p>Sign in and explore stored scans, risks and release evidence.</p></div></div></article>
        </section>

        <p class="footer-note">No external API request is executed by the demo importer.</p>
    </main>
</body>
</html>
