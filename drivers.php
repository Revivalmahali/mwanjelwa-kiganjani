<?php

/**
 * Mwanjelwa Kiganjani - Admin Drivers List View
 *
 * Expects: $driverList, $search, $status, $page, $perPage, $total, $totalPages
 */

use App\Services\Csrf;
use App\Services\DriverAdminManagement;

$driverList = $driverList ?? [];
$search = $search ?? '';
$status = $status ?? '';
$page = $page ?? 1;
$perPage = $perPage ?? 20;
$total = $total ?? 0;
$totalPages = $totalPages ?? 1;

$escape = static function (mixed $value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

$formatDate = static function (mixed $value): string {
    $timestamp = strtotime((string) $value);

    return $timestamp === false ? 'Unknown' : date('d M Y H:i', $timestamp);
};

$statusLabels = [
    'pending' => 'Pending',
    'active' => 'Active',
    'inactive' => 'Inactive',
    'missing' => 'Missing profile',
];

$baseUrl = app_url('admin/drivers.php');
$hasFilters = $search !== '' || $status !== '';

$pageUrl = static function (int $targetPage) use ($baseUrl, $search, $status): string {
    $params = [];

    if ($search !== '') {
        $params['q'] = $search;
    }

    if ($status !== '') {
        $params['status'] = $status;
    }

    if ($targetPage > 1) {
        $params['page'] = $targetPage;
    }

    return $params === [] ? $baseUrl : $baseUrl . '?' . http_build_query($params);
};

$showingFrom = $total === 0 ? 0 : (($page - 1) * $perPage) + 1;
$showingTo = $total === 0 ? 0 : min($page * $perPage, $total);

$startPage = max(1, $page - 2);
$endPage = min($totalPages, $page + 2);
?>
<section class="page-heading">
    <div>
        <p class="breadcrumb">
            <a class="text-link" href="<?php echo $escape(app_url('admin/dashboard.php')); ?>">Dashboard</a>
            <span aria-hidden="true">/</span>
            <span>Drivers</span>
        </p>
        <h1>Drivers</h1>
        <p class="page-intro">Review registered drivers, pending profiles, and account access.</p>
    </div>
</section>

<section class="panel">
    <form class="search-form" method="get" action="<?php echo $escape($baseUrl); ?>">
        <label class="visually-hidden" for="driver-search">Search drivers</label>
        <input
            type="search"
            id="driver-search"
            name="q"
            value="<?php echo $escape($search); ?>"
            placeholder="Search by name, phone or email"
        >

        <label class="visually-hidden" for="driver-status">Filter by status</label>
        <select id="driver-status" name="status">
            <option value="">All statuses</option>
            <?php foreach ($statusLabels as $statusValue => $statusLabel): ?>
                <option value="<?php echo $escape($statusValue); ?>"<?php echo $status === $statusValue ? ' selected' : ''; ?>>
                    <?php echo $escape($statusLabel); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit" class="btn btn--primary">Filter</button>

        <?php if ($hasFilters): ?>
            <a class="btn btn--secondary" href="<?php echo $escape($baseUrl); ?>">Clear</a>
        <?php endif; ?>
    </form>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th scope="col">Driver</th>
                    <th scope="col">Contact</th>
                    <th scope="col">Residential address</th>
                    <th scope="col">Account</th>
                    <th scope="col">Verification</th>
                    <th scope="col">Registered</th>
                    <th scope="col">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($driverList === []): ?>
                    <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <?php if ($hasFilters): ?>
                                    <strong>No drivers matched your filters.</strong>
                                    <p>Try a different search term or status filter.</p>
                                    <p><a class="text-link" href="<?php echo $escape($baseUrl); ?>">Clear filters</a></p>
                                <?php else: ?>
                                    <strong>No registered drivers yet.</strong>
                                    <p>Driver accounts will appear here after self-registration.</p>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($driverList as $driver): ?>
                        <?php
                        $driverStatus = (string) ($driver['driver_status'] ?? 'inconsistent');
                        $driverStatusLabel = DriverAdminManagement::statusLabel($driverStatus);
                        $profileMissing = ($driver['driver_profile_id'] ?? null) === null;
                        $accountStatus = strtolower(trim((string) ($driver['account_status'] ?? '')));
                        $verificationStatus = strtolower(trim((string) ($driver['verification_status'] ?? '')));
                        $residentialAddress = trim((string) ($driver['residential_address'] ?? ''));
                        $profileIssues = DriverAdminManagement::profileCompletenessIssues($driver);
                        $email = trim((string) ($driver['email'] ?? ''));
                        $detailUrl = app_url('admin/driver.php?id=' . (int) $driver['id']);
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo $escape((string) $driver['full_name']); ?></strong>
                                <span class="table-subtext">#<?php echo (int) $driver['id']; ?></span>
                            </td>
                            <td>
                                <span class="table-line"><?php echo $escape((string) $driver['phone']); ?></span>
                                <?php if ($email !== ''): ?>
                                    <span class="table-subtext"><?php echo $escape($email); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($profileMissing): ?>
                                    <span class="muted">No driver profile</span>
                                <?php elseif ($residentialAddress !== ''): ?>
                                    <?php echo $escape($residentialAddress); ?>
                                <?php else: ?>
                                    <span class="muted">Not provided</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge <?php echo $escape(DriverAdminManagement::statusClass($driverStatus)); ?>">
                                    <?php echo $escape($driverStatusLabel); ?>
                                </span>
                                <span class="table-subtext">Account: <?php echo $escape($accountStatus !== '' ? $accountStatus : 'unknown'); ?></span>
                            </td>
                            <td>
                                <?php if ($profileMissing): ?>
                                    <span class="badge badge--neutral">Missing profile</span>
                                <?php else: ?>
                                    <span class="badge <?php echo $verificationStatus === 'verified' ? 'badge--active' : ($verificationStatus === 'rejected' ? 'badge--inactive' : 'badge--pending'); ?>">
                                        <?php echo $escape($verificationStatus !== '' ? $verificationStatus : 'unknown'); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $escape($formatDate($driver['created_at'])); ?></td>
                            <td>
                                <div class="table-actions">
                                    <a class="text-link" href="<?php echo $escape($detailUrl); ?>">
                                        <?php echo $driverStatus === 'pending' ? 'Review / Approve' : 'View Profile'; ?>
                                    </a>

                                    <?php if (!$profileMissing && $profileIssues !== [] && $driverStatus !== 'active'): ?>
                                        <span class="muted" title="<?php echo $escape('Complete before approval or activation: ' . implode(', ', $profileIssues)); ?>">Profile incomplete</span>
                                    <?php endif; ?>

                                    <?php if (DriverAdminManagement::canDeactivate($driver)): ?>
                                        <form class="inline-action-form" method="post" action="<?php echo $escape($detailUrl); ?>">
                                            <?php echo Csrf::field(); ?>
                                            <input type="hidden" name="action" value="deactivate">
                                            <button type="submit" class="text-link text-link--button text-link--danger">Deactivate</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($total > 0): ?>
        <div class="pagination">
            <span class="pagination-summary">
                Showing <?php echo (int) $showingFrom; ?>&ndash;<?php echo (int) $showingTo; ?>
                of <?php echo (int) $total; ?> drivers
            </span>

            <?php if ($totalPages > 1): ?>
                <nav class="pagination-links" aria-label="Drivers pagination">
                    <?php if ($page > 1): ?>
                        <a class="pagination-link" href="<?php echo $escape($pageUrl($page - 1)); ?>">Previous</a>
                    <?php else: ?>
                        <span class="pagination-link is-disabled">Previous</span>
                    <?php endif; ?>

                    <?php for ($pageNumber = $startPage; $pageNumber <= $endPage; $pageNumber++): ?>
                        <?php if ($pageNumber === $page): ?>
                            <span class="pagination-link is-current" aria-current="page"><?php echo (int) $pageNumber; ?></span>
                        <?php else: ?>
                            <a class="pagination-link" href="<?php echo $escape($pageUrl($pageNumber)); ?>"><?php echo (int) $pageNumber; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <a class="pagination-link" href="<?php echo $escape($pageUrl($page + 1)); ?>">Next</a>
                    <?php else: ?>
                        <span class="pagination-link is-disabled">Next</span>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</section>
