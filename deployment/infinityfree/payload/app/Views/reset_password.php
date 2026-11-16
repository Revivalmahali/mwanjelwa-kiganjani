<?php

/**
 * Mwanjelwa Kiganjani - Reset Password Form View
 *
 * This view is rendered by App\Controllers\AuthController::resetPassword().
 */

/** @var string $token */
/** @var string $email */
/** @var string $password */
/** @var string $confirm_password */
/** @var string[] $errors */
/** @var string $success */
?>

<?php
if (!isset($errors) || !is_array($errors)) {
    $errors = [];
}
if (!isset($success)) {
    $success = '';
}
if (!isset($token)) {
    $token = '';
}
if (!isset($email)) {
    $email = '';
}
if (!isset($password)) {
    $password = '';
}
if (!isset($confirm_password)) {
    $confirm_password = '';
}
?>

<?php if ($errors !== []): ?>
    <div class="error-list">
        <strong>Please correct the following errors:</strong>
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if ($success !== ''): ?>
    <div class="message">
        <?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?>
    </div>
<?php endif; ?>

<form method="post" action="reset_password.php" autocomplete="off">
    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token ?? '', ENT_QUOTES, 'UTF-8'); ?>">

    <div class="form-group">
        <label for="email">Email Address</label>
        <input
            type="email"
            id="email"
            name="email"
            value="<?php echo htmlspecialchars($email ?? '', ENT_QUOTES, 'UTF-8'); ?>"
            placeholder="you@example.com"
            required
            autofocus
            readonly
        >
    </div>

    <div class="form-group">
        <label for="password">New Password</label>
        <input
            type="password"
            id="password"
            name="password"
            value="<?php echo htmlspecialchars($password ?? '', ENT_QUOTES, 'UTF-8'); ?>"
            placeholder="Minimum 8 characters"
            required
        >
    </div>

    <div class="form-group">
        <label for="confirm_password">Confirm New Password</label>
        <input
            type="password"
            id="confirm_password"
            name="confirm_password"
            value="<?php echo htmlspecialchars($confirm_password ?? '', ENT_QUOTES, 'UTF-8'); ?>"
            placeholder="Re-enter your new password"
            required
        >
    </div>

    <button type="submit" class="submit-btn">Reset Password</button>
</form>

<p class="auth-links">
    <a class="link" href="login.php">Back to log in</a>
</p>
