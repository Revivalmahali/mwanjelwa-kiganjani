<?php

declare(strict_types=1);

namespace App\Services;

use InvalidArgumentException;
use PDO;
use PDOException;
use RuntimeException;
use Throwable;

/**
 * Mwanjelwa Kiganjani - Driver Management Service
 *
 * Phase 5.1 covers driver self-registration only. The service validates the
 * approved registration fields and creates a pending driver account, driver
 * role assignment and driver profile in one transaction.
 */
class DriverManagement
{
    private const MAX_NAME_PART_LENGTH = 50;
    private const MAX_FULL_NAME_LENGTH = 150;
    private const MAX_PHONE_LENGTH = 20;
    private const MAX_ADDRESS_LENGTH = 500;
    private const MAX_EMAIL_LENGTH = 255;
    private const MIN_PASSWORD_LENGTH = 8;

    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * Validate the driver registration form.
     *
     * @param array<string, mixed> $input
     * @return array{data: array<string, string>, errors: array<string, string>}
     */
    public function validateRegistration(array $input): array
    {
        $firstName = $this->stringValue($input['first_name'] ?? '');
        $middleName = $this->stringValue($input['middle_name'] ?? '');
        $lastName = $this->stringValue($input['last_name'] ?? '');
        $phone = $this->stringValue($input['phone'] ?? '');
        $email = $this->stringValue($input['email'] ?? '');
        $residentialAddress = $this->stringValue($input['residential_address'] ?? '');
        $password = $this->passwordValue($input['password'] ?? '');
        $confirmPassword = $this->passwordValue($input['confirm_password'] ?? '');

        $errors = [];

        if ($firstName === '') {
            $errors['first_name'] = 'First name is required.';
        } elseif ($this->textLength($firstName) > self::MAX_NAME_PART_LENGTH) {
            $errors['first_name'] = 'First name must be 50 characters or fewer.';
        }

        if ($middleName === '') {
            $errors['middle_name'] = 'Middle name is required.';
        } elseif ($this->textLength($middleName) > self::MAX_NAME_PART_LENGTH) {
            $errors['middle_name'] = 'Middle name must be 50 characters or fewer.';
        }

        if ($lastName === '') {
            $errors['last_name'] = 'Last name is required.';
        } elseif ($this->textLength($lastName) > self::MAX_NAME_PART_LENGTH) {
            $errors['last_name'] = 'Last name must be 50 characters or fewer.';
        }

        if ($phone === '') {
            $errors['phone'] = 'Phone number is required.';
        } elseif (!preg_match('/^\+?[0-9]{9,15}$/', $phone)) {
            $errors['phone'] = 'Enter a valid phone number with 9 to 15 digits. A leading + is allowed.';
        } elseif (strlen($phone) > self::MAX_PHONE_LENGTH) {
            $errors['phone'] = 'Phone number must be 20 characters or fewer.';
        }

        if ($residentialAddress === '') {
            $errors['residential_address'] = 'Residential address is required.';
        } elseif ($this->textLength($residentialAddress) > self::MAX_ADDRESS_LENGTH) {
            $errors['residential_address'] = 'Residential address must be 500 characters or fewer.';
        }

        if ($email !== '') {
            if (strlen($email) > self::MAX_EMAIL_LENGTH) {
                $errors['email'] = 'Email address must be 255 characters or fewer.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Please enter a valid email address.';
            }
        }

        if ($password === '') {
            $errors['password'] = 'Password is required.';
        } elseif (strlen($password) < self::MIN_PASSWORD_LENGTH) {
            $errors['password'] = 'Password must be at least 8 characters.';
        }

        if ($confirmPassword === '') {
            $errors['confirm_password'] = 'Please confirm your password.';
        } elseif ($password !== '' && $password !== $confirmPassword) {
            $errors['confirm_password'] = 'Password and confirmation password do not match.';
        }

        $fullName = trim($firstName . ' ' . $middleName . ' ' . $lastName);
        $fullName = (string) preg_replace('/\s+/', ' ', $fullName);

        if ($this->textLength($fullName) > self::MAX_FULL_NAME_LENGTH) {
            $errors['_form'] = 'The combined first, middle and last name must be 150 characters or fewer.';
        }

        return [
            'data' => [
                'first_name' => $firstName,
                'middle_name' => $middleName,
                'last_name' => $lastName,
                'full_name' => $fullName,
                'phone' => $phone,
                'email' => $email,
                'residential_address' => $residentialAddress,
                'password' => $password,
            ],
            'errors' => $errors,
        ];
    }

    /**
     * Create a pending driver account, driver role and driver profile.
     *
     * @param array<string, string> $data Validated registration data.
     * @throws InvalidArgumentException When the phone or email already exists.
     * @throws RuntimeException When the driver role is missing.
     * @throws Throwable When the transaction fails.
     */
    public function registerDriver(array $data): int
    {
        $phone = (string) ($data['phone'] ?? '');
        $email = trim((string) ($data['email'] ?? ''));
        $email = $email === '' ? null : $email;

        $stmt = $this->pdo->prepare('SELECT id FROM users WHERE phone = :phone LIMIT 1');
        $stmt->execute([':phone' => $phone]);

        if ($stmt->fetch() !== false) {
            throw new InvalidArgumentException('A user with this phone number is already registered.');
        }

        if ($email !== null) {
            $stmt = $this->pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
            $stmt->execute([':email' => $email]);

            if ($stmt->fetch() !== false) {
                throw new InvalidArgumentException('A user with this email is already registered.');
            }
        }

        $stmt = $this->pdo->prepare('SELECT id FROM roles WHERE name = :name LIMIT 1');
        $stmt->execute([':name' => 'driver']);
        $roleId = $stmt->fetchColumn();

        if ($roleId === false) {
            throw new RuntimeException('The "driver" role does not exist in the roles table.');
        }

        $passwordHash = password_hash((string) $data['password'], PASSWORD_DEFAULT);

        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO users (full_name, phone, email, password_hash, account_status)
                 VALUES (:full_name, :phone, :email, :password_hash, :account_status)'
            );
            $stmt->execute([
                ':full_name' => (string) $data['full_name'],
                ':phone' => $phone,
                ':email' => $email,
                ':password_hash' => $passwordHash,
                ':account_status' => 'pending',
            ]);
            $userId = (int) $this->pdo->lastInsertId();

            $stmt = $this->pdo->prepare(
                'INSERT INTO user_roles (user_id, role_id, assigned_by)
                 VALUES (:user_id, :role_id, NULL)'
            );
            $stmt->execute([
                ':user_id' => $userId,
                ':role_id' => (int) $roleId,
            ]);

            $stmt = $this->pdo->prepare(
                'INSERT INTO driver_profiles
                    (user_id, license_number, residential_address, verification_status)
                 VALUES
                    (:user_id, NULL, :residential_address, :verification_status)'
            );
            $stmt->execute([
                ':user_id' => $userId,
                ':residential_address' => (string) $data['residential_address'],
                ':verification_status' => 'pending',
            ]);

            $this->pdo->commit();

            return $userId;
        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            if (($e->errorInfo[1] ?? null) === 1062) {
                $message = $e->getMessage();

                if (str_contains($message, 'uq_users_phone')) {
                    throw new InvalidArgumentException(
                        'A user with this phone number is already registered.',
                        0,
                        $e
                    );
                }

                if (str_contains($message, 'uq_users_email')) {
                    throw new InvalidArgumentException(
                        'A user with this email is already registered.',
                        0,
                        $e
                    );
                }

                throw new InvalidArgumentException(
                    'A user with this phone number or email is already registered.',
                    0,
                    $e
                );
            }

            throw $e;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }

    private function stringValue(mixed $value): string
    {
        return is_string($value) ? trim($value) : '';
    }

    private function passwordValue(mixed $value): string
    {
        return is_string($value) ? $value : '';
    }

    private function textLength(string $value): int
    {
        return function_exists('mb_strlen')
            ? mb_strlen($value, 'UTF-8')
            : strlen($value);
    }
}
