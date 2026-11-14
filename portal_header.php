<?php

/**
 * Mwanjelwa Kiganjani - Admin Portal Header
 *
 * Shared left-sidebar shell for protected admin pages. Expects:
 * $pageTitle, $navItems, $activeNav, $userName, $flash
 */

$pageTitle = $pageTitle ?? 'Admin';
$navItems = $navItems ?? [];
$activeNav = $activeNav ?? '';
$userName = $userName ?? 'Administrator';
$flash = $flash ?? null;
$homeUrl = $navItems['Dashboard'] ?? app_url('dashboard.php');
$logoutUrl = $navItems['Logout'] ?? app_url('logout.php');

/**
 * Return the inline SVG icon for a navigation item.
 *
 * @param string $label
 * @return string
 */
$navIcon = static function (string $label): string {
    return match ($label) {
        'Dashboard' => '<svg class="admin-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><rect x="3" y="3" width="7" height="7" rx="1"></rect><rect x="14" y="3" width="7" height="7" rx="1"></rect><rect x="3" y="14" width="7" height="7" rx="1"></rect><rect x="14" y="14" width="7" height="7" rx="1"></rect></svg>',
        'Create Delivery' => '<svg class="admin-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M12 5v14"></path><path d="M5 12h14"></path></svg>',
        'Deliveries' => '<svg class="admin-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M3 7h11v10H3z"></path><path d="M14 10h4l3 3v4h-7z"></path><circle cx="7" cy="18" r="1.5"></circle><circle cx="17" cy="18" r="1.5"></circle></svg>',
        'Drivers' => '<svg class="admin-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="12" cy="8" r="4"></circle><path d="M4 21v-1a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v1"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>',
        'Users' => '<svg class="admin-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>',
        'Logout' => '<svg class="admin-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>',
        default => '<svg class="admin-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="9"></circle></svg>',
    };
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars((string) $pageTitle, ENT_QUOTES, 'UTF-8'); ?> - Mwanjelwa Kiganjani</title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars(app_url('assets/css/portal.css'), ENT_QUOTES, 'UTF-8'); ?>">
</head>
<body>
<div class="portal portal--admin">
    <aside class="admin-sidebar">
        <div class="admin-brand">
            <a class="brand-name" href="<?php echo htmlspecialchars($homeUrl, ENT_QUOTES, 'UTF-8'); ?>">Mwanjelwa Kiganjani</a>
            <span class="brand-tag">Admin</span>
        </div>

        <nav class="admin-nav" aria-label="Admin navigation">
            <?php foreach ($navItems as $label => $url): ?>
                <?php if ($label === 'Logout') { continue; } ?>
                <?php
                $classes = ['admin-nav-link'];
                if ($label === $activeNav) {
                    $classes[] = 'is-active';
                }
                ?>
                <a class="<?php echo htmlspecialchars(implode(' ', $classes), ENT_QUOTES, 'UTF-8'); ?>"
                   href="<?php echo htmlspecialchars((string) $url, ENT_QUOTES, 'UTF-8'); ?>">
                    <?php echo $navIcon((string) $label); ?>
                    <span><?php echo htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8'); ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="admin-sidebar-footer">
            <div class="admin-user">
                <span class="admin-user-label">Signed in as</span>
                <strong><?php echo htmlspecialchars((string) $userName, ENT_QUOTES, 'UTF-8'); ?></strong>
            </div>
            <a class="admin-nav-link admin-nav-link--logout" href="<?php echo htmlspecialchars($logoutUrl, ENT_QUOTES, 'UTF-8'); ?>">
                <?php echo $navIcon('Logout'); ?>
                <span>Logout</span>
            </a>
        </div>
    </aside>

    <div class="admin-workspace">
        <header class="admin-topbar">
            <div class="admin-topbar-context">
                <span class="admin-topbar-label">Admin Portal</span>
                <strong class="admin-topbar-title"><?php echo htmlspecialchars((string) $pageTitle, ENT_QUOTES, 'UTF-8'); ?></strong>
            </div>
            <div class="admin-topbar-user">
                <span>Signed in as</span>
                <strong><?php echo htmlspecialchars((string) $userName, ENT_QUOTES, 'UTF-8'); ?></strong>
            </div>
        </header>

        <main class="portal-main portal-main--admin">
            <?php if (is_array($flash) && isset($flash['type'], $flash['message'])): ?>
                <?php $flashType = $flash['type'] === 'success' ? 'success' : 'error'; ?>
                <div class="flash flash--<?php echo $flashType; ?>" role="status">
                    <?php echo htmlspecialchars((string) $flash['message'], ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>
