<?php

/**
 * Mwanjelwa Kiganjani - Admin Operations Overview View
 *
 * Expects: $deliveryCounts, $liveDeliveries, $recentActivities,
 *          $driverSummary, $recentUsers
 */

$statusLabels = [
    'pending' => 'Pending',
    'in_transit' => 'In transit',
    'delivered' => 'Delivered',
    'cancelled' => 'Cancelled',
];

$statusClasses = [
    'pending' => 'badge--pending',
    'in_transit' => 'badge--in-transit',
    'delivered' => 'badge--delivered',
    'cancelled' => 'badge--cancelled',
];

$activityLabels = [
    'role_assigned' => 'Role assigned',
    'delivery_created' => 'Delivery created',
    'delivery_updated' => 'Delivery updated',
    'delivery_cancelled' => 'Delivery cancelled',
    'driver_approved' => 'Driver approved',
    'driver_profile_updated' => 'Driver profile updated',
    'driver_activated' => 'Driver activated',
    'driver_deactivated' => 'Driver deactivated',
];

$formatDateTime = static function (mixed $value): string {
    $timestamp = strtotime((string) $value);

    return $timestamp === false ? 'Unknown' : date('d M Y H:i', $timestamp);
};
?>
<section class="page-heading">
    <div>
        <h1>Operations Overview</h1>
        <p class="page-intro">Live delivery operations, driver availability, and recent system activity.</p>
    </div>
</section>

<section class="stat-grid stat-grid--operations" aria-label="Delivery and driver statistics">
    <article class="stat-card stat-card--total">
        <span class="stat-label">Total Deliveries</span>
        <span class="stat-value"><?php echo (int) ($deliveryCounts['total'] ?? 0); ?></span>
    </article>
    <article class="stat-card stat-card--pending">
        <span class="stat-label">Pending Deliveries</span>
        <span class="stat-value"><?php echo (int) ($deliveryCounts['pending'] ?? 0); ?></span>
    </article>
    <article class="stat-card stat-card--transit">
        <span class="stat-label">In Transit</span>
        <span class="stat-value"><?php echo (int) ($deliveryCounts['in_transit'] ?? 0); ?></span>
    </article>
    <article class="stat-card stat-card--delivered">
        <span class="stat-label">Delivered</span>
        <span class="stat-value"><?php echo (int) ($deliveryCounts['delivered'] ?? 0); ?></span>
    </article>
    <article class="stat-card stat-card--cancelled">
        <span class="stat-label">Cancelled</span>
        <span class="stat-value"><?php echo (int) ($deliveryCounts['cancelled'] ?? 0); ?></span>
    </article>
    <article class="stat-card stat-card--drivers">
        <span class="stat-label">Total Drivers</span>
        <span class="stat-value"><?php echo (int) ($driverSummary['total_drivers'] ?? 0); ?></span>
    </article>
</section>

<section class="panel">
    <div class="panel-heading">
        <h2>Live Operations</h2>
        <span class="panel-meta"><?php echo count($liveDeliveries); ?> active</span>
    </div>

    <?php if ($liveDeliveries === []): ?>
        <div class="empty-state">
            <strong>No live deliveries.</strong>
            <p>Pending and in-transit deliveries will appear here when they are created.</p>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th scope="col">Delivery</th>
                        <th scope="col">Customer</th>
                        <th scope="col">Route</th>
                        <th scope="col">Status</th>
                        <th scope="col">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($liveDeliveries as $delivery): ?>
                        <?php
                        $statusKey = strtolower(trim((string) $delivery['delivery_status']));
                        $statusLabel = $statusLabels[$statusKey]
                            ?? ($statusKey !== '' ? ucwords(str_replace('_', ' ', $statusKey)) : 'Unknown');
                        $statusClass = $statusClasses[$statusKey] ?? 'badge--neutral';
                        $customerName = trim((string) ($delivery['customer_name'] ?? ''));
                        $customerPhone = trim((string) ($delivery['customer_phone'] ?? ''));
                        $sellerName = trim((string) ($delivery['seller_name'] ?? ''));
                        ?>
                        <tr>
                            <td>
                                <strong>#<?php echo (int) $delivery['id']; ?></strong>
                                <span class="table-subtext"><?php echo htmlspecialchars($formatDateTime($delivery['created_at']), ENT_QUOTES, 'UTF-8'); ?></span>
                            </td>
                            <td>
                                <span class="table-line"><?php echo htmlspecialchars($customerName !== '' ? $customerName : 'Unknown customer', ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php if ($customerPhone !== ''): ?>
                                    <span class="table-subtext"><?php echo htmlspecialchars($customerPhone, ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php endif; ?>
                                <?php if ($sellerName !== ''): ?>
                                    <span class="table-subtext">Seller: <?php echo htmlspecialchars($sellerName, ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="table-line"><?php echo htmlspecialchars((string) $delivery['pickup_address'], ENT_QUOTES, 'UTF-8'); ?></span>
                                <span class="table-subtext">to: <?php echo htmlspecialchars((string) $delivery['destination_address'], ENT_QUOTES, 'UTF-8'); ?></span>
                            </td>
                            <td>
                                <span class="badge <?php echo htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            </td>
                            <td>
                                <a class="text-link" href="<?php echo htmlspecialchars(app_url('admin/delivery.php?id=' . (int) $delivery['id']), ENT_QUOTES, 'UTF-8'); ?>">View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<div class="content-grid">
    <section class="panel">
        <div class="panel-heading">
            <h2>Recent Activities</h2>
        </div>

        <?php if ($recentActivities === []): ?>
            <div class="empty-state">
                <strong>No activity recorded yet.</strong>
                <p>Admin audit actions will appear here when they are recorded.</p>
            </div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th scope="col">Activity</th>
                            <th scope="col">Admin</th>
                            <th scope="col">When</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentActivities as $activity): ?>
                            <?php
                            $actionKey = strtolower(trim((string) $activity['action_type']));
                            $actionLabel = $activityLabels[$actionKey]
                                ?? ($actionKey !== '' ? ucwords(str_replace('_', ' ', $actionKey)) : 'Unknown action');
                            $activityDescription = trim((string) ($activity['description'] ?? ''));
                            $adminName = trim((string) ($activity['admin_name'] ?? ''));
                            ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($actionLabel, ENT_QUOTES, 'UTF-8'); ?></strong>
                                    <?php if ($activityDescription !== ''): ?>
                                        <span class="table-subtext"><?php echo htmlspecialchars($activityDescription, ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($adminName !== '' ? $adminName : 'Unknown admin', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($formatDateTime($activity['created_at']), ENT_QUOTES, 'UTF-8'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <p class="form-note">
            Delivery creation, editing, cancellation, driver approval, driver profile
            updates, activation, and deactivation are logged. Other status lifecycle
            events such as accepted, completed, and reassigned are not logged yet.
            This panel shows real admin audit records only.
        </p>
    </section>

    <div class="dashboard-stack">
        <section class="panel">
            <div class="panel-heading">
                <h2>Drivers Overview</h2>
            </div>

            <dl class="detail-list">
                <div class="detail-row">
                    <dt>Total drivers</dt>
                    <dd><strong><?php echo (int) ($driverSummary['total_drivers'] ?? 0); ?></strong> <span class="muted">driver role</span></dd>
                </div>
                <div class="detail-row">
                    <dt>Driver profiles</dt>
                    <dd><?php echo (int) ($driverSummary['profiles'] ?? 0); ?></dd>
                </div>
                <div class="detail-row">
                    <dt>Verified</dt>
                    <dd><span class="badge badge--delivered"><?php echo (int) ($driverSummary['verified'] ?? 0); ?></span></dd>
                </div>
                <div class="detail-row">
                    <dt>Pending verification</dt>
                    <dd><span class="badge badge--pending"><?php echo (int) ($driverSummary['pending'] ?? 0); ?></span></dd>
                </div>
                <?php $missingDriverProfiles = max(0, (int) ($driverSummary['total_drivers'] ?? 0) - (int) ($driverSummary['profiles'] ?? 0)); ?>
                <?php if ($missingDriverProfiles > 0): ?>
                    <div class="detail-row">
                        <dt>Missing profiles</dt>
                        <dd><span class="badge badge--neutral"><?php echo $missingDriverProfiles; ?></span></dd>
                    </div>
                <?php endif; ?>
            </dl>

            <?php if ((int) ($driverSummary['profiles'] ?? 0) === 0): ?>
                <p class="form-note">No driver profiles exist yet.</p>
            <?php endif; ?>

            <p class="form-note">
                Review and activate eligible drivers from the Drivers page. Active, verified
                drivers can then access their delivery activity.
            </p>

            <p><a class="text-link" href="<?php echo htmlspecialchars(app_url('admin/drivers.php'), ENT_QUOTES, 'UTF-8'); ?>">Manage drivers</a></p>
        </section>

        <section class="panel">
            <div class="panel-heading">
                <h2>Quick Actions</h2>
            </div>

            <div class="quick-actions">
                <a class="quick-action" href="<?php echo htmlspecialchars(app_url('admin/create-delivery.php'), ENT_QUOTES, 'UTF-8'); ?>">
                    <span class="quick-action-title">Create Delivery</span>
                    <span class="quick-action-note">Record a manual customer delivery.</span>
                    <span class="quick-action-link">Open &rarr;</span>
                </a>

                <a class="quick-action" href="<?php echo htmlspecialchars(app_url('admin/deliveries.php'), ENT_QUOTES, 'UTF-8'); ?>">
                    <span class="quick-action-title">View All Deliveries</span>
                    <span class="quick-action-note">Browse, search, and filter recorded deliveries.</span>
                    <span class="quick-action-link">Open &rarr;</span>
                </a>

                <a class="quick-action" href="<?php echo htmlspecialchars(app_url('admin/drivers.php'), ENT_QUOTES, 'UTF-8'); ?>">
                    <span class="quick-action-title">Manage Drivers</span>
                    <span class="quick-action-note">Review pending drivers and manage activation.</span>
                    <span class="quick-action-link">Open &rarr;</span>
                </a>

                <a class="quick-action" href="<?php echo htmlspecialchars(app_url('admin/users.php'), ENT_QUOTES, 'UTF-8'); ?>">
                    <span class="quick-action-title">Manage Users</span>
                    <span class="quick-action-note">Review accounts and assign roles.</span>
                    <span class="quick-action-link">Open &rarr;</span>
                </a>
            </div>
        </section>
    </div>
</div>

<section class="panel">
    <div class="panel-heading">
        <h2>Recently Registered Users</h2>
        <a class="text-link" href="<?php echo htmlspecialchars(app_url('admin/users.php'), ENT_QUOTES, 'UTF-8'); ?>">View all users</a>
    </div>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th scope="col">Name</th>
                    <th scope="col">Phone</th>
                    <th scope="col">Status</th>
                    <th scope="col">Joined</th>
                    <th scope="col">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($recentUsers === []): ?>
                    <tr>
                        <td colspan="5" class="empty-state">No users found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recentUsers as $recentUser): ?>
                        <?php $isActive = $recentUser['account_status'] === 'active'; ?>
                        <tr>
                            <td><?php echo htmlspecialchars((string) $recentUser['full_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string) $recentUser['phone'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <span class="badge <?php echo $isActive ? 'badge--active' : 'badge--inactive'; ?>">
                                    <?php echo htmlspecialchars((string) $recentUser['account_status'], ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars(date('d M Y', strtotime((string) $recentUser['created_at'])), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <a class="text-link" href="<?php echo htmlspecialchars(app_url('admin/user.php?id=' . (int) $recentUser['id']), ENT_QUOTES, 'UTF-8'); ?>">View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
