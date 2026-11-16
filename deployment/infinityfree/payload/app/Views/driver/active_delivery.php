<?php

use App\Services\Csrf;

$activeDelivery = $activeDelivery ?? null;

$escape = static function (mixed $value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

$formatDate = static function (mixed $value): string {
    $timestamp = strtotime((string) $value);

    return $timestamp === false ? 'Unknown' : date('d M Y H:i', $timestamp);
};

$pickupDescription = $activeDelivery !== null
    ? trim((string) ($activeDelivery['pickup_description'] ?? ''))
    : '';
$destinationDescription = $activeDelivery !== null
    ? trim((string) ($activeDelivery['destination_description'] ?? ''))
    : '';
$deliveryDescription = $activeDelivery !== null
    ? trim((string) ($activeDelivery['delivery_description'] ?? ''))
    : '';
$assignmentStatus = $activeDelivery !== null
    ? strtolower(trim((string) ($activeDelivery['assignment_status'] ?? '')))
    : '';
?>
<section class="page-heading">
    <div>
        <p class="breadcrumb">
            <span>Driver Portal</span>
            <span aria-hidden="true">/</span>
            <span>My Active Delivery</span>
        </p>
        <h1>My Active Delivery</h1>
        <p class="page-intro">The delivery you have accepted and are currently handling.</p>
    </div>
</section>

<?php if ($activeDelivery === null): ?>
    <section class="panel">
        <div class="panel-heading">
            <h2>No active delivery</h2>
        </div>

        <div class="empty-state">
            <img class="empty-state-art" src="<?php echo $escape(app_url('assets/images/empty-deliveries.svg')); ?>" alt="" aria-hidden="true">
            <strong>You have no active delivery.</strong>
            <p>Accept an available delivery to see its details here.</p>
        </div>

        <p class="form-note">
            <a class="text-link" href="<?php echo $escape(app_url('driver/available-deliveries.php')); ?>">View available deliveries</a>
            <span aria-hidden="true"> &middot; </span>
            <a class="text-link" href="<?php echo $escape(app_url('driver/dashboard.php')); ?>">Back to dashboard</a>
        </p>
    </section>
<?php else: ?>
    <ol class="delivery-progress" aria-label="Delivery progress">
        <li class="is-complete"><span class="delivery-progress-dot">1</span><span>Accepted</span></li>
        <li class="is-current" aria-current="step"><span class="delivery-progress-dot">2</span><span>In transit</span></li>
        <li><span class="delivery-progress-dot">3</span><span>Delivered</span></li>
    </ol>
    <div class="content-grid">
        <div class="dashboard-stack">
            <section class="panel">
                <div class="panel-heading">
                    <h2>Delivery #<?php echo (int) $activeDelivery['id']; ?></h2>
                    <span class="badge badge--in-transit">In transit</span>
                </div>

                <dl class="detail-list">
                    <div class="detail-row">
                        <dt>Customer name</dt>
                        <dd><?php echo $escape($activeDelivery['customer_name'] ?? ''); ?></dd>
                    </div>
                    <div class="detail-row">
                        <dt>Customer phone</dt>
                        <dd><?php echo $escape($activeDelivery['customer_phone'] ?? ''); ?></dd>
                    </div>
                    <div class="detail-row">
                        <dt>Cargo type</dt>
                        <dd><?php echo $escape($activeDelivery['cargo_type'] ?? ''); ?></dd>
                    </div>
                    <div class="detail-row">
                        <dt>Cargo value</dt>
                        <dd>TZS <?php echo $escape(number_format((float) ($activeDelivery['cargo_value'] ?? 0), 2)); ?></dd>
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
                        <dd><?php echo $escape($activeDelivery['pickup_address'] ?? ''); ?></dd>
                    </div>
                    <div class="detail-row">
                        <dt>Pickup description</dt>
                        <dd>
                            <?php if ($pickupDescription === ''): ?>
                                <span class="muted">Not provided</span>
                            <?php else: ?>
                                <?php echo nl2br($escape($pickupDescription)); ?>
                            <?php endif; ?>
                        </dd>
                    </div>
                    <div class="detail-row">
                        <dt>Destination address</dt>
                        <dd><?php echo $escape($activeDelivery['destination_address'] ?? ''); ?></dd>
                    </div>
                    <div class="detail-row">
                        <dt>Destination description</dt>
                        <dd>
                            <?php if ($destinationDescription === ''): ?>
                                <span class="muted">Not provided</span>
                            <?php else: ?>
                                <?php echo nl2br($escape($destinationDescription)); ?>
                            <?php endif; ?>
                        </dd>
                    </div>
                </dl>
            </section>
        </div>

        <div class="dashboard-stack">
            <section class="panel">
                <div class="panel-heading">
                    <h2>Assignment</h2>
                </div>

                <dl class="detail-list">
                    <div class="detail-row">
                        <dt>Delivery status</dt>
                        <dd><span class="badge badge--in-transit">In transit</span></dd>
                    </div>
                    <div class="detail-row">
                        <dt>Assignment status</dt>
                        <dd><?php echo $escape($assignmentStatus !== '' ? ucfirst(str_replace('_', ' ', $assignmentStatus)) : 'Unknown'); ?></dd>
                    </div>
                    <div class="detail-row">
                        <dt>Accepted at</dt>
                        <dd><?php echo $escape($formatDate($activeDelivery['assigned_at'] ?? '')); ?></dd>
                    </div>
                    <div class="detail-row">
                        <dt>Created at</dt>
                        <dd><?php echo $escape($formatDate($activeDelivery['created_at'] ?? '')); ?></dd>
                    </div>
                    <?php if ($deliveryDescription !== ''): ?>
                        <div class="detail-row">
                            <dt>Delivery notes</dt>
                            <dd><?php echo nl2br($escape($deliveryDescription)); ?></dd>
                        </div>
                    <?php endif; ?>
                </dl>

                <form class="form-stack" method="post" action="<?php echo $escape(app_url('driver/complete-delivery.php')); ?>">
                    <?php echo Csrf::field(); ?>
                    <input type="hidden" name="delivery_id" value="<?php echo (int) ($activeDelivery['id'] ?? 0); ?>">
                    <label class="checkbox-label">
                        <input type="checkbox" name="confirm_delivered" value="1" required>
                        <span>I confirm this delivery has been delivered to the customer.</span>
                    </label>
                    <button type="submit" class="btn btn--primary">Mark as Delivered</button>
                </form>
            </section>

            <p class="form-note">
                <a class="text-link" href="<?php echo $escape(app_url('driver/dashboard.php')); ?>">Back to dashboard</a>
            </p>
        </div>
    </div>
<?php endif; ?>
