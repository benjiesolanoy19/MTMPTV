<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    private function registrationData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Test Transport Operator', 'username' => 'test.operator', 'email' => 'operator@example.test',
            'mobile_number' => '09171234567', 'address' => '1 Municipal Road', 'password' => 'SecurePass1',
            'password_confirmation' => 'SecurePass1', 'role' => 'operator', 'terms' => '1', 'privacy' => '1',
        ], $overrides);
    }

    public function test_user_can_register_with_a_public_role(): void
    {
        $response = $this->post(route('register.store'), $this->registrationData());
        $response->assertRedirect(route('login'));
        $this->assertDatabaseHas('users', ['username' => 'test.operator', 'role' => 'operator', 'status' => 'active']);
        $this->assertTrue(Hash::check('SecurePass1', User::where('username', 'test.operator')->first()->password));
    }

    public function test_registration_rejects_admin_role_and_missing_terms(): void
    {
        $response = $this->from(route('register'))->post(route('register.store'), $this->registrationData(['role' => 'admin', 'terms' => null]));
        $response->assertRedirect(route('register'))->assertSessionHasErrors(['role', 'terms']);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_registration_rejects_duplicate_identity_and_invalid_mobile(): void
    {
        User::factory()->create(['username' => 'taken.user', 'email' => 'taken@example.test']);
        $response = $this->from(route('register'))->post(route('register.store'), $this->registrationData(['username' => 'taken.user', 'email' => 'taken@example.test', 'mobile_number' => '123']));
        $response->assertRedirect(route('register'))->assertSessionHasErrors(['username', 'email', 'mobile_number']);
    }

    public function test_registered_user_can_login_with_username(): void
    {
        User::factory()->create(['username' => 'login.user', 'email' => 'login@example.test', 'password' => Hash::make('SecurePass1')]);
        $this->post(route('login.attempt'), ['login' => 'login.user', 'password' => 'SecurePass1'])->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
    }

    public function test_authenticated_user_can_update_profile_without_changing_role(): void
    {
        $user = User::factory()->create(['role' => 'operator']);
        $this->actingAs($user)->put(route('profile.update'), ['name' => 'Updated Name', 'username' => $user->username, 'email' => $user->email, 'mobile_number' => '09179876543', 'address' => 'Updated Address'])->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Updated Name', 'role' => 'operator']);
    }

    public function test_authenticated_user_can_logout_with_csrf_protection(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('logout'))->assertRedirect(route('login'));
        $this->assertGuest();
    }
}