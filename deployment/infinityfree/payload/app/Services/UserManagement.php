<?php

declare(strict_types=1);

namespace App\Services;

use InvalidArgumentException;
use PDO;
use RuntimeException;

/**
 * Mwanjelwa Kiganjani - User Management Service
 *
 * Read queries for the admin user list and the role assignment action.
 * All queries use prepared statements. Role changes are written inside a
 * transaction together with an admin_actions audit record.
 */
class UserManagement
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** Total number of registered users. */
    public function countUsers(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    }

    /**
     * Count users per role.
     *
     * @return array<string, int>
     */
    public function getRoleCounts(): array
    {
        $counts = [
            'customer' => 0,
            'seller' => 0,
            'driver' => 0,
            'admin' => 0,
        ];

        $rows = $this->pdo->query(
            'SELECT r.name, COUNT(ur.user_id) AS total
             FROM roles r
             LEFT JOIN user_roles ur ON ur.role_id = r.id
             GROUP BY r.id, r.name'
        )->fetchAll();

        foreach ($rows as $row) {
            $counts[$row['name']] = (int) $row['total'];
        }

        return $counts;
    }

    /**
     * Most recently registered users.
     *
     * @return array<int, array<string, mixed>>
     */
    public function recentUsers(int $limit = 5): array
    {
        $limit = max(1, min($limit, 50));

        $stmt = $this->pdo->prepare(
            'SELECT id, full_name, phone, email, account_status, created_at
             FROM users
             ORDER BY created_at DESC, id DESC
             LIMIT :limit'
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * List users with their role names, optionally filtered by a search term.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listUsers(string $search = ''): array
    {
        $sql = 'SELECT u.id, u.full_name, u.phone, u.email, u.account_status,
                       u.created_at,
                       GROUP_CONCAT(DISTINCT r.name ORDER BY r.name SEPARATOR ",") AS role_names
                FROM users u
                LEFT JOIN user_roles ur ON ur.user_id = u.id
                LEFT JOIN roles r ON r.id = ur.role_id';

        $params = [];

        if ($search !== '') {
            $sql .= ' WHERE u.full_name LIKE :search_name
                         OR u.phone LIKE :search_phone
                         OR u.email LIKE :search_email';

            $like = '%' . $search . '%';
            $params[':search_name'] = $like;
            $params[':search_phone'] = $like;
            $params[':search_email'] = $like;
        }

        $sql .= ' GROUP BY u.id, u.full_name, u.phone, u.email, u.account_status, u.created_at
                  ORDER BY u.created_at DESC, u.id DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $users = $stmt->fetchAll();

        foreach ($users as &$user) {
            $user['roles'] = $user['role_names'] !== null && $user['role_names'] !== ''
                ? explode(',', $user['role_names'])
                : [];
            unset($user['role_names']);
        }
        unset($user);

        return $users;
    }

    /**
     * Find one user with their role names.
     *
     * @return array<string, mixed>|null
     */
    public function findUser(int $userId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, full_name, phone, email, account_status, created_at
             FROM users
             WHERE id = :id'
        );
        $stmt->execute([':id' => $userId]);

        $user = $stmt->fetch();

        if ($user === false) {
            return null;
        }

        $stmt = $this->pdo->prepare(
            'SELECT r.name
             FROM user_roles ur
             JOIN roles r ON r.id = ur.role_id
             WHERE ur.user_id = :id
             ORDER BY r.name'
        );
        $stmt->execute([':id' => $userId]);

        $user['roles'] = $stmt->fetchAll(PDO::FETCH_COLUMN);

        return $user;
    }

    /**
     * Assign a role to a user and write an audit record.
     *
     * Only "driver" and "admin" can be assigned through this method.
     * Seller is intentionally postponed; customer is assigned at registration.
     *
     * @throws InvalidArgumentException For invalid input or unsafe changes.
     * @throws RuntimeException When the role is missing from the database.
     */
    public function assignRole(int $userId, string $roleName, int $adminId): void
    {
        $roleName = strtolower(trim($roleName));
        $allowedRoles = ['driver', 'admin'];

        if (!in_array($roleName, $allowedRoles, true)) {
            throw new InvalidArgumentException('That role cannot be assigned here.');
        }

        $user = $this->findUser($userId);

        if ($user === null) {
            throw new InvalidArgumentException('User not found.');
        }

        if ($user['account_status'] !== 'active') {
            throw new InvalidArgumentException('Only active users can receive a new role.');
        }

        if (in_array($roleName, $user['roles'], true)) {
            throw new InvalidArgumentException(
                'This user already has the ' . $roleName . ' role.'
            );
        }

        $stmt = $this->pdo->prepare('SELECT id FROM roles WHERE name = :name');
        $stmt->execute([':name' => $roleName]);
        $roleId = $stmt->fetchColumn();

        if ($roleId === false) {
            throw new RuntimeException('The requested role does not exist.');
        }

        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO user_roles (user_id, role_id, assigned_by)
                 VALUES (:user_id, :role_id, :assigned_by)'
            );
            $stmt->execute([
                ':user_id' => $userId,
                ':role_id' => (int) $roleId,
                ':assigned_by' => $adminId,
            ]);

            $this->logAction(
                $adminId,
                $userId,
                'role_assigned',
                'Assigned the "' . $roleName . '" role to ' . $user['full_name'] . '.'
            );

            $this->pdo->commit();
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }

    /** Write an audit record to admin_actions. */
    private function logAction(
        int $adminId,
        ?int $targetUserId,
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
}
