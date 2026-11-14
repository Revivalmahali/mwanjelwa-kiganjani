<?php

/**
 * Mwanjelwa Kiganjani - Admin Users List View
 *
 * Expects: $userList, $search
 */
?>
<section class="page-heading">
    <div>
        <h1>Users</h1>
        <p class="page-intro">View registered users and their assigned roles.</p>
    </div>
</section>

<section class="panel">
    <form class="search-form" method="get" action="<?php echo htmlspecialchars(app_url('admin/users.php'), ENT_QUOTES, 'UTF-8'); ?>">
        <label class="visually-hidden" for="user-search">Search users</label>
        <input
            type="search"
            id="user-search"
            name="q"
            value="<?php echo htmlspecialchars((string) $search, ENT_QUOTES, 'UTF-8'); ?>"
            placeholder="Search by name, phone or email"
        >
        <button type="submit" class="btn btn--primary">Search</button>
        <?php if ($search !== ''): ?>
            <a class="btn btn--secondary" href="<?php echo htmlspecialchars(app_url('admin/users.php'), ENT_QUOTES, 'UTF-8'); ?>">Clear</a>
        <?php endif; ?>
    </form>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th scope="col">User</th>
                    <th scope="col">Contact</th>
                    <th scope="col">Roles</th>
                    <th scope="col">Status</th>
                    <th scope="col">Joined</th>
                    <th scope="col">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($userList === []): ?>
                    <tr>
                        <td colspan="6" class="empty-state">
                            <?php echo $search !== '' ? 'No users matched your search.' : 'No users found.'; ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($userList as $listedUser): ?>
                        <?php $isActive = $listedUser['account_status'] === 'active'; ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars((string) $listedUser['full_name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                <span class="table-subtext">#<?php echo (int) $listedUser['id']; ?></span>
                            </td>
                            <td>
                                <span class="table-line"><?php echo htmlspecialchars((string) $listedUser['phone'], ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php if (($listedUser['email'] ?? '') !== ''): ?>
                                    <span class="table-subtext"><?php echo htmlspecialchars((string) $listedUser['email'], ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($listedUser['roles'] === []): ?>
                                    <span class="muted">No roles</span>
                                <?php else: ?>
                                    <span class="role-badges">
                                        <?php foreach ($listedUser['roles'] as $role): ?>
                                            <span class="badge badge--role"><?php echo htmlspecialchars((string) $role, ENT_QUOTES, 'UTF-8'); ?></span>
                                        <?php endforeach; ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge <?php echo $isActive ? 'badge--active' : 'badge--inactive'; ?>">
                                    <?php echo htmlspecialchars((string) $listedUser['account_status'], ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars(date('d M Y', strtotime((string) $listedUser['created_at'])), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <a class="text-link" href="<?php echo htmlspecialchars(app_url('admin/user.php?id=' . (int) $listedUser['id']), ENT_QUOTES, 'UTF-8'); ?>">View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
