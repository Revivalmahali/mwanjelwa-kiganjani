<?php

/**
 * Mwanjelwa Kiganjani - Admin Deliveries List View
 *
 * Expects: $deliveryList, $search, $status, $page, $perPage, $total, $totalPages
 */

$deliveryList = $deliveryList ?? [];
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
    'in_transit' => 'In Transit',
    'delivered' => 'Delivered',
    'cancelled' => 'Cancelled',
];

$statusClasses = [
    'pending' => 'badge--pending',
    'in_transit' => 'badge--in-transit',
    'delivered' => 'badge--delivered',
    'cancelled' => 'badge--cancelled',
];

$baseUrl = app_url('admin/deliveries.php');
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
            <span>Deliveries</span>
        </p>
        <h1>Deliveries</h1>
        <p class="page-intro">View, search, and filter all recorded deliveries.</p>
    </div>
</section>

<section class="panel">
    <form class="search-form" method="get" action="<?php echo $escape($baseUrl); ?>">
        <label class="visually-hidden" for="delivery-search">Search deliveries</label>
        <input
            type="search"
            id="delivery-search"
            name="q"
            value="<?php echo $escape($search); ?>"
            placeholder="Search by ID, customer, phone, cargo or address"
        >

        <label class="visually-hidden" for="delivery-status">Filter by status</label>
        <select id="delivery-status" name="status">
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
                    <th scope="col">Delivery</th>
                    <th scope="col">Customer</th>
                    <th scope="col">Route</th>
                    <th scope="col">Status</th>
                    <th scope="col">Driver</th>
                    <th scope="col">Date</th>
                    <th scope="col">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($deliveryList === []): ?>
                    <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <?php if ($hasFilters): ?>
                                    <strong>No deliveries matched your filters.</strong>
                                    <p>Try a different search term or status filter.</p>
                                    <p><a class="text-link" href="<?php echo $escape($baseUrl); ?>">Clear filters</a></p>
                                <?php else: ?>
                                    <strong>No deliveries recorded yet.</strong>
                                    <p>New deliveries will appear here once they are created.</p>
                                    <p><a class="text-link" href="<?php echo $escape(app_url('admin/create-delivery.php')); ?>">Create Delivery</a></p>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($deliveryList as $delivery): ?>
                        <?php
                        $statusKey = strtolower(trim((string) $delivery['delivery_status']));
                        $statusLabel = $statusLabels[$statusKey]
                            ?? ($statusKey !== '' ? ucwords(str_replace('_', ' ', $statusKey)) : 'Unknown');
                        $statusClass = $statusClasses[$statusKey] ?? 'badge--neutral';

                        $customerName = trim((string) ($delivery['customer_name'] ?? ''));
                        $customerPhone = trim((string) ($delivery['customer_phone'] ?? ''));
                        $cargoType = trim((string) ($delivery['cargo_type'] ?? ''));
                        $driverName = trim((string) ($delivery['driver_name'] ?? ''));
                        ?>
                        <tr>
                            <td>
                                <strong>#<?php echo (int) $delivery['id']; ?></strong>
                                <?php if ($cargoType !== ''): ?>
                                    <span class="table-subtext"><?php echo $escape($cargoType); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="table-line"><?php echo $escape($customerName !== '' ? $customerName : 'Unknown customer'); ?></span>
                                <?php if ($customerPhone !== ''): ?>
                                    <span class="table-subtext"><?php echo $escape($customerPhone); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="table-line"><?php echo $escape((string) $delivery['pickup_address']); ?></span>
                                <span class="table-subtext">to: <?php echo $escape((string) $delivery['destination_address']); ?></span>
                            </td>
                            <td>
                                <span class="badge <?php echo $escape($statusClass); ?>">
                                    <?php echo $escape($statusLabel); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($driverName !== ''): ?>
                                    <?php echo $escape($driverName); ?>
                                <?php else: ?>
                                    <span class="muted">No driver assigned</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $escape($formatDate($delivery['created_at'])); ?></td>
                            <td>
                                <a class="text-link" href="<?php echo $escape(app_url('admin/delivery.php?id=' . (int) $delivery['id'])); ?>">View</a>
                                <?php if ($statusKey === 'pending'): ?>
                                    <span class="muted" aria-hidden="true"> &middot; </span>
                                    <a class="text-link" href="<?php echo $escape(app_url('admin/edit-delivery.php?id=' . (int) $delivery['id'])); ?>">Edit</a>
                                <?php endif; ?>
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
                of <?php echo (int) $total; ?> deliveries
            </span>

            <?php if ($totalPages > 1): ?>
                <nav class="pagination-links" aria-label="Deliveries pagination">
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
