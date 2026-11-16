<?php

use App\Services\Csrf;

$availableDeliveries = $availableDeliveries ?? [];

$escape = static function (mixed $value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

$formatDate = static function (mixed $value): string {
    $timestamp = strtotime((string) $value);

    return $timestamp === false ? 'Unknown' : date('d M Y H:i', $timestamp);
};
?>
<section class="page-heading">
    <div>
        <p class="breadcrumb">
            <span>Driver Portal</span>
            <span aria-hidden="true">/</span>
            <span>Available Deliveries</span>
        </p>
        <h1>Available Deliveries</h1>
        <p class="page-intro">Accept a pending delivery to make it your active delivery.</p>
    </div>
</section>

<section class="panel">
    <div class="panel-heading">
        <h2>Pending deliveries</h2>
        <span class="panel-meta">Customer contact details are shown after acceptance.</span>
    </div>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th scope="col">Delivery</th>
                    <th scope="col">Cargo</th>
                    <th scope="col">Route</th>
                    <th scope="col">Created</th>
                    <th scope="col">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($availableDeliveries === []): ?>
                    <tr>
                        <td colspan="5">
                            <div class="empty-state">
                                <img class="empty-state-art" src="<?php echo $escape(app_url('assets/images/empty-deliveries.svg')); ?>" alt="" aria-hidden="true">
                                <strong>No available deliveries right now.</strong>
                                <p>New pending deliveries will appear here.</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($availableDeliveries as $delivery): ?>
                        <tr>
                            <td><strong>#<?php echo (int) $delivery['id']; ?></strong></td>
                            <td><?php echo $escape($delivery['cargo_type'] ?? ''); ?></td>
                            <td>
                                <span class="table-line"><?php echo $escape($delivery['pickup_address'] ?? ''); ?></span>
                                <span class="table-subtext">to: <?php echo $escape($delivery['destination_address'] ?? ''); ?></span>
                            </td>
                            <td><?php echo $escape($formatDate($delivery['created_at'] ?? '')); ?></td>
                            <td>
                                <form class="inline-action-form" method="post" action="<?php echo $escape(app_url('driver/accept-delivery.php')); ?>">
                                    <?php echo Csrf::field(); ?>
                                    <input type="hidden" name="delivery_id" value="<?php echo (int) $delivery['id']; ?>">
                                    <button type="submit" class="btn btn--primary">Accept Delivery</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <p class="form-note">
        <a class="text-link" href="<?php echo $escape(app_url('driver/dashboard.php')); ?>">Back to dashboard</a>
    </p>
</section>
