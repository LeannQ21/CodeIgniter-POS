<?php
$filename = (string) ($productImage ?? '');
$imageUrl = base_url('images/product-placeholder.svg');

if (
    preg_match('/\A[a-f0-9]{32}\.png\z/', $filename) &&
    is_file(FCPATH . 'uploads/products/' . $filename)
) {
    $imageUrl = base_url('uploads/products/' . $filename);
}
?>

<img src="<?= esc($imageUrl, 'attr') ?>" alt="Product image" width="80" height="80"
    style="object-fit: cover; border-radius: 8px;">