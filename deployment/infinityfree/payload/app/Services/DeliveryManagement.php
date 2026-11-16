<?php

declare(strict_types=1);

namespace App\Services;

use InvalidArgumentException;
use PDO;
use Throwable;

/**
 * Mwanjelwa Kiganjani - Delivery Management Service
 *
 * Validates and creates manual deliveries entered by an authenticated Admin,
 * updates editable fields on pending deliveries, and provides lookups for the
 * Admin delivery pages. The service never accepts delivery_status or created_by
 * from user input.
 */
class DeliveryManagement
{
    private const STATUS_PENDING = 'pending';
    private const STATUS_CANCELLED = 'cancelled';
    private const ASSIGNMENT_CANCELLED = 'cancelled';
    private const ALLOWED_STATUSES = ['pending', 'in_transit', 'delivered', 'cancelled'];
    private const CANCELLABLE_STATUSES = ['pending', 'in_transit'];
    private const MAX_ADDRESS_LENGTH = 500;
    private const MAX_DESCRIPTION_LENGTH = 2000;
    private const MAX_CANCELLATION_REASON_LENGTH = 500;
    private const EDITABLE_FIELD_LABELS = [
        'customer_name' => 'customer name',
        'customer_phone' => 'customer phone',
        'cargo_type' => 'cargo type',
        'cargo_value' => 'cargo value',
        'pickup_address' => 'pickup address',
        'pickup_description' => 'pickup description',
        'destination_address' => 'destination address',
        'destination_description' => 'destination description',
    ];

    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * Validate and normalise the Create Delivery form input.
     *
     * @param array<string, mixed> $input
     * @return array{
     *     data: array<string, string|null>,
     *     errors: array<string, string>
     * }
     */
    public function validateManualDelivery(array $input): array
    {
        $errors = [];
        $data = [];

        $customerName = trim((string) ($input['customer_name'] ?? ''));
        if ($customerName === '') {
            $errors['customer_name'] = 'Customer name is required.';
        } elseif ($this->textLength($customerName) > 150) {
            $errors['customer_name'] = 'Customer name must be 150 characters or fewer.';
        }
        $data['customer_name'] = $customerName;

        $customerPhone = trim((string) ($input['customer_phone'] ?? ''));
        if ($customerPhone === '') {
            $errors['customer_phone'] = 'Customer phone is required.';
        } elseif ($this->textLength($customerPhone) > 20) {
            $errors['customer_phone'] = 'Customer phone must be 20 characters or fewer.';
        } elseif (!preg_match('/^\+?[0-9][0-9\s\-()]{5,19}$/', $customerPhone)) {
            $errors['customer_phone'] = 'Enter a valid phone number using digits, spaces, dashes, or brackets.';
        }
        $data['customer_phone'] = $customerPhone;

        $cargoType = trim((string) ($input['cargo_type'] ?? ''));
        if ($cargoType === '') {
            $errors['cargo_type'] = 'Cargo type is required.';
        } elseif ($this->textLength($cargoType) > 150) {
            $errors['cargo_type'] = 'Cargo type must be 150 characters or fewer.';
        }
        $data['cargo_type'] = $cargoType;

        $rawCargoValue = trim((string) ($input['cargo_value'] ?? ''));
        $normalisedCargoValue = str_replace([',', ' '], '', $rawCargoValue);

        if ($rawCargoValue === '') {
            $errors['cargo_value'] = 'Cargo value is required.';
        } elseif (!preg_match('/^\d{1,13}(\.\d{1,2})?$/', $normalisedCargoValue)) {
            $errors['cargo_value'] = 'Enter a valid non-negative amount with up to 2 decimal places.';
        }
        $data['cargo_value'] = $normalisedCargoValue;

        $pickupAddress = trim((string) ($input['pickup_address'] ?? ''));
        if ($pickupAddress === '') {
            $errors['pickup_address'] = 'Pickup location is required.';
        } elseif ($this->textLength($pickupAddress) > self::MAX_ADDRESS_LENGTH) {
            $errors['pickup_address'] = 'Pickup location must be 500 characters or fewer.';
        }
        $data['pickup_address'] = $pickupAddress;

        $pickupDescription = trim((string) ($input['pickup_description'] ?? ''));
        if ($this->textLength($pickupDescription) > self::MAX_DESCRIPTION_LENGTH) {
            $errors['pickup_description'] = 'Pickup description must be 2000 characters or fewer.';
        }
        $data['pickup_description'] = $pickupDescription !== '' ? $pickupDescription : null;

        $destinationAddress = trim((string) ($input['destination_address'] ?? ''));
        if ($destinationAddress === '') {
            $errors['destination_address'] = 'Destination is required.';
        } elseif ($this->textLength($destinationAddress) > self::MAX_ADDRESS_LENGTH) {
            $errors['destination_address'] = 'Destination must be 500 characters or fewer.';
        }
        $data['destination_address'] = $destinationAddress;

        $destinationDescription = trim((string) ($input['destination_description'] ?? ''));
        if ($this->textLength($destinationDescription) > self::MAX_DESCRIPTION_LENGTH) {
            $errors['destination_description'] = 'Destination description must be 2000 characters or fewer.';
        }
        $data['destination_description'] = $destinationDescription !== ''
            ? $destinationDescription
            : null;

        return [
            'data' => $data,
            'errors' => $errors,
        ];
    }

    /**
     * Insert a validated manual delivery and its admin audit record.
     *
     * @param array<string, string|null> $data
     * @throws InvalidArgumentException When required data is missing.
     * @throws Throwable When the transaction fails.
     */
    public function createManualDelivery(array $data, int $adminId): int
    {
        if ($adminId <= 0) {
            throw new InvalidArgumentException('A valid Admin account is required.');
        }

        $requiredFields = [
            'customer_name',
            'customer_phone',
            'cargo_type',
            'cargo_value',
            'pickup_address',
            'destination_address',
        ];

        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || trim((string) $data[$field]) === '') {
                throw new InvalidArgumentException('The delivery data is incomplete.');
            }
        }

        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO deliveries (
                    customer_id,
                    seller_id,
                    customer_name,
                    customer_phone,
                    cargo_type,
                    cargo_value,
                    pickup_address,
                    pickup_description,
                    destination_address,
                    destination_description,
                    delivery_status,
                    created_by
                 ) VALUES (
                    NULL,
                    NULL,
                    :customer_name,
                    :customer_phone,
                    :cargo_type,
                    :cargo_value,
                    :pickup_address,
                    :pickup_description,
                    :destination_address,
                    :destination_description,
                    :delivery_status,
                    :created_by
                 )'
            );

            $pickupDescription = $data['pickup_description'] ?? null;
            $destinationDescription = $data['destination_description'] ?? null;

            $stmt->bindValue(':customer_name', (string) $data['customer_name'], PDO::PARAM_STR);
            $stmt->bindValue(':customer_phone', (string) $data['customer_phone'], PDO::PARAM_STR);
            $stmt->bindValue(':cargo_type', (string) $data['cargo_type'], PDO::PARAM_STR);
            $stmt->bindValue(':cargo_value', (string) $data['cargo_value'], PDO::PARAM_STR);
            $stmt->bindValue(':pickup_address', (string) $data['pickup_address'], PDO::PARAM_STR);
            $stmt->bindValue(
                ':pickup_description',
                $pickupDescription,
                $pickupDescription === null ? PDO::PARAM_NULL : PDO::PARAM_STR
            );
            $stmt->bindValue(':destination_address', (string) $data['destination_address'], PDO::PARAM_STR);
            $stmt->bindValue(
                ':destination_description',
                $destinationDescription,
                $destinationDescription === null ? PDO::PARAM_NULL : PDO::PARAM_STR
            );
            $stmt->bindValue(':delivery_status', self::STATUS_PENDING, PDO::PARAM_STR);
            $stmt->bindValue(':created_by', $adminId, PDO::PARAM_INT);
            $stmt->execute();

            $deliveryId = (int) $this->pdo->lastInsertId();

            $this->logAction(
                $adminId,
                'delivery_created',
                'Created delivery #' . $deliveryId . ' for ' . (string) $data['customer_name'] . '.'
            );

            $this->pdo->commit();

            return $deliveryId;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }

    /**
     * Find a single delivery and the Admin who created it.
     *
     * @return array<string, mixed>|null Null when the delivery does not exist.
     */
    public function findDelivery(int $deliveryId): ?array
    {
        if ($deliveryId <= 0) {
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
                    d.created_by,
                    d.created_at,
                    d.updated_at,
                    u.full_name AS created_by_name
             FROM deliveries d
             LEFT JOIN users u ON u.id = d.created_by
             WHERE d.id = :id
             LIMIT 1'
        );
        $stmt->bindValue(':id', $deliveryId, PDO::PARAM_INT);
        $stmt->execute();

        $delivery = $stmt->fetch();

        return $delivery === false ? null : $delivery;
    }

    /**
     * Find the most recent driver assignment for a delivery.
     *
     * @return array<string, mixed>|null Null when no driver is assigned.
     */
    public function findCurrentAssignment(int $deliveryId): ?array
    {
        if ($deliveryId <= 0) {
            return null;
        }

        $stmt = $this->pdo->prepare(
            'SELECT u.full_name AS driver_name,
                    u.phone AS driver_phone,
                    dp.license_number,
                    da.assignment_status,
                    da.assigned_at,
                    da.completed_at
             FROM delivery_assignments da
             JOIN driver_profiles dp ON dp.id = da.driver_id
             JOIN users u ON u.id = dp.user_id
             WHERE da.delivery_id = :id
             ORDER BY da.assigned_at DESC, da.id DESC
             LIMIT 1'
        );
        $stmt->bindValue(':id', $deliveryId, PDO::PARAM_INT);
        $stmt->execute();

        $assignment = $stmt->fetch();

        return $assignment === false ? null : $assignment;
    }

    /**
     * Update the editable fields on a pending delivery.
     *
     * @param array<string, string|null> $data
     * @return array{outcome: string, changed_fields: array<int, string>}
     * @throws InvalidArgumentException When the admin ID is invalid.
     * @throws Throwable When the transaction fails.
     */
    public function updatePendingDelivery(
        int $deliveryId,
        array $data,
        int $adminId,
        string $expectedUpdatedAt
    ): array {
        if ($deliveryId <= 0) {
            return ['outcome' => 'not_found', 'changed_fields' => []];
        }

        if ($adminId <= 0) {
            throw new InvalidArgumentException('A valid Admin account is required.');
        }

        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare(
                'SELECT id,
                        delivery_status,
                        customer_name,
                        customer_phone,
                        cargo_type,
                        cargo_value,
                        pickup_address,
                        pickup_description,
                        destination_address,
                        destination_description,
                        updated_at
                 FROM deliveries
                 WHERE id = :id
                 FOR UPDATE'
            );
            $stmt->bindValue(':id', $deliveryId, PDO::PARAM_INT);
            $stmt->execute();
            $current = $stmt->fetch();

            if ($current === false) {
                $this->pdo->rollBack();

                return ['outcome' => 'not_found', 'changed_fields' => []];
            }

            if (strtolower(trim((string) $current['delivery_status'])) !== self::STATUS_PENDING) {
                $this->pdo->rollBack();

                return ['outcome' => 'not_editable', 'changed_fields' => []];
            }

            if ($expectedUpdatedAt === '' || (string) $current['updated_at'] !== $expectedUpdatedAt) {
                $this->pdo->rollBack();

                return ['outcome' => 'conflict', 'changed_fields' => []];
            }

            $changedFields = [];
            $values = [];

            foreach (self::EDITABLE_FIELD_LABELS as $field => $label) {
                $currentValue = $this->normaliseEditableValue($field, $current[$field] ?? null);
                $newValue = $this->normaliseEditableValue($field, $data[$field] ?? null);
                $values[$field] = $newValue;

                if ($currentValue !== $newValue) {
                    $changedFields[] = $field;
                }
            }

            if ($changedFields === []) {
                $this->pdo->commit();

                return ['outcome' => 'unchanged', 'changed_fields' => []];
            }

            $stmt = $this->pdo->prepare(
                'UPDATE deliveries
                 SET customer_name = :customer_name,
                     customer_phone = :customer_phone,
                     cargo_type = :cargo_type,
                     cargo_value = :cargo_value,
                     pickup_address = :pickup_address,
                     pickup_description = :pickup_description,
                     destination_address = :destination_address,
                     destination_description = :destination_description
                 WHERE id = :id
                   AND delivery_status = :pending_status
                   AND updated_at = :expected_updated_at'
            );

            $stmt->bindValue(':customer_name', (string) $values['customer_name'], PDO::PARAM_STR);
            $stmt->bindValue(':customer_phone', (string) $values['customer_phone'], PDO::PARAM_STR);
            $stmt->bindValue(':cargo_type', (string) $values['cargo_type'], PDO::PARAM_STR);
            $stmt->bindValue(':cargo_value', (string) $values['cargo_value'], PDO::PARAM_STR);
            $stmt->bindValue(':pickup_address', (string) $values['pickup_address'], PDO::PARAM_STR);
            $stmt->bindValue(
                ':pickup_description',
                $values['pickup_description'],
                $values['pickup_description'] === null ? PDO::PARAM_NULL : PDO::PARAM_STR
            );
            $stmt->bindValue(':destination_address', (string) $values['destination_address'], PDO::PARAM_STR);
            $stmt->bindValue(
                ':destination_description',
                $values['destination_description'],
                $values['destination_description'] === null ? PDO::PARAM_NULL : PDO::PARAM_STR
            );
            $stmt->bindValue(':id', $deliveryId, PDO::PARAM_INT);
            $stmt->bindValue(':pending_status', self::STATUS_PENDING, PDO::PARAM_STR);
            $stmt->bindValue(':expected_updated_at', $expectedUpdatedAt, PDO::PARAM_STR);
            $stmt->execute();

            if ($stmt->rowCount() === 0) {
                $this->pdo->commit();

                return ['outcome' => 'unchanged', 'changed_fields' => []];
            }

            $changedLabels = [];
            foreach ($changedFields as $field) {
                $changedLabels[] = self::EDITABLE_FIELD_LABELS[$field];
            }

            $this->logAction(
                $adminId,
                'delivery_updated',
                'Updated delivery #' . $deliveryId . ' (' . implode(', ', $changedLabels) . ').'
            );

            $this->pdo->commit();

            return ['outcome' => 'updated', 'changed_fields' => $changedLabels];
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }

    public static function isCancellableStatus(string $status): bool
    {
        return in_array(strtolower(trim($status)), self::CANCELLABLE_STATUSES, true);
    }

    public function validateCancellationReason(string $reason): array
    {
        $reason = trim($reason);
        $errors = [];

        if ($reason === '') {
            $errors['cancellation_reason'] = 'Cancellation reason is required.';
        } elseif ($this->textLength($reason) > self::MAX_CANCELLATION_REASON_LENGTH) {
            $errors['cancellation_reason'] = 'Cancellation reason must be 500 characters or fewer.';
        }

        return [
            'reason' => $reason,
            'errors' => $errors,
        ];
    }

    public function cancelDelivery(int $deliveryId, string $reason, int $adminId): array
    {
        if ($deliveryId <= 0) {
            return ['outcome' => 'not_found', 'previous_status' => ''];
        }

        if ($adminId <= 0) {
            throw new InvalidArgumentException('A valid Admin account is required.');
        }

        $reason = trim($reason);

        if ($reason === '') {
            throw new InvalidArgumentException('A cancellation reason is required.');
        }

        if ($this->textLength($reason) > self::MAX_CANCELLATION_REASON_LENGTH) {
            throw new InvalidArgumentException('Cancellation reason must be 500 characters or fewer.');
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
            $current = $stmt->fetch();

            if ($current === false) {
                $this->pdo->rollBack();

                return ['outcome' => 'not_found', 'previous_status' => ''];
            }

            $previousStatus = strtolower(trim((string) $current['delivery_status']));

            if (!self::isCancellableStatus($previousStatus)) {
                $this->pdo->rollBack();

                return ['outcome' => 'not_cancellable', 'previous_status' => $previousStatus];
            }

            $stmt = $this->pdo->prepare(
                'UPDATE deliveries
                 SET delivery_status = :cancelled_status
                 WHERE id = :id
                   AND delivery_status = :previous_status'
            );
            $stmt->bindValue(':cancelled_status', self::STATUS_CANCELLED, PDO::PARAM_STR);
            $stmt->bindValue(':id', $deliveryId, PDO::PARAM_INT);
            $stmt->bindValue(':previous_status', $previousStatus, PDO::PARAM_STR);
            $stmt->execute();

            if ($stmt->rowCount() === 0) {
                $this->pdo->rollBack();

                return ['outcome' => 'not_cancellable', 'previous_status' => $previousStatus];
            }

            $stmt = $this->pdo->prepare(
                'UPDATE delivery_assignments
                 SET assignment_status = :cancelled_status
                 WHERE delivery_id = :delivery_id
                   AND completed_at IS NULL
                   AND assignment_status IN (\'assigned\', \'accepted\', \'in_transit\')'
            );
            $stmt->bindValue(':cancelled_status', self::ASSIGNMENT_CANCELLED, PDO::PARAM_STR);
            $stmt->bindValue(':delivery_id', $deliveryId, PDO::PARAM_INT);
            $stmt->execute();

            $this->logAction(
                $adminId,
                'delivery_cancelled',
                'Cancelled delivery #' . $deliveryId
                    . ' (was ' . $this->statusLabelForAudit($previousStatus) . ').'
                    . ' Reason: ' . $reason
            );

            $this->pdo->commit();

            return ['outcome' => 'cancelled', 'previous_status' => $previousStatus];
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }

    /**
     * List deliveries for the Admin Deliveries page.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listDeliveries(string $search, string $status, int $limit, int $offset): array
    {
        $limit = max(1, min($limit, 100));
        $offset = max(0, $offset);

        $params = [];
        $where = $this->deliveryFilterClause($search, $status, $params);

        $stmt = $this->pdo->prepare(
            'SELECT d.id,
                    d.customer_name,
                    d.customer_phone,
                    d.cargo_type,
                    d.pickup_address,
                    d.destination_address,
                    d.delivery_status,
                    d.created_at,
                    drv.full_name AS driver_name
             FROM deliveries d
             LEFT JOIN delivery_assignments da
                    ON da.id = (
                        SELECT da2.id
                        FROM delivery_assignments da2
                        WHERE da2.delivery_id = d.id
                        ORDER BY da2.assigned_at DESC, da2.id DESC
                        LIMIT 1
                    )
             LEFT JOIN driver_profiles dp ON dp.id = da.driver_id
             LEFT JOIN users drv ON drv.id = dp.user_id'
            . $where
            . ' ORDER BY d.created_at DESC, d.id DESC
                LIMIT :limit OFFSET :offset'
        );

        foreach ($params as $name => $value) {
            $stmt->bindValue($name, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /** Count deliveries matching the same filters as listDeliveries(). */
    public function countDeliveries(string $search, string $status): int
    {
        $params = [];
        $where = $this->deliveryFilterClause($search, $status, $params);

        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM deliveries d' . $where
        );

        foreach ($params as $name => $value) {
            $stmt->bindValue($name, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }

        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    /**
     * Build the shared WHERE clause and bound parameters for delivery lists.
     *
     * @param array<string, string|int> $params
     */
    private function deliveryFilterClause(string $search, string $status, array &$params): string
    {
        $conditions = [];

        if (in_array($status, self::ALLOWED_STATUSES, true)) {
            $conditions[] = 'd.delivery_status = :status';
            $params[':status'] = $status;
        }

        if ($search !== '') {
            $like = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $search) . '%';

            $searchConditions = [
                'd.customer_name LIKE :search_name ESCAPE \'!\'',
                'd.customer_phone LIKE :search_phone ESCAPE \'!\'',
                'd.cargo_type LIKE :search_cargo ESCAPE \'!\'',
                'd.pickup_address LIKE :search_pickup ESCAPE \'!\'',
                'd.destination_address LIKE :search_destination ESCAPE \'!\'',
            ];

            $params[':search_name'] = $like;
            $params[':search_phone'] = $like;
            $params[':search_cargo'] = $like;
            $params[':search_pickup'] = $like;
            $params[':search_destination'] = $like;

            if (ctype_digit($search) && strlen($search) <= 18) {
                $searchConditions[] = 'd.id = :search_id';
                $params[':search_id'] = (int) $search;
            }

            $conditions[] = '(' . implode(' OR ', $searchConditions) . ')';
        }

        return $conditions === [] ? '' : ' WHERE ' . implode(' AND ', $conditions);
    }

    private function normaliseEditableValue(string $field, mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        if ($field === 'cargo_value') {
            return $this->normaliseCargoValueForComparison($value);
        }

        if ($field === 'pickup_description' || $field === 'destination_description') {
            return $value === '' ? null : $value;
        }

        return $value;
    }

    private function normaliseCargoValueForComparison(string $value): string
    {
        if ($value === '') {
            return '';
        }

        if (!preg_match('/^(\d+)(?:\.(\d{1,2}))?$/', $value, $matches)) {
            return $value;
        }

        $whole = ltrim($matches[1], '0');
        if ($whole === '') {
            $whole = '0';
        }

        $fraction = str_pad($matches[2] ?? '', 2, '0');

        return $whole . '.' . $fraction;
    }

    private function statusLabelForAudit(string $status): string
    {
        $labels = [
            'pending' => 'pending',
            'in_transit' => 'in transit',
            'delivered' => 'delivered',
            'cancelled' => 'cancelled',
        ];

        return $labels[$status] ?? ($status !== '' ? str_replace('_', ' ', $status) : 'unknown');
    }

    /** Write an audit record to admin_actions. */
    private function logAction(int $adminId, string $actionType, string $description): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO admin_actions (admin_id, action_type, target_user_id, description)
             VALUES (:admin_id, :action_type, NULL, :description)'
        );
        $stmt->bindValue(':admin_id', $adminId, PDO::PARAM_INT);
        $stmt->bindValue(':action_type', $actionType, PDO::PARAM_STR);
        $stmt->bindValue(':description', $description, PDO::PARAM_STR);
        $stmt->execute();
    }

    private function textLength(string $value): int
    {
        return function_exists('mb_strlen')
            ? mb_strlen($value, 'UTF-8')
            : strlen($value);
    }
}
