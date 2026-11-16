<?php

$completedDeliveries = $completedDeliveries ?? [];

$escape = static function (mixed $value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
?>
<section class="page-heading">
    <div>
        <p class="breadcrumb">
            <span>Driver Portal</span>
            <span aria-hidden="true">/</span>
            <span>Delivery History</span>
        </p>
        <h1>Delivery History</h1>
        <p class="page-intro">A record of deliveries you have completed.</p>
    </div>
</section>

<section class="panel">
    <?php if ($completedDeliveries === []): ?>
        <div class="panel-heading">
            <h2>No delivery history</h2>
        </div>
        <div class="empty-state">
            <img class="empty-state-art" src="<?php echo $escape(app_url('assets/images/empty-deliveries.svg')); ?>" alt="" aria-hidden="true">
            <strong>No completed deliveries yet</strong>
            <p>Completed deliveries will appear here.</p>
        </div>
    <?php else: ?>
        <div class="panel-heading">
            <h2>Completed deliveries</h2>
            <span class="panel-meta"><?php echo count($completedDeliveries); ?> most recent</span>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th scope="col">Delivery</th>
                        <th scope="col">Cargo</th>
                        <th scope="col">Route</th>
                        <th scope="col">Status</th>
                        <th scope="col">Completed</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($completedDeliveries as $delivery): ?>
                        <?php $completedAt = strtotime((string) ($delivery['completed_at'] ?? '')); ?>
                        <tr>
                            <td><strong>#<?php echo (int) ($delivery['id'] ?? 0); ?></strong></td>
                            <td><?php echo $escape($delivery['cargo_type'] ?? ''); ?></td>
                            <td>
                                <span class="table-line"><?php echo $escape($delivery['pickup_address'] ?? ''); ?></span>
                                <span class="table-subtext">to: <?php echo $escape($delivery['destination_address'] ?? ''); ?></span>
                            </td>
                            <td><span class="badge badge--delivered">Delivered</span></td>
                            <td><?php echo $escape($completedAt === false ? 'Unknown' : date('d M Y H:i', $completedAt)); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <p class="form-note">
        <a class="text-link" href="<?php echo $escape(app_url('driver/dashboard.php')); ?>">Back to dashboard</a>
    </p>
</section>
