<?php

declare(strict_types=1);

namespace App\Services;

use PDO;

/**
 * Mwanjelwa Kiganjani - Operations Overview Service
 *
 * Read-only queries that power the admin operations homepage. This service
 * never writes to the database and never invents data: every figure and list
 * comes directly from the existing tables.
 */
class OperationsOverview
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * Delivery totals grouped by the known workflow statuses.
     *
     * @return array{total: int, pending: int, in_transit: int, delivered: int, cancelled: int}
     */
    public function deliveryCounts(): array
    {
        $counts = [
            'total' => 0,
            'pending' => 0,
            'in_transit' => 0,
            'delivered' => 0,
            'cancelled' => 0,
        ];

        $rows = $this->pdo->query(
            'SELECT delivery_status, COUNT(*) AS total
             FROM deliveries
             GROUP BY delivery_status'
        )->fetchAll();

        foreach ($rows as $row) {
            $status = strtolower(trim((string) $row['delivery_status']));
            $total = (int) $row['total'];

            $counts['total'] += $total;

            if (array_key_exists($status, $counts) && $status !== 'total') {
                $counts[$status] += $total;
            }
        }

        return $counts;
    }

    /**
     * Deliveries that are still part of live operations.
     *
     * @return array<int, array<string, mixed>>
     */
    public function liveDeliveries(int $limit = 10): array
    {
        $limit = max(1, min($limit, 50));

        $stmt = $this->pdo->prepare(
             "SELECT d.id,
                    d.delivery_status,
                    d.pickup_address,
                    d.destination_address,
                    d.delivery_description,
                    d.created_at,
                    d.customer_phone,
                    COALESCE(NULLIF(TRIM(d.customer_name), ''), c.full_name) AS customer_name,
                    sp.business_name AS seller_name
             FROM deliveries d
             LEFT JOIN users c ON c.id = d.customer_id
             LEFT JOIN seller_profiles sp ON sp.id = d.seller_id
             WHERE d.delivery_status IN ('pending', 'in_transit')
             ORDER BY CASE d.delivery_status
                          WHEN 'pending' THEN 0
                          ELSE 1
                      END,
                      d.created_at ASC,
                      d.id ASC
             LIMIT :limit"
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Recent admin audit actions. Delivery lifecycle events are not logged
     * yet, so this panel can only show the records that actually exist.
     *
     * @return array<int, array<string, mixed>>
     */
    public function recentActivities(int $limit = 8): array
    {
        $limit = max(1, min($limit, 50));

        $stmt = $this->pdo->prepare(
            'SELECT aa.id,
                    aa.action_type,
                    aa.description,
                    aa.created_at,
                    u.full_name AS admin_name
             FROM admin_actions aa
             LEFT JOIN users u ON u.id = aa.admin_id
             ORDER BY aa.created_at DESC, aa.id DESC
             LIMIT :limit'
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Driver-related summary figures.
     *
     * The driver role count comes from user_roles. The profile figures come
     * from driver_profiles. They are reported separately because a user can
     * hold the driver role without having a driver profile yet.
     *
     * @return array{total_drivers: int, profiles: int, verified: int, pending: int}
     */
    public function driverSummary(): array
    {
        $summary = [
            'total_drivers' => 0,
            'profiles' => 0,
            'verified' => 0,
            'pending' => 0,
        ];

        $stmt = $this->pdo->query(
            "SELECT COUNT(*)
             FROM user_roles ur
             JOIN roles r ON r.id = ur.role_id
             WHERE r.name = 'driver'"
        );
        $summary['total_drivers'] = (int) $stmt->fetchColumn();

        $rows = $this->pdo->query(
            'SELECT verification_status, COUNT(*) AS total
             FROM driver_profiles
             GROUP BY verification_status'
        )->fetchAll();

        foreach ($rows as $row) {
            $status = strtolower(trim((string) $row['verification_status']));
            $total = (int) $row['total'];

            $summary['profiles'] += $total;

            if ($status === 'verified') {
                $summary['verified'] += $total;
            } elseif ($status === 'pending') {
                $summary['pending'] += $total;
            }
        }

        return $summary;
    }
}
