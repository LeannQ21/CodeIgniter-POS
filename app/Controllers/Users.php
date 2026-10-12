<?php

namespace App\Controllers;

use App\Models\UserModel;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\Exceptions\PageNotFoundException;
use App\Models\SaleModel;

class Users extends BaseController
{
    public function index()
    {
        $model = new UserModel();

        return view('users', [
            'users' => $model->findAll(),
        ]);
    }

    public function new()
    {
        return view('user_form', ['user' => null]);
    }

    public function create()
    {
        return $this->saveUser();
    }

    public function edit($id)
    {
        $model = new UserModel();
        $user = $model->find($id);

        if (!$user) {
            throw PageNotFoundException::forPageNotFound(
                'Staff account not found.'
            );
        }

        return view('user_form', ['user' => $user]);
    }

    public function update($id)
    {
        $model = new UserModel();
        $user = $model->find($id);

        if (!$user) {
            throw PageNotFoundException::forPageNotFound(
                'Staff account not found.'
            );
        }

        return $this->saveUser($user);
    }

    public function delete($id)
    {
        $model = new UserModel();
        $user = $model->find($id);

        if (!$user) {
            throw PageNotFoundException::forPageNotFound(
                'Staff account not found.'
            );
        }

        if ((int) $user['id'] === (int) session()->get('user_id')) {
            return redirect()->to(site_url('users'))
                ->with('error', 'You cannot delete your own logged-in account.');
        }

        $saleModel = new SaleModel();

        $existingSale = $saleModel
            ->where('sold_by', $user['id'])
            ->first();

        if ($existingSale) {
            return redirect()->to(site_url('users'))
                ->with(
                    'error',
                    'This staff account cannot be deleted because it has recorded sales.'
                );
        }

        try {
            $deleted = $model->delete($user['id']);
        } catch (DatabaseException $e) {
            log_message('error', 'Staff deletion failed: {message}', [
                'message' => $e->getMessage(),
            ]);

            $deleted = false;
        }

        if (!$deleted) {
            return redirect()->to(site_url('users'))
                ->with('error', 'Unable to delete this staff account. Please try again.');
        }

        $this->removeAvatarFile($user['avatar'] ?? null);
        
        return redirect()->to(site_url('users'))
            ->with('success', 'Staff account deleted successfully.');
    }

    public function uploadAvatar($id)
    {
        $model = new UserModel();
        $user = $model->find($id);

        if (!$user) {
            throw PageNotFoundException::forPageNotFound(
                'Staff account not found.'
            );
        }

        $editUrl = site_url('users/edit/' . $user['id']);

        $rules = [
            'avatar' => [
                'label' => 'Avatar',
                'rules' => [
                    'uploaded[avatar]',
                    'max_size[avatar,2048]',
                    'is_image[avatar]',
                    'mime_in[avatar,image/jpeg,image/png]',
                    'ext_in[avatar,jpg,jpeg,png]',
                ],
            ],
        ];

        if (!$this->validateData([], $rules)) {
            return redirect()->to($editUrl)
                ->with('errors', $this->validator->getErrors());
        }

        $file = $this->request->getFile('avatar');
        $filename = null;

        try {
            if (!$file || !$file->isValid()) {
                throw new \RuntimeException('The uploaded file is invalid.');
            }

            $directory = FCPATH . 'uploads/avatars/';

            if (!is_dir($directory)) {
                throw new \RuntimeException('The avatars folder does not exist.');
            }

            if (!is_writable($directory)) {
                throw new \RuntimeException('The avatars folder is not writable.');
            }

            $filename = bin2hex(random_bytes(16)) . '.png';

            $saved = service('image', 'gd')
                ->withFile($file->getTempName())
                ->withResource()
                ->fit(160, 160, 'center')
                ->convert(IMAGETYPE_PNG)
                ->save($directory . $filename);

            if (!$saved) {
                throw new \RuntimeException('Unable to save the thumbnail.');
            }

            if (!$model->update($user['id'], ['avatar' => $filename])) {
                throw new \RuntimeException('Unable to save the avatar filename.');
            }
        } catch (\Throwable $e) {
            $this->removeAvatarFile($filename);

            log_message('error', 'Avatar upload failed: {message}', [
                'message' => $e->getMessage(),
            ]);

            return redirect()->to($editUrl)
                ->with('errors', [
                    'avatar' => 'Unable to prepare or save the avatar. Check that PHP GD is enabled and the avatars folder is writable.',
                ]);
        }

        // Remove the old thumbnail only after the replacement is saved.
        $this->removeAvatarFile($user['avatar'] ?? null);

        return redirect()->to($editUrl)
            ->with('success', 'Avatar updated successfully.');
    }

    private function removeAvatarFile(?string $filename): void
    {
        // Only remove files matching our generated avatar filenames.
        if (
            !$filename ||
            !preg_match('/\A[a-f0-9]{32}\.png\z/', $filename)
        ) {
            return;
        }

        $path = FCPATH . 'uploads/avatars/' . $filename;

        if (is_file($path) && !@unlink($path)) {
            log_message('error', 'Unable to remove avatar file: {filename}', [
                'filename' => $filename,
            ]);
        }
    }

    private function saveUser(?array $user = null)
    {
        $isEditing = $user !== null;
        $id = $isEditing ? (int) $user['id'] : null;

        $input = [
            'username' => $this->request->getPost('username'),
            'full_name' => $this->request->getPost('full_name'),
            'password' => $this->request->getPost('password') ?? '',
        ];

        // Require a password for new accounts and existing accounts
        // that do not yet have a password hash.
        $needsPassword = !$isEditing || empty($user['password']);

        $uniqueRule = $isEditing
            ? "is_unique[users.username,id,{$id}]"
            : 'is_unique[users.username]';

        $passwordRule = ($needsPassword || $input['password'] !== '')
            ? 'required|string|min_length[8]'
            : 'string';

        $rules = [
            'username' => 'required|string|max_length[50]|' . $uniqueRule,
            'full_name' => 'required|string|max_length[100]',
            'password' => $passwordRule,
        ];

        $this->validateData($input, $rules);
        $errors = $this->validator->getErrors();

        if (is_string($input['password'])) {
            if (strlen($input['password']) > 72) {
                $errors['password'] = 'Password must not exceed 72 bytes.';
            }

            if (str_contains($input['password'], "\0")) {
                $errors['password'] = 'Password contains an invalid character.';
            }
        }

        $formUrl = $isEditing
            ? site_url('users/edit/' . $id)
            : site_url('users/new');

        // Preserve only these fields after errors, never the password.
        $formInput = [
            'username' => is_string($input['username'])
                ? $input['username'] : '',
            'full_name' => is_string($input['full_name'])
                ? $input['full_name'] : '',
        ];

        if ($errors) {
            return redirect()->to($formUrl)
                ->with('errors', $errors)
                ->with('staff_input', $formInput);
        }

        $data = [
            'username' => $input['username'],
            'full_name' => $input['full_name'],
        ];

        if ($input['password'] !== '') {
            $data['password'] = password_hash(
                $input['password'],
                PASSWORD_DEFAULT
            );
        }

        $model = new UserModel();

        try {
            if ($isEditing) {
                $saved = $model->update($id, $data);
            } else {
                $data['created_at'] = date('Y-m-d H:i:s');
                $saved = $model->insert($data);
            }
        } catch (DatabaseException $e) {
            log_message('error', 'Staff save failed: {message}', [
                'message' => $e->getMessage(),
            ]);

            $saved = false;
        }

        if (!$saved) {
            return redirect()->to($formUrl)
                ->with('errors', [
                    'save' => 'Unable to save. Check that the username is unique and try again.',
                ])
                ->with('staff_input', $formInput);
        }

        // Keep the current session accurate when editing yourself.
        if ($isEditing && (int) session()->get('user_id') === $id) {
            session()->regenerate(true);

            session()->set([
                'username' => $data['username'],
                'full_name' => $data['full_name'],
            ]);
        }

        return redirect()->to(site_url('users'))
            ->with(
                'success',
                $isEditing
                ? 'Staff account updated successfully.'
                : 'Staff account created successfully.'
            );
    }
}