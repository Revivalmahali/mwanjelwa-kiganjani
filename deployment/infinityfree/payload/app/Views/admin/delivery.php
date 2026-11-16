<?php

/**
 * Mwanjelwa Kiganjani - Admin Delivery Details View
 *
 * Expects: $delivery (array|null), $assignment (array|null)
 *
 * This is a read-only page. Every value comes directly from the deliveries,
 * users, delivery_assignments, and driver_profiles tables.
 */

$delivery = $delivery ?? null;
$assignment = $assignment ?? null;

$formatDateTime = static function (mixed $value): string {
    $timestamp = strtotime((string) $value);

    return $timestamp === false ? 'Unknown' : date('d M Y H:i', $timestamp);
};

$dashboardUrl = app_url('admin/dashboard.php');
$deliveriesUrl = app_url('admin/deliveries.php');
?>

<?php if ($delivery === null): ?>
    <section class="page-heading">
        <div>
            <p class="breadcrumb">
                <a class="text-link" href="<?php echo htmlspecialchars($dashboardUrl, ENT_QUOTES, 'UTF-8'); ?>">Dashboard</a>
                <span aria-hidden="true">/</span>
                <a class="text-link" href="<?php echo htmlspecialchars($deliveriesUrl, ENT_QUOTES, 'UTF-8'); ?>">Deliveries</a>
                <span aria-hidden="true">/</span>
                <span>Delivery not found</span>
            </p>
            <h1>Delivery not found</h1>
            <p class="page-intro">The requested delivery does not exist or the identifier is invalid.</p>
        </div>
    </section>

    <section class="panel">
        <div class="empty-state">
            <strong>No delivery to display.</strong>
            <p>Return to the dashboard to view live operations.</p>
        </div>

        <div class="form-actions">
            <a class="btn btn--primary" href="<?php echo htmlspecialchars($deliveriesUrl, ENT_QUOTES, 'UTF-8'); ?>">Back to Deliveries</a>
        </div>
    </section>
    <?php return; ?>
<?php endif; ?>

<?php
$statusKey = strtolower(trim((string) $delivery['delivery_status']));
$canCancel = $canCancel ?? in_array($statusKey, ['pending', 'in_transit'], true);

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

$statusLabel = $statusLabels[$statusKey]
    ?? ($statusKey !== '' ? ucwords(str_replace('_', ' ', $statusKey)) : 'Unknown');
$statusClass = $statusClasses[$statusKey] ?? 'badge--neutral';

$createdByName = trim((string) ($delivery['created_by_name'] ?? ''));
$createdByName = $createdByName !== '' ? $createdByName : 'Unknown admin';

$pickupDescription = trim((string) ($delivery['pickup_description'] ?? ''));
$destinationDescription = trim((string) ($delivery['destination_description'] ?? ''));
$deliveryDescription = trim((string) ($delivery['delivery_description'] ?? ''));

$driverName = $assignment !== null
    ? trim((string) ($assignment['driver_name'] ?? ''))
    : '';
$driverPhone = $assignment !== null
    ? trim((string) ($assignment['driver_phone'] ?? ''))
    : '';
$driverLicense = $assignment !== null
    ? trim((string) ($assignment['license_number'] ?? ''))
    : '';
$assignmentStatus = $assignment !== null
    ? trim((string) ($assignment['assignment_status'] ?? ''))
    : '';
$assignmentStatusKey = strtolower($assignmentStatus);
$assignmentStatusLabel = $assignmentStatusKey !== ''
    ? ucfirst(str_replace('_', ' ', $assignmentStatusKey))
    : '';
$assignmentTimeLabel = $assignmentStatusKey === 'accepted' ? 'Accepted at' : 'Assigned at';
?>
<section class="page-heading">
    <div>
        <p class="breadcrumb">
            <a class="text-link" href="<?php echo htmlspecialchars($dashboardUrl, ENT_QUOTES, 'UTF-8'); ?>">Dashboard</a>
            <span aria-hidden="true">/</span>
            <a class="text-link" href="<?php echo htmlspecialchars($deliveriesUrl, ENT_QUOTES, 'UTF-8'); ?>">Deliveries</a>
            <span aria-hidden="true">/</span>
            <span>Delivery #<?php echo (int) $delivery['id']; ?></span>
        </p>
        <h1>Delivery #<?php echo (int) $delivery['id']; ?></h1>
        <p class="page-intro">
            <span class="badge <?php echo htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8'); ?>">
                <?php echo htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8'); ?>
            </span>
            <span class="muted">Created <?php echo htmlspecialchars($formatDateTime($delivery['created_at']), ENT_QUOTES, 'UTF-8'); ?></span>
        </p>
    </div>
</section>

<div class="content-grid">
    <div class="dashboard-stack">
        <section class="panel">
            <div class="panel-heading">
                <h2>Customer &amp; cargo</h2>
            </div>

            <dl class="detail-list">
                <div class="detail-row">
                    <dt>Customer name</dt>
                    <dd><?php echo htmlspecialchars((string) $delivery['customer_name'], ENT_QUOTES, 'UTF-8'); ?></dd>
                </div>
                <div class="detail-row">
                    <dt>Customer phone</dt>
                    <dd><?php echo htmlspecialchars((string) $delivery['customer_phone'], ENT_QUOTES, 'UTF-8'); ?></dd>
                </div>
                <div class="detail-row">
                    <dt>Cargo type</dt>
                    <dd><?php echo htmlspecialchars((string) $delivery['cargo_type'], ENT_QUOTES, 'UTF-8'); ?></dd>
                </div>
                <div class="detail-row">
                    <dt>Cargo value</dt>
                    <dd>TZS <?php echo htmlspecialchars(number_format((float) $delivery['cargo_value'], 2), ENT_QUOTES, 'UTF-8'); ?></dd>
                </div>
            </dl>
        </section>

        <section class="panel">
            <div class="panel-heading">
                <h2>Route</h2>
            </div>

            <dl class="detail-list">
                <div class="detail-row">
                    <dt>Pickup address</dt>
                    <dd><?php echo htmlspecialchars((string) $delivery['pickup_address'], ENT_QUOTES, 'UTF-8'); ?></dd>
                </div>
                <div class="detail-row">
                    <dt>Pickup description</dt>
                    <dd>
                        <?php if ($pickupDescription === ''): ?>
                            <span class="muted">Not provided</span>
                        <?php else: ?>
                            <?php echo nl2br(htmlspecialchars($pickupDescription, ENT_QUOTES, 'UTF-8')); ?>
                        <?php endif; ?>
                    </dd>
                </div>
                <div class="detail-row">
                    <dt>Destination address</dt>
                    <dd><?php echo htmlspecialchars((string) $delivery['destination_address'], ENT_QUOTES, 'UTF-8'); ?></dd>
                </div>
                <div class="detail-row">
                    <dt>Destination description</dt>
                    <dd>
                        <?php if ($destinationDescription === ''): ?>
                            <span class="muted">Not provided</span>
                        <?php else: ?>
                            <?php echo nl2br(htmlspecialchars($destinationDescription, ENT_QUOTES, 'UTF-8')); ?>
                        <?php endif; ?>
                    </dd>
                </div>
            </dl>
        </section>
    </div>

    <div class="dashboard-stack">
        <section class="panel">
            <div class="panel-heading">
                <h2>Delivery record</h2>
            </div>

            <dl class="detail-list">
                <div class="detail-row">
                    <dt>Delivery ID</dt>
                    <dd>#<?php echo (int) $delivery['id']; ?></dd>
                </div>
                <div class="detail-row">
                    <dt>Status</dt>
                    <dd>
                        <span class="badge <?php echo htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                    </dd>
                </div>
                <div class="detail-row">
                    <dt>Created by</dt>
                    <dd><?php echo htmlspecialchars($createdByName, ENT_QUOTES, 'UTF-8'); ?></dd>
                </div>
                <div class="detail-row">
                    <dt>Created at</dt>
                    <dd><?php echo htmlspecialchars($formatDateTime($delivery['created_at']), ENT_QUOTES, 'UTF-8'); ?></dd>
                </div>
                <div class="detail-row">
                    <dt>Updated at</dt>
                    <dd><?php echo htmlspecialchars($formatDateTime($delivery['updated_at']), ENT_QUOTES, 'UTF-8'); ?></dd>
                </div>
                <?php if ($deliveryDescription !== ''): ?>
                    <div class="detail-row">
                        <dt>Delivery notes</dt>
                        <dd><?php echo nl2br(htmlspecialchars($deliveryDescription, ENT_QUOTES, 'UTF-8')); ?></dd>
                    </div>
                <?php endif; ?>
            </dl>
        </section>

        <section class="panel">
            <div class="panel-heading">
                <h2>Assigned driver</h2>
            </div>

            <?php if ($assignment === null): ?>
                <div class="empty-state">
                    <strong>No driver has accepted this delivery yet.</strong>
                    <p>Accepted deliveries will appear here.</p>
                </div>
            <?php else: ?>
                <dl class="detail-list">
                    <div class="detail-row">
                        <dt>Driver name</dt>
                        <dd><?php echo htmlspecialchars($driverName !== '' ? $driverName : 'Unknown driver', ENT_QUOTES, 'UTF-8'); ?></dd>
                    </div>
                    <?php if ($driverPhone !== ''): ?>
                        <div class="detail-row">
                            <dt>Phone</dt>
                            <dd><?php echo htmlspecialchars($driverPhone, ENT_QUOTES, 'UTF-8'); ?></dd>
                        </div>
                    <?php endif; ?>
                    <?php if ($driverLicense !== ''): ?>
                        <div class="detail-row">
                            <dt>Licence number</dt>
                            <dd><?php echo htmlspecialchars($driverLicense, ENT_QUOTES, 'UTF-8'); ?></dd>
                        </div>
                    <?php endif; ?>
                    <?php if ($assignmentStatusLabel !== ''): ?>
                        <div class="detail-row">
                            <dt>Assignment status</dt>
                            <dd><?php echo htmlspecialchars($assignmentStatusLabel, ENT_QUOTES, 'UTF-8'); ?></dd>
                        </div>
                    <?php endif; ?>
                    <div class="detail-row">
                        <dt><?php echo htmlspecialchars($assignmentTimeLabel, ENT_QUOTES, 'UTF-8'); ?></dt>
                        <dd><?php echo htmlspecialchars($formatDateTime($assignment['assigned_at']), ENT_QUOTES, 'UTF-8'); ?></dd>
                    </div>
                    <?php if (trim((string) ($assignment['completed_at'] ?? '')) !== ''): ?>
                        <div class="detail-row">
                            <dt>Completed at</dt>
                            <dd><?php echo htmlspecialchars($formatDateTime($assignment['completed_at']), ENT_QUOTES, 'UTF-8'); ?></dd>
                        </div>
                    <?php endif; ?>
                </dl>
            <?php endif; ?>
        </section>
    </div>
</div>

        <div class="form-actions">
            <?php if ($statusKey === 'pending'): ?>
                <a class="btn btn--primary" href="<?php echo htmlspecialchars(app_url('admin/edit-delivery.php?id=' . (int) $delivery['id']), ENT_QUOTES, 'UTF-8'); ?>">Edit Delivery</a>
            <?php endif; ?>
            <?php if ($canCancel): ?>
                <a class="btn btn--danger" href="<?php echo htmlspecialchars(app_url('admin/cancel-delivery.php?id=' . (int) $delivery['id']), ENT_QUOTES, 'UTF-8'); ?>">Cancel Delivery</a>
            <?php endif; ?>
            <a class="btn btn--secondary" href="<?php echo htmlspecialchars($deliveriesUrl, ENT_QUOTES, 'UTF-8'); ?>">Back to Deliveries</a>
        </div>
