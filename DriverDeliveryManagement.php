<?php

declare(strict_types=1);

namespace App\Services;

use InvalidArgumentException;
use PDO;
use Throwable;

/**
 * Mwanjelwa Kiganjani - Driver Delivery Management Service
 *
 * Provides the driver-facing available, active, and completed delivery
 * queries plus the delivery acceptance and completion transactions. Until an
 * accepted_at migration is approved, assigned_at is used as the acceptance
 * timestamp and assigned_by stores the accepting driver's user ID.
 */
class DriverDeliveryManagement
{
    private const STATUS_PENDING = 'pending';
    private const STATUS_IN_TRANSIT = 'in_transit';
    private const STATUS_DELIVERED = 'delivered';
    private const ASSIGNMENT_ACCEPTED = 'accepted';
    private const ASSIGNMENT_COMPLETED = 'completed';
    private const AVAILABLE_LIMIT = 50;
    private const HISTORY_LIMIT = 50;

    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listAvailableDeliveries(int $limit = self::AVAILABLE_LIMIT): array
    {
        $limit = max(1, min($limit, 100));

        $stmt = $this->pdo->prepare(
            'SELECT d.id,
                    d.cargo_type,
                    d.pickup_address,
                    d.destination_address,
                    d.created_at
             FROM deliveries d
             LEFT JOIN delivery_assignments da
                    ON da.delivery_id = d.id
                   AND da.completed_at IS NULL
                   AND da.assignment_status IN (\'assigned\', \'accepted\', \'in_transit\')
             WHERE d.delivery_status = :pending_status
               AND da.id IS NULL
             ORDER BY d.created_at ASC, d.id ASC
             LIMIT :limit'
        );
        $stmt->bindValue(':pending_status', self::STATUS_PENDING, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function countAvailableDeliveries(): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*)
             FROM deliveries d
             LEFT JOIN delivery_assignments da
                    ON da.delivery_id = d.id
                   AND da.completed_at IS NULL
                   AND da.assignment_status IN (\'assigned\', \'accepted\', \'in_transit\')
             WHERE d.delivery_status = :pending_status
               AND da.id IS NULL'
        );
        $stmt->bindValue(':pending_status', self::STATUS_PENDING, PDO::PARAM_STR);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findActiveDelivery(int $driverProfileId): ?array
    {
        if ($driverProfileId <= 0) {
            return null;
        }

        $stmt = $this->pdo->prepare(
            'SELECT d.id,
                    d.delivery_status,
                    d.customer_name,
                    d.customer_phone,
                    d.cargo_type,
                    d.cargo_value,
                    d.pickup_address,
                    d.pickup_description,
                    d.destination_address,
                    d.destination_description,
                    d.delivery_description,
                    d.created_at,
                    d.updated_at,
                    da.id AS assignment_id,
                    da.assignment_status,
                    da.assigned_at,
                    da.completed_at
             FROM delivery_assignments da
             JOIN deliveries d ON d.id = da.delivery_id
             WHERE da.driver_id = :driver_profile_id
               AND da.completed_at IS NULL
               AND da.assignment_status IN (\'accepted\', \'in_transit\')
               AND d.delivery_status = :in_transit_status
             ORDER BY da.assigned_at DESC, da.id DESC
             LIMIT 1'
        );
        $stmt->bindValue(':driver_profile_id', $driverProfileId, PDO::PARAM_INT);
        $stmt->bindValue(':in_transit_status', self::STATUS_IN_TRANSIT, PDO::PARAM_STR);
        $stmt->execute();

        $delivery = $stmt->fetch();

        return $delivery === false ? null : $delivery;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listCompletedDeliveries(int $driverProfileId, int $limit = self::HISTORY_LIMIT): array
    {
        if ($driverProfileId <= 0) {
            return [];
        }

        $limit = max(1, min($limit, 100));

        $stmt = $this->pdo->prepare(
            'SELECT d.id,
                    d.delivery_status,
                    d.cargo_type,
                    d.cargo_value,
                    d.pickup_address,
                    d.destination_address,
                    d.created_at,
                    d.updated_at,
                    da.id AS assignment_id,
                    da.assignment_status,
                    da.assigned_at,
                    da.completed_at
             FROM delivery_assignments da
             JOIN deliveries d ON d.id = da.delivery_id
             WHERE da.driver_id = :driver_profile_id
               AND da.assignment_status = :completed_status
               AND da.completed_at IS NOT NULL
               AND d.delivery_status = :delivered_status
             ORDER BY da.completed_at DESC, da.id DESC
             LIMIT :limit'
        );
        $stmt->bindValue(':driver_profile_id', $driverProfileId, PDO::PARAM_INT);
        $stmt->bindValue(':completed_status', self::ASSIGNMENT_COMPLETED, PDO::PARAM_STR);
        $stmt->bindValue(':delivered_status', self::STATUS_DELIVERED, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * @return array{
     *     outcome: string,
     *     delivery_id: int,
     *     driver_profile_id: int,
     *     assignment_id: int
     * }
     */
    public function completeDelivery(int $deliveryId, int $driverUserId): array
    {
        if ($deliveryId <= 0 || $driverUserId <= 0) {
            throw new InvalidArgumentException('A valid delivery and driver are required.');
        }

        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare(
                'SELECT id, delivery_status
                 FROM deliveries
                 WHERE id = :id
                 FOR UPDATE'
            );
            $stmt->bindValue(':id', $deliveryId, PDO::PARAM_INT);
            $stmt->execute();
            $delivery = $stmt->fetch();

            if ($delivery === false) {
                $this->pdo->rollBack();

                return $this->result('not_found', $deliveryId);
            }

            if (strtolower(trim((string) $delivery['delivery_status'])) !== self::STATUS_IN_TRANSIT) {
                $this->pdo->rollBack();

                return $this->result('not_in_transit', $deliveryId);
            }

            $stmt = $this->pdo->prepare(
                'SELECT id, account_status
                 FROM users
                 WHERE id = :id
                 FOR UPDATE'
            );
            $stmt->bindValue(':id', $driverUserId, PDO::PARAM_INT);
            $stmt->execute();
            $user = $stmt->fetch();

            if ($user === false || strtolower(trim((string) $user['account_status'])) !== 'active') {
                $this->pdo->rollBack();

                return $this->result('not_eligible', $deliveryId);
            }

            $stmt = $this->pdo->prepare(
                'SELECT 1
                 FROM user_roles ur
                 JOIN roles r ON r.id = ur.role_id
                 WHERE ur.user_id = :id AND r.name = :driver_role
                 LIMIT 1'
            );
            $stmt->bindValue(':id', $driverUserId, PDO::PARAM_INT);
            $stmt->bindValue(':driver_role', 'driver', PDO::PARAM_STR);
            $stmt->execute();

            if ($stmt->fetchColumn() === false) {
                $this->pdo->rollBack();

                return $this->result('not_eligible', $deliveryId);
            }

            $stmt = $this->pdo->prepare(
                'SELECT id, verification_status
                 FROM driver_profiles
                 WHERE user_id = :user_id
                 FOR UPDATE'
            );
            $stmt->bindValue(':user_id', $driverUserId, PDO::PARAM_INT);
            $stmt->execute();
            $profile = $stmt->fetch();

            if (
                $profile === false
                || strtolower(trim((string) $profile['verification_status'])) !== 'verified'
            ) {
                $this->pdo->rollBack();

                return $this->result('not_eligible', $deliveryId);
            }

            $driverProfileId = (int) $profile['id'];

            $stmt = $this->pdo->prepare(
                'SELECT id, assignment_status
                 FROM delivery_assignments
                 WHERE delivery_id = :delivery_id
                   AND driver_id = :driver_id
                   AND completed_at IS NULL
                   AND assignment_status IN (\'accepted\', \'in_transit\')
                 ORDER BY assigned_at DESC, id DESC
                 LIMIT 1
                 FOR UPDATE'
            );
            $stmt->bindValue(':delivery_id', $deliveryId, PDO::PARAM_INT);
            $stmt->bindValue(':driver_id', $driverProfileId, PDO::PARAM_INT);
            $stmt->execute();
            $assignment = $stmt->fetch();

            if ($assignment === false) {
                $stmt = $this->pdo->prepare(
                    'SELECT id
                     FROM delivery_assignments
                     WHERE delivery_id = :delivery_id
                       AND driver_id = :driver_id
                       AND completed_at IS NOT NULL
                     LIMIT 1'
                );
                $stmt->bindValue(':delivery_id', $deliveryId, PDO::PARAM_INT);
                $stmt->bindValue(':driver_id', $driverProfileId, PDO::PARAM_INT);
                $stmt->execute();
                $completedAssignmentExists = $stmt->fetchColumn() !== false;

                $this->pdo->rollBack();

                if ($completedAssignmentExists) {
                    return $this->result('already_completed', $deliveryId, $driverProfileId);
                }

                return $this->result('not_assigned', $deliveryId, $driverProfileId);
            }

            $assignmentId = (int) $assignment['id'];

            $stmt = $this->pdo->prepare(
                'UPDATE deliveries
                 SET delivery_status = :delivered_status
                 WHERE id = :id
                   AND delivery_status = :in_transit_status'
            );
            $stmt->bindValue(':delivered_status', self::STATUS_DELIVERED, PDO::PARAM_STR);
            $stmt->bindValue(':id', $deliveryId, PDO::PARAM_INT);
            $stmt->bindValue(':in_transit_status', self::STATUS_IN_TRANSIT, PDO::PARAM_STR);
            $stmt->execute();

            if ($stmt->rowCount() === 0) {
                $this->pdo->rollBack();

                return $this->result('not_in_transit', $deliveryId, $driverProfileId, $assignmentId);
            }

            $stmt = $this->pdo->prepare(
                'UPDATE delivery_assignments
                 SET assignment_status = :completed_status,
                     completed_at = CURRENT_TIMESTAMP
                 WHERE id = :assignment_id
                   AND completed_at IS NULL
                   AND assignment_status IN (\'accepted\', \'in_transit\')'
            );
            $stmt->bindValue(':completed_status', self::ASSIGNMENT_COMPLETED, PDO::PARAM_STR);
            $stmt->bindValue(':assignment_id', $assignmentId, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() === 0) {
                $this->pdo->rollBack();

                return $this->result('already_completed', $deliveryId, $driverProfileId, $assignmentId);
            }

            $this->pdo->commit();

            return $this->result('completed', $deliveryId, $driverProfileId, $assignmentId);
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }

    /**
     * @return array{
     *     outcome: string,
     *     delivery_id: int,
     *     driver_profile_id: int,
     *     assignment_id: int
     * }
     */
    public function acceptDelivery(int $deliveryId, int $driverUserId): array
    {
        if ($deliveryId <= 0 || $driverUserId <= 0) {
            throw new InvalidArgumentException('A valid delivery and driver are required.');
        }

        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare(
                'SELECT id, delivery_status
                 FROM deliveries
                 WHERE id = :id
                 FOR UPDATE'
            );
            $stmt->bindValue(':id', $deliveryId, PDO::PARAM_INT);
            $stmt->execute();
            $delivery = $stmt->fetch();

            if ($delivery === false) {
                $this->pdo->rollBack();

                return $this->result('not_found', $deliveryId);
            }

            if (strtolower(trim((string) $delivery['delivery_status'])) !== self::STATUS_PENDING) {
                $this->pdo->rollBack();

                return $this->result('not_available', $deliveryId);
            }

            $stmt = $this->pdo->prepare(
                'SELECT id, account_status
                 FROM users
                 WHERE id = :id
                 FOR UPDATE'
            );
            $stmt->bindValue(':id', $driverUserId, PDO::PARAM_INT);
            $stmt->execute();
            $user = $stmt->fetch();

            if ($user === false || strtolower(trim((string) $user['account_status'])) !== 'active') {
                $this->pdo->rollBack();

                return $this->result('not_eligible', $deliveryId);
            }

            $stmt = $this->pdo->prepare(
                'SELECT 1
                 FROM user_roles ur
                 JOIN roles r ON r.id = ur.role_id
                 WHERE ur.user_id = :id AND r.name = :driver_role
                 LIMIT 1'
            );
            $stmt->bindValue(':id', $driverUserId, PDO::PARAM_INT);
            $stmt->bindValue(':driver_role', 'driver', PDO::PARAM_STR);
            $stmt->execute();

            if ($stmt->fetchColumn() === false) {
                $this->pdo->rollBack();

                return $this->result('not_eligible', $deliveryId);
            }

            $stmt = $this->pdo->prepare(
                'SELECT id, verification_status
                 FROM driver_profiles
                 WHERE user_id = :user_id
                 FOR UPDATE'
            );
            $stmt->bindValue(':user_id', $driverUserId, PDO::PARAM_INT);
            $stmt->execute();
            $profile = $stmt->fetch();

            if (
                $profile === false
                || strtolower(trim((string) $profile['verification_status'])) !== 'verified'
            ) {
                $this->pdo->rollBack();

                return $this->result('not_eligible', $deliveryId);
            }

            $driverProfileId = (int) $profile['id'];

            $stmt = $this->pdo->prepare(
                'SELECT id
                 FROM delivery_assignments
                 WHERE driver_id = :driver_id
                   AND completed_at IS NULL
                   AND assignment_status IN (\'assigned\', \'accepted\', \'in_transit\')
                 LIMIT 1
                 FOR UPDATE'
            );
            $stmt->bindValue(':driver_id', $driverProfileId, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->fetchColumn() !== false) {
                $this->pdo->rollBack();

                return $this->result('already_active', $deliveryId, $driverProfileId);
            }

            $stmt = $this->pdo->prepare(
                'SELECT id
                 FROM delivery_assignments
                 WHERE delivery_id = :delivery_id
                   AND completed_at IS NULL
                   AND assignment_status IN (\'assigned\', \'accepted\', \'in_transit\')
                 LIMIT 1
                 FOR UPDATE'
            );
            $stmt->bindValue(':delivery_id', $deliveryId, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->fetchColumn() !== false) {
                $this->pdo->rollBack();

                return $this->result('not_available', $deliveryId, $driverProfileId);
            }

            $stmt = $this->pdo->prepare(
                'UPDATE deliveries
                 SET delivery_status = :in_transit_status
                 WHERE id = :id
                   AND delivery_status = :pending_status'
            );
            $stmt->bindValue(':in_transit_status', self::STATUS_IN_TRANSIT, PDO::PARAM_STR);
            $stmt->bindValue(':id', $deliveryId, PDO::PARAM_INT);
            $stmt->bindValue(':pending_status', self::STATUS_PENDING, PDO::PARAM_STR);
            $stmt->execute();

            if ($stmt->rowCount() === 0) {
                $this->pdo->rollBack();

                return $this->result('not_available', $deliveryId, $driverProfileId);
            }

            $stmt = $this->pdo->prepare(
                'INSERT INTO delivery_assignments (
                    delivery_id,
                    driver_id,
                    assigned_by,
                    assignment_status,
                    assigned_at
                 ) VALUES (
                    :delivery_id,
                    :driver_id,
                    :assigned_by,
                    :assignment_status,
                    CURRENT_TIMESTAMP
                 )'
            );
            $stmt->bindValue(':delivery_id', $deliveryId, PDO::PARAM_INT);
            $stmt->bindValue(':driver_id', $driverProfileId, PDO::PARAM_INT);
            $stmt->bindValue(':assigned_by', $driverUserId, PDO::PARAM_INT);
            $stmt->bindValue(':assignment_status', self::ASSIGNMENT_ACCEPTED, PDO::PARAM_STR);
            $stmt->execute();

            $assignmentId = (int) $this->pdo->lastInsertId();

            $this->pdo->commit();

            return $this->result('accepted', $deliveryId, $driverProfileId, $assignmentId);
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }

    /**
     * @return array{
     *     outcome: string,
     *     delivery_id: int,
     *     driver_profile_id: int,
     *     assignment_id: int
     * }
     */
    private function result(
        string $outcome,
        int $deliveryId,
        int $driverProfileId = 0,
        int $assignmentId = 0
    ): array {
        return [
            'outcome' => $outcome,
            'delivery_id' => $deliveryId,
            'driver_profile_id' => $driverProfileId,
            'assignment_id' => $assignmentId,
        ];
    }
}
