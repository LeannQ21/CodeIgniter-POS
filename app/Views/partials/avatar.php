<?php
$filename = (string) ($avatar ?? '');
$avatarUrl = base_url('images/avatar-placeholder.svg');

if (
    preg_match('/\A[a-f0-9]{32}\.png\z/', $filename) &&
    is_file(FCPATH . 'uploads/avatars/' . $filename)
) {
    $avatarUrl = base_url('uploads/avatars/' . $filename);
}
?>

<img src="<?= esc($avatarUrl, 'attr') ?>" alt="Staff avatar" width="64" height="64"
    style="object-fit: cover; border-radius: 8px;">