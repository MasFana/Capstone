<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Shield\Models\UserModel as ShieldUserModel;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Database\RawSql;

class AppUserProvider extends ShieldUserModel
{
    protected function initialize(): void
    {
        parent::initialize();

        $this->allowedFields = [
            ...$this->allowedFields,
            'role_id',
            'name',
            'is_active',
        ];

        $this->useSoftDeletes = true;
        $this->deletedField   = 'deleted_at';
    }

    public function findByUsername(string $username): ?User
    {
        $user = $this->where('username', $username)
                     ->where('deleted_at', null)
                     ->first();

        return $user;
    }

    public function findById($id): ?User
    {
        return $this->withDeleted()->find($id);
    }

    public function findActiveById(int $id): ?User
    {
        $user = $this->where('id', $id)
                     ->where('is_active', true)
                     ->where('deleted_at', null)
                     ->first();

        return $user;
    }

    public function isActiveUser(User $user): bool
    {
        $userData = $this->where('id', $user->id)
                         ->where('is_active', true)
                         ->where('deleted_at', null)
                         ->first();

        return $userData !== null;
    }

    public function getUserWithRole(int $userId): ?array
    {
        $user = $this->asArray()->find($userId);
        
        if (!$user) {
            return null;
        }

        $roleModel = new RoleModel();
        $role = $roleModel->find($user['role_id']);
        
        if ($role) {
            $user['role'] = $role;
        }

        return $user;
    }

    public function getActiveUserWithRole(int $userId): ?array
    {
        return $this->select('users.*, roles.name as role_name')
            ->join('roles', 'roles.id = users.role_id')
            ->where('users.id', $userId)
            ->where('users.is_active', true)
            ->where('users.deleted_at', null)
            ->asArray()
            ->first();
    }

    public function delete($id = null, bool $purge = false): bool
    {
        if (is_int($id) || is_string($id)) {
            $user = $this->findById($id);

            if ($user instanceof User) {
                $user->revokeAllAccessTokens();
            }
        }

        return parent::delete($id, $purge);
    }
}
