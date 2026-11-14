<?php

/**
 * Mwanjelwa Kiganjani - Forgot Password Form View
 *
 * This view is rendered by App\Controllers\AuthController.
 * All submitted values are escaped with htmlspecialchars() to protect
 * against XSS.
 */

/** @var string $email */
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
if (!isset($email)) {
    $email = '';
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

<form method="post" action="forgot_password.php" autocomplete="off">
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
        >
    </div>

    <button type="submit" class="submit-btn">Send Reset Link</button>
</form>

<p class="auth-links">
    <a class="link" href="login.php">Remembered your password?</a>
</p>
