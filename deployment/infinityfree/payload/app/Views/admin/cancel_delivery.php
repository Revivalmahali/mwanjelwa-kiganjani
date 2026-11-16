<?php

$delivery = $delivery ?? null;
$assignment = $assignment ?? null;
$reason = $reason ?? '';
$errors = $errors ?? [];
$formToken = $formToken ?? '';
$formAction = $formAction ?? '';
$backUrl = $backUrl ?? '';
$canCancel = $canCancel ?? false;
$cancelBlockedReason = $cancelBlockedReason ?? '';
$confirmed = $confirmed ?? false;

$escape = static function (mixed $value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

$fieldHasError = static fn (string $field): bool => isset($errors[$field]);

$fieldError = static fn (string $field): string => (string) ($errors[$field] ?? '');

$dashboardUrl = app_url('admin/dashboard.php');
$deliveriesUrl = app_url('admin/deliveries.php');
?>
<?php if ($delivery === null): ?>
    <section class="page-heading">
        <div>
            <p class="breadcrumb">
                <a class="text-link" href="<?php echo $escape($dashboardUrl); ?>">Dashboard</a>
                <span aria-hidden="true">/</span>
                <a class="text-link" href="<?php echo $escape($deliveriesUrl); ?>">Deliveries</a>
                <span aria-hidden="true">/</span>
                <span>Delivery not found</span>
            </p>
            <h1>Delivery not found</h1>
            <p class="page-intro">The requested delivery does not exist or the identifier is invalid.</p>
        </div>
    </section>

    <section class="panel">
        <div class="empty-state">
            <strong>No delivery to cancel.</strong>
            <p>Return to the deliveries list to choose a valid delivery.</p>
        </div>

        <div class="form-actions">
            <a class="btn btn--primary" href="<?php echo $escape($deliveriesUrl); ?>">Back to Deliveries</a>
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

$driverName = $assignment !== null
    ? trim((string) ($assignment['driver_name'] ?? ''))
    : '';

$deliveryUrl = app_url('admin/delivery.php?id=' . (int) $delivery['id']);
$backUrl = $backUrl !== '' ? $backUrl : $deliveryUrl;
?>
<section class="page-heading">
    <div>
        <p class="breadcrumb">
            <a class="text-link" href="<?php echo $escape($dashboardUrl); ?>">Dashboard</a>
            <span aria-hidden="true">/</span>
            <a class="text-link" href="<?php echo $escape($deliveriesUrl); ?>">Deliveries</a>
            <span aria-hidden="true">/</span>
            <a class="text-link" href="<?php echo $escape($deliveryUrl); ?>">Delivery #<?php echo (int) $delivery['id']; ?></a>
            <span aria-hidden="true">/</span>
            <span>Cancel</span>
        </p>
        <h1>Cancel Delivery #<?php echo (int) $delivery['id']; ?></h1>
        <p class="page-intro">
            <span class="badge <?php echo $escape($statusClass); ?>">
                <?php echo $escape($statusLabel); ?>
            </span>
            <span class="muted">This action cannot be undone.</span>
        </p>
    </div>
</section>

<?php if (!$canCancel): ?>
    <section class="panel">
        <div class="empty-state">
            <strong>This delivery cannot be cancelled.</strong>
            <p><?php echo $escape($cancelBlockedReason); ?></p>
        </div>

        <div class="form-actions">
            <a class="btn btn--primary" href="<?php echo $escape($deliveryUrl); ?>">Back to Delivery</a>
            <a class="btn btn--secondary" href="<?php echo $escape($deliveriesUrl); ?>">Back to Deliveries</a>
        </div>
    </section>
    <?php return; ?>
<?php endif; ?>

<?php if ($errors !== []): ?>
    <div class="error-list" role="alert">
        <strong>Please correct the following errors:</strong>
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?php echo $escape((string) $error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="warning-box" role="note">
    <strong>Cancelling this delivery cannot be undone.</strong>
    The delivery record will remain, but its status will become Cancelled.
</div>

<?php if ($statusKey === 'in_transit'): ?>
    <div class="warning-box" role="note">
        <strong>This delivery is already in transit.</strong>
        The driver may already be en route. Confirm with the customer and driver before cancelling.
    </div>
<?php endif; ?>

<?php if ($assignment !== null): ?>
    <div class="warning-box" role="note">
        <strong>Driver already assigned.</strong>
        <?php if ($driverName !== ''): ?>
            A driver (<?php echo $escape($driverName); ?>) is assigned to this delivery.
        <?php else: ?>
            A driver is assigned to this delivery.
        <?php endif; ?>
        The assignment record will be preserved and marked cancelled.
    </div>
<?php endif; ?>

<form class="panel delivery-form" method="post" action="<?php echo $escape($formAction); ?>">
    <?php echo \App\Services\Csrf::field(); ?>
    <input
        type="hidden"
        name="_form_token"
        value="<?php echo $escape($formToken); ?>"
    >

    <section class="form-section">
        <div class="form-section-heading">
            <h2>Cancellation details</h2>
            <p class="form-note">
                Explain why this delivery is being cancelled. The reason is recorded in the admin activity log.
            </p>
        </div>

        <div class="form-grid">
            <div class="form-group form-group--full <?php echo $fieldHasError('cancellation_reason') ? 'has-error' : ''; ?>">
                <label for="cancellation_reason">Cancellation Reason <span class="required" aria-hidden="true">*</span></label>
                <textarea
                    id="cancellation_reason"
                    name="cancellation_reason"
                    rows="4"
                    maxlength="500"
                    required
                    aria-invalid="<?php echo $fieldHasError('cancellation_reason') ? 'true' : 'false'; ?>"
                    <?php if ($fieldHasError('cancellation_reason')): ?>aria-describedby="cancellation_reason_error"<?php endif; ?>
                ><?php echo $escape($reason); ?></textarea>
                <?php if ($fieldHasError('cancellation_reason')): ?>
                    <span class="field-error" id="cancellation_reason_error"><?php echo $escape($fieldError('cancellation_reason')); ?></span>
                <?php endif; ?>
            </div>
        </div>

        <label class="checkbox-row">
            <input
                type="checkbox"
                name="confirm_cancel"
                value="1"
                <?php echo $confirmed ? 'checked' : ''; ?>
                required
                aria-invalid="<?php echo $fieldHasError('confirm_cancel') ? 'true' : 'false'; ?>"
                <?php if ($fieldHasError('confirm_cancel')): ?>aria-describedby="confirm_cancel_error"<?php endif; ?>
            >
            <span>I understand this will cancel the delivery and cannot be undone.</span>
        </label>
        <?php if ($fieldHasError('confirm_cancel')): ?>
            <span class="field-error" id="confirm_cancel_error"><?php echo $escape($fieldError('confirm_cancel')); ?></span>
        <?php endif; ?>
    </section>

    <div class="form-actions">
        <button type="submit" class="btn btn--danger">Cancel Delivery</button>
        <a class="btn btn--secondary" href="<?php echo $escape($backUrl); ?>">Back to Delivery</a>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var form = document.querySelector('.delivery-form');

    if (!form) {
        return;
    }

    form.addEventListener('submit', function () {
        var button = form.querySelector('button[type="submit"]');

        if (button) {
            button.disabled = true;
            button.textContent = 'Cancelling...';
        }
    });
});
</script>
