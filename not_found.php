<?php

/**
 * Mwanjelwa Kiganjani - Not Found Page View
 *
 * This view is rendered by App\Controllers\AuthController::notFound().
 */

?>
<section class="auth-result">
    <p class="auth-kicker">404</p>

    <p class="message">
        The page you are looking for could not be found.
    </p>

    <a class="submit-btn" href="<?php echo htmlspecialchars(app_url('index.php'), ENT_QUOTES, 'UTF-8'); ?>">Go back home</a>
</section>
