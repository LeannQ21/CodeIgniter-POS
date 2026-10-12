<?php

namespace App\Filters;

use App\Models\UserModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();

        if (!$session->get('logged_in')) {
            return redirect()->to(site_url('login'));
        }

        $userId = (int) $session->get('user_id');
        $model = new UserModel();

        $user = $userId > 0 ? $model->find($userId) : null;

        if (!$user) {
            $session->destroy();

            return redirect()->to(site_url('login'));
        }

        // Keep session details aligned with the current account.
        $session->set([
            'username' => $user['username'],
            'full_name' => $user['full_name'],
        ]);
    }

    public function after(
        RequestInterface $request,
        ResponseInterface $response,
        $arguments = null
    ) {
        // No action needed.
    }
}