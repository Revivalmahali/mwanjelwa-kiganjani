<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\Auth;
use App\Services\Csrf;
use App\Services\Database;
use App\Services\DeliveryManagement;
use App\Services\DriverAdminManagement;
use App\Services\OperationsOverview;
use App\Services\UserManagement;
use InvalidArgumentException;
use Throwable;

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../Services/Database.php';
require_once __DIR__ . '/../Services/Auth.php';
require_once __DIR__ . '/../Services/Csrf.php';
require_once __DIR__ . '/../Services/DeliveryManagement.php';
require_once __DIR__ . '/../Services/DriverAdminManagement.php';
require_once __DIR__ . '/../Services/OperationsOverview.php';
require_once __DIR__ . '/../Services/UserManagement.php';

/**
 * Mwanjelwa Kiganjani - Admin Controller
 *
 * Every method starts with Auth::requireRole('admin'), so access is
 * enforced on the server before any database query or HTML output.
 */
class AdminController
{
    private const DELIVERY_FORM_TOKEN_KEY = 'admin_create_delivery_token';
    private const EDIT_DELIVERY_FORM_TOKEN_PREFIX = 'admin_edit_delivery_token_';
    private const CANCEL_DELIVERY_FORM_TOKEN_PREFIX = 'admin_cancel_delivery_token_';
    private const EDIT_DRIVER_FORM_TOKEN_PREFIX = 'admin_edit_driver_token_';

    public function dashboard(): void
    {
        Auth::requireRole('admin');

        $pdo = Database::connect();
        $users = new UserManagement($pdo);
        $overview = new OperationsOverview($pdo);

        $deliveryCounts = $overview->deliveryCounts();
        $liveDeliveries = $overview->liveDeliveries(10);
        $recentActivities = $overview->recentActivities(8);
        $driverSummary = $overview->driverSummary();
        $recentUsers = $users->recentUsers(5);
        $flash = $this->pullFlash();

        $pageTitle = 'Operations Overview';
        $activeNav = 'Dashboard';
        $navItems = $this->adminNav();
        $userName = $this->adminName();

        $this->renderHeader($pageTitle, $navItems, $activeNav, $userName, $flash);
        require __DIR__ . '/../Views/admin/dashboard.php';
        require __DIR__ . '/../Views/layouts/portal_footer.php';
    }

    public function createDelivery(): void
    {
        Auth::requireRole('admin');

        $deliveries = new DeliveryManagement(Database::connect());
        $form = $this->deliveryFormInput([]);
        $errors = [];
        $isPost = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
        $formToken = $isPost ? '' : $this->issueDeliveryFormToken();

        if ($isPost) {
            $token = isset($_POST['_csrf_token']) ? (string) $_POST['_csrf_token'] : null;

            if (!Csrf::verify($token)) {
                $this->abort(403, 'Invalid security token. Please go back and try again.');
            }

            $submittedFormToken = isset($_POST['_form_token']) ? (string) $_POST['_form_token'] : '';
            $expectedFormToken = (string) ($_SESSION[self::DELIVERY_FORM_TOKEN_KEY] ?? '');
            unset($_SESSION[self::DELIVERY_FORM_TOKEN_KEY]);

            if ($expectedFormToken === '' || !hash_equals($expectedFormToken, $submittedFormToken)) {
                $this->setFlash(
                    'error',
                    'This form was already submitted. Please review the dashboard before trying again.'
                );
                $this->redirectToDashboard();
            }

            $form = $this->deliveryFormInput($_POST);
            $validation = $deliveries->validateManualDelivery($form);
            $errors = $validation['errors'];

            if ($errors === []) {
                try {
                    $adminId = (int) ($_SESSION['user_id'] ?? 0);
                    $deliveryId = $deliveries->createManualDelivery($validation['data'], $adminId);

                    $this->setFlash(
                        'success',
                        'Delivery #' . $deliveryId . ' was created successfully.'
                    );
                    $this->redirectToDashboard();
                } catch (Throwable $e) {
                    $this->logError($e, 'Admin delivery creation error');
                    $errors['_form'] = 'The delivery could not be created. Please try again.';
                }
            }

            $formToken = $this->issueDeliveryFormToken();
            http_response_code(422);
        }

        $flash = $this->pullFlash();

        $pageTitle = 'Create Delivery';
        $activeNav = 'Create Delivery';
        $navItems = $this->adminNav();
        $userName = $this->adminName();
        $formAction = app_url('admin/create-delivery.php');
        $cancelUrl = app_url('admin/dashboard.php');

        $this->renderHeader($pageTitle, $navItems, $activeNav, $userName, $flash);
        require __DIR__ . '/../Views/admin/create_delivery.php';
        require __DIR__ . '/../Views/layouts/portal_footer.php';
    }

    public function users(): void
    {
        Auth::requireRole('admin');

        $search = trim((string) ($_GET['q'] ?? ''));
        $users = new UserManagement(Database::connect());
        $userList = $users->listUsers($search);
        $flash = $this->pullFlash();

        $pageTitle = 'Users';
        $activeNav = 'Users';
        $navItems = $this->adminNav();
        $userName = $this->adminName();

        $this->renderHeader($pageTitle, $navItems, $activeNav, $userName, $flash);
        require __DIR__ . '/../Views/admin/users.php';
        require __DIR__ . '/../Views/layouts/portal_footer.php';
    }

    public function user(): void
    {
        Auth::requireRole('admin');

        $targetId = (int) ($_GET['id'] ?? 0);

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $this->handleRoleAssignment($targetId);
        }

        $users = new UserManagement(Database::connect());
        $target = $targetId > 0 ? $users->findUser($targetId) : null;

        if ($target === null) {
            $this->setFlash('error', 'User not found.');
            $this->redirectToUsers();
        }

        $flash = $this->pullFlash();

        $pageTitle = 'User Details';
        $activeNav = 'Users';
        $navItems = $this->adminNav();
        $userName = $this->adminName();

        $this->renderHeader($pageTitle, $navItems, $activeNav, $userName, $flash);
        require __DIR__ . '/../Views/admin/user.php';
        require __DIR__ . '/../Views/layouts/portal_footer.php';
    }

    public function drivers(): void
    {
        Auth::requireRole('admin');

        $rawSearch = $_GET['q'] ?? '';
        $search = is_string($rawSearch) ? trim($rawSearch) : '';
        if (function_exists('mb_substr')) {
            $search = mb_substr($search, 0, 100, 'UTF-8');
        } else {
            $search = substr($search, 0, 100);
        }

        $rawStatus = $_GET['status'] ?? '';
        $status = is_string($rawStatus) ? strtolower(trim($rawStatus)) : '';
        if (!in_array($status, ['pending', 'active', 'inactive', 'missing'], true)) {
            $status = '';
        }

        $page = filter_var(
            $_GET['page'] ?? 1,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );
        if ($page === false) {
            $page = 1;
        }

        $perPage = 20;

        try {
            $drivers = new DriverAdminManagement(Database::connect());
            $total = $drivers->countDrivers($search, $status);
            $totalPages = max(1, (int) ceil($total / $perPage));

            if ($page > $totalPages) {
                $page = $totalPages;
            }

            $offset = ($page - 1) * $perPage;
            $driverList = $total > 0
                ? $drivers->listDrivers($search, $status, $perPage, $offset)
                : [];
        } catch (Throwable $e) {
            $this->logError($e, 'Admin drivers list error');
            $this->abort(500, 'The drivers could not be loaded. Please try again.');
        }

        $flash = $this->pullFlash();

        $pageTitle = 'Drivers';
        $activeNav = 'Drivers';
        $navItems = $this->adminNav();
        $userName = $this->adminName();

        $this->renderHeader($pageTitle, $navItems, $activeNav, $userName, $flash);
        require __DIR__ . '/../Views/admin/drivers.php';
        require __DIR__ . '/../Views/layouts/portal_footer.php';
    }

    public function driver(): void
    {
        Auth::requireRole('admin');

        $rawId = $_GET['id'] ?? null;
        $targetId = filter_var(
            $rawId,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        if ($targetId === false) {
            $this->setFlash('error', 'Driver not found.');
            $this->redirectToDrivers();
        }

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $this->handleDriverStatusChange((int) $targetId);
        }

        try {
            $drivers = new DriverAdminManagement(Database::connect());
            $driver = $drivers->findDriver((int) $targetId);
        } catch (Throwable $e) {
            $this->logError($e, 'Admin driver details error');
            $this->abort(500, 'The driver could not be loaded. Please try again.');
        }

        if ($driver === null) {
            $this->setFlash('error', 'Driver not found.');
            $this->redirectToDrivers();
        }

        $flash = $this->pullFlash();

        $pageTitle = ($driver['driver_status'] ?? '') === 'pending'
            ? 'Driver Profile Review'
            : 'Driver Profile';
        $activeNav = 'Drivers';
        $navItems = $this->adminNav();
        $userName = $this->adminName();

        $this->renderHeader($pageTitle, $navItems, $activeNav, $userName, $flash);
        require __DIR__ . '/../Views/admin/driver.php';
        require __DIR__ . '/../Views/layouts/portal_footer.php';
    }

    public function editDriver(): void
    {
        Auth::requireRole('admin');

        $rawId = $_GET['id'] ?? null;
        $userId = filter_var(
            $rawId,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        if ($userId === false) {
            $this->setFlash('error', 'Driver not found.');
            $this->redirectToDrivers();
        }

        $userId = (int) $userId;

        try {
            $drivers = new DriverAdminManagement(Database::connect());
            $driver = $drivers->findDriver($userId);
        } catch (Throwable $e) {
            $this->logError($e, 'Admin driver profile edit load error');
            $this->abort(500, 'The driver profile could not be loaded. Please try again.');
        }

        if ($driver === null) {
            $this->setFlash('error', 'Driver not found.');
            $this->redirectToDrivers();
        }

        if (($driver['driver_profile_id'] ?? null) === null) {
            $this->setFlash(
                'error',
                'This driver has no driver profile to edit. No profile was created automatically.'
            );
            $this->redirectToDriver($userId);
        }

        $isPost = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
        $form = $this->driverProfileFormInput($driver);
        $errors = [];
        $formToken = !$isPost ? $this->issueEditDriverFormToken($userId) : '';
        $expectedUpdatedAt = (string) ($driver['profile_updated_at'] ?? '');

        if ($isPost) {
            $token = isset($_POST['_csrf_token']) ? (string) $_POST['_csrf_token'] : null;

            if (!Csrf::verify($token)) {
                $this->abort(403, 'Invalid security token. Please go back and try again.');
            }

            $submittedFormToken = isset($_POST['_form_token']) ? (string) $_POST['_form_token'] : '';
            $formTokenKey = $this->editDriverFormTokenKey($userId);
            $expectedFormToken = (string) ($_SESSION[$formTokenKey] ?? '');
            unset($_SESSION[$formTokenKey]);

            if ($expectedFormToken === ''
                || !hash_equals($expectedFormToken, $submittedFormToken)
            ) {
                $this->setFlash(
                    'error',
                    'This form was already submitted. Please review the driver before trying again.'
                );
                $this->redirectToDriver($userId);
            }

            $form = $this->driverProfileFormInput($_POST);
            $expectedUpdatedAt = trim((string) ($_POST['_expected_updated_at'] ?? ''));
            $validation = $drivers->validateProfileUpdate($form);
            $errors = $validation['errors'];

            if ($errors === []) {
                try {
                    $adminId = (int) ($_SESSION['user_id'] ?? 0);
                    $result = $drivers->updateDriverProfile(
                        $userId,
                        $validation['data'],
                        $adminId,
                        $expectedUpdatedAt
                    );

                    if ($result['outcome'] === 'updated') {
                        $this->setFlash('success', 'The driver profile was updated successfully.');
                        $this->redirectToDriver($userId);
                    }

                    if ($result['outcome'] === 'unchanged') {
                        $this->setFlash('success', 'No changes were made.');
                        $this->redirectToDriver($userId);
                    }

                    if ($result['outcome'] === 'conflict') {
                        $this->setFlash(
                            'error',
                            'This driver profile was changed by another admin while you were editing. '
                            . 'Review the latest details and try again.'
                        );
                        $this->redirectToDriver($userId);
                    }

                    $this->setFlash('error', 'Driver profile not found.');
                    $this->redirectToDrivers();
                } catch (InvalidArgumentException $e) {
                    $errors['_form'] = $e->getMessage();
                } catch (Throwable $e) {
                    $this->logError($e, 'Admin driver profile update error');
                    $errors['_form'] = 'The driver profile could not be updated. Please try again.';
                }
            }

            $formToken = $this->issueEditDriverFormToken($userId);
            http_response_code(422);
        }

        $flash = $this->pullFlash();
        $pageTitle = 'Edit Driver Profile';
        $activeNav = 'Drivers';
        $navItems = $this->adminNav();
        $userName = $this->adminName();
        $formAction = app_url('admin/edit-driver.php?id=' . $userId);
        $cancelUrl = app_url('admin/driver.php?id=' . $userId);

        $this->renderHeader($pageTitle, $navItems, $activeNav, $userName, $flash);
        require __DIR__ . '/../Views/admin/edit_driver.php';
        require __DIR__ . '/../Views/layouts/portal_footer.php';
    }

    public function delivery(): void
    {
        Auth::requireRole('admin');

        $rawId = $_GET['id'] ?? null;
        $deliveryId = filter_var(
            $rawId,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        $delivery = null;
        $assignment = null;

        if ($deliveryId !== false) {
            try {
                $deliveries = new DeliveryManagement(Database::connect());
                $delivery = $deliveries->findDelivery($deliveryId);

                if ($delivery !== null) {
                    $assignment = $deliveries->findCurrentAssignment($deliveryId);
                }
            } catch (Throwable $e) {
                $this->logError($e, 'Admin delivery details error');
                $this->abort(500, 'The delivery could not be loaded. Please try again.');
            }
        }

        if ($delivery === null) {
            http_response_code(404);
        }

        $flash = $delivery === null ? null : $this->pullFlash();
        $pageTitle = $delivery === null
            ? 'Delivery Not Found'
            : 'Delivery #' . (int) $delivery['id'];
        $activeNav = 'Deliveries';
        $navItems = $this->adminNav();
        $userName = $this->adminName();

        $this->renderHeader($pageTitle, $navItems, $activeNav, $userName, $flash);
        require __DIR__ . '/../Views/admin/delivery.php';
        require __DIR__ . '/../Views/layouts/portal_footer.php';
    }

    public function editDelivery(): void
    {
        Auth::requireRole('admin');

        $rawId = $_GET['id'] ?? null;
        $deliveryId = filter_var(
            $rawId,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        $delivery = null;
        $assignment = null;

        if ($deliveryId !== false) {
            try {
                $deliveries = new DeliveryManagement(Database::connect());
                $delivery = $deliveries->findDelivery($deliveryId);

                if ($delivery !== null) {
                    $assignment = $deliveries->findCurrentAssignment($deliveryId);
                }
            } catch (Throwable $e) {
                $this->logError($e, 'Admin delivery edit load error');
                $this->abort(500, 'The delivery could not be loaded. Please try again.');
            }
        }

        if ($delivery === null) {
            http_response_code(404);

            $flash = null;
            $pageTitle = 'Delivery Not Found';
            $activeNav = 'Deliveries';
            $navItems = $this->adminNav();
            $userName = $this->adminName();
            $form = [];
            $errors = [];
            $formToken = '';
            $formAction = '';
            $cancelUrl = app_url('admin/deliveries.php');
            $expectedUpdatedAt = '';
            $canEdit = false;
            $editBlockedReason = '';

            $this->renderHeader($pageTitle, $navItems, $activeNav, $userName, $flash);
            require __DIR__ . '/../Views/admin/edit_delivery.php';
            require __DIR__ . '/../Views/layouts/portal_footer.php';

            return;
        }

        $deliveryId = (int) $delivery['id'];
        $statusKey = strtolower(trim((string) $delivery['delivery_status']));
        $canEdit = $statusKey === 'pending';
        $editBlockedReason = $canEdit
            ? ''
            : 'This delivery cannot be edited because its status is '
                . $this->deliveryStatusLabel($statusKey) . '.';

        $isPost = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
        $form = $this->deliveryFormInput($delivery);
        $errors = [];
        $formToken = $canEdit && !$isPost ? $this->issueEditDeliveryFormToken($deliveryId) : '';
        $expectedUpdatedAt = (string) ($delivery['updated_at'] ?? '');

        if ($isPost) {
            $token = isset($_POST['_csrf_token']) ? (string) $_POST['_csrf_token'] : null;

            if (!Csrf::verify($token)) {
                $this->abort(403, 'Invalid security token. Please go back and try again.');
            }

            if (!$canEdit) {
                $this->setFlash('error', $editBlockedReason);
                $this->redirectToDelivery($deliveryId);
            }

            $submittedFormToken = isset($_POST['_form_token']) ? (string) $_POST['_form_token'] : '';
            $formTokenKey = $this->editDeliveryFormTokenKey($deliveryId);
            $expectedFormToken = (string) ($_SESSION[$formTokenKey] ?? '');
            unset($_SESSION[$formTokenKey]);

            if ($expectedFormToken === '' || !hash_equals($expectedFormToken, $submittedFormToken)) {
                $this->setFlash(
                    'error',
                    'This form was already submitted. Please review the delivery before trying again.'
                );
                $this->redirectToDelivery($deliveryId);
            }

            $form = $this->deliveryFormInput($_POST);
            $expectedUpdatedAt = trim((string) ($_POST['_expected_updated_at'] ?? ''));
            $validation = $deliveries->validateManualDelivery($form);
            $errors = $validation['errors'];

            if ($errors === []) {
                try {
                    $adminId = (int) ($_SESSION['user_id'] ?? 0);
                    $result = $deliveries->updatePendingDelivery(
                        $deliveryId,
                        $validation['data'],
                        $adminId,
                        $expectedUpdatedAt
                    );

                    if ($result['outcome'] === 'updated') {
                        $this->setFlash(
                            'success',
                            'Delivery #' . $deliveryId . ' was updated successfully.'
                        );
                        $this->redirectToDelivery($deliveryId);
                    }

                    if ($result['outcome'] === 'unchanged') {
                        $this->setFlash('success', 'No changes were made.');
                        $this->redirectToDelivery($deliveryId);
                    }

                    if ($result['outcome'] === 'conflict') {
                        $this->setFlash(
                            'error',
                            'This delivery was changed by another admin while you were editing. '
                            . 'Review the latest details and try again.'
                        );
                        $this->redirectToDelivery($deliveryId);
                    }

                    if ($result['outcome'] === 'not_editable') {
                        $this->setFlash(
                            'error',
                            'This delivery can no longer be edited because its status changed.'
                        );
                        $this->redirectToDelivery($deliveryId);
                    }

                    $this->setFlash('error', 'Delivery not found.');
                    $this->redirectToDeliveries();
                } catch (InvalidArgumentException $e) {
                    $errors['_form'] = $e->getMessage();
                } catch (Throwable $e) {
                    $this->logError($e, 'Admin delivery update error');
                    $errors['_form'] = 'The delivery could not be updated. Please try again.';
                }
            }

            $formToken = $this->issueEditDeliveryFormToken($deliveryId);
            http_response_code(422);
        }

        $flash = $this->pullFlash();
        $pageTitle = 'Edit Delivery #' . $deliveryId;
        $activeNav = 'Deliveries';
        $navItems = $this->adminNav();
        $userName = $this->adminName();
        $formAction = app_url('admin/edit-delivery.php?id=' . $deliveryId);
        $cancelUrl = app_url('admin/delivery.php?id=' . $deliveryId);

        $this->renderHeader($pageTitle, $navItems, $activeNav, $userName, $flash);
        require __DIR__ . '/../Views/admin/edit_delivery.php';
        require __DIR__ . '/../Views/layouts/portal_footer.php';
    }

    public function cancelDelivery(): void
    {
        Auth::requireRole('admin');

        $rawId = $_GET['id'] ?? null;
        $deliveryId = filter_var(
            $rawId,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        $delivery = null;
        $assignment = null;
        $deliveries = null;

        if ($deliveryId !== false) {
            try {
                $deliveries = new DeliveryManagement(Database::connect());
                $delivery = $deliveries->findDelivery($deliveryId);

                if ($delivery !== null) {
                    $assignment = $deliveries->findCurrentAssignment($deliveryId);
                }
            } catch (Throwable $e) {
                $this->logError($e, 'Admin delivery cancel load error');
                $this->abort(500, 'The delivery could not be loaded. Please try again.');
            }
        }

        if ($delivery === null) {
            http_response_code(404);

            $flash = null;
            $pageTitle = 'Delivery Not Found';
            $activeNav = 'Deliveries';
            $navItems = $this->adminNav();
            $userName = $this->adminName();
            $reason = '';
            $errors = [];
            $formToken = '';
            $formAction = '';
            $backUrl = app_url('admin/deliveries.php');
            $canCancel = false;
            $cancelBlockedReason = '';
            $confirmed = false;

            $this->renderHeader($pageTitle, $navItems, $activeNav, $userName, $flash);
            require __DIR__ . '/../Views/admin/cancel_delivery.php';
            require __DIR__ . '/../Views/layouts/portal_footer.php';

            return;
        }

        $deliveryId = (int) $delivery['id'];
        $statusKey = strtolower(trim((string) $delivery['delivery_status']));
        $canCancel = DeliveryManagement::isCancellableStatus($statusKey);
        $cancelBlockedReason = $canCancel
            ? ''
            : 'This delivery cannot be cancelled because its status is '
                . $this->deliveryStatusLabel($statusKey) . '.';

        $isPost = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
        $reason = '';
        $errors = [];
        $confirmed = false;
        $formToken = $canCancel && !$isPost ? $this->issueCancelDeliveryFormToken($deliveryId) : '';

        if ($isPost) {
            $token = isset($_POST['_csrf_token']) ? (string) $_POST['_csrf_token'] : null;

            if (!Csrf::verify($token)) {
                $this->abort(403, 'Invalid security token. Please go back and try again.');
            }

            if (!$canCancel) {
                $this->setFlash('error', $cancelBlockedReason);
                $this->redirectToDelivery($deliveryId);
            }

            $submittedFormToken = isset($_POST['_form_token']) ? (string) $_POST['_form_token'] : '';
            $formTokenKey = $this->cancelDeliveryFormTokenKey($deliveryId);
            $expectedFormToken = (string) ($_SESSION[$formTokenKey] ?? '');

            if ($expectedFormToken === '' || !hash_equals($expectedFormToken, $submittedFormToken)) {
                $this->setFlash(
                    'error',
                    'This form was already submitted. Please review the delivery before trying again.'
                );
                $this->redirectToDelivery($deliveryId);
            }

            unset($_SESSION[$formTokenKey]);

            $reason = trim((string) ($_POST['cancellation_reason'] ?? ''));
            $confirmed = ($_POST['confirm_cancel'] ?? '') === '1';
            $validation = $deliveries->validateCancellationReason($reason);
            $errors = $validation['errors'];

            if (!$confirmed) {
                $errors['confirm_cancel'] = 'Please confirm that you want to cancel this delivery.';
            }

            if ($errors === []) {
                try {
                    $adminId = (int) ($_SESSION['user_id'] ?? 0);
                    $result = $deliveries->cancelDelivery(
                        $deliveryId,
                        $validation['reason'],
                        $adminId
                    );

                    if ($result['outcome'] === 'cancelled') {
                        $this->setFlash(
                            'success',
                            'Delivery #' . $deliveryId . ' was cancelled successfully.'
                        );
                        $this->redirectToDelivery($deliveryId);
                    }

                    if ($result['outcome'] === 'not_cancellable') {
                        $this->setFlash(
                            'error',
                            'This delivery can no longer be cancelled because its status changed.'
                        );
                        $this->redirectToDelivery($deliveryId);
                    }

                    $this->setFlash('error', 'Delivery not found.');
                    $this->redirectToDeliveries();
                } catch (InvalidArgumentException $e) {
                    $errors['_form'] = $e->getMessage();
                } catch (Throwable $e) {
                    $this->logError($e, 'Admin delivery cancellation error');
                    $errors['_form'] = 'The delivery could not be cancelled. Please try again.';
                }
            }

            $formToken = $this->issueCancelDeliveryFormToken($deliveryId);
            http_response_code(422);
        }

        $flash = $this->pullFlash();
        $pageTitle = 'Cancel Delivery #' . $deliveryId;
        $activeNav = 'Deliveries';
        $navItems = $this->adminNav();
        $userName = $this->adminName();
        $formAction = app_url('admin/cancel-delivery.php?id=' . $deliveryId);
        $backUrl = app_url('admin/delivery.php?id=' . $deliveryId);

        $this->renderHeader($pageTitle, $navItems, $activeNav, $userName, $flash);
        require __DIR__ . '/../Views/admin/cancel_delivery.php';
        require __DIR__ . '/../Views/layouts/portal_footer.php';
    }

    public function deliveries(): void
    {
        Auth::requireRole('admin');

        $rawSearch = $_GET['q'] ?? '';
        $search = is_string($rawSearch) ? trim($rawSearch) : '';
        if (function_exists('mb_substr')) {
            $search = mb_substr($search, 0, 100, 'UTF-8');
        } else {
            $search = substr($search, 0, 100);
        }

        $rawStatus = $_GET['status'] ?? '';
        $status = is_string($rawStatus) ? strtolower(trim($rawStatus)) : '';
        if (!in_array($status, ['pending', 'in_transit', 'delivered', 'cancelled'], true)) {
            $status = '';
        }

        $page = filter_var(
            $_GET['page'] ?? 1,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );
        if ($page === false) {
            $page = 1;
        }

        $perPage = 20;

        try {
            $deliveries = new DeliveryManagement(Database::connect());
            $total = $deliveries->countDeliveries($search, $status);
            $totalPages = max(1, (int) ceil($total / $perPage));

            if ($page > $totalPages) {
                $page = $totalPages;
            }

            $offset = ($page - 1) * $perPage;
            $deliveryList = $total > 0
                ? $deliveries->listDeliveries($search, $status, $perPage, $offset)
                : [];
        } catch (Throwable $e) {
            $this->logError($e, 'Admin deliveries list error');
            $this->abort(500, 'The deliveries could not be loaded. Please try again.');
        }

        $flash = $this->pullFlash();

        $pageTitle = 'Deliveries';
        $activeNav = 'Deliveries';
        $navItems = $this->adminNav();
        $userName = $this->adminName();

        $this->renderHeader($pageTitle, $navItems, $activeNav, $userName, $flash);
        require __DIR__ . '/../Views/admin/deliveries.php';
        require __DIR__ . '/../Views/layouts/portal_footer.php';
    }

    private function handleRoleAssignment(int $targetId): void
    {
        $token = isset($_POST['_csrf_token']) ? (string) $_POST['_csrf_token'] : null;

        if (!Csrf::verify($token)) {
            $this->abort(403, 'Invalid security token. Please go back and try again.');
        }

        $action = (string) ($_POST['action'] ?? '');

        if ($action !== 'assign_role') {
            $this->setFlash('error', 'Unknown action.');
            $this->redirectToUser($targetId);
        }

        $role = strtolower(trim((string) ($_POST['role'] ?? '')));

        if ($role === 'admin' && ($_POST['confirm_admin'] ?? '') !== '1') {
            $this->setFlash(
                'error',
                'Please confirm that you want to grant Admin access.'
            );
            $this->redirectToUser($targetId);
        }

        try {
            $adminId = (int) ($_SESSION['user_id'] ?? 0);
            $users = new UserManagement(Database::connect());
            $users->assignRole($targetId, $role, $adminId);

            $this->setFlash(
                'success',
                'The ' . $role . ' role was assigned successfully. '
                . 'The user must log out and log in again to see the new role.'
            );
        } catch (InvalidArgumentException $e) {
            $this->setFlash('error', $e->getMessage());
        } catch (Throwable $e) {
            $this->logError($e, 'Admin role assignment error');
            $this->setFlash(
                'error',
                'Something went wrong while assigning the role. Please try again.'
            );
        }

        $this->redirectToUser($targetId);
    }

    private function handleDriverStatusChange(int $targetId): void
    {
        $token = isset($_POST['_csrf_token']) ? (string) $_POST['_csrf_token'] : null;

        if (!Csrf::verify($token)) {
            $this->abort(403, 'Invalid security token. Please go back and try again.');
        }

        $action = strtolower(trim((string) ($_POST['action'] ?? '')));

        if (!in_array($action, ['approve', 'activate', 'deactivate'], true)) {
            $this->setFlash('error', 'Unknown action.');
            $this->redirectToDriver($targetId);
        }

        try {
            $adminId = (int) ($_SESSION['user_id'] ?? 0);
            $drivers = new DriverAdminManagement(Database::connect());
            $result = match ($action) {
                'approve' => $drivers->approveDriver($targetId, $adminId),
                'activate' => $drivers->activateDriver($targetId, $adminId),
                default => $drivers->deactivateDriver($targetId, $adminId),
            };

            if ($result['outcome'] === 'approved') {
                $this->setFlash('success', 'The driver was approved successfully.');
            } elseif ($result['outcome'] === 'activated') {
                $this->setFlash('success', 'The driver was activated successfully.');
            } elseif ($result['outcome'] === 'deactivated') {
                $this->setFlash('success', 'The driver was deactivated successfully.');
            } else {
                $this->setFlash('success', 'No changes were made.');
            }
        } catch (InvalidArgumentException $e) {
            $this->setFlash('error', $e->getMessage());
        } catch (Throwable $e) {
            $this->logError($e, 'Admin driver status change error');
            $this->setFlash(
                'error',
                'Something went wrong while updating the driver. Please try again.'
            );
        }

        $this->redirectToDriver($targetId);
    }

    /** @return array<string, string> */
    private function adminNav(): array
    {
        return [
            'Dashboard' => app_url('admin/dashboard.php'),
            'Create Delivery' => app_url('admin/create-delivery.php'),
            'Deliveries' => app_url('admin/deliveries.php'),
            'Drivers' => app_url('admin/drivers.php'),
            'Users' => app_url('admin/users.php'),
            'Logout' => app_url('logout.php'),
        ];
    }

    private function adminName(): string
    {
        return (string) ($_SESSION['full_name'] ?? 'Administrator');
    }

    /**
     * @param array<string, string> $navItems
     * @param array<string, string>|null $flash
     */
    private function renderHeader(
        string $pageTitle,
        array $navItems,
        string $activeNav,
        string $userName,
        ?array $flash
    ): void {
        require __DIR__ . '/../Views/layouts/portal_header.php';
    }

    private function setFlash(string $type, string $message): void
    {
        $_SESSION['admin_flash'] = [
            'type' => $type,
            'message' => $message,
        ];
    }

    /** @return array<string, string>|null */
    private function pullFlash(): ?array
    {
        $flash = $_SESSION['admin_flash'] ?? null;
        unset($_SESSION['admin_flash']);

        if (!is_array($flash) || !isset($flash['type'], $flash['message'])) {
            return null;
        }

        return [
            'type' => (string) $flash['type'],
            'message' => (string) $flash['message'],
        ];
    }

    /**
     * @param array<string, mixed> $source
     * @return array<string, string>
     */
    private function deliveryFormInput(array $source): array
    {
        return [
            'customer_name' => trim((string) ($source['customer_name'] ?? '')),
            'customer_phone' => trim((string) ($source['customer_phone'] ?? '')),
            'cargo_type' => trim((string) ($source['cargo_type'] ?? '')),
            'cargo_value' => trim((string) ($source['cargo_value'] ?? '')),
            'pickup_address' => trim((string) ($source['pickup_address'] ?? '')),
            'pickup_description' => trim((string) ($source['pickup_description'] ?? '')),
            'destination_address' => trim((string) ($source['destination_address'] ?? '')),
            'destination_description' => trim((string) ($source['destination_description'] ?? '')),
        ];
    }

    private function issueDeliveryFormToken(): string
    {
        $token = bin2hex(random_bytes(24));
        $_SESSION[self::DELIVERY_FORM_TOKEN_KEY] = $token;

        return $token;
    }

    /**
     * @param array<string, mixed> $source
     * @return array<string, string>
     */
    private function driverProfileFormInput(array $source): array
    {
        return [
            'residential_address' => trim((string) ($source['residential_address'] ?? '')),
            'license_number' => trim((string) ($source['license_number'] ?? '')),
        ];
    }

    private function editDeliveryFormTokenKey(int $deliveryId): string
    {
        return self::EDIT_DELIVERY_FORM_TOKEN_PREFIX . $deliveryId;
    }

    private function issueEditDeliveryFormToken(int $deliveryId): string
    {
        $token = bin2hex(random_bytes(24));
        $_SESSION[$this->editDeliveryFormTokenKey($deliveryId)] = $token;

        return $token;
    }

    private function editDriverFormTokenKey(int $userId): string
    {
        return self::EDIT_DRIVER_FORM_TOKEN_PREFIX . $userId;
    }

    private function issueEditDriverFormToken(int $userId): string
    {
        $token = bin2hex(random_bytes(24));
        $_SESSION[$this->editDriverFormTokenKey($userId)] = $token;

        return $token;
    }

    private function cancelDeliveryFormTokenKey(int $deliveryId): string
    {
        return self::CANCEL_DELIVERY_FORM_TOKEN_PREFIX . $deliveryId;
    }

    private function issueCancelDeliveryFormToken(int $deliveryId): string
    {
        $token = bin2hex(random_bytes(24));
        $_SESSION[$this->cancelDeliveryFormTokenKey($deliveryId)] = $token;

        return $token;
    }

    private function deliveryStatusLabel(string $status): string
    {
        $labels = [
            'pending' => 'Pending',
            'in_transit' => 'In Transit',
            'delivered' => 'Delivered',
            'cancelled' => 'Cancelled',
        ];

        return $labels[$status]
            ?? ($status !== '' ? ucwords(str_replace('_', ' ', $status)) : 'Unknown');
    }

    private function redirectToDashboard(): void
    {
        header('Location: ' . app_url('admin/dashboard.php'));
        exit;
    }

    private function redirectToDelivery(int $deliveryId): void
    {
        header('Location: ' . app_url('admin/delivery.php?id=' . $deliveryId));
        exit;
    }

    private function redirectToDeliveries(): void
    {
        header('Location: ' . app_url('admin/deliveries.php'));
        exit;
    }

    private function redirectToDriver(int $targetId): void
    {
        header('Location: ' . app_url('admin/driver.php?id=' . $targetId));
        exit;
    }

    private function redirectToDrivers(): void
    {
        header('Location: ' . app_url('admin/drivers.php'));
        exit;
    }

    private function redirectToUser(int $targetId): void
    {
        header('Location: ' . app_url('admin/user.php?id=' . $targetId));
        exit;
    }

    private function redirectToUsers(): void
    {
        header('Location: ' . app_url('admin/users.php'));
        exit;
    }

    private function abort(int $status, string $message): void
    {
        http_response_code($status);
        header('Content-Type: text/html; charset=UTF-8');

        $safeMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

        echo '<!DOCTYPE html>' . "\n"
            . '<html lang="en">' . "\n"
            . '<head>' . "\n"
            . '    <meta charset="UTF-8">' . "\n"
            . '    <title>Request rejected</title>' . "\n"
            . '</head>' . "\n"
            . '<body>' . "\n"
            . '    <h1>Request rejected</h1>' . "\n"
            . '    <p>' . $safeMessage . '</p>' . "\n"
            . '</body>' . "\n"
            . '</html>';

        exit;
    }

    private function logError(Throwable $e, string $context = 'Admin error'): void
    {
        $logDir = __DIR__ . '/../../storage/logs';
        $logFile = $logDir . '/errors.log';

        if (!is_dir($logDir)) {
            mkdir($logDir, 0775, true);
        }

        $timestamp = date('Y-m-d H:i:s');
        $message = sprintf(
            "[%s] %s: %s in %s on line %s\n",
            $timestamp,
            $context,
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
        );

        @file_put_contents($logFile, $message, FILE_APPEND | LOCK_EX);
    }
}
