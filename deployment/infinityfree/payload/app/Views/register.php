<?php
/**
 * Mwanjelwa Kiganjani - Registration Form View
 *
 * This view is rendered by App\Controllers\AuthController.
 * All submitted values are escaped with htmlspecialchars() to protect
 * against XSS.
 */

/** @var string $full_name */
/** @var string $phone */
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
if (!isset($full_name)) {
    $full_name = '';
}
if (!isset($phone)) {
    $phone = '';
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

<form method="post" action="register.php" autocomplete="off">
    <div class="form-group">
        <label for="full_name">Full Name</label>
        <input
            type="text"
            id="full_name"
            name="full_name"
            value="<?php echo htmlspecialchars($full_name ?? '', ENT_QUOTES, 'UTF-8'); ?>"
            placeholder="Enter your full name"
            required
            autofocus
        >
    </div>

    <div class="form-group">
        <label for="phone">Phone Number</label>
        <input
            type="text"
            id="phone"
            name="phone"
            value="<?php echo htmlspecialchars($phone ?? '', ENT_QUOTES, 'UTF-8'); ?>"
            placeholder="e.g. 255712345678"
            required
        >
    </div>

    <div class="form-group">
        <label for="email">Email Address (optional)</label>
        <input
            type="email"
            id="email"
            name="email"
            value="<?php echo htmlspecialchars($email ?? '', ENT_QUOTES, 'UTF-8'); ?>"
            placeholder="you@example.com"
        >
    </div>

    <div class="form-group">
        <label for="password">Password</label>
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
        <label for="confirm_password">Confirm Password</label>
        <input
            type="password"
            id="confirm_password"
            name="confirm_password"
            value="<?php echo htmlspecialchars($confirm_password ?? '', ENT_QUOTES, 'UTF-8'); ?>"
            placeholder="Re-enter your password"
            required
        >
    </div>

    <button type="submit" class="submit-btn">Create Account</button>
</form>

<p class="auth-links">
    Already have an account? <a class="link" href="login.php">Log in</a>
</p>

<p class="auth-links">
    Want to drive with us? <a class="link" href="driver/register.php">Register as a driver</a>
</p>
