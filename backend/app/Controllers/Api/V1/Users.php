<?php

namespace App\Controllers\Api\V1;

use App\Controllers\BaseController;
use App\Services\UserManagementService;
use CodeIgniter\HTTP\ResponseInterface;

class Users extends BaseController
{
    protected UserManagementService $userService;

    public function __construct()
    {
        $this->userService = new UserManagementService();
    }

    public function index(): ResponseInterface
    {
        $users = $this->userService->getAllUsers();

        return $this->response
            ->setStatusCode(200)
            ->setJSON([
                'data' => $users,
            ]);
    }

    public function show(int $id): ResponseInterface
    {
        $user = $this->userService->getUserById($id);

        if (!$user) {
            return $this->response
                ->setStatusCode(404)
                ->setJSON([
                    'message' => 'User not found.',
                ]);
        }

        return $this->response
            ->setStatusCode(200)
            ->setJSON([
                'data' => $user,
            ]);
    }

    public function create(): ResponseInterface
    {
        $data = $this->request->getJSON(true);

        $rules = [
            'role_id'  => 'required|is_natural_no_zero',
            'name'     => 'required|max_length[255]',
            'username' => 'required|max_length[100]|is_unique[users.username]',
            'password' => 'required|min_length[8]',
            'email'    => 'permit_empty|valid_email|max_length[255]',
        ];

        if (!$this->validateData($data ?? [], $rules)) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'message' => 'Validation failed.',
                    'errors'  => $this->validator->getErrors(),
                ]);
        }

        $result = $this->userService->createUser($data);

        if (!$result['success']) {
            $statusCode = isset($result['errors']) ? 400 : 422;
            $response = ['message' => $result['message']];
            
            if (isset($result['errors'])) {
                $response['errors'] = $result['errors'];
            }
            
            return $this->response
                ->setStatusCode($statusCode)
                ->setJSON($response);
        }

        return $this->response
            ->setStatusCode(201)
            ->setJSON([
                'message' => 'User created successfully.',
                'data'    => $result['user'],
            ]);
    }

    public function update(int $id): ResponseInterface
    {
        $data = $this->request->getJSON(true) ?? [];
        $validationData = [
            ...$data,
            'id' => $id,
        ];

        $rules = [
            'id'       => 'required|is_natural_no_zero',
            'role_id'  => 'permit_empty|is_natural_no_zero',
            'name'     => 'permit_empty|max_length[255]',
            'email'    => 'permit_empty|valid_email|max_length[255]',
        ];

        if (array_key_exists('username', $data)) {
            $rules['username'] = 'required|max_length[100]|is_unique[users.username,id,{id}]';
        }

        if (!$this->validateData($validationData, $rules)) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'message' => 'Validation failed.',
                    'errors'  => $this->validator->getErrors(),
                ]);
        }

        $result = $this->userService->updateUser($id, $data);

        if (!$result['success']) {
            $statusCode = $result['message'] === 'User not found.' ? 404 : 422;
            $response = ['message' => $result['message']];
            
            if (isset($result['errors'])) {
                $response['errors'] = $result['errors'];
            }
            
            return $this->response
                ->setStatusCode($statusCode)
                ->setJSON($response);
        }

        return $this->response
            ->setStatusCode(200)
            ->setJSON([
                'message' => 'User updated successfully.',
                'data'    => $result['user'],
            ]);
    }

    public function activate(int $id): ResponseInterface
    {
        $result = $this->userService->activateUser($id);

        if (!$result['success']) {
            return $this->response
                ->setStatusCode(404)
                ->setJSON([
                    'message' => $result['message'],
                ]);
        }

        return $this->response
            ->setStatusCode(200)
            ->setJSON([
                'message' => 'User activated successfully.',
                'data'    => $result['user'],
            ]);
    }

    public function deactivate(int $id): ResponseInterface
    {
        $result = $this->userService->deactivateUser($id);

        if (!$result['success']) {
            return $this->response
                ->setStatusCode(404)
                ->setJSON([
                    'message' => $result['message'],
                ]);
        }

        return $this->response
            ->setStatusCode(200)
            ->setJSON([
                'message' => 'User deactivated successfully.',
                'data'    => $result['user'],
            ]);
    }

    public function changePassword(int $id): ResponseInterface
    {
        $data = $this->request->getJSON(true);

        $rules = [
            'password' => 'required|min_length[8]',
        ];

        if (!$this->validateData($data ?? [], $rules)) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'message' => 'Validation failed.',
                    'errors'  => $this->validator->getErrors(),
                ]);
        }

        $result = $this->userService->changePassword($id, $data['password']);

        if (!$result['success']) {
            return $this->response
                ->setStatusCode(404)
                ->setJSON([
                    'message' => $result['message'],
                ]);
        }

        return $this->response
            ->setStatusCode(200)
            ->setJSON([
                'message' => $result['message'],
            ]);
    }

    public function delete(int $id): ResponseInterface
    {
        $result = $this->userService->deleteUser($id);

        if (!$result['success']) {
            return $this->response
                ->setStatusCode(404)
                ->setJSON([
                    'message' => $result['message'],
                ]);
        }

        return $this->response
            ->setStatusCode(200)
            ->setJSON([
                'message' => $result['message'],
            ]);
    }
}
