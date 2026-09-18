<?php

namespace Tests\Feature;

use App\Models\{RolePermission, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportUserTest extends TestCase
{
    use RefreshDatabase;

    private function reportUser(): User
    {
        foreach (['view dashboard', 'view operators', 'view vehicles', 'view franchises', 'view permits', 'view renewals', 'view violations', 'view reports', 'view my reports', 'view notifications'] as $permission) {
            RolePermission::create(['role' => 'viewer', 'permission' => $permission]);
        }
        return User::factory()->create(['role' => 'viewer']);
    }

    public function test_report_user_can_view_dashboard_and_public_modules(): void
    {
        $user = $this->reportUser();
        $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertSee('Public reports dashboard')->assertSee('Reports')->assertSee('My reports')->assertSee('Notifications')->assertDontSee('Applications')->assertDontSee('User management');
        foreach ([route('reports.index'), route('reports.mine'), route('operators.index'), route('vehicles.index'), route('franchises.index'), route('permits.index'), route('renewals.index'), route('violations.index'), route('notifications.index'), route('profile.show')] as $url) {
            $this->actingAs($user)->get($url)->assertOk();
        }
    }

    public function test_report_user_cannot_access_management_or_application_routes(): void
    {
        $user = $this->reportUser();
        foreach ([route('applications.index'), route('operators.create'), route('vehicles.create'), route('users.index'), route('permissions.index')] as $url) {
            $this->actingAs($user)->get($url)->assertForbidden();
        }
        $this->actingAs($user)->post(route('operators.store'), [])->assertForbidden();
        $this->actingAs($user)->post(route('vehicles.store'), [])->assertForbidden();
    }

    public function test_report_user_profile_cannot_change_role_or_status(): void
    {
        $user = $this->reportUser();
        $this->actingAs($user)->put(route('profile.update'), ['name' => 'Public Reader', 'username' => $user->username, 'email' => $user->email, 'mobile_number' => '09171234567', 'address' => 'Updated address', 'role' => 'admin', 'status' => 'suspended'])->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Public Reader', 'role' => 'viewer', 'status' => 'active']);
    }
}