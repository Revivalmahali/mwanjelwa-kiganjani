<?php

declare(strict_types=1);

namespace App\Services;

use PDO;

class DriverDashboard
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findDriver(int $userId): ?array
    {
        if ($userId <= 0) {
            return null;
        }

        $stmt = $this->pdo->prepare(
            'SELECT u.id, u.full_name, u.phone, u.email, u.account_status, u.created_at,
                    dp.id AS driver_profile_id,
                    dp.license_number,
                    dp.residential_address,
                    dp.verification_status,
                    dp.created_at AS profile_created_at,
                    dp.updated_at AS profile_updated_at
             FROM users u
             INNER JOIN driver_profiles dp ON dp.user_id = u.id
             WHERE u.id = :user_id
             LIMIT 1'
        );
        $stmt->execute([':user_id' => $userId]);

        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }
}
