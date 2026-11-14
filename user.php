<?php

/**
 * Mwanjelwa Kiganjani - Admin User Details View
 *
 * Expects: $target (user with a "roles" array)
 */

$hasDriver = in_array('driver', $target['roles'], true);
$hasAdmin = in_array('admin', $target['roles'], true);
$isActive = $target['account_status'] === 'active';
$formAction = app_url('admin/user.php?id=' . (int) $target['id']);
?>
<section class="page-heading">
    <div>
        <p class="breadcrumb">
            <a class="text-link" href="<?php echo htmlspecialchars(app_url('admin/users.php'), ENT_QUOTES, 'UTF-8'); ?>">Users</a>
            <span aria-hidden="true">/</span>
            <span><?php echo htmlspecialchars((string) $target['full_name'], ENT_QUOTES, 'UTF-8'); ?></span>
        </p>
        <h1><?php echo htmlspecialchars((string) $target['full_name'], ENT_QUOTES, 'UTF-8'); ?></h1>
        <p class="page-intro">User #<?php echo (int) $target['id']; ?></p>
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
                <dd><?php echo htmlspecialchars((string) $target['full_name'], ENT_QUOTES, 'UTF-8'); ?></dd>
            </div>
            <div class="detail-row">
                <dt>Phone</dt>
                <dd><?php echo htmlspecialchars((string) $target['phone'], ENT_QUOTES, 'UTF-8'); ?></dd>
            </div>
            <div class="detail-row">
                <dt>Email</dt>
                <dd>
                    <?php echo ($target['email'] ?? '') !== ''
                        ? htmlspecialchars((string) $target['email'], ENT_QUOTES, 'UTF-8')
                        : '<span class="muted">Not provided</span>'; ?>
                </dd>
            </div>
            <div class="detail-row">
                <dt>Status</dt>
                <dd>
                    <span class="badge <?php echo $isActive ? 'badge--active' : 'badge--inactive'; ?>">
                        <?php echo htmlspecialchars((string) $target['account_status'], ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                </dd>
            </div>
            <div class="detail-row">
                <dt>Registered</dt>
                <dd><?php echo htmlspecialchars(date('d M Y', strtotime((string) $target['created_at'])), ENT_QUOTES, 'UTF-8'); ?></dd>
            </div>
            <div class="detail-row">
                <dt>Assigned roles</dt>
                <dd>
                    <?php if ($target['roles'] === []): ?>
                        <span class="muted">No roles</span>
                    <?php else: ?>
                        <span class="role-badges">
                            <?php foreach ($target['roles'] as $role): ?>
                                <span class="badge badge--role"><?php echo htmlspecialchars((string) $role, ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php endforeach; ?>
                        </span>
                    <?php endif; ?>
                </dd>
            </div>
        </dl>
    </section>

    <section class="panel">
        <div class="panel-heading">
            <h2>Role management</h2>
        </div>

        <p class="form-note">
            Roles are enforced on the server. A user must log out and log in again
            after a role is assigned before the new role takes effect.
        </p>

        <?php if (!$hasDriver): ?>
            <form class="role-form" method="post" action="<?php echo htmlspecialchars($formAction, ENT_QUOTES, 'UTF-8'); ?>">
                <?php echo \App\Services\Csrf::field(); ?>
                <input type="hidden" name="action" value="assign_role">
                <input type="hidden" name="role" value="driver">

                <h3>Assign Driver role</h3>
                <p class="form-note">
                    This gives the user access to driver-only pages. Assigning this role
                    does not create a driver profile; review missing profiles on the
                    Drivers page.
                </p>
                <button type="submit" class="btn btn--primary">Assign Driver role</button>
            </form>
        <?php endif; ?>

        <?php if (!$hasAdmin): ?>
            <form class="role-form" method="post" action="<?php echo htmlspecialchars($formAction, ENT_QUOTES, 'UTF-8'); ?>">
                <?php echo \App\Services\Csrf::field(); ?>
                <input type="hidden" name="action" value="assign_role">
                <input type="hidden" name="role" value="admin">

                <h3>Assign Admin role</h3>
                <div class="warning-box">
                    <strong>High-risk action.</strong>
                    Admins can view all users and assign roles. Only grant this to a
                    trusted account.
                </div>
                <label class="checkbox-row">
                    <input type="checkbox" name="confirm_admin" value="1" required>
                    <span>I understand this grants full Admin access.</span>
                </label>
                <button type="submit" class="btn btn--danger">Assign Admin role</button>
            </form>
        <?php endif; ?>

        <?php if ($hasDriver && $hasAdmin): ?>
            <p class="empty-state">No further roles are available to assign in this phase.</p>
        <?php endif; ?>

        <div class="info-box">
            <strong>Seller role:</strong> postponed to a later phase.
        </div>

        <p class="form-note">
            User suspension, deletion, password changes, and role removal are not
            available in this phase.
        </p>
    </section>
</div>
