<?php

namespace App\Models;

use CodeIgniter\Model;

class SaleModel extends Model
{
    protected $table = 'sales';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'product_id',
        'customer_id',
        'sold_by',
        'quantity',
        'total_price',
        'created_at',
    ];

    public function history(): array
    {
        return $this
            ->select(
                'sales.*, products.name AS product_name, '
                . 'customers.full_name AS customer_name, '
                . 'users.full_name AS staff_name'
            )
            ->join('products', 'products.id = sales.product_id')
            ->join('customers', 'customers.id = sales.customer_id', 'left')
            ->join('users', 'users.id = sales.sold_by')
            ->orderBy('sales.id', 'DESC')
            ->findAll();
    }

    public function recordSale(
        int $productId,
        ?int $customerId,
        int $staffId,
        int $quantity
    ): int {
        if ($quantity < 1) {
            throw new \DomainException('Quantity must be at least 1.');
        }

        $db = $this->db;

        if (!$db->transBegin()) {
            throw new \RuntimeException('Unable to begin the sale transaction.');
        }

        try {
            $productModel = new ProductModel($db);

            // Check stock and deduct it in the same database operation.
            // Quantity is an integer, not an unchecked SQL expression.
            $updated = $productModel->builder()
                ->where('id', $productId)
                ->where('stock_quantity >=', $quantity)
                ->set('stock_quantity', 'stock_quantity - ' . $quantity, false)
                ->update();

            if (!$updated) {
                throw new \RuntimeException('Unable to update product stock.');
            }

            if ($db->affectedRows() !== 1) {
                $product = $productModel->find($productId);

                if (!$product) {
                    throw new \DomainException('The selected product no longer exists.');
                }

                throw new \DomainException(
                    'Insufficient stock. Available quantity: '
                    . $product['stock_quantity'] . '.'
                );
            }

            // The product row remains locked until commit or rollback.
            $product = $productModel->find($productId);

            if (!$product) {
                throw new \RuntimeException('Unable to retrieve the product.');
            }

            if ($customerId !== null) {
                $customerModel = new CustomerModel($db);

                if (!$customerModel->find($customerId)) {
                    throw new \DomainException(
                        'The selected customer no longer exists.'
                    );
                }
            }

            // MySQL DECIMAL(10,2) returns a price with two decimal places.
            // Calculate using integer centavos.
            $price = (string) $product['price'];

            if (!preg_match('/\A[0-9]{1,8}\.[0-9]{2}\z/', $price)) {
                throw new \RuntimeException('The product has an invalid stored price.');
            }

            $unitCentavos = (int) str_replace('.', '', $price);
            $maximumTotalCentavos = 9999999999;

            if (
                $unitCentavos > 0 &&
                $quantity > intdiv($maximumTotalCentavos, $unitCentavos)
            ) {
                throw new \DomainException(
                    'The sale total exceeds the supported amount. Use a smaller quantity.'
                );
            }

            $totalCentavos = $unitCentavos * $quantity;

            $totalPrice = sprintf(
                '%d.%02d',
                intdiv($totalCentavos, 100),
                $totalCentavos % 100
            );

            $saleId = $this->insert([
                'product_id' => $productId,
                'customer_id' => $customerId,
                'sold_by' => $staffId,
                'quantity' => $quantity,
                'total_price' => $totalPrice,
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            if ($saleId === false || !$db->transStatus()) {
                throw new \RuntimeException('Unable to save the sale.');
            }

            if (!$db->transCommit()) {
                throw new \RuntimeException('Unable to commit the sale.');
            }

            return (int) $saleId;
        } catch (\Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }
}