<?php

/**
 * Mwanjelwa Kiganjani - Login Form View
 *
 * This view is rendered by App\Controllers\AuthController.
 * All submitted values are escaped with htmlspecialchars() to protect
 * against XSS.
 */

/** @var string $email */
/** @var string $password */
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
if (!isset($password)) {
    $password = '';
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

<form method="post" action="login.php" autocomplete="off">
    <div class="form-group">
        <label for="email">Email or Phone</label>
        <input
            type="text"
            id="email"
            name="email"
            value="<?php echo htmlspecialchars($email ?? '', ENT_QUOTES, 'UTF-8'); ?>"
            placeholder="you@example.com or your phone number"
            required
            autofocus
        >
    </div>

    <div class="form-group">
        <label for="password">Password</label>
        <input
            type="password"
            id="password"
            name="password"
            value="<?php echo htmlspecialchars($password ?? '', ENT_QUOTES, 'UTF-8'); ?>"
            placeholder="Enter your password"
            required
        >
    </div>

    <button type="submit" class="submit-btn">Log In</button>
</form>

<p class="auth-links auth-links--row">
    <a class="link" href="forgot_password.php">Forgot your password?</a>
</p>

<p class="auth-links">
    Don't have an account? <a class="link" href="register.php">Create one</a>
</p>

<p class="auth-links">
    Want to drive with us? <a class="link" href="driver/register.php">Register as a driver</a>
</p>
