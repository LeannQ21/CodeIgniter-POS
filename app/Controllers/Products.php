<?php

namespace App\Controllers;

use App\Models\ProductModel;
use App\Models\SaleModel;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\Exceptions\PageNotFoundException;

class Products extends BaseController
{
    public function index()
    {
        $model = new ProductModel();

        return view('products', [
            'products' => $model->findAll(),
        ]);
    }

    public function new()
    {
        return view('product_form', ['product' => null]);
    }

    public function create()
    {
        return $this->saveProduct();
    }

    public function edit($id)
    {
        $model = new ProductModel();
        $product = $model->find($id);

        if (!$product) {
            throw PageNotFoundException::forPageNotFound(
                'Product not found.'
            );
        }

        return view('product_form', ['product' => $product]);
    }

    public function update($id)
    {
        $model = new ProductModel();
        $product = $model->find($id);

        if (!$product) {
            throw PageNotFoundException::forPageNotFound(
                'Product not found.'
            );
        }

        return $this->saveProduct($product);
    }

    public function delete($id)
    {
        $model = new ProductModel();
        $product = $model->find($id);

        if (!$product) {
            throw PageNotFoundException::forPageNotFound(
                'Product not found.'
            );
        }

        $saleModel = new SaleModel();

        $existingSale = $saleModel
            ->where('product_id', $product['id'])
            ->first();

        if ($existingSale) {
            return redirect()->to(site_url('products'))
                ->with(
                    'error',
                    'This product cannot be deleted because it has recorded sales.'
                );
        }

        try {
            $deleted = $model->delete($product['id']);
        } catch (DatabaseException $e) {
            log_message('error', 'Product deletion failed: {message}', [
                'message' => $e->getMessage(),
            ]);

            $deleted = false;
        }

        if (!$deleted) {
            return redirect()->to(site_url('products'))
                ->with('error', 'Unable to delete this product. Please try again.');
        }

        $this->removeProductImage($product['image'] ?? null);

        return redirect()->to(site_url('products'))
            ->with('success', 'Product deleted successfully.');
    }

    public function uploadImage($id)
    {
        $model = new ProductModel();
        $product = $model->find($id);

        if (!$product) {
            throw PageNotFoundException::forPageNotFound(
                'Product not found.'
            );
        }

        $editUrl = site_url('products/edit/' . $product['id']);

        $rules = [
            'image' => [
                'label' => 'Product image',
                'rules' => [
                    'uploaded[image]',
                    'max_size[image,2048]',
                    'is_image[image]',
                    'mime_in[image,image/jpeg,image/png]',
                    'ext_in[image,jpg,jpeg,png]',
                ],
            ],
        ];

        if (!$this->validateData([], $rules)) {
            return redirect()->to($editUrl)
                ->with('errors', $this->validator->getErrors());
        }

        $file = $this->request->getFile('image');
        $filename = null;

        try {
            if (!$file || !$file->isValid()) {
                throw new \RuntimeException('The uploaded file is invalid.');
            }

            $directory = FCPATH . 'uploads/products/';

            if (!is_dir($directory)) {
                throw new \RuntimeException('The product image folder does not exist.');
            }

            if (!is_writable($directory)) {
                throw new \RuntimeException('The product image folder is not writable.');
            }

            $filename = bin2hex(random_bytes(16)) . '.png';

            $saved = service('image', 'gd')
                ->withFile($file->getTempName())
                ->withResource()
                ->fit(320, 320, 'center')
                ->convert(IMAGETYPE_PNG)
                ->save($directory . $filename);

            if (!$saved) {
                throw new \RuntimeException('Unable to save the product thumbnail.');
            }

            if (!$model->update($product['id'], ['image' => $filename])) {
                throw new \RuntimeException('Unable to save the image filename.');
            }
        } catch (\Throwable $e) {
            $this->removeProductImage($filename);

            log_message('error', 'Product image upload failed: {message}', [
                'message' => $e->getMessage(),
            ]);

            return redirect()->to($editUrl)
                ->with('errors', [
                    'image' => 'Unable to prepare or save the product image. Check the application log for details.',
                ]);
        }

        // Remove the old image after its replacement is saved.
        $this->removeProductImage($product['image'] ?? null);

        return redirect()->to($editUrl)
            ->with('success', 'Product image updated successfully.');
    }

    private function removeProductImage(?string $filename): void
    {
        if (
            !$filename ||
            !preg_match('/\A[a-f0-9]{32}\.png\z/', $filename)
        ) {
            return;
        }

        $path = FCPATH . 'uploads/products/' . $filename;

        if (is_file($path) && !@unlink($path)) {
            log_message('error', 'Unable to remove product image: {filename}', [
                'filename' => $filename,
            ]);
        }
    }

    private function saveProduct(?array $product = null)
    {
        $isEditing = $product !== null;

        $data = [
            'name' => $this->request->getPost('name'),
            'price' => $this->request->getPost('price'),
            'stock_quantity' => $this->request->getPost('stock_quantity'),
        ];

        $stockRule = 'required|string|is_natural|max_length[10]'
            . '|less_than_equal_to[2147483647]';

        $rules = [
            'name' => [
                'label' => 'Product name',
                'rules' => 'required|string|max_length[100]',
            ],
            'price' => [
                'label' => 'Price',
                'rules' => [
                    'required',
                    'string',
                    'regex_match[/\A[0-9]{1,8}(\.[0-9]{1,2})?\z/]',
                ],
                'errors' => [
                    'regex_match' => 'Price must be from 0 to 99999999.99, with up to two decimal places.',
                ],
            ],
            'stock_quantity' => [
                'label' => 'Stock quantity',
                'rules' => $stockRule,
            ],
        ];

        $validationData = $data;

        if ($isEditing) {
            $validationData['original_stock_quantity'] =
                $this->request->getPost('original_stock_quantity');

            $rules['original_stock_quantity'] = [
                'label' => 'Original stock quantity',
                'rules' => $stockRule,
            ];
        }

        $formUrl = $isEditing
            ? site_url('products/edit/' . $product['id'])
            : site_url('products/new');

        if (!$this->validateData($validationData, $rules)) {
            return redirect()->to($formUrl)
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $db = db_connect();
        $model = new ProductModel($db);

        try {
            if ($isEditing) {
                $expectedStock = (int) $validationData['original_stock_quantity'];

                // Save only if stock still matches the form's original value.
                $saved = $model->builder()
                    ->where('id', $product['id'])
                    ->where('stock_quantity', $expectedStock)
                    ->update($data);

                if ($saved && $db->affectedRows() === 0) {
                    // An unchanged submission also affects zero rows.
                    $unchanged = $model
                        ->where('id', $product['id'])
                        ->where('stock_quantity', $expectedStock)
                        ->where($data)
                        ->first();

                    if (!$unchanged) {
                        return redirect()->to($formUrl)
                            ->with('errors', [
                                'stock' => 'This product changed while you were editing. Your changes were not saved. Review the latest values and try again.',
                            ]);
                    }
                }
            } else {
                $data['created_at'] = date('Y-m-d H:i:s');
                $saved = $model->insert($data);
            }
        } catch (DatabaseException $e) {
            log_message('error', 'Product save failed: {message}', [
                'message' => $e->getMessage(),
            ]);

            $saved = false;
        }

        if (!$saved) {
            return redirect()->to($formUrl)
                ->withInput()
                ->with('errors', [
                    'save' => 'Unable to save this product. Please try again.',
                ]);
        }

        return redirect()->to(site_url('products'))
            ->with(
                'success',
                $isEditing
                ? 'Product updated successfully.'
                : 'Product created successfully.'
            );
    }
}