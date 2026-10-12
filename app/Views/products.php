<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Products</title>
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

    <h1>Products</h1>

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

    <a href="<?= site_url('products/new') ?>">Add Product</a>

    <br><br>

    <table border="1" cellpadding="10">
        <thead>
            <tr>
                <th>Image</th>
                <th>Name</th>
                <th>Price</th>
                <th>Stock</th>
                <th>Actions</th>
            </tr>
        </thead>

        <tbody>
            <?php if (empty($products)): ?>
                <tr>
                    <td colspan="5">No products found.</td>
                </tr>
            <?php endif; ?>

            <?php foreach ($products as $product): ?>
                <tr>
                    <td>
                        <?= view('partials/product_image', [
                            'productImage' => $product['image'] ?? null,
                        ]) ?>
                    </td>
                    <td><?= esc($product['name']) ?></td>
                    <td>
                        PHP <?= number_format((float) $product['price'], 2) ?>
                    </td>
                    <td><?= esc($product['stock_quantity']) ?></td>
                    <td>
                        <a href="<?= site_url('products/edit/' . $product['id']) ?>">
                            Edit
                        </a>

                        <form action="<?= site_url('products/delete/' . $product['id']) ?>" method="post"
                            style="display: inline;" onsubmit="return confirm('Delete this product permanently?');">
                            <?= csrf_field() ?>
                            <button type="submit">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

</body>

</html>