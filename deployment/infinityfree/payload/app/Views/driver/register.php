<?php

/**
 * Mwanjelwa Kiganjani - Driver Registration View
 *
 * Rendered by App\Controllers\DriverAuthController.
 * All submitted values are escaped with htmlspecialchars().
 */

/** @var array<string, string> $values */
/** @var array<string, string> $errors */
/** @var string $success */

$values = is_array($values ?? null) ? $values : [];
$errors = is_array($errors ?? null) ? $errors : [];
$success = isset($success) && is_string($success) ? $success : '';

$value = static function (string $key) use ($values): string {
    return htmlspecialchars((string) ($values[$key] ?? ''), ENT_QUOTES, 'UTF-8');
};
?>
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

<?php if ($success !== ''): ?>
    <div class="message" role="status">
        <?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?>
    </div>

    <p class="form-links">
        <a class="link" href="<?php echo htmlspecialchars(app_url('login.php'), ENT_QUOTES, 'UTF-8'); ?>">Go to login</a>
    </p>
<?php else: ?>
    <p class="form-note">
        Register as a driver. Your account will remain pending until an administrator approves it.
    </p>

    <form method="post" action="<?php echo htmlspecialchars(app_url('driver/register.php'), ENT_QUOTES, 'UTF-8'); ?>" autocomplete="off">
        <?php echo \App\Services\Csrf::field(); ?>

        <div class="form-group">
            <label for="first_name">First Name</label>
            <input
                type="text"
                id="first_name"
                name="first_name"
                value="<?php echo $value('first_name'); ?>"
                maxlength="50"
                required
                autofocus
            >
        </div>

        <div class="form-group">
            <label for="middle_name">Middle Name</label>
            <input
                type="text"
                id="middle_name"
                name="middle_name"
                value="<?php echo $value('middle_name'); ?>"
                maxlength="50"
                required
            >
        </div>

        <div class="form-group">
            <label for="last_name">Last Name</label>
            <input
                type="text"
                id="last_name"
                name="last_name"
                value="<?php echo $value('last_name'); ?>"
                maxlength="50"
                required
            >
        </div>

        <div class="form-group">
            <label for="phone">Phone Number</label>
            <input
                type="tel"
                id="phone"
                name="phone"
                value="<?php echo $value('phone'); ?>"
                placeholder="e.g. 255712345678"
                maxlength="20"
                required
            >
        </div>

        <div class="form-group">
            <label for="email">Email Address (optional)</label>
            <input
                type="email"
                id="email"
                name="email"
                value="<?php echo $value('email'); ?>"
                placeholder="you@example.com"
                maxlength="255"
            >
        </div>

        <div class="form-group">
            <label for="residential_address">Residential Address</label>
            <textarea
                id="residential_address"
                name="residential_address"
                maxlength="500"
                placeholder="Enter your residential address"
                required
            ><?php echo $value('residential_address'); ?></textarea>
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input
                type="password"
                id="password"
                name="password"
                placeholder="Minimum 8 characters"
                required
            >
        </div>

        <div class="form-group">
            <label for="confirm_password">Confirm Password</label>
            <input
                type="password"
                id="confirm_password"
                name="confirm_password"
                placeholder="Re-enter your password"
                required
            >
        </div>

        <button type="submit" class="submit-btn">Create Driver Account</button>
    </form>

    <div class="form-links">
        <p>
            Already have an account?
            <a class="link" href="<?php echo htmlspecialchars(app_url('login.php'), ENT_QUOTES, 'UTF-8'); ?>">Log in</a>
        </p>
        <p>
            Need a customer account?
            <a class="link" href="<?php echo htmlspecialchars(app_url('register.php'), ENT_QUOTES, 'UTF-8'); ?>">Register as a customer</a>
        </p>
    </div>
<?php endif; ?>
