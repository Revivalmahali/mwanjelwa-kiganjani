<?php

declare(strict_types=1);

namespace App\Services;

use InvalidArgumentException;
use PDO;
use PDOException;
use Throwable;

/**
 * Mwanjelwa Kiganjani - Admin Driver Management Service
 *
 * Read queries for the admin driver list/details pages and the approved
 * approval/activation/deactivation actions. Status changes are written inside
 * a transaction together with an admin_actions audit record.
 */
class DriverAdminManagement
{
    private const MAX_RESIDENTIAL_ADDRESS_LENGTH = 500;
    private const MAX_LICENSE_NUMBER_LENGTH = 50;
    private const EDITABLE_PROFILE_LABELS = [
        'residential_address' => 'residential address',
        'license_number' => 'licence number',
    ];

    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * Derive the single status used by the admin driver pages.
     */
    public static function statusFor(
        ?string $accountStatus,
        ?string $verificationStatus,
        bool $hasProfile
    ): string {
        if (!$hasProfile) {
            return 'missing';
        }

        $account = strtolower(trim((string) $accountStatus));
        $verification = strtolower(trim((string) $verificationStatus));

        if ($account === 'inactive') {
            return 'inactive';
        }

        if ($verification === 'rejected') {
            return 'rejected';
        }

        if ($account === 'active' && $verification === 'verified') {
            return 'active';
        }

        if ($account === 'pending' || $verification === 'pending') {
            return 'pending';
        }

        return 'inconsistent';
    }

    public static function statusLabel(string $status): string
    {
        $labels = [
            'pending' => 'Pending',
            'active' => 'Active',
            'inactive' => 'Inactive',
            'missing' => 'Missing profile',
            'rejected' => 'Rejected',
            'inconsistent' => 'Needs review',
        ];

        return $labels[$status] ?? 'Unknown';
    }

    public static function statusClass(string $status): string
    {
        $classes = [
            'pending' => 'badge--pending',
            'active' => 'badge--active',
            'inactive' => 'badge--inactive',
            'missing' => 'badge--neutral',
            'rejected' => 'badge--inactive',
            'inconsistent' => 'badge--neutral',
        ];

        return $classes[$status] ?? 'badge--neutral';
    }

    /**
     * @param array<string, mixed> $driver
     * @return array<int, string>
     */
    public static function profileCompletenessIssues(array $driver): array
    {
        $issues = [];

        if (($driver['driver_profile_id'] ?? null) === null) {
            $issues[] = 'driver profile';
        }

        if (trim((string) ($driver['full_name'] ?? '')) === '') {
            $issues[] = 'full name';
        }

        if (trim((string) ($driver['phone'] ?? '')) === '') {
            $issues[] = 'phone number';
        }

        if (trim((string) ($driver['residential_address'] ?? '')) === '') {
            $issues[] = 'residential address';
        }

        return $issues;
    }

    /**
     * @param array<string, mixed> $driver
     */
    public static function isProfileComplete(array $driver): bool
    {
        return self::profileCompletenessIssues($driver) === [];
    }

    /**
     * @param array<string, mixed> $driver
     */
    public static function canApprove(array $driver): bool
    {
        return ($driver['driver_status'] ?? '') === 'pending'
            && self::isProfileComplete($driver);
    }

    /**
     * @param array<string, mixed> $driver
     */
    public static function canActivate(array $driver): bool
    {
        if (($driver['driver_profile_id'] ?? null) === null) {
            return false;
        }

        $status = (string) ($driver['driver_status'] ?? '');

        if ($status === 'active' || $status === 'pending') {
            return false;
        }

        return self::isProfileComplete($driver);
    }

    /**
     * @param array<string, mixed> $driver
     */
    public static function canDeactivate(array $driver): bool
    {
        if (($driver['driver_profile_id'] ?? null) === null) {
            return false;
        }

        if ((int) ($driver['has_active_delivery'] ?? 0) === 1) {
            return false;
        }

        return strtolower(trim((string) ($driver['account_status'] ?? ''))) === 'active';
    }

    public function countDrivers(string $search = '', string $status = ''): int
    {
        [$where, $params] = $this->buildFilters($search, $status);

        $sql = 'SELECT COUNT(*)
                FROM users u
                JOIN user_roles ur ON ur.user_id = u.id
                JOIN roles r ON r.id = ur.role_id AND r.name = :driver_role
                LEFT JOIN driver_profiles dp ON dp.user_id = u.id';

        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $params[':driver_role'] = 'driver';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listDrivers(
        string $search = '',
        string $status = '',
        int $limit = 20,
        int $offset = 0
    ): array {
        $limit = max(1, min($limit, 100));
        $offset = max(0, $offset);

        [$where, $params] = $this->buildFilters($search, $status);

        $sql = 'SELECT u.id, u.full_name, u.phone, u.email, u.account_status,
                       u.created_at,
                       dp.id AS driver_profile_id,
                       dp.license_number,
                       dp.residential_address,
                       dp.verification_status,
                       dp.created_at AS profile_created_at,
                       dp.updated_at AS profile_updated_at,
                       EXISTS (
                           SELECT 1
                           FROM delivery_assignments da
                           WHERE da.driver_id = dp.id
                             AND da.completed_at IS NULL
                             AND da.assignment_status IN (\'assigned\', \'accepted\', \'in_transit\')
                       ) AS has_active_delivery
                FROM users u
                JOIN user_roles ur ON ur.user_id = u.id
                JOIN roles r ON r.id = ur.role_id AND r.name = :driver_role
                LEFT JOIN driver_profiles dp ON dp.user_id = u.id';

        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY u.created_at DESC, u.id DESC
                  LIMIT :limit OFFSET :offset';

        $params[':driver_role'] = 'driver';

        $stmt = $this->pdo->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $drivers = $stmt->fetchAll();

        foreach ($drivers as &$driver) {
            $driver['driver_status'] = self::statusFor(
                $driver['account_status'] ?? null,
                $driver['verification_status'] ?? null,
                $driver['driver_profile_id'] !== null
            );
        }
        unset($driver);

        return $drivers;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findDriver(int $userId): ?array
    {
        if ($userId <= 0) {
            return null;
        }

        $stmt = $this->pdo->prepare(
            'SELECT u.id, u.full_name, u.phone, u.email, u.account_status,
                    u.created_at, u.updated_at,
                    dp.id AS driver_profile_id,
                    dp.license_number,
                    dp.residential_address,
                    dp.verification_status,
                    dp.created_at AS profile_created_at,
                    dp.updated_at AS profile_updated_at,
                    EXISTS (
                        SELECT 1
                        FROM delivery_assignments da
                        WHERE da.driver_id = dp.id
                          AND da.completed_at IS NULL
                          AND da.assignment_status IN (\'assigned\', \'accepted\', \'in_transit\')
                    ) AS has_active_delivery
             FROM users u
             JOIN user_roles ur ON ur.user_id = u.id
             JOIN roles r ON r.id = ur.role_id AND r.name = :driver_role
             LEFT JOIN driver_profiles dp ON dp.user_id = u.id
             WHERE u.id = :id
             LIMIT 1'
        );
        $stmt->execute([
            ':driver_role' => 'driver',
            ':id' => $userId,
        ]);

        $driver = $stmt->fetch();

        if ($driver === false) {
            return null;
        }

        $driver['driver_status'] = self::statusFor(
            $driver['account_status'] ?? null,
            $driver['verification_status'] ?? null,
            $driver['driver_profile_id'] !== null
        );

        $stmt = $this->pdo->prepare(
            'SELECT r.name
             FROM user_roles ur
             JOIN roles r ON r.id = ur.role_id
             WHERE ur.user_id = :id
             ORDER BY r.name'
        );
        $stmt->execute([':id' => $userId]);
        $driver['roles'] = $stmt->fetchAll(PDO::FETCH_COLUMN);

        return $driver;
    }

    /**
     * Validate the editable driver profile fields.
     *
     * @param array<string, mixed> $input
     * @return array{
     *     data: array{residential_address: string, license_number: string|null},
     *     errors: array<string, string>
     * }
     */
    public function validateProfileUpdate(array $input): array
    {
        $residentialAddress = trim((string) ($input['residential_address'] ?? ''));
        $licenseNumber = trim((string) ($input['license_number'] ?? ''));
        $errors = [];

        if ($residentialAddress === '') {
            $errors['residential_address'] = 'Residential address is required.';
        } elseif ($this->textLength($residentialAddress) > self::MAX_RESIDENTIAL_ADDRESS_LENGTH) {
            $errors['residential_address'] = 'Residential address must be 500 characters or fewer.';
        }

        if ($licenseNumber !== ''
            && $this->textLength($licenseNumber) > self::MAX_LICENSE_NUMBER_LENGTH
        ) {
            $errors['license_number'] = 'Licence number must be 50 characters or fewer.';
        }

        return [
            'data' => [
                'residential_address' => $residentialAddress,
                'license_number' => $licenseNumber === '' ? null : $licenseNumber,
            ],
            'errors' => $errors,
        ];
    }

    /**
     * Update the editable fields on an existing driver profile.
     *
     * Account status and verification status are intentionally not changed here.
     *
     * @param array{residential_address: string, license_number: string|null} $data
     * @return array{outcome: string, changed_fields: array<int, string>}
     * @throws InvalidArgumentException When the admin ID or profile data is invalid.
     * @throws Throwable When the transaction fails.
     */
    public function updateDriverProfile(
        int $userId,
        array $data,
        int $adminId,
        string $expectedUpdatedAt
    ): array {
        if ($userId <= 0) {
            return ['outcome' => 'not_found', 'changed_fields' => []];
        }

        if ($adminId <= 0) {
            throw new InvalidArgumentException('A valid Admin account is required.');
        }

        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare(
                'SELECT id, full_name
                 FROM users
                 WHERE id = :id
                 FOR UPDATE'
            );
            $stmt->execute([':id' => $userId]);
            $user = $stmt->fetch();

            if ($user === false) {
                $this->pdo->rollBack();

                return ['outcome' => 'not_found', 'changed_fields' => []];
            }

            $stmt = $this->pdo->prepare(
                'SELECT 1
                 FROM user_roles ur
                 JOIN roles r ON r.id = ur.role_id
                 WHERE ur.user_id = :id AND r.name = :driver_role
                 LIMIT 1'
            );
            $stmt->execute([
                ':id' => $userId,
                ':driver_role' => 'driver',
            ]);

            if ($stmt->fetchColumn() === false) {
                throw new InvalidArgumentException('This user does not have the Driver role.');
            }

            $stmt = $this->pdo->prepare(
                'SELECT id, residential_address, license_number, updated_at
                 FROM driver_profiles
                 WHERE user_id = :id
                 FOR UPDATE'
            );
            $stmt->execute([':id' => $userId]);
            $profile = $stmt->fetch();

            if ($profile === false) {
                $this->pdo->rollBack();

                return ['outcome' => 'not_found', 'changed_fields' => []];
            }

            if ($expectedUpdatedAt === ''
                || (string) $profile['updated_at'] !== $expectedUpdatedAt
            ) {
                $this->pdo->rollBack();

                return ['outcome' => 'conflict', 'changed_fields' => []];
            }

            $currentAddress = trim((string) ($profile['residential_address'] ?? ''));
            $currentLicense = trim((string) ($profile['license_number'] ?? ''));
            $newAddress = trim((string) ($data['residential_address'] ?? ''));
            $newLicense = trim((string) ($data['license_number'] ?? ''));

            $changedFields = [];

            if ($currentAddress !== $newAddress) {
                $changedFields[] = 'residential_address';
            }

            if ($currentLicense !== $newLicense) {
                $changedFields[] = 'license_number';
            }

            if ($changedFields === []) {
                $this->pdo->commit();

                return ['outcome' => 'unchanged', 'changed_fields' => []];
            }

            try {
                $stmt = $this->pdo->prepare(
                    'UPDATE driver_profiles
                     SET residential_address = :residential_address,
                         license_number = :license_number
                     WHERE user_id = :id
                       AND updated_at = :expected_updated_at'
                );
                $stmt->bindValue(
                    ':residential_address',
                    $newAddress,
                    PDO::PARAM_STR
                );
                $stmt->bindValue(
                    ':license_number',
                    $newLicense === '' ? null : $newLicense,
                    $newLicense === '' ? PDO::PARAM_NULL : PDO::PARAM_STR
                );
                $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
                $stmt->bindValue(
                    ':expected_updated_at',
                    $expectedUpdatedAt,
                    PDO::PARAM_STR
                );
                $stmt->execute();
            } catch (PDOException $e) {
                if ((int) ($e->errorInfo[1] ?? 0) === 1062) {
                    throw new InvalidArgumentException(
                        'That licence number is already used by another driver.'
                    );
                }

                throw $e;
            }

            if ($stmt->rowCount() === 0) {
                $this->pdo->commit();

                return ['outcome' => 'unchanged', 'changed_fields' => []];
            }

            $changedLabels = [];

            foreach ($changedFields as $field) {
                $changedLabels[] = self::EDITABLE_PROFILE_LABELS[$field];
            }

            $this->logAction(
                $adminId,
                $userId,
                'driver_profile_updated',
                'Updated driver profile for ' . (string) $user['full_name']
                . ' (' . implode(', ', $changedLabels) . ').'
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

    /**
     * @return array{outcome: string, driver_status: string}
     */
    public function approveDriver(int $userId, int $adminId): array
    {
        return $this->changeStatus($userId, $adminId, 'approve');
    }

    /**
     * @return array{outcome: string, driver_status: string}
     */
    public function activateDriver(int $userId, int $adminId): array
    {
        return $this->changeStatus($userId, $adminId, 'activate');
    }

    /**
     * @return array{outcome: string, driver_status: string}
     */
    public function deactivateDriver(int $userId, int $adminId): array
    {
        return $this->changeStatus($userId, $adminId, 'deactivate');
    }

    /**
     * @return array{outcome: string, driver_status: string}
     */
    private function changeStatus(int $userId, int $adminId, string $action): array
    {
        if ($userId <= 0) {
            throw new InvalidArgumentException('Driver not found.');
        }

        if (!in_array($action, ['approve', 'activate', 'deactivate'], true)) {
            throw new InvalidArgumentException('Unknown driver action.');
        }

        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare(
                'SELECT id, full_name, phone, account_status
                 FROM users
                 WHERE id = :id
                 FOR UPDATE'
            );
            $stmt->execute([':id' => $userId]);
            $user = $stmt->fetch();

            if ($user === false) {
                throw new InvalidArgumentException('Driver not found.');
            }

            $stmt = $this->pdo->prepare(
                'SELECT 1
                 FROM user_roles ur
                 JOIN roles r ON r.id = ur.role_id
                 WHERE ur.user_id = :id AND r.name = :driver_role
                 LIMIT 1'
            );
            $stmt->execute([
                ':id' => $userId,
                ':driver_role' => 'driver',
            ]);

            if ($stmt->fetchColumn() === false) {
                throw new InvalidArgumentException('This user does not have the Driver role.');
            }

            $stmt = $this->pdo->prepare(
                'SELECT id, verification_status, residential_address
                 FROM driver_profiles
                 WHERE user_id = :id
                 FOR UPDATE'
            );
            $stmt->execute([':id' => $userId]);
            $profile = $stmt->fetch();

            if ($profile === false) {
                throw new InvalidArgumentException(
                    'This driver has no driver profile. No profile was created automatically.'
                );
            }

            $currentStatus = self::statusFor(
                $user['account_status'] ?? null,
                $profile['verification_status'] ?? null,
                true
            );

            $profileIssues = self::profileCompletenessIssues([
                'driver_profile_id' => (int) $profile['id'],
                'full_name' => $user['full_name'] ?? null,
                'phone' => $user['phone'] ?? null,
                'residential_address' => $profile['residential_address'] ?? null,
            ]);

            if ($action === 'approve' || $action === 'activate') {
                if ($currentStatus === 'active') {
                    $this->pdo->rollBack();

                    return [
                        'outcome' => 'unchanged',
                        'driver_status' => $currentStatus,
                    ];
                }

                if ($action === 'approve') {
                    if ($currentStatus !== 'pending') {
                        throw new InvalidArgumentException('Only pending drivers can be approved.');
                    }
                } elseif ($currentStatus === 'pending') {
                    throw new InvalidArgumentException(
                        'Pending drivers must be approved using the Approve action.'
                    );
                }

                if ($profileIssues !== []) {
                    throw new InvalidArgumentException(
                        'This driver profile is incomplete. Missing: '
                        . implode(', ', $profileIssues)
                        . '. Complete the profile before approval or activation.'
                    );
                }

                $stmt = $this->pdo->prepare(
                    'UPDATE users SET account_status = :account_status WHERE id = :id'
                );
                $stmt->execute([
                    ':account_status' => 'active',
                    ':id' => $userId,
                ]);

                $stmt = $this->pdo->prepare(
                    'UPDATE driver_profiles
                     SET verification_status = :verification_status
                     WHERE user_id = :id'
                );
                $stmt->execute([
                    ':verification_status' => 'verified',
                    ':id' => $userId,
                ]);

                $isApproval = $action === 'approve';

                $this->logAction(
                    $adminId,
                    $userId,
                    $isApproval ? 'driver_approved' : 'driver_activated',
                    ($isApproval ? 'Approved' : 'Activated')
                    . ' driver account for ' . (string) $user['full_name'] . '.'
                );

                $this->pdo->commit();

                return [
                    'outcome' => $isApproval ? 'approved' : 'activated',
                    'driver_status' => 'active',
                ];
            }

            if ($user['account_status'] !== 'active') {
                if ($user['account_status'] === 'inactive') {
                    $this->pdo->rollBack();

                    return [
                        'outcome' => 'unchanged',
                        'driver_status' => $currentStatus,
                    ];
                }

                throw new InvalidArgumentException('Only an active driver can be deactivated.');
            }

            $stmt = $this->pdo->prepare(
                'SELECT 1
                 FROM delivery_assignments
                 WHERE driver_id = :driver_id
                   AND completed_at IS NULL
                   AND assignment_status IN (\'assigned\', \'accepted\', \'in_transit\')
                 LIMIT 1
                 FOR UPDATE'
            );
            $stmt->execute([':driver_id' => (int) $profile['id']]);

            if ($stmt->fetchColumn() !== false) {
                throw new InvalidArgumentException(
                    'This driver has an active delivery. Complete or cancel it before deactivating the driver.'
                );
            }

            $stmt = $this->pdo->prepare(
                'UPDATE users SET account_status = :account_status WHERE id = :id'
            );
            $stmt->execute([
                ':account_status' => 'inactive',
                ':id' => $userId,
            ]);

            $this->logAction(
                $adminId,
                $userId,
                'driver_deactivated',
                'Deactivated driver account for ' . (string) $user['full_name'] . '.'
            );

            $this->pdo->commit();

            return [
                'outcome' => 'deactivated',
                'driver_status' => 'inactive',
            ];
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }

    /**
     * @return array{0: array<int, string>, 1: array<string, string>}
     */
    private function buildFilters(string $search, string $status): array
    {
        $where = [];
        $params = [];

        if ($search !== '') {
            $where[] = '(u.full_name LIKE :search_name
                         OR u.phone LIKE :search_phone
                         OR u.email LIKE :search_email)';

            $like = '%' . $search . '%';
            $params[':search_name'] = $like;
            $params[':search_phone'] = $like;
            $params[':search_email'] = $like;
        }

        $statusConditions = [
            'missing' => 'dp.id IS NULL',
            'active' => "u.account_status = 'active' AND dp.verification_status = 'verified'",
            'inactive' => "u.account_status = 'inactive'",
            'pending' => "dp.id IS NOT NULL
                          AND u.account_status <> 'inactive'
                          AND (u.account_status = 'pending' OR dp.verification_status = 'pending')",
            'rejected' => "dp.id IS NOT NULL
                           AND u.account_status <> 'inactive'
                           AND dp.verification_status = 'rejected'",
        ];

        if (isset($statusConditions[$status])) {
            $where[] = '(' . $statusConditions[$status] . ')';
        }

        return [$where, $params];
    }

    private function logAction(
        int $adminId,
        int $targetUserId,
        string $actionType,
        string $description
    ): void {
        $stmt = $this->pdo->prepare(
            'INSERT INTO admin_actions (admin_id, action_type, target_user_id, description)
             VALUES (:admin_id, :action_type, :target_user_id, :description)'
        );
        $stmt->execute([
            ':admin_id' => $adminId,
            ':action_type' => $actionType,
            ':target_user_id' => $targetUserId,
            ':description' => $description,
        ]);
    }

    private function textLength(string $value): int
    {
        return function_exists('mb_strlen')
            ? mb_strlen($value, 'UTF-8')
            : strlen($value);
    }
}
