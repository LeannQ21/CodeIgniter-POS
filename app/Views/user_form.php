<?php
$isEditing = !empty($user);
$needsPassword = !$isEditing || empty($user['password']);

$title = $isEditing ? 'Edit Staff Account' : 'Add Staff Account';

$action = $isEditing
    ? site_url('users/update/' . $user['id'])
    : site_url('users/create');

$input = session()->getFlashdata('staff_input') ?? ($user ?? []);
$errors = session()->getFlashdata('errors') ?? [];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title><?= esc($title) ?></title>

    <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
</head>

<body>

    <h1><?= esc($title) ?></h1>

    <?php if ($message = session()->getFlashdata('success')): ?>
        <p role="status" style="color: green;">
            <?= esc($message) ?>
        </p>
    <?php endif; ?>

    <?php if ($errors): ?>
        <div role="alert" style="color: red;">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= esc($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form action="<?= esc($action, 'attr') ?>" method="post">
        <?= csrf_field() ?>

        <label for="username">Username</label>
        <br>
        <input id="username" type="text" name="username" maxlength="50" autocomplete="off" required
            value="<?= esc($input['username'] ?? '', 'attr') ?>">

        <br><br>

        <label for="full_name">Full Name</label>
        <br>
        <input id="full_name" type="text" name="full_name" maxlength="100" required
            value="<?= esc($input['full_name'] ?? '', 'attr') ?>">

        <br><br>

        <label for="password">
            <?= $needsPassword ? 'Password' : 'New Password (optional)' ?>
        </label>
        <br>
        <input id="password" type="password" name="password" minlength="8" maxlength="72" autocomplete="new-password"
            <?= $needsPassword ? 'required' : '' ?>>

        <p>
            <?php if ($needsPassword): ?>
                Set a password with at least 8 characters.
            <?php else: ?>
                Leave blank to keep the current password.
                A new password must have at least 8 characters.
            <?php endif; ?>
        </p>

        <button type="submit">
            <?= $isEditing ? 'Update Staff' : 'Save Staff' ?>
        </button>
    </form>
    <?php if ($isEditing): ?>
        <h2>Staff Avatar</h2>

        <?= view('partials/avatar', [
            'avatar' => $user['avatar'] ?? null,
        ]) ?>

        <p>Choose a JPG or PNG image, up to 2 MB.</p>
        <p>Save any account-detail changes before uploading an avatar.</p>

        <form action="<?= site_url('users/avatar/' . $user['id']) ?>" method="post" enctype="multipart/form-data">
            <?= csrf_field() ?>

            <label for="avatar">Avatar image</label>
            <br>

            <input id="avatar" type="file" name="avatar" accept=".jpg,.jpeg,.png" required>

            <br><br>

            <button type="submit">Save Avatar</button>
        </form>
    <?php else: ?>
        <p>After creating this account, open Edit to upload an avatar.</p>
    <?php endif; ?>

    <br>

    <a href="<?= site_url('users') ?>">Back to Staff Accounts</a>

</body>

</html>