<?php

/**
 * Mwanjelwa Kiganjani - Home / Landing Page View
 *
 * This view is rendered by App\Controllers\AuthController::home().
 * It is the entry page of the application before a user is authenticated.
 */

?>
<section class="landing-content" aria-label="Delivery service introduction">
    <p class="landing-copy">
        A dependable local delivery network connecting people, businesses, and drivers across Mbeya.
    </p>

    <ul class="landing-points">
        <li>Thoughtful pickup and handover</li>
        <li>Local drivers you can count on</li>
        <li>Clear delivery progress</li>
    </ul>

    <div class="landing-actions">
        <a class="submit-btn" href="<?php echo htmlspecialchars(app_url('register.php'), ENT_QUOTES, 'UTF-8'); ?>">Create an account</a>
        <a class="submit-btn btn--outline" href="<?php echo htmlspecialchars(app_url('login.php'), ENT_QUOTES, 'UTF-8'); ?>">Log in</a>
    </div>

    <p class="landing-secondary-action">
        Want to drive with us? <a class="link" href="<?php echo htmlspecialchars(app_url('driver/register.php'), ENT_QUOTES, 'UTF-8'); ?>">Register as a driver</a>
    </p>
</section>
