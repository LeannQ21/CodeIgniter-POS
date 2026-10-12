<?php

namespace App\Controllers;

use App\Models\UserModel;

class Auth extends BaseController
{
    public function login()
    {
        if (session()->get('logged_in')) {
            return redirect()->to(site_url('/'));
        }

        return view('login');
    }

    public function attemptLogin()
    {
        $data = [
            'username' => $this->request->getPost('username'),
            'password' => $this->request->getPost('password'),
        ];

        $rules = [
            'username' => 'required|string|max_length[50]',
            'password' => 'required|string',
        ];

        if (!$this->validateData($data, $rules)) {
            return redirect()->to(site_url('login'))
                ->with('error', 'Enter a valid username and password.');
        }

        $model = new UserModel();

        $user = $model
            ->where('username', $data['username'])
            ->first();

        if (
            !$user ||
            !password_verify($data['password'], $user['password'])
        ) {
            return redirect()->to(site_url('login'))
                ->with('error', 'Invalid username or password.');
        }

        $session = session();

        // Replace the session ID after successful authentication.
        $session->regenerate(true);

        $session->set([
            'user_id' => $user['id'],
            'username' => $user['username'],
            'full_name' => $user['full_name'],
            'logged_in' => true,
        ]);

        return redirect()->to(site_url('/'));
    }

    public function logout()
    {
        session()->destroy();

        return redirect()->to(site_url('login'));
    }
}