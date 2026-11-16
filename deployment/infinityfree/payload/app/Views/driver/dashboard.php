<?php

$escape = static function (mixed $value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

$formatDate = static function (mixed $value): string {
    $timestamp = strtotime((string) $value);

    return $timestamp === false ? 'Unknown' : date('d M Y H:i', $timestamp);
};

$accountStatus = strtolower(trim((string) ($driver['account_status'] ?? '')));
$verificationStatus = strtolower(trim((string) ($driver['verification_status'] ?? '')));
$email = trim((string) ($driver['email'] ?? ''));
$profileId = (int) ($driver['driver_profile_id'] ?? 0);
$activeDelivery = $activeDelivery ?? null;
$availableDeliveryCount = (int) ($availableDeliveryCount ?? 0);

$statusLabel = static function (string $status): string {
    return $status === '' ? 'Unknown' : ucfirst(str_replace('_', ' ', $status));
};
?>
<section class="page-heading">
    <div>
        <p class="breadcrumb">
            <span>Driver Portal</span>
            <span aria-hidden="true">/</span>
            <span>Dashboard</span>
        </p>
        <h1>Welcome back, <?php echo $escape($driver['full_name'] ?? 'Driver'); ?></h1>
        <p class="page-intro">Your driver account overview.</p>
    </div>
</section>

<section class="driver-welcome" aria-label="Driver delivery overview">
    <div class="driver-welcome-copy">
        <span class="driver-welcome-kicker">Your route starts here</span>
        <p>Every careful pickup brings a delivery closer to home.</p>
        <a class="btn btn--light" href="<?php echo $escape(app_url($activeDelivery !== null ? 'driver/active-delivery.php' : 'driver/available-deliveries.php')); ?>">
            <?php echo $activeDelivery !== null ? 'Continue active delivery' : 'Find a delivery'; ?>
        </a>
    </div>
    <img src="<?php echo $escape(app_url('assets/images/delivery-hero.svg')); ?>" alt="" aria-hidden="true">
</section>

<div class="stat-grid stat-grid--operations">
    <article class="stat-card stat-card--drivers">
        <span class="stat-label">Account Status</span>
        <span class="stat-value"><?php echo $escape($statusLabel($accountStatus)); ?></span>
        <span class="muted">Active driver access</span>
    </article>

    <article class="stat-card stat-card--delivered">
        <span class="stat-label">Profile Status</span>
        <span class="stat-value"><?php echo $escape($statusLabel($verificationStatus)); ?></span>
        <span class="muted">Driver profile verification</span>
    </article>

    <article class="stat-card stat-card--pending">
        <span class="stat-label">Active Delivery</span>
        <span class="stat-value">
            <?php echo $activeDelivery !== null ? '#' . (int) $activeDelivery['id'] : 'None'; ?>
        </span>
        <span class="muted">
            <?php echo $activeDelivery !== null ? 'In transit' : 'No active delivery'; ?>
        </span>
    </article>

    <article class="stat-card stat-card--transit">
        <span class="stat-label">Available Deliveries</span>
        <span class="stat-value"><?php echo $availableDeliveryCount; ?></span>
        <span class="muted">Pending deliveries</span>
    </article>
</div>

<div class="dashboard-stack">
    <section class="panel">
        <div class="panel-heading">
            <h2>Driver Identity</h2>
            <?php if ($profileId > 0): ?>
                <span class="panel-meta">Profile #<?php echo $profileId; ?></span>
            <?php endif; ?>
        </div>

        <dl class="detail-list">
            <div class="detail-row">
                <dt>Full name</dt>
                <dd><?php echo $escape($driver['full_name'] ?? ''); ?></dd>
            </div>
            <div class="detail-row">
                <dt>Phone</dt>
                <dd><?php echo $escape($driver['phone'] ?? ''); ?></dd>
            </div>
            <?php if ($email !== ''): ?>
                <div class="detail-row">
                    <dt>Email</dt>
                    <dd><?php echo $escape($email); ?></dd>
                </div>
            <?php endif; ?>
            <div class="detail-row">
                <dt>Account status</dt>
                <dd><span class="badge <?php echo $accountStatus === 'active' ? 'badge--active' : 'badge--inactive'; ?>"><?php echo $escape($statusLabel($accountStatus)); ?></span></dd>
            </div>
            <div class="detail-row">
                <dt>Verification status</dt>
                <dd><span class="badge <?php echo $verificationStatus === 'verified' ? 'badge--delivered' : 'badge--pending'; ?>"><?php echo $escape($statusLabel($verificationStatus)); ?></span></dd>
            </div>
            <div class="detail-row">
                <dt>Registered</dt>
                <dd><?php echo $escape($formatDate($driver['created_at'] ?? '')); ?></dd>
            </div>
        </dl>

        <p class="form-note">
            <a class="text-link" href="<?php echo $escape(app_url('driver/profile.php')); ?>">View my profile</a>
        </p>
    </section>

    <section class="panel">
        <div class="panel-heading">
            <h2>Delivery Activity</h2>
        </div>

        <?php if ($activeDelivery === null): ?>
            <div class="empty-state">
                <strong>No active delivery</strong>
                <p>
                    <?php if ($availableDeliveryCount > 0): ?>
                        <?php echo $availableDeliveryCount; ?> pending
                        <?php echo $availableDeliveryCount === 1 ? 'delivery is' : 'deliveries are'; ?>
                        waiting to be accepted.
                    <?php else: ?>
                        New pending deliveries will appear when an administrator creates them.
                    <?php endif; ?>
                </p>
            </div>
        <?php else: ?>
            <dl class="detail-list">
                <div class="detail-row">
                    <dt>Delivery</dt>
                    <dd>#<?php echo (int) $activeDelivery['id']; ?></dd>
                </div>
                <div class="detail-row">
                    <dt>Customer</dt>
                    <dd><?php echo $escape($activeDelivery['customer_name'] ?? ''); ?></dd>
                </div>
                <div class="detail-row">
                    <dt>Route</dt>
                    <dd>
                        <span class="table-line"><?php echo $escape($activeDelivery['pickup_address'] ?? ''); ?></span>
                        <span class="table-subtext">to: <?php echo $escape($activeDelivery['destination_address'] ?? ''); ?></span>
                    </dd>
                </div>
                <div class="detail-row">
                    <dt>Accepted at</dt>
                    <dd><?php echo $escape($formatDate($activeDelivery['assigned_at'] ?? '')); ?></dd>
                </div>
            </dl>

            <p class="form-note">
                <a class="text-link" href="<?php echo $escape(app_url('driver/active-delivery.php')); ?>">View active delivery</a>
            </p>
        <?php endif; ?>
    </section>

    <section class="panel">
        <div class="panel-heading">
            <h2>Quick Links</h2>
        </div>

        <div class="quick-actions">
            <a class="quick-action" href="<?php echo $escape(app_url('driver/available-deliveries.php')); ?>">
                <span class="quick-action-title">Available Deliveries</span>
                <span class="quick-action-note">
                    <?php echo $availableDeliveryCount; ?> pending
                    <?php echo $availableDeliveryCount === 1 ? 'delivery' : 'deliveries'; ?>
                    ready to accept.
                </span>
                <span class="quick-action-link">Open &rarr;</span>
            </a>

            <a class="quick-action" href="<?php echo $escape(app_url('driver/active-delivery.php')); ?>">
                <span class="quick-action-title">My Active Delivery</span>
                <span class="quick-action-note">
                    <?php echo $activeDelivery !== null ? 'View your current in-transit delivery.' : 'You have no active delivery.'; ?>
                </span>
                <span class="quick-action-link">Open &rarr;</span>
            </a>

            <a class="quick-action" href="<?php echo $escape(app_url('driver/history.php')); ?>">
                <span class="quick-action-title">Delivery History</span>
                <span class="quick-action-note">Completed delivery records will appear here.</span>
                <span class="quick-action-link">Open &rarr;</span>
            </a>

            <a class="quick-action" href="<?php echo $escape(app_url('driver/profile.php')); ?>">
                <span class="quick-action-title">My Profile</span>
                <span class="quick-action-note">Review your driver account and profile details.</span>
                <span class="quick-action-link">Open &rarr;</span>
            </a>
        </div>
    </section>
</div>
