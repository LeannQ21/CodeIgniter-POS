<?php

namespace App\Controllers;

use App\Models\CustomerModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use App\Models\SaleModel;
use CodeIgniter\Database\Exceptions\DatabaseException;

class Customers extends BaseController
{
    public function index()
    {
        $model = new CustomerModel();

        return view('customers', [
            'customers' => $model->findAll(),
        ]);
    }

    public function new()
    {
        return view('customer_form', [
            'customer' => null,
        ]);
    }

    public function create()
    {
        $data = $this->customerInput();

        if (!$this->validateData($data, $this->customerRules())) {
            return redirect()->to(site_url('customers/new'))
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $model = new CustomerModel();

        $data['created_at'] = date('Y-m-d H:i:s');

        try {
            $saved = $model->insert($data);
        } catch (DatabaseException $e) {
            log_message('error', 'Customer creation failed: {message}', [
                'message' => $e->getMessage(),
            ]);

            $saved = false;
        }

        if (!$saved) {
            return redirect()->to(site_url('customers/new'))
                ->withInput()
                ->with('errors', [
                    'save' => 'Unable to save this customer. Please try again.',
                ]);
        }

        return redirect()->to(site_url('customers'))
            ->with('success', 'Customer added successfully.');
    }

    public function edit($id)
    {
        $model = new CustomerModel();
        $customer = $model->find($id);

        if (!$customer) {
            throw PageNotFoundException::forPageNotFound(
                'Customer not found.'
            );
        }

        return view('customer_form', [
            'customer' => $customer,
        ]);
    }

    public function update($id)
    {
        $model = new CustomerModel();

        if (!$model->find($id)) {
            throw PageNotFoundException::forPageNotFound(
                'Customer not found.'
            );
        }

        $data = $this->customerInput();

        if (!$this->validateData($data, $this->customerRules())) {
            return redirect()->to(site_url('customers/edit/' . $id))
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        try {
            $saved = $model->update($id, $data);
        } catch (DatabaseException $e) {
            log_message('error', 'Customer update failed: {message}', [
                'message' => $e->getMessage(),
            ]);

            $saved = false;
        }

        if (!$saved) {
            return redirect()->to(site_url('customers/edit/' . $id))
                ->withInput()
                ->with('errors', [
                    'save' => 'Unable to update this customer. Please try again.',
                ]);
        }

        return redirect()->to(site_url('customers'))
            ->with('success', 'Customer updated successfully.');
    }

    public function delete($id)
    {
        $model = new CustomerModel();
        $customer = $model->find($id);

        if (!$customer) {
            throw PageNotFoundException::forPageNotFound(
                'Customer not found.'
            );
        }

        $saleModel = new SaleModel();

        $existingSale = $saleModel
            ->where('customer_id', $id)
            ->first();

        if ($existingSale) {
            return redirect()->to(site_url('customers'))
                ->with(
                    'error',
                    'This customer cannot be deleted because they have recorded sales.'
                );
        }

        try {
            $deleted = $model->delete($id);
        } catch (DatabaseException $e) {
            log_message('error', 'Customer deletion failed: {message}', [
                'message' => $e->getMessage(),
            ]);

            return redirect()->to(site_url('customers'))
                ->with('error', 'Unable to delete this customer. Please try again.');
        }

        if (!$deleted) {
            return redirect()->to(site_url('customers'))
                ->with('error', 'Unable to delete this customer. Please try again.');
        }

        return redirect()->to(site_url('customers'))
            ->with('success', 'Customer deleted successfully.');
    }

    private function customerInput(): array
    {
        return [
            'full_name' => $this->request->getPost('full_name'),
            'email' => $this->request->getPost('email'),
            'phone' => $this->request->getPost('phone'),
        ];
    }

    private function customerRules(): array
    {
        return [
            'full_name' => 'required|string|max_length[100]',
            'email' => 'required|valid_email|max_length[100]',
            'phone' => 'permit_empty|string|max_length[20]',
        ];
    }
}