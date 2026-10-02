<?php

namespace Tests\Feature;

use App\Models\{Report, RolePermission, User};
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

    public function test_create_report_page_uses_leaflet_and_not_google_maps(): void
    {
        $user = $this->reportUser();

        $response = $this->actingAs($user)->get(route('reports.create'))->assertOk();

        $response->assertSee('unpkg.com/leaflet@1.9.4/dist/leaflet.css', false);
        $response->assertSee('unpkg.com/leaflet@1.9.4/dist/leaflet.js', false);
        $response->assertSee('tile.openstreetmap.org', false);
        $response->assertSee('OpenStreetMap', false);
        $response->assertSee('id="reportMap"', false);
        $response->assertSee('name="latitude"', false);
        $response->assertSee('name="longitude"', false);

        $content = $response->getContent();
        $this->assertStringNotContainsString('maps.googleapis.com', $content);
        $this->assertStringNotContainsString('google.maps', $content);
        $this->assertStringNotContainsString('Google Maps API key is not configured', $content);
    }

    public function test_report_is_stored_with_selected_map_coordinates(): void
    {
        $user = $this->reportUser();

        $this->actingAs($user)->post(route('reports.store'), [
            'report_type' => 'Illegal Parking',
            'date_submitted' => now()->toDateString(),
            'location' => 'Public Market Road',
            'latitude' => '14.59951234',
            'longitude' => '120.98425678',
            'description' => 'Tricycle parked along the road.',
        ])->assertRedirect();

        $report = Report::latest('id')->first();

        $this->assertSame($user->id, $report->submitted_by);
        $this->assertEqualsWithDelta(14.59951234, (float) $report->latitude, 0.00000001);
        $this->assertEqualsWithDelta(120.98425678, (float) $report->longitude, 0.00000001);
        $this->assertSame('Public Market Road', $report->location);
        $this->assertSame('Submitted', $report->status);
    }

    public function test_report_requires_a_valid_map_location(): void
    {
        $user = $this->reportUser();
        $payload = [
            'report_type' => 'Illegal Parking',
            'date_submitted' => now()->toDateString(),
            'description' => 'Tricycle parked along the road.',
        ];

        $this->actingAs($user)->post(route('reports.store'), $payload)
            ->assertSessionHasErrors(['latitude', 'longitude']);

        $this->actingAs($user)->post(route('reports.store'), $payload + ['latitude' => '999', 'longitude' => '999'])
            ->assertSessionHasErrors(['latitude', 'longitude']);

        $this->assertSame(0, Report::count());
    }

    public function test_saved_report_displays_its_location_on_the_map(): void
    {
        $user = $this->reportUser();
        $report = Report::create([
            'report_number' => 'RPT-TEST-1',
            'submitted_by' => $user->id,
            'report_type' => 'Road Obstruction',
            'date_submitted' => now()->toDateString(),
            'location' => 'Municipal Hall',
            'latitude' => 14.5995,
            'longitude' => 120.9842,
            'description' => 'Blocked entrance.',
            'status' => 'Submitted',
        ]);

        $this->actingAs($user)->get(route('reports.show', $report))
            ->assertOk()
            ->assertSee('id="reportDetailMap"', false)
            ->assertSee('14.599500', false);
    }
}