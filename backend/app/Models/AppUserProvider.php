<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Shield\Models\UserModel as ShieldUserModel;
use CodeIgniter\Shield\Entities\User;

class AppUserProvider extends ShieldUserModel
{
    protected function initialize(): void
    {
        parent::initialize();

        $this->allowedFields = [
            ...$this->allowedFields,
            'role_id',
            'name',
            'email',
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
        $user = $this->find($id);

        return $user instanceof User ? $user : null;
    }

    public function findByIdIncludingDeleted($id): ?User
    {
        $user = $this->withDeleted()->find($id);

        return $user instanceof User ? $user : null;
    }

    public function findActiveById(int $id): ?User
    {
        $user = $this->where('id', $id)
                     ->where('is_active', true)
                     ->where('deleted_at', null)
                     ->first();

        return $user instanceof User ? $user : null;
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
        return $this->select('users.*, roles.name as role_name')
            ->join('roles', 'roles.id = users.role_id')
            ->where('users.id', $userId)
            ->asArray()
            ->first();
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

    public function getAllWithRoles(): array
    {
        return $this->select('users.*, roles.name as role_name')
            ->join('roles', 'roles.id = users.role_id')
            ->where('users.deleted_at', null)
            ->asArray()
            ->findAll();
    }

    public function revokeAllUserTokens(int $userId): void
    {
        $user = $this->findByIdIncludingDeleted($userId);
        
        if ($user instanceof User) {
            $user->revokeAllAccessTokens();
        }
    }

    public function delete($id = null, bool $purge = false): bool
    {
        if (is_int($id) || is_string($id)) {
            $user = $this->findByIdIncludingDeleted($id);

            if ($user instanceof User) {
                $user->revokeAllAccessTokens();
            }
        }

        return parent::delete($id, $purge);
    }
}
