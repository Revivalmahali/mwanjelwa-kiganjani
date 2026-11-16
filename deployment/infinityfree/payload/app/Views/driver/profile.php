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
$address = trim((string) ($driver['residential_address'] ?? ''));
$licenseNumber = trim((string) ($driver['license_number'] ?? ''));

$statusLabel = static function (string $status): string {
    return $status === '' ? 'Unknown' : ucfirst(str_replace('_', ' ', $status));
};
?>
<section class="page-heading">
    <div>
        <p class="breadcrumb">
            <span>Driver Portal</span>
            <span aria-hidden="true">/</span>
            <span>My Profile</span>
        </p>
        <h1>My Profile</h1>
        <p class="page-intro">Review your driver account and profile details.</p>
    </div>
</section>

<section class="panel">
    <div class="panel-heading">
        <h2>Driver Information</h2>
        <span class="panel-meta">Read only</span>
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
        <div class="detail-row">
            <dt>Email</dt>
            <dd><?php echo $email !== '' ? $escape($email) : '<span class="muted">Not provided</span>'; ?></dd>
        </div>
        <div class="detail-row">
            <dt>Residential address</dt>
            <dd><?php echo $address !== '' ? nl2br($escape($address)) : '<span class="muted">Not provided</span>'; ?></dd>
        </div>
        <?php if ($licenseNumber !== ''): ?>
            <div class="detail-row">
                <dt>License number</dt>
                <dd><?php echo $escape($licenseNumber); ?></dd>
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
        <div class="detail-row">
            <dt>Profile created</dt>
            <dd><?php echo $escape($formatDate($driver['profile_created_at'] ?? '')); ?></dd>
        </div>
    </dl>

    <p class="form-note">Profile editing is not available in this phase. Contact an administrator if your details need to be corrected.</p>
</section>
