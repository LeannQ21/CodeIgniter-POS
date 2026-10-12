<?php

namespace App\Controllers;

use App\Models\CustomerModel;
use App\Models\ProductModel;
use App\Models\SaleModel;

class Sales extends BaseController
{
    public function index()
    {
        $model = new SaleModel();

        return view('sales', [
            'sales' => $model->history(),
        ]);
    }

    public function new()
    {
        $productModel = new ProductModel();
        $customerModel = new CustomerModel();

        return view('sale_form', [
            'products' => $productModel
                ->orderBy('name', 'ASC')
                ->findAll(),
            'customers' => $customerModel
                ->orderBy('full_name', 'ASC')
                ->findAll(),
        ]);
    }

    public function create()
    {
        $data = [
            'product_id' => $this->request->getPost('product_id'),
            'customer_id' => $this->request->getPost('customer_id') ?? '',
            'quantity' => $this->request->getPost('quantity'),
        ];

        $positiveInteger = 'required|string|is_natural_no_zero'
            . '|max_length[10]|less_than_equal_to[2147483647]';

        $rules = [
            'product_id' => [
                'label' => 'Product',
                'rules' => $positiveInteger,
            ],
            'quantity' => [
                'label' => 'Quantity',
                'rules' => $positiveInteger,
            ],
        ];

        // An empty customer selection represents a walk-in sale.
        if ($data['customer_id'] !== '') {
            $rules['customer_id'] = [
                'label' => 'Customer',
                'rules' => $positiveInteger,
            ];
        }

        if (!$this->validateData($data, $rules)) {
            return redirect()->to(site_url('sales/new'))
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $customerId = $data['customer_id'] === ''
            ? null
            : (int) $data['customer_id'];

        $model = new SaleModel();

        try {
            $saleId = $model->recordSale(
                (int) $data['product_id'],
                $customerId,
                (int) session()->get('user_id'),
                (int) $data['quantity']
            );
        } catch (\DomainException $e) {
            return redirect()->to(site_url('sales/new'))
                ->withInput()
                ->with('errors', ['sale' => $e->getMessage()]);
        } catch (\Throwable $e) {
            log_message('error', 'Sale recording failed: {message}', [
                'message' => $e->getMessage(),
            ]);

            return redirect()->to(site_url('sales/new'))
                ->withInput()
                ->with('errors', [
                    'sale' => 'Unable to record the sale. Please try again.',
                ]);
        }

        return redirect()->to(site_url('sales'))
            ->with('success', 'Sale #' . $saleId . ' recorded successfully.');
    }
}