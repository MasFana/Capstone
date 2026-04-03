<?php

namespace Tests\Feature\Api\V1;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Shield\Entities\User;
use App\Models\AppUserProvider;
use App\Models\RoleModel;

class UsersTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = false;
    protected $refresh     = true;
    protected $namespace   = 'App';

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->seedRoles();
        $this->seedUsers();
    }

    protected function seedRoles(): void
    {
        $roleModel = new RoleModel();
        $roleModel->insertBatch([
            ['name' => 'admin'],
            ['name' => 'dapur'],
            ['name' => 'gudang'],
        ]);
    }

    protected function seedUsers(): void
    {
        $roleModel = new RoleModel();
        $userProvider = new AppUserProvider();

        $adminRole = $roleModel->findByName('admin');
        $spkRole = $roleModel->findByName('dapur');

        $adminUser = new User([
            'role_id'   => $adminRole['id'],
            'name'      => 'Admin User',
            'username'  => 'admin',
            'email'     => 'admin@example.com',
            'is_active' => true,
            'active'    => true,
        ]);
        $adminUser->fill(['password' => 'password123']);
        $userProvider->insert($adminUser, true);

        $spkUser = new User([
            'role_id'   => $spkRole['id'],
            'name'      => 'SPK User',
            'username'  => 'spkuser',
            'email'     => 'spk@example.com',
            'is_active' => true,
            'active'    => true,
        ]);
        $spkUser->fill(['password' => 'password123']);
        $userProvider->insert($spkUser, true);
    }

    protected function loginAsAdmin(): string
    {
        $result = $this->withBodyFormat('json')
            ->post('api/v1/auth/login', [
                'username' => 'admin',
                'password' => 'password123',
            ]);
        
        $json = json_decode($result->getJSON(), true);
        return $json['access_token'];
    }

    protected function loginAsNonAdmin(): string
    {
        $result = $this->withBodyFormat('json')
            ->post('api/v1/auth/login', [
                'username' => 'spkuser',
                'password' => 'password123',
            ]);
        
        $json = json_decode($result->getJSON(), true);
        return $json['access_token'];
    }

    public function testListUsersWithoutAuth(): void
    {
        $result = $this->get('api/v1/users');
        $result->assertStatus(401);
    }

    public function testListUsersAsNonAdmin(): void
    {
        $token = $this->loginAsNonAdmin();

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->get('api/v1/users');

        $result->assertStatus(403);
        $result->assertJSONFragment(['message' => 'Insufficient permissions.']);
    }

    public function testListUsersAsAdmin(): void
    {
        $token = $this->loginAsAdmin();

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->get('api/v1/users');

        $result->assertStatus(200);
        
        $json = json_decode($result->getJSON(), true);
        $this->assertArrayHasKey('data', $json);
        $this->assertIsArray($json['data']);
        $this->assertGreaterThanOrEqual(2, count($json['data']));
        
        foreach ($json['data'] as $user) {
            $this->assertArrayNotHasKey('password', $user);
            $this->assertArrayHasKey('username', $user);
            $this->assertArrayHasKey('name', $user);
            $this->assertArrayHasKey('role', $user);
        }
    }

    public function testShowUserWithoutAuth(): void
    {
        $result = $this->get('api/v1/users/1');
        $result->assertStatus(401);
    }

    public function testShowUserAsNonAdmin(): void
    {
        $token = $this->loginAsNonAdmin();

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->get('api/v1/users/1');

        $result->assertStatus(403);
    }

    public function testShowUserAsAdmin(): void
    {
        $token = $this->loginAsAdmin();

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->get('api/v1/users/1');

        $result->assertStatus(200);
        
        $json = json_decode($result->getJSON(), true);
        $this->assertArrayHasKey('data', $json);
        $this->assertArrayNotHasKey('password', $json['data']);
        $this->assertArrayHasKey('username', $json['data']);
        $this->assertArrayHasKey('role', $json['data']);
    }

    public function testShowNonExistentUser(): void
    {
        $token = $this->loginAsAdmin();

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->get('api/v1/users/99999');

        $result->assertStatus(404);
        $result->assertJSONFragment(['message' => 'User not found.']);
    }

    public function testCreateUserAsAdmin(): void
    {
        $token = $this->loginAsAdmin();
        $roleModel = new RoleModel();
        $gudangRole = $roleModel->findByName('gudang');

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->withBodyFormat('json')
            ->post('api/v1/users', [
                'role_id'  => $gudangRole['id'],
                'name'     => 'New User',
                'username' => 'newuser',
                'email'    => 'newuser@example.com',
                'password' => 'newpassword123',
            ]);

        $result->assertStatus(201);
        $result->assertJSONFragment(['message' => 'User created successfully.']);
        
        $json = json_decode($result->getJSON(), true);
        $this->assertArrayHasKey('data', $json);
        $this->assertSame('newuser', $json['data']['username']);
        $this->assertSame('New User', $json['data']['name']);
        $this->assertArrayNotHasKey('password', $json['data']);
    }

    public function testCreateUserWithoutEmailAsAdmin(): void
    {
        $token = $this->loginAsAdmin();
        $roleModel = new RoleModel();
        $gudangRole = $roleModel->findByName('gudang');

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->withBodyFormat('json')
            ->post('api/v1/users', [
                'role_id'  => $gudangRole['id'],
                'name'     => 'Email Optional User',
                'username' => 'emailoptional',
                'password' => 'newpassword123',
            ]);

        $result->assertStatus(201);
        $result->assertJSONFragment(['message' => 'User created successfully.']);

        $json = json_decode($result->getJSON(), true);
        $this->assertArrayHasKey('data', $json);
        $this->assertSame('emailoptional', $json['data']['username']);
        $this->assertSame('Email Optional User', $json['data']['name']);
        $this->assertArrayNotHasKey('password', $json['data']);
        $this->assertArrayHasKey('email', $json['data']);
        $this->assertNull($json['data']['email']);
    }

    public function testCreateUserWithMissingFields(): void
    {
        $token = $this->loginAsAdmin();

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->withBodyFormat('json')
            ->post('api/v1/users', [
                'name' => 'Incomplete User',
            ]);

        $result->assertStatus(400);
        
        $json = json_decode($result->getJSON(), true);
        $this->assertArrayHasKey('errors', $json);
        $this->assertArrayHasKey('role_id', $json['errors']);
        $this->assertArrayHasKey('username', $json['errors']);
        $this->assertArrayHasKey('password', $json['errors']);
    }

    public function testCreateUserWithDuplicateUsername(): void
    {
        $token = $this->loginAsAdmin();
        $roleModel = new RoleModel();
        $gudangRole = $roleModel->findByName('gudang');

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->withBodyFormat('json')
            ->post('api/v1/users', [
                'role_id'  => $gudangRole['id'],
                'name'     => 'Duplicate User',
                'username' => 'admin',
                'password' => 'password123',
            ]);

        $result->assertStatus(400);
        
        $json = json_decode($result->getJSON(), true);
        $this->assertArrayHasKey('errors', $json);
        $this->assertArrayHasKey('username', $json['errors']);
    }

    public function testUpdateUserAsAdmin(): void
    {
        $token = $this->loginAsAdmin();

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->withBodyFormat('json')
            ->put('api/v1/users/2', [
                'name'  => 'Updated SPK User',
                'email' => 'updated@example.com',
            ]);

        $result->assertStatus(200);
        $result->assertJSONFragment(['message' => 'User updated successfully.']);
        
        $json = json_decode($result->getJSON(), true);
        $this->assertSame('Updated SPK User', $json['data']['name']);
        $this->assertSame('updated@example.com', $json['data']['email']);
    }

    public function testUpdateUserRole(): void
    {
        $token = $this->loginAsAdmin();
        $roleModel = new RoleModel();
        $gudangRole = $roleModel->findByName('gudang');

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->withBodyFormat('json')
            ->put('api/v1/users/2', [
                'role_id' => $gudangRole['id'],
            ]);

        $result->assertStatus(200);
        
        $json = json_decode($result->getJSON(), true);
        $this->assertSame($gudangRole['id'], $json['data']['role_id']);
        $this->assertSame('gudang', $json['data']['role']['name']);
    }

    public function testUpdateUserUsernameAsAdmin(): void
    {
        $token = $this->loginAsAdmin();

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->withBodyFormat('json')
            ->put('api/v1/users/2', [
                'username' => 'updatedspkuser',
            ]);

        $result->assertStatus(200);
        $result->assertJSONFragment(['message' => 'User updated successfully.']);

        $json = json_decode($result->getJSON(), true);
        $this->assertSame('updatedspkuser', $json['data']['username']);

        $showResult = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->get('api/v1/users/2');

        $showResult->assertStatus(200);
        $showJson = json_decode($showResult->getJSON(), true);
        $this->assertSame('updatedspkuser', $showJson['data']['username']);
    }

    public function testUpdateUserUsernameWithSameValueAsAdmin(): void
    {
        $token = $this->loginAsAdmin();

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->withBodyFormat('json')
            ->put('api/v1/users/2', [
                'username' => 'spkuser',
            ]);

        $result->assertStatus(200);
        $result->assertJSONFragment(['message' => 'User updated successfully.']);

        $json = json_decode($result->getJSON(), true);
        $this->assertSame('spkuser', $json['data']['username']);
    }

    public function testUpdateUserUsernameWithDuplicateValueFails(): void
    {
        $token = $this->loginAsAdmin();

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->withBodyFormat('json')
            ->put('api/v1/users/2', [
                'username' => 'admin',
            ]);

        $result->assertStatus(400);

        $json = json_decode($result->getJSON(), true);
        $this->assertArrayHasKey('errors', $json);
        $this->assertArrayHasKey('username', $json['errors']);
    }

    public function testActivateUser(): void
    {
        $token = $this->loginAsAdmin();
        $userProvider = new AppUserProvider();
        
        $userProvider->update(2, ['is_active' => false, 'active' => false]);

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->patch('api/v1/users/2/activate');

        $result->assertStatus(200);
        $result->assertJSONFragment(['message' => 'User activated successfully.']);
        
        $json = json_decode($result->getJSON(), true);
        $this->assertTrue($json['data']['is_active']);
    }

    public function testDeactivateUser(): void
    {
        $token = $this->loginAsAdmin();

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->patch('api/v1/users/2/deactivate');

        $result->assertStatus(200);
        $result->assertJSONFragment(['message' => 'User deactivated successfully.']);
        
        $json = json_decode($result->getJSON(), true);
        $this->assertFalse($json['data']['is_active']);
    }

    public function testDeactivatedUserCannotLogin(): void
    {
        $token = $this->loginAsAdmin();

        $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->patch('api/v1/users/2/deactivate');

        $result = $this->withBodyFormat('json')
            ->post('api/v1/auth/login', [
                'username' => 'spkuser',
                'password' => 'password123',
            ]);

        $result->assertStatus(401);
        $result->assertJSONFragment(['message' => 'Account is inactive or has been deleted.']);
    }

    public function testChangePassword(): void
    {
        $token = $this->loginAsAdmin();

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->withBodyFormat('json')
            ->patch('api/v1/users/2/password', [
                'password' => 'newpassword456',
            ]);

        $result->assertStatus(200);
        $this->assertStringContainsString('Password changed successfully', $result->getJSON());
        $this->assertStringContainsString('All access tokens have been revoked', $result->getJSON());
    }

    public function testOldPasswordFailsAfterChange(): void
    {
        $token = $this->loginAsAdmin();

        $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->withBodyFormat('json')
            ->patch('api/v1/users/2/password', [
                'password' => 'newpassword789',
            ]);

        $result = $this->withBodyFormat('json')
            ->post('api/v1/auth/login', [
                'username' => 'spkuser',
                'password' => 'password123',
            ]);

        $result->assertStatus(401);
        $result->assertJSONFragment(['message' => 'Invalid credentials.']);
    }

    public function testNewPasswordSucceedsAfterChange(): void
    {
        $token = $this->loginAsAdmin();

        $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->withBodyFormat('json')
            ->patch('api/v1/users/2/password', [
                'password' => 'brandnewpassword',
            ]);

        $result = $this->withBodyFormat('json')
            ->post('api/v1/auth/login', [
                'username' => 'spkuser',
                'password' => 'brandnewpassword',
            ]);

        $result->assertStatus(200);
        $result->assertJSONFragment(['message' => 'Login successful.']);
    }

    public function testPasswordChangeRevokesAllTokens(): void
    {
        $spkToken = $this->loginAsNonAdmin();
        $adminToken = $this->loginAsAdmin();

        $meResultBefore = $this->withHeaders(['Authorization' => 'Bearer ' . $spkToken])
            ->get('api/v1/auth/me');
        $meResultBefore->assertStatus(200);

        $this->withHeaders(['Authorization' => 'Bearer ' . $adminToken])
            ->withBodyFormat('json')
            ->patch('api/v1/users/2/password', [
                'password' => 'revokedpassword',
            ]);

        $meResultAfter = $this->withHeaders(['Authorization' => 'Bearer ' . $spkToken])
            ->get('api/v1/auth/me');

        $meResultAfter->assertStatus(401);
    }

    public function testDeleteUser(): void
    {
        $token = $this->loginAsAdmin();

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->delete('api/v1/users/2');

        $result->assertStatus(200);
        $result->assertJSONFragment(['message' => 'User deleted successfully.']);

        $showResult = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->get('api/v1/users/2');

        $showResult->assertStatus(404);
    }

    public function testDeleteUserRevokesTokens(): void
    {
        $spkToken = $this->loginAsNonAdmin();
        $adminToken = $this->loginAsAdmin();

        $meResultBefore = $this->withHeaders(['Authorization' => 'Bearer ' . $spkToken])
            ->get('api/v1/auth/me');
        $meResultBefore->assertStatus(200);

        $this->withHeaders(['Authorization' => 'Bearer ' . $adminToken])
            ->delete('api/v1/users/2');

        $meResultAfter = $this->withHeaders(['Authorization' => 'Bearer ' . $spkToken])
            ->get('api/v1/auth/me');

        $meResultAfter->assertStatus(401);
    }

    public function testCannotUpdateDeletedUser(): void
    {
        $token = $this->loginAsAdmin();

        $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->delete('api/v1/users/2');

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->withBodyFormat('json')
            ->put('api/v1/users/2', [
                'name' => 'Should Not Update',
            ]);

        $result->assertStatus(404);
        $result->assertJSONFragment(['message' => 'User not found.']);
    }

    public function testCannotActivateDeletedUser(): void
    {
        $token = $this->loginAsAdmin();

        $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->delete('api/v1/users/2');

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->patch('api/v1/users/2/activate');

        $result->assertStatus(404);
        $result->assertJSONFragment(['message' => 'User not found.']);
    }

    public function testCannotDeactivateDeletedUser(): void
    {
        $token = $this->loginAsAdmin();

        $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->delete('api/v1/users/2');

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->patch('api/v1/users/2/deactivate');

        $result->assertStatus(404);
        $result->assertJSONFragment(['message' => 'User not found.']);
    }

    public function testCannotChangePasswordForDeletedUser(): void
    {
        $token = $this->loginAsAdmin();

        $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->delete('api/v1/users/2');

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->withBodyFormat('json')
            ->patch('api/v1/users/2/password', [
                'password' => 'newpassword123',
            ]);

        $result->assertStatus(404);
        $result->assertJSONFragment(['message' => 'User not found.']);
    }

    public function testUpdateUserEmailPersistsAndAllowsDetailFetch(): void
    {
        $token = $this->loginAsAdmin();

        $updateResult = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->withBodyFormat('json')
            ->put('api/v1/users/2', [
                'email' => 'changed-email@example.com',
            ]);

        $updateResult->assertStatus(200);
        $updateJson = json_decode($updateResult->getJSON(), true);
        $this->assertSame('changed-email@example.com', $updateJson['data']['email']);

        $showResult = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->get('api/v1/users/2');

        $showResult->assertStatus(200);
        $showJson = json_decode($showResult->getJSON(), true);
        $this->assertSame('changed-email@example.com', $showJson['data']['email']);
    }
}
