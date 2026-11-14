<?php

/**
 * Mwanjelwa Kiganjani - Login Success View
 *
 * This view is rendered by App\Controllers\AuthController when a user
 * logs in successfully.
 */

/** @var string $full_name */
/** @var string $email */
/** @var string $phone */
?>
<section class="auth-result">
    <h2>Welcome back, <?php echo htmlspecialchars($full_name, ENT_QUOTES, 'UTF-8'); ?></h2>

    <p class="message">
        You are logged in as the account with email
        <?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>
        (or phone
        <?php echo htmlspecialchars($phone, ENT_QUOTES, 'UTF-8'); ?>).
    </p>

    <div class="landing-actions">
        <a class="submit-btn" href="register.php">Create another account</a>
        <a class="submit-btn" href="login.php">Log out</a>
    </div>

</section>
