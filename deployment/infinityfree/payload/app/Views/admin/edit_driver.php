<?php

/**
 * Mwanjelwa Kiganjani - Admin Edit Driver Profile View
 *
 * Expects: $driver, $form, $errors, $formToken, $formAction, $cancelUrl,
 *          $expectedUpdatedAt
 */

use App\Services\Csrf;
use App\Services\DriverAdminManagement;

$driver = $driver ?? [];
$form = $form ?? [];
$errors = $errors ?? [];
$formToken = $formToken ?? '';
$formAction = $formAction ?? '';
$cancelUrl = $cancelUrl ?? app_url('admin/drivers.php');
$expectedUpdatedAt = $expectedUpdatedAt ?? '';

$escape = static function (mixed $value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

$formatDate = static function (mixed $value): string {
    $timestamp = strtotime((string) $value);

    return $timestamp === false ? 'Unknown' : date('d M Y H:i', $timestamp);
};

$fieldHasError = static fn (string $field): bool => isset($errors[$field]);
$fieldError = static fn (string $field): string => (string) ($errors[$field] ?? '');
$formGroupClass = static function (string $field) use ($errors): string {
    return 'form-group' . (isset($errors[$field]) ? ' has-error' : '');
};

$driverStatus = (string) ($driver['driver_status'] ?? 'inconsistent');
$driverStatusLabel = DriverAdminManagement::statusLabel($driverStatus);
$driverStatusClass = DriverAdminManagement::statusClass($driverStatus);
$profileMissing = ($driver['driver_profile_id'] ?? null) === null;
$profileIssues = DriverAdminManagement::profileCompletenessIssues($driver);
$accountStatus = strtolower(trim((string) ($driver['account_status'] ?? '')));
$verificationStatus = strtolower(trim((string) ($driver['verification_status'] ?? '')));
$roles = is_array($driver['roles'] ?? null) ? $driver['roles'] : [];
$driversUrl = app_url('admin/drivers.php');
$detailUrl = app_url('admin/driver.php?id=' . (int) ($driver['id'] ?? 0));
?>
<section class="page-heading">
    <div>
        <p class="breadcrumb">
            <a class="text-link" href="<?php echo $escape(app_url('admin/dashboard.php')); ?>">Dashboard</a>
            <span aria-hidden="true">/</span>
            <a class="text-link" href="<?php echo $escape($driversUrl); ?>">Drivers</a>
            <span aria-hidden="true">/</span>
            <a class="text-link" href="<?php echo $escape($detailUrl); ?>"><?php echo $escape((string) ($driver['full_name'] ?? 'Driver')); ?></a>
            <span aria-hidden="true">/</span>
            <span>Edit Profile</span>
        </p>
        <h1>Edit Driver Profile</h1>
        <p class="page-intro">
            Driver #<?php echo (int) ($driver['id'] ?? 0); ?>
            <span class="badge <?php echo $escape($driverStatusClass); ?>"><?php echo $escape($driverStatusLabel); ?></span>
        </p>
    </div>
</section>

<?php if ($profileMissing): ?>
    <section class="panel">
        <div class="empty-state">
            <strong>No driver profile exists for this user.</strong>
            <p>No profile was created automatically. Return to the driver review page.</p>
        </div>

        <div class="form-actions">
            <a class="btn btn--primary" href="<?php echo $escape($detailUrl); ?>">Back to Driver</a>
            <a class="btn btn--secondary" href="<?php echo $escape($driversUrl); ?>">Back to Drivers</a>
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

<?php if ($profileIssues !== []): ?>
    <div class="warning-box" role="note">
        <strong>Driver profile incomplete.</strong>
        Approval or activation remains unavailable until the following required information is provided:
        <?php echo $escape(implode(', ', $profileIssues)); ?>.
    </div>
<?php endif; ?>

<section class="panel">
    <div class="panel-heading">
        <h2>Driver information</h2>
    </div>

    <dl class="detail-list">
        <div class="detail-row">
            <dt>Full name</dt>
            <dd><?php echo $escape((string) ($driver['full_name'] ?? '')); ?></dd>
        </div>
        <div class="detail-row">
            <dt>Phone</dt>
            <dd><?php echo $escape((string) ($driver['phone'] ?? '')); ?></dd>
        </div>
        <div class="detail-row">
            <dt>Email</dt>
            <dd>
                <?php echo trim((string) ($driver['email'] ?? '')) !== ''
                    ? $escape((string) $driver['email'])
                    : '<span class="muted">Not provided</span>'; ?>
            </dd>
        </div>
        <div class="detail-row">
            <dt>Account status</dt>
            <dd>
                <span class="badge <?php echo $accountStatus === 'active' ? 'badge--active' : ($accountStatus === 'inactive' ? 'badge--inactive' : 'badge--pending'); ?>">
                    <?php echo $escape($accountStatus !== '' ? $accountStatus : 'unknown'); ?>
                </span>
            </dd>
        </div>
        <div class="detail-row">
            <dt>Verification</dt>
            <dd>
                <span class="badge <?php echo $verificationStatus === 'verified' ? 'badge--active' : ($verificationStatus === 'rejected' ? 'badge--inactive' : 'badge--pending'); ?>">
                    <?php echo $escape($verificationStatus !== '' ? $verificationStatus : 'unknown'); ?>
                </span>
            </dd>
        </div>
        <div class="detail-row">
            <dt>Assigned roles</dt>
            <dd>
                <?php if ($roles === []): ?>
                    <span class="muted">No roles</span>
                <?php else: ?>
                    <span class="role-badges">
                        <?php foreach ($roles as $role): ?>
                            <span class="badge badge--role"><?php echo $escape((string) $role); ?></span>
                        <?php endforeach; ?>
                    </span>
                <?php endif; ?>
            </dd>
        </div>
        <div class="detail-row">
            <dt>Registered</dt>
            <dd><?php echo $escape($formatDate($driver['created_at'] ?? '')); ?></dd>
        </div>
    </dl>

    <p class="form-note">
        Only the residential address and licence number can be edited here.
        Saving this form does not change account status or verification status.
    </p>
</section>

<form class="panel driver-profile-form" method="post" action="<?php echo $escape($formAction); ?>">
    <?php echo Csrf::field(); ?>
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
            <h2>Editable profile information</h2>
            <p class="form-note">The residential address is required before approval or activation.</p>
        </div>

        <div class="form-grid">
            <div class="form-group form-group--full <?php echo $fieldHasError('residential_address') ? 'has-error' : ''; ?>">
                <label for="residential_address">Residential Address <span class="required" aria-hidden="true">*</span></label>
                <textarea
                    id="residential_address"
                    name="residential_address"
                    rows="4"
                    maxlength="500"
                    autocomplete="street-address"
                    required
                    aria-invalid="<?php echo $fieldHasError('residential_address') ? 'true' : 'false'; ?>"
                    <?php if ($fieldHasError('residential_address')): ?>aria-describedby="residential_address_error"<?php endif; ?>
                ><?php echo $escape($form['residential_address'] ?? ''); ?></textarea>
                <span class="field-hint">Maximum 500 characters.</span>
                <?php if ($fieldHasError('residential_address')): ?>
                    <span class="field-error" id="residential_address_error"><?php echo $escape($fieldError('residential_address')); ?></span>
                <?php endif; ?>
            </div>

            <div class="<?php echo $formGroupClass('license_number'); ?>">
                <label for="license_number">Licence Number <span class="field-optional">(optional)</span></label>
                <input
                    type="text"
                    id="license_number"
                    name="license_number"
                    value="<?php echo $escape($form['license_number'] ?? ''); ?>"
                    maxlength="50"
                    autocomplete="off"
                    aria-invalid="<?php echo $fieldHasError('license_number') ? 'true' : 'false'; ?>"
                    <?php if ($fieldHasError('license_number')): ?>aria-describedby="license_number_error"<?php endif; ?>
                >
                <span class="field-hint">Optional. Must be unique when provided.</span>
                <?php if ($fieldHasError('license_number')): ?>
                    <span class="field-error" id="license_number_error"><?php echo $escape($fieldError('license_number')); ?></span>
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
    var form = document.querySelector('.driver-profile-form');

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
