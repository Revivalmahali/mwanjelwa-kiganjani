<?php

/**
 * Mwanjelwa Kiganjani - Admin Driver Details View
 *
 * Expects: $driver (driver record with roles and driver_status)
 */

use App\Services\Csrf;
use App\Services\DriverAdminManagement;

$driver = $driver ?? [];
$escape = static function (mixed $value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

$formatDate = static function (mixed $value): string {
    $timestamp = strtotime((string) $value);

    return $timestamp === false ? 'Unknown' : date('d M Y H:i', $timestamp);
};

$driverStatus = (string) ($driver['driver_status'] ?? 'inconsistent');
$driverStatusLabel = DriverAdminManagement::statusLabel($driverStatus);
$driverStatusClass = DriverAdminManagement::statusClass($driverStatus);
$profileMissing = ($driver['driver_profile_id'] ?? null) === null;
$profileIssues = DriverAdminManagement::profileCompletenessIssues($driver);
$accountStatus = strtolower(trim((string) ($driver['account_status'] ?? '')));
$verificationStatus = strtolower(trim((string) ($driver['verification_status'] ?? '')));
$hasActiveDelivery = (int) ($driver['has_active_delivery'] ?? 0) === 1;
$roles = is_array($driver['roles'] ?? null) ? $driver['roles'] : [];
$detailUrl = app_url('admin/driver.php?id=' . (int) $driver['id']);
$editProfileUrl = app_url('admin/edit-driver.php?id=' . (int) $driver['id']);
$driversUrl = app_url('admin/drivers.php');
?>
<section class="page-heading">
    <div>
        <p class="breadcrumb">
            <a class="text-link" href="<?php echo $escape($driversUrl); ?>">Drivers</a>
            <span aria-hidden="true">/</span>
            <span><?php echo $escape((string) $driver['full_name']); ?></span>
        </p>
        <h1><?php echo $escape((string) $driver['full_name']); ?></h1>
        <p class="page-intro">
            Driver #<?php echo (int) $driver['id']; ?>
            <span class="badge <?php echo $escape($driverStatusClass); ?>"><?php echo $escape($driverStatusLabel); ?></span>
        </p>
    </div>
</section>

<div class="content-grid">
    <section class="panel">
        <div class="panel-heading">
            <h2>Account details</h2>
        </div>

        <dl class="detail-list">
            <div class="detail-row">
                <dt>Full name</dt>
                <dd><?php echo $escape((string) $driver['full_name']); ?></dd>
            </div>
            <div class="detail-row">
                <dt>Phone</dt>
                <dd><?php echo $escape((string) $driver['phone']); ?></dd>
            </div>
            <div class="detail-row">
                <dt>Email</dt>
                <dd>
                    <?php echo ($driver['email'] ?? '') !== ''
                        ? $escape((string) $driver['email'])
                        : '<span class="muted">Not provided</span>'; ?>
                </dd>
            </div>
            <div class="detail-row">
                <dt>Account status</dt>
                <dd>
                    <span class="badge <?php echo $accountStatus === 'active' ? 'badge--active' : 'badge--inactive'; ?>">
                        <?php echo $escape($accountStatus !== '' ? $accountStatus : 'unknown'); ?>
                    </span>
                </dd>
            </div>
            <div class="detail-row">
                <dt>Driver status</dt>
                <dd>
                    <span class="badge <?php echo $escape($driverStatusClass); ?>"><?php echo $escape($driverStatusLabel); ?></span>
                </dd>
            </div>
            <div class="detail-row">
                <dt>Registered</dt>
                <dd><?php echo $escape($formatDate($driver['created_at'])); ?></dd>
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
        </dl>
    </section>

    <section class="panel">
        <div class="panel-heading">
            <h2>Driver profile</h2>
        </div>

        <?php if ($profileMissing): ?>
            <div class="empty-state">
                <strong>No driver profile exists for this user.</strong>
                <p>
                    The user has the Driver role, but no driver profile was created.
                    No profile was created automatically. Activation and deactivation
                    are unavailable until a profile exists.
                </p>
            </div>
        <?php else: ?>
            <dl class="detail-list">
                <div class="detail-row">
                    <dt>Profile ID</dt>
                    <dd>#<?php echo (int) $driver['driver_profile_id']; ?></dd>
                </div>
                <div class="detail-row">
                    <dt>Residential address</dt>
                    <dd>
                        <?php echo trim((string) ($driver['residential_address'] ?? '')) !== ''
                            ? nl2br($escape((string) $driver['residential_address']))
                            : '<span class="muted">Not provided</span>'; ?>
                    </dd>
                </div>
                <div class="detail-row">
                    <dt>Licence number</dt>
                    <dd>
                        <?php echo trim((string) ($driver['license_number'] ?? '')) !== ''
                            ? $escape((string) $driver['license_number'])
                            : '<span class="muted">Not provided</span>'; ?>
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
                    <dt>Profile created</dt>
                    <dd><?php echo $escape($formatDate($driver['profile_created_at'])); ?></dd>
                </div>
                <div class="detail-row">
                    <dt>Profile updated</dt>
                    <dd><?php echo $escape($formatDate($driver['profile_updated_at'])); ?></dd>
                </div>
            </dl>
        <?php endif; ?>
    </section>
</div>

<?php if ($hasActiveDelivery): ?>
    <div class="warning-box" role="note">
        <strong>Active delivery in progress.</strong>
        This driver cannot be deactivated until the active delivery is completed or cancelled.
    </div>
<?php endif; ?>

<?php if (!$profileMissing && $profileIssues !== [] && $driverStatus !== 'active'): ?>
    <div class="warning-box" role="note">
        <strong>Driver profile incomplete.</strong>
        Approval or activation is unavailable until the following required information is provided:
        <?php echo $escape(implode(', ', $profileIssues)); ?>.
        Use Edit Profile to update the residential address or licence number.
    </div>
<?php endif; ?>

<div class="form-actions">
    <?php if (!$profileMissing): ?>
        <a class="btn btn--secondary" href="<?php echo $escape($editProfileUrl); ?>">Edit Profile</a>
    <?php endif; ?>

    <?php if (DriverAdminManagement::canApprove($driver)): ?>
        <form class="inline-action-form" method="post" action="<?php echo $escape($detailUrl); ?>">
            <?php echo Csrf::field(); ?>
            <input type="hidden" name="action" value="approve">
            <button type="submit" class="btn btn--primary">Approve Driver</button>
        </form>
    <?php elseif (DriverAdminManagement::canActivate($driver)): ?>
        <form class="inline-action-form" method="post" action="<?php echo $escape($detailUrl); ?>">
            <?php echo Csrf::field(); ?>
            <input type="hidden" name="action" value="activate">
            <button type="submit" class="btn btn--primary">Activate Driver</button>
        </form>
    <?php endif; ?>

    <?php if (DriverAdminManagement::canDeactivate($driver)): ?>
        <form class="inline-action-form" method="post" action="<?php echo $escape($detailUrl); ?>">
            <?php echo Csrf::field(); ?>
            <input type="hidden" name="action" value="deactivate">
            <button type="submit" class="btn btn--danger">Deactivate Driver</button>
        </form>
    <?php endif; ?>

    <a class="btn btn--secondary" href="<?php echo $escape($driversUrl); ?>">Back to Drivers</a>
</div>
