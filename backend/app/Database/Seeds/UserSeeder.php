<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use CodeIgniter\Shield\Entities\User;

class UserSeeder extends Seeder
{
    public function run()
    {
        $roleModel = new \App\Models\RoleModel();
        
        $superAdminRole = $roleModel->findByName('Super Admin');
        $spkRole = $roleModel->findByName('SPK/Gizi');
        $gudangRole = $roleModel->findByName('Gudang');

        $users = [
            [
                'role_id'   => $superAdminRole['id'],
                'name'      => 'Super Admin User',
                'username'  => 'superadmin',
                'email'     => 'superadmin@example.com',
                'is_active' => true,
                'active'    => true,
            ],
            [
                'role_id'   => $spkRole['id'],
                'name'      => 'SPK Gizi User',
                'username'  => 'spkgizi',
                'email'     => 'spkgizi@example.com',
                'is_active' => true,
                'active'    => true,
            ],
            [
                'role_id'   => $gudangRole['id'],
                'name'      => 'Gudang User',
                'username'  => 'gudang',
                'email'     => 'gudang@example.com',
                'is_active' => true,
                'active'    => true,
            ],
        ];

        $userProvider = new \App\Models\AppUserProvider();

        foreach ($users as $userData) {
            $user = new User($userData);
            $user->fill(['password' => 'password123']);
            $userProvider->insert($user, true);
        }
    }
}
