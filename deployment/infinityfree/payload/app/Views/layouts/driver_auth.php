<?php

/**
 * Mwanjelwa Kiganjani - Driver Authentication Layout
 *
 * Shared public layout for driver registration. Expects:
 * $title, $content
 */

$pageTitle = htmlspecialchars((string) ($title ?? 'Driver Registration'), ENT_QUOTES, 'UTF-8');
$stylesheetVersion = (string) (@filemtime(__DIR__ . '/../../../public/assets/css/portal.css') ?: 0);
$stylesheetUrl = htmlspecialchars(
    app_url('assets/css/portal.css?v=' . rawurlencode($stylesheetVersion)),
    ENT_QUOTES,
    'UTF-8'
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#f6f7f2">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="<?php echo $stylesheetUrl; ?>">
    <script src="<?php echo htmlspecialchars(app_url('assets/js/ui.js'), ENT_QUOTES, 'UTF-8'); ?>" defer></script>
</head>
<body class="auth-body">
    <div class="auth-page">
        <aside class="auth-visual" aria-label="Mwanjelwa Kiganjani driver registration">
            <a class="auth-brand" href="<?php echo htmlspecialchars(app_url('index.php'), ENT_QUOTES, 'UTF-8'); ?>"><span class="auth-brand-mark" aria-hidden="true">MK</span><span>Mwanjelwa Kiganjani</span></a>
            <div class="auth-visual-copy"><span class="auth-kicker">Ride with purpose</span><p>Bring local deliveries closer to every doorstep.</p></div>
            <img class="auth-illustration" src="<?php echo htmlspecialchars(app_url('assets/images/delivery-hero.svg'), ENT_QUOTES, 'UTF-8'); ?>" alt="Illustration of a motorcycle courier carrying a parcel">
            <p class="auth-visual-caption">A trusted local network, powered by drivers like you.</p>
        </aside>
        <main class="container auth-main">
            <a class="auth-mobile-brand" href="<?php echo htmlspecialchars(app_url('index.php'), ENT_QUOTES, 'UTF-8'); ?>"><span class="auth-brand-mark" aria-hidden="true">MK</span><span>Mwanjelwa Kiganjani</span></a>
            <div class="auth-heading"><span class="auth-kicker">Driver Portal</span><h1><?php echo $pageTitle; ?></h1></div>
            <div class="auth-content">
                <div class="form-container">
            <?php echo $content; ?>
                </div>
            </div>
            <footer class="auth-footer">Reliable deliveries, rooted in Mbeya.</footer>
        </main>
    </div>
</body>
</html>
