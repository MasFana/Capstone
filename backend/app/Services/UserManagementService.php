<?php

namespace App\Services;

use App\Models\AppUserProvider;
use App\Models\RoleModel;
use CodeIgniter\Shield\Entities\User;

class UserManagementService
{
    protected AppUserProvider $userProvider;
    protected RoleModel $roleModel;

    public function __construct()
    {
        $this->userProvider = new AppUserProvider();
        $this->roleModel    = new RoleModel();
    }

    public function getAllUsers(): array
    {
        $users = $this->userProvider->getAllWithRoles();
        
        return array_map(function ($user) {
            return $this->formatUserResponse($user);
        }, $users);
    }

    public function getUserById(int $id): ?array
    {
        $user = $this->userProvider->getUserWithRole($id);
        
        if (!$user) {
            return null;
        }

        return $this->formatUserResponse($user);
    }

    public function createUser(array $data): array
    {
        // Resolve role_name to role_id if provided
        if (isset($data['role_name']) && !isset($data['role_id'])) {
            $roleId = $this->roleModel->getIdByName($data['role_name']);
            if ($roleId === null) {
                return [
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors'  => ['role_name' => 'The selected role is invalid.'],
                ];
            }
            $data['role_id'] = $roleId;
        }

        $role = $this->roleModel->find($data['role_id']);
        $roleName = is_array($role) && isset($role['name']) && is_string($role['name']) ? $role['name'] : null;

        if ($roleName === null || !$this->isAllowedRole($roleName)) {
            return [
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => ['role_id' => 'The selected role is invalid.'],
            ];
        }

        $userData = [
            'role_id'   => $data['role_id'],
            'name'      => $data['name'],
            'username'  => $data['username'],
            'is_active' => $data['is_active'] ?? true,
            'active'    => $data['is_active'] ?? true,
        ];

        if (isset($data['email'])) {
            $userData['email'] = $data['email'];
        }

        $user = new User($userData);

        $user->fill(['password' => $data['password']]);

        $inserted = $this->userProvider->insert($user, true);

        if (!$inserted) {
            return [
                'success' => false,
                'message' => 'Failed to create user.',
                'errors'  => $this->userProvider->errors(),
            ];
        }

        $userId = $this->userProvider->getInsertID();
        $createdUser = $this->getUserById((int) $userId);

        return [
            'success' => true,
            'user'    => $createdUser,
        ];
    }

    public function updateUser(int $id, array $data): array
    {
        $user = $this->userProvider->findById($id);

        if (!$user) {
            return [
                'success' => false,
                'message' => 'User not found.',
            ];
        }

        // Resolve role_name to role_id if provided
        if (isset($data['role_name']) && !isset($data['role_id'])) {
            $roleId = $this->roleModel->getIdByName($data['role_name']);
            if ($roleId === null) {
                return [
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors'  => ['role_name' => 'The selected role is invalid.'],
                ];
            }
            $data['role_id'] = $roleId;
        }

        if (isset($data['role_id'])) {
            $role = $this->roleModel->find($data['role_id']);
            $roleName = is_array($role) && isset($role['name']) && is_string($role['name']) ? $role['name'] : null;

            if ($roleName === null || !$this->isAllowedRole($roleName)) {
                return [
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors'  => ['role_id' => 'The selected role is invalid.'],
                ];
            }
        }

        $updateData = [];

        if (isset($data['role_id'])) {
            $updateData['role_id'] = $data['role_id'];
        }
        if (isset($data['name'])) {
            $updateData['name'] = $data['name'];
        }
        if (isset($data['username'])) {
            $updateData['username'] = $data['username'];
        }
        if (isset($data['email'])) {
            $updateData['email'] = $data['email'];
        }
        if (isset($data['is_active'])) {
            $updateData['is_active'] = (bool) $data['is_active'];
            $updateData['active'] = (bool) $data['is_active'];
        }

        if ($updateData !== []) {
            $updated = $this->userProvider->update($id, $updateData);

            if (!$updated) {
                return [
                    'success' => false,
                    'message' => 'Failed to update user.',
                    'errors'  => $this->userProvider->errors(),
                ];
            }
        }

        if (isset($data['email'])) {
            $identityUser = $this->userProvider->findById($id);

            if (!$identityUser) {
                return [
                    'success' => false,
                    'message' => 'User not found.',
                ];
            }

            $identityUser->email = $data['email'];

            $identitySynced = $this->userProvider->save($identityUser);

            if (!$identitySynced) {
                return [
                    'success' => false,
                    'message' => 'Failed to update user.',
                    'errors'  => $this->userProvider->errors(),
                ];
            }
        }

        $updatedUser = $this->getUserById($id);

        return [
            'success' => true,
            'user'    => $updatedUser,
        ];
    }

    public function activateUser(int $id): array
    {
        $user = $this->userProvider->findById($id);

        if (!$user) {
            return [
                'success' => false,
                'message' => 'User not found.',
            ];
        }

        $updated = $this->userProvider->update($id, [
            'is_active' => true,
            'active'    => true,
        ]);

        if (!$updated) {
            return [
                'success' => false,
                'message' => 'Failed to activate user.',
            ];
        }

        $updatedUser = $this->getUserById($id);

        return [
            'success' => true,
            'user'    => $updatedUser,
        ];
    }

    public function deactivateUser(int $id): array
    {
        $user = $this->userProvider->findById($id);

        if (!$user) {
            return [
                'success' => false,
                'message' => 'User not found.',
            ];
        }

        $updated = $this->userProvider->update($id, [
            'is_active' => false,
            'active'    => false,
        ]);

        if (!$updated) {
            return [
                'success' => false,
                'message' => 'Failed to deactivate user.',
            ];
        }

        $updatedUser = $this->getUserById($id);

        return [
            'success' => true,
            'user'    => $updatedUser,
        ];
    }

    public function changePassword(int $id, string $newPassword): array
    {
        $user = $this->userProvider->findById($id);

        if (!$user) {
            return [
                'success' => false,
                'message' => 'User not found.',
            ];
        }

        $user->fill(['password' => $newPassword]);
        $updated = $this->userProvider->save($user);

        if (!$updated) {
            return [
                'success' => false,
                'message' => 'Failed to update password.',
            ];
        }

        $this->userProvider->revokeAllUserTokens($id);

        return [
            'success' => true,
            'message' => 'Password changed successfully. All access tokens have been revoked.',
        ];
    }

    public function deleteUser(int $id): array
    {
        $user = $this->userProvider->findById($id);

        if (!$user) {
            return [
                'success' => false,
                'message' => 'User not found.',
            ];
        }

        $deleted = $this->userProvider->delete($id);

        if (!$deleted) {
            return [
                'success' => false,
                'message' => 'Failed to delete user.',
            ];
        }

        return [
            'success' => true,
            'message' => 'User deleted successfully.',
        ];
    }

    protected function formatUserResponse(array $userData): array
    {
        unset($userData['password']);

        $response = [
            'id'         => $userData['id'],
            'role_id'    => $userData['role_id'],
            'name'       => $userData['name'],
            'username'   => $userData['username'],
            'email'      => $userData['email'] ?? null,
            'is_active'  => (bool) $userData['is_active'],
            'created_at' => $userData['created_at'],
            'updated_at' => $userData['updated_at'],
        ];

        if (isset($userData['role_name'])) {
            $response['role'] = [
                'id'   => $userData['role_id'],
                'name' => $userData['role_name'],
            ];
        }

        return $response;
    }

    protected function isAllowedRole(string $roleName): bool
    {
        return in_array($roleName, ['admin', 'dapur', 'gudang'], true);
    }
}
