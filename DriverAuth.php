<?php

declare(strict_types=1);

namespace App\Services;

use Throwable;

require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/Database.php';

/**
 * Mwanjelwa Kiganjani - Driver Access Guard
 *
 * Protected driver pages should call requireActiveDriver(). The guard checks
 * the live database state so a driver whose account is deactivated or whose
 * profile is still pending cannot keep using a stale session.
 *
 * This guard is intentionally separate from Auth::requireRole('admin') and
 * from the customer dashboard so existing customer and admin behaviour is
 * not changed.
 */
class DriverAuth
{
    /**
     * @return array{
     *     allowed: bool,
     *     reason: string,
     *     profile_id: int|null,
     *     account_status: string,
     *     verification_status: string|null
     * }
     */
    public static function state(?int $userId = null): array
    {
        if ($userId === null) {
            if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
                session_start();
            }

            $userId = (int) ($_SESSION['user_id'] ?? 0);
        }

        $state = [
            'allowed' => false,
            'reason' => 'not_logged_in',
            'profile_id' => null,
            'account_status' => '',
            'verification_status' => null,
        ];

        if ($userId <= 0) {
            return $state;
        }

        try {
            $pdo = Database::connect();

            $stmt = $pdo->prepare(
                'SELECT u.id, u.account_status,
                        dp.id AS profile_id,
                        dp.verification_status,
                        EXISTS (
                            SELECT 1
                            FROM user_roles ur
                            JOIN roles r ON r.id = ur.role_id
                            WHERE ur.user_id = u.id AND r.name = :driver_role
                        ) AS has_driver_role
                 FROM users u
                 LEFT JOIN driver_profiles dp ON dp.user_id = u.id
                 WHERE u.id = :id
                 LIMIT 1'
            );
            $stmt->execute([
                ':driver_role' => 'driver',
                ':id' => $userId,
            ]);
            $row = $stmt->fetch();
        } catch (Throwable) {
            $state['reason'] = 'unavailable';

            return $state;
        }

        if ($row === false) {
            $state['reason'] = 'not_found';

            return $state;
        }

        $state['account_status'] = (string) $row['account_status'];
        $state['verification_status'] = $row['verification_status'] !== null
            ? (string) $row['verification_status']
            : null;
        $state['profile_id'] = $row['profile_id'] !== null
            ? (int) $row['profile_id']
            : null;

        if ((int) $row['has_driver_role'] !== 1) {
            $state['reason'] = 'missing_role';

            return $state;
        }

        if ($state['profile_id'] === null) {
            $state['reason'] = 'missing_profile';

            return $state;
        }

        if ($state['account_status'] !== 'active') {
            $state['reason'] = 'inactive_account';

            return $state;
        }

        if ($state['verification_status'] !== 'verified') {
            $state['reason'] = 'unverified_profile';

            return $state;
        }

        $state['allowed'] = true;
        $state['reason'] = 'allowed';

        return $state;
    }

    /**
     * Require a logged-in, active and verified driver.
     *
     * Guests are redirected to login. Logged-in users who are not eligible
     * receive a 403 response. Pending, inactive, rejected, unverified and
     * missing-profile drivers are all denied.
     */
    public static function requireActiveDriver(?string $intendedUrl = null): void
    {
        Auth::requireLogin($intendedUrl);

        $state = self::state();

        if ($state['allowed']) {
            return;
        }

        http_response_code(403);
        header('Content-Type: text/html; charset=UTF-8');

        $message = match ($state['reason']) {
            'missing_profile' => 'Your driver profile is not available. Please contact an administrator.',
            'inactive_account' => 'This driver account is not active.',
            'unverified_profile' => 'This driver profile has not been verified yet.',
            'missing_role' => 'You do not have permission to view this page.',
            'not_found' => 'The driver account could not be found.',
            'unavailable' => 'Driver access could not be verified. Please try again.',
            default => 'You do not have permission to view this page.',
        };

        echo '<!DOCTYPE html>' . "\n"
            . '<html lang="en">' . "\n"
            . '<head>' . "\n"
            . '    <meta charset="UTF-8">' . "\n"
            . '    <title>Access denied</title>' . "\n"
            . '</head>' . "\n"
            . '<body>' . "\n"
            . '    <h1>403 - Access Denied</h1>' . "\n"
            . '    <p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>' . "\n"
            . '</body>' . "\n"
            . '</html>';

        exit;
    }
}
