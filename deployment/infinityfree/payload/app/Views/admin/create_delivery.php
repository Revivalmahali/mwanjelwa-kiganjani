<?php

/**
 * Mwanjelwa Kiganjani - Admin Create Delivery View
 *
 * Expects: $form, $errors, $formToken, $formAction, $cancelUrl
 */

$fieldHasError = static fn (string $field): bool => isset($errors[$field]);

$fieldError = static fn (string $field): string => (string) ($errors[$field] ?? '');

$formGroupClass = static function (string $field) use ($errors): string {
    return 'form-group' . (isset($errors[$field]) ? ' has-error' : '');
};
?>
<section class="page-heading">
    <div>
        <p class="breadcrumb">
            <a class="text-link" href="<?php echo htmlspecialchars(app_url('admin/dashboard.php'), ENT_QUOTES, 'UTF-8'); ?>">Dashboard</a>
            <span aria-hidden="true">/</span>
            <span>Create Delivery</span>
        </p>
        <h1>Create Delivery</h1>
        <p class="page-intro">Record a manual delivery for a customer. New deliveries start as Pending.</p>
    </div>
</section>

<?php if ($errors !== []): ?>
    <div class="error-list" role="alert">
        <strong>Please correct the following errors:</strong>
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8'); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form class="panel delivery-form" method="post" action="<?php echo htmlspecialchars($formAction, ENT_QUOTES, 'UTF-8'); ?>">
    <?php echo \App\Services\Csrf::field(); ?>
    <input
        type="hidden"
        name="_form_token"
        value="<?php echo htmlspecialchars($formToken, ENT_QUOTES, 'UTF-8'); ?>"
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
                    value="<?php echo htmlspecialchars($form['customer_name'], ENT_QUOTES, 'UTF-8'); ?>"
                    maxlength="150"
                    autocomplete="name"
                    required
                    aria-invalid="<?php echo $fieldHasError('customer_name') ? 'true' : 'false'; ?>"
                    <?php if ($fieldHasError('customer_name')): ?>aria-describedby="customer_name_error"<?php endif; ?>
                >
                <?php if ($fieldHasError('customer_name')): ?>
                    <span class="field-error" id="customer_name_error"><?php echo htmlspecialchars($fieldError('customer_name'), ENT_QUOTES, 'UTF-8'); ?></span>
                <?php endif; ?>
            </div>

            <div class="<?php echo $formGroupClass('customer_phone'); ?>">
                <label for="customer_phone">Customer Phone <span class="required" aria-hidden="true">*</span></label>
                <input
                    type="tel"
                    id="customer_phone"
                    name="customer_phone"
                    value="<?php echo htmlspecialchars($form['customer_phone'], ENT_QUOTES, 'UTF-8'); ?>"
                    maxlength="20"
                    autocomplete="tel"
                    required
                    aria-invalid="<?php echo $fieldHasError('customer_phone') ? 'true' : 'false'; ?>"
                    <?php if ($fieldHasError('customer_phone')): ?>aria-describedby="customer_phone_error"<?php endif; ?>
                >
                <?php if ($fieldHasError('customer_phone')): ?>
                    <span class="field-error" id="customer_phone_error"><?php echo htmlspecialchars($fieldError('customer_phone'), ENT_QUOTES, 'UTF-8'); ?></span>
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
                    value="<?php echo htmlspecialchars($form['cargo_type'], ENT_QUOTES, 'UTF-8'); ?>"
                    maxlength="150"
                    placeholder="e.g. Building materials"
                    autocomplete="off"
                    required
                    aria-invalid="<?php echo $fieldHasError('cargo_type') ? 'true' : 'false'; ?>"
                    <?php if ($fieldHasError('cargo_type')): ?>aria-describedby="cargo_type_error"<?php endif; ?>
                >
                <?php if ($fieldHasError('cargo_type')): ?>
                    <span class="field-error" id="cargo_type_error"><?php echo htmlspecialchars($fieldError('cargo_type'), ENT_QUOTES, 'UTF-8'); ?></span>
                <?php endif; ?>
            </div>

            <div class="<?php echo $formGroupClass('cargo_value'); ?>">
                <label for="cargo_value">Cargo Value (TZS) <span class="required" aria-hidden="true">*</span></label>
                <input
                    type="text"
                    id="cargo_value"
                    name="cargo_value"
                    value="<?php echo htmlspecialchars($form['cargo_value'], ENT_QUOTES, 'UTF-8'); ?>"
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
                    <span class="field-error" id="cargo_value_error"><?php echo htmlspecialchars($fieldError('cargo_value'), ENT_QUOTES, 'UTF-8'); ?></span>
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
                    value="<?php echo htmlspecialchars($form['pickup_address'], ENT_QUOTES, 'UTF-8'); ?>"
                    maxlength="500"
                    autocomplete="off"
                    required
                    aria-invalid="<?php echo $fieldHasError('pickup_address') ? 'true' : 'false'; ?>"
                    <?php if ($fieldHasError('pickup_address')): ?>aria-describedby="pickup_address_error"<?php endif; ?>
                >
                <?php if ($fieldHasError('pickup_address')): ?>
                    <span class="field-error" id="pickup_address_error"><?php echo htmlspecialchars($fieldError('pickup_address'), ENT_QUOTES, 'UTF-8'); ?></span>
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
                ><?php echo htmlspecialchars($form['pickup_description'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                <?php if ($fieldHasError('pickup_description')): ?>
                    <span class="field-error" id="pickup_description_error"><?php echo htmlspecialchars($fieldError('pickup_description'), ENT_QUOTES, 'UTF-8'); ?></span>
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
                    value="<?php echo htmlspecialchars($form['destination_address'], ENT_QUOTES, 'UTF-8'); ?>"
                    maxlength="500"
                    autocomplete="off"
                    required
                    aria-invalid="<?php echo $fieldHasError('destination_address') ? 'true' : 'false'; ?>"
                    <?php if ($fieldHasError('destination_address')): ?>aria-describedby="destination_address_error"<?php endif; ?>
                >
                <?php if ($fieldHasError('destination_address')): ?>
                    <span class="field-error" id="destination_address_error"><?php echo htmlspecialchars($fieldError('destination_address'), ENT_QUOTES, 'UTF-8'); ?></span>
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
                ><?php echo htmlspecialchars($form['destination_description'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                <?php if ($fieldHasError('destination_description')): ?>
                    <span class="field-error" id="destination_description_error"><?php echo htmlspecialchars($fieldError('destination_description'), ENT_QUOTES, 'UTF-8'); ?></span>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <div class="form-actions">
        <button type="submit" class="btn btn--primary">Create Delivery</button>
        <a class="btn btn--secondary" href="<?php echo htmlspecialchars($cancelUrl, ENT_QUOTES, 'UTF-8'); ?>">Cancel</a>
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
            button.textContent = 'Creating...';
        }
    });
});
</script>
