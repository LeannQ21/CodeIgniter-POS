<!DOCTYPE html>
<html>

<head>
    <title>Users</title>
</head>

<body>
    <nav>
        <a href="<?= site_url('/') ?>">Home</a> |
        <a href="<?= site_url('about') ?>">About</a> |
        <a href="<?= site_url('customers') ?>">Customers</a> |
        <a href="<?= site_url('users') ?>">Users</a> |
        <a href="<?= site_url('products') ?>">Products</a> |
        <a href="<?= site_url('sales/new') ?>">Record Sale</a> |
        <a href="<?= site_url('sales') ?>">Sales History</a>
    </nav>

    <hr>
    <h1>User Accounts</h1>
    <?php if ($message = session()->getFlashdata('success')): ?>
        <p role="status" style="color: green;">
            <?= esc($message) ?>
        </p>
    <?php endif; ?>

    <?php if ($message = session()->getFlashdata('error')): ?>
        <p role="alert" style="color: red;">
            <?= esc($message) ?>
        </p>
    <?php endif; ?>

    <a href="<?= site_url('users/new') ?>">Add Staff Account</a>

    <br><br>

    <table border="1" cellpadding="10">
        <tr>
            <th>Avatar</th>
            <th>Username</th>
            <th>Full Name</th>
            <th>Actions</th>
        </tr>

        <?php foreach ($users as $user): ?>
            <tr>
                <td>
                    <?= view('partials/avatar', [
                        'avatar' => $user['avatar'] ?? null,
                    ]) ?>
                </td>
                <td><?= esc($user['username']) ?></td>
                <td><?= esc($user['full_name']) ?></td>
                <td>
                    <a href="<?= site_url('users/edit/' . $user['id']) ?>">
                        Edit
                    </a>

                    <form action="<?= site_url('users/delete/' . $user['id']) ?>" method="post" style="display: inline;"
                        onsubmit="return confirm('Delete this staff account permanently?');">
                        <?= csrf_field() ?>

                        <button type="submit">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>

    </table>

</body>

</html>