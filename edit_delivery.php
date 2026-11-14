<?php

/**
 * Mwanjelwa Kiganjani - Admin Edit Delivery View
 *
 * Expects: $delivery (array|null), $assignment (array|null), $form, $errors,
 *          $formToken, $formAction, $cancelUrl, $expectedUpdatedAt,
 *          $canEdit, $editBlockedReason
 */

$delivery = $delivery ?? null;
$assignment = $assignment ?? null;
$form = $form ?? [];
$errors = $errors ?? [];
$formToken = $formToken ?? '';
$formAction = $formAction ?? '';
$cancelUrl = $cancelUrl ?? app_url('admin/deliveries.php');
$expectedUpdatedAt = $expectedUpdatedAt ?? '';
$canEdit = $canEdit ?? false;
$editBlockedReason = $editBlockedReason ?? '';

$escape = static function (mixed $value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

$fieldHasError = static fn (string $field): bool => isset($errors[$field]);

$fieldError = static fn (string $field): string => (string) ($errors[$field] ?? '');

$formGroupClass = static function (string $field) use ($errors): string {
    return 'form-group' . (isset($errors[$field]) ? ' has-error' : '');
};

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
            <strong>No delivery to edit.</strong>
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
            <span>Edit</span>
        </p>
        <h1>Edit Delivery #<?php echo (int) $delivery['id']; ?></h1>
        <p class="page-intro">
            <span class="badge <?php echo $escape($statusClass); ?>">
                <?php echo $escape($statusLabel); ?>
            </span>
            <span class="muted">Only Pending deliveries can be edited.</span>
        </p>
    </div>
</section>

<?php if (!$canEdit): ?>
    <section class="panel">
        <div class="empty-state">
            <strong>This delivery is read-only.</strong>
            <p><?php echo $escape($editBlockedReason); ?></p>
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

<?php if ($assignment !== null): ?>
    <div class="warning-box" role="note">
        <strong>Driver already assigned.</strong>
        <?php if ($driverName !== ''): ?>
            A driver (<?php echo $escape($driverName); ?>) is already assigned to this delivery.
        <?php else: ?>
            A driver is already assigned to this delivery.
        <?php endif; ?>
        Saving changes will not change the driver assignment.
    </div>
<?php endif; ?>

<form class="panel delivery-form" method="post" action="<?php echo $escape($formAction); ?>">
    <?php echo \App\Services\Csrf::field(); ?>
    <input
        type="hidden"
        name="_form_token"
        value="<?php echo $escape($formToken); ?>"
    >
    <input
        type="hidden"
        name="_expected_updated_at"
        value="<?php echo $escape($expectedUpdatedAt); ?>"
    >

    <section class="form-section">
        <div class="form-section-heading">
            <h2>Customer information</h2>
            <p class="form-note">The customer does not need a registered account.</p>
        </div>

        <div class="form-grid">
            <div class="<?php echo $formGroupClass('customer_name'); ?>">
                <label for="customer_name">Customer Name <span class="required" aria-hidden="true">*</span></label>
                <input
                    type="text"
                    id="customer_name"
                    name="customer_name"
                    value="<?php echo $escape($form['customer_name'] ?? ''); ?>"
                    maxlength="150"
                    autocomplete="name"
                    required
                    aria-invalid="<?php echo $fieldHasError('customer_name') ? 'true' : 'false'; ?>"
                    <?php if ($fieldHasError('customer_name')): ?>aria-describedby="customer_name_error"<?php endif; ?>
                >
                <?php if ($fieldHasError('customer_name')): ?>
                    <span class="field-error" id="customer_name_error"><?php echo $escape($fieldError('customer_name')); ?></span>
                <?php endif; ?>
            </div>

            <div class="<?php echo $formGroupClass('customer_phone'); ?>">
                <label for="customer_phone">Customer Phone <span class="required" aria-hidden="true">*</span></label>
                <input
                    type="tel"
                    id="customer_phone"
                    name="customer_phone"
                    value="<?php echo $escape($form['customer_phone'] ?? ''); ?>"
                    maxlength="20"
                    autocomplete="tel"
                    required
                    aria-invalid="<?php echo $fieldHasError('customer_phone') ? 'true' : 'false'; ?>"
                    <?php if ($fieldHasError('customer_phone')): ?>aria-describedby="customer_phone_error"<?php endif; ?>
                >
                <?php if ($fieldHasError('customer_phone')): ?>
                    <span class="field-error" id="customer_phone_error"><?php echo $escape($fieldError('customer_phone')); ?></span>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="form-section">
        <div class="form-section-heading">
            <h2>Cargo information</h2>
            <p class="form-note">Describe the cargo and enter its declared value in Tanzanian shillings.</p>
        </div>

        <div class="form-grid">
            <div class="<?php echo $formGroupClass('cargo_type'); ?>">
                <label for="cargo_type">Cargo Type <span class="required" aria-hidden="true">*</span></label>
                <input
                    type="text"
                    id="cargo_type"
                    name="cargo_type"
                    value="<?php echo $escape($form['cargo_type'] ?? ''); ?>"
                    maxlength="150"
                    placeholder="e.g. Building materials"
                    autocomplete="off"
                    required
                    aria-invalid="<?php echo $fieldHasError('cargo_type') ? 'true' : 'false'; ?>"
                    <?php if ($fieldHasError('cargo_type')): ?>aria-describedby="cargo_type_error"<?php endif; ?>
                >
                <?php if ($fieldHasError('cargo_type')): ?>
                    <span class="field-error" id="cargo_type_error"><?php echo $escape($fieldError('cargo_type')); ?></span>
                <?php endif; ?>
            </div>

            <div class="<?php echo $formGroupClass('cargo_value'); ?>">
                <label for="cargo_value">Cargo Value (TZS) <span class="required" aria-hidden="true">*</span></label>
                <input
                    type="text"
                    id="cargo_value"
                    name="cargo_value"
                    value="<?php echo $escape($form['cargo_value'] ?? ''); ?>"
                    maxlength="20"
                    inputmode="decimal"
                    placeholder="e.g. 125000.00"
                    autocomplete="off"
                    required
                    aria-invalid="<?php echo $fieldHasError('cargo_value') ? 'true' : 'false'; ?>"
                    <?php if ($fieldHasError('cargo_value')): ?>aria-describedby="cargo_value_error"<?php endif; ?>
                >
                <span class="field-hint">Non-negative amount. Commas and spaces are accepted.</span>
                <?php if ($fieldHasError('cargo_value')): ?>
                    <span class="field-error" id="cargo_value_error"><?php echo $escape($fieldError('cargo_value')); ?></span>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="form-section">
        <div class="form-section-heading">
            <h2>Pickup information</h2>
            <p class="form-note">Where should the cargo be collected?</p>
        </div>

        <div class="form-grid">
            <div class="form-group form-group--full <?php echo $fieldHasError('pickup_address') ? 'has-error' : ''; ?>">
                <label for="pickup_address">Pickup Location <span class="required" aria-hidden="true">*</span></label>
                <input
                    type="text"
                    id="pickup_address"
                    name="pickup_address"
                    value="<?php echo $escape($form['pickup_address'] ?? ''); ?>"
                    maxlength="500"
                    autocomplete="off"
                    required
                    aria-invalid="<?php echo $fieldHasError('pickup_address') ? 'true' : 'false'; ?>"
                    <?php if ($fieldHasError('pickup_address')): ?>aria-describedby="pickup_address_error"<?php endif; ?>
                >
                <?php if ($fieldHasError('pickup_address')): ?>
                    <span class="field-error" id="pickup_address_error"><?php echo $escape($fieldError('pickup_address')); ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group form-group--full <?php echo $fieldHasError('pickup_description') ? 'has-error' : ''; ?>">
                <label for="pickup_description">Pickup Description <span class="field-optional">(optional)</span></label>
                <textarea
                    id="pickup_description"
                    name="pickup_description"
                    rows="3"
                    maxlength="2000"
                    placeholder="Building, gate, contact person, or other pickup instructions"
                    aria-invalid="<?php echo $fieldHasError('pickup_description') ? 'true' : 'false'; ?>"
                    <?php if ($fieldHasError('pickup_description')): ?>aria-describedby="pickup_description_error"<?php endif; ?>
                ><?php echo $escape($form['pickup_description'] ?? ''); ?></textarea>
                <?php if ($fieldHasError('pickup_description')): ?>
                    <span class="field-error" id="pickup_description_error"><?php echo $escape($fieldError('pickup_description')); ?></span>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="form-section">
        <div class="form-section-heading">
            <h2>Destination information</h2>
            <p class="form-note">Where should the cargo be delivered?</p>
        </div>

        <div class="form-grid">
            <div class="form-group form-group--full <?php echo $fieldHasError('destination_address') ? 'has-error' : ''; ?>">
                <label for="destination_address">Destination <span class="required" aria-hidden="true">*</span></label>
                <input
                    type="text"
                    id="destination_address"
                    name="destination_address"
                    value="<?php echo $escape($form['destination_address'] ?? ''); ?>"
                    maxlength="500"
                    autocomplete="off"
                    required
                    aria-invalid="<?php echo $fieldHasError('destination_address') ? 'true' : 'false'; ?>"
                    <?php if ($fieldHasError('destination_address')): ?>aria-describedby="destination_address_error"<?php endif; ?>
                >
                <?php if ($fieldHasError('destination_address')): ?>
                    <span class="field-error" id="destination_address_error"><?php echo $escape($fieldError('destination_address')); ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group form-group--full <?php echo $fieldHasError('destination_description') ? 'has-error' : ''; ?>">
                <label for="destination_description">Destination Description <span class="field-optional">(optional)</span></label>
                <textarea
                    id="destination_description"
                    name="destination_description"
                    rows="3"
                    maxlength="2000"
                    placeholder="Building, gate, contact person, or other delivery instructions"
                    aria-invalid="<?php echo $fieldHasError('destination_description') ? 'true' : 'false'; ?>"
                    <?php if ($fieldHasError('destination_description')): ?>aria-describedby="destination_description_error"<?php endif; ?>
                ><?php echo $escape($form['destination_description'] ?? ''); ?></textarea>
                <?php if ($fieldHasError('destination_description')): ?>
                    <span class="field-error" id="destination_description_error"><?php echo $escape($fieldError('destination_description')); ?></span>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <div class="form-actions">
        <button type="submit" class="btn btn--primary">Save Changes</button>
        <a class="btn btn--secondary" href="<?php echo $escape($cancelUrl); ?>">Cancel</a>
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
            button.textContent = 'Saving...';
        }
    });
});
</script>
