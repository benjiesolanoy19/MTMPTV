<?php

namespace Tests\Feature;

use App\Models\AdministratorApplication;
use App\Models\AuditLog;
use App\Models\Report;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdministratorApplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_non_administrator_can_apply_once_without_changing_role(): void
    {
        $administrator = User::factory()->create(['role' => 'admin']);
        $applicant = User::factory()->create(['role' => 'viewer']);

        $this->actingAs($applicant)->get(route('administrator-application.create'))
            ->assertOk()
            ->assertSee('Apply as Administrator')
            ->assertSee($applicant->roleLabel());

        $this->actingAs($applicant)->post(route('administrator-application.store'), [
            'user_id' => $administrator->id,
            'role' => 'admin',
            'status' => 'approved',
            'reason' => 'I have relevant experience.',
            'experience' => 'I have managed administrative workflows.',
        ])->assertRedirect(route('administrator-application.create'));

        $application = AdministratorApplication::firstOrFail();
        $this->assertSame($applicant->id, $application->user_id);
        $this->assertSame('pending', $application->status);
        $this->assertSame('viewer', $applicant->fresh()->role);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $administrator->id,
            'title' => 'New administrator application',
            'action_url' => route('administrator-applications.show', $application, false),
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $applicant->id,
            'module' => 'Administrator Applications',
            'record_id' => $application->id,
            'action' => 'submitted',
        ]);

        $this->actingAs($applicant)->post(route('administrator-application.store'), [
            'reason' => 'A duplicate request.',
        ])->assertSessionHasErrors('application');

        $this->assertSame(1, AdministratorApplication::where('user_id', $applicant->id)->count());
    }

    public function test_admin_approval_promotes_applicant_and_records_transactional_side_effects(): void
    {
        $administrator = User::factory()->create(['role' => 'admin']);
        $applicant = User::factory()->create(['role' => 'staff']);
        $application = AdministratorApplication::create([
            'user_id' => $applicant->id,
            'reason' => 'I can support system administration.',
            'status' => 'pending',
            'submitted_at' => now(),
        ]);

        $this->actingAs($administrator)->get(route('administrator-applications.show', $application))
            ->assertOk()
            ->assertSee($applicant->name)
            ->assertSee('Approve and promote');
        $this->actingAs($administrator)->get(route('administrator-applications.index', [
            'status' => 'pending',
            'search' => $applicant->username,
        ]))->assertOk()->assertSee($applicant->username);

        $this->actingAs($administrator)->post(route('administrator-applications.approve', $application), [
            'admin_remarks' => 'Approved after review.',
        ])->assertRedirect(route('administrator-applications.show', $application));

        $this->assertSame('admin', $applicant->fresh()->role);
        $this->assertDatabaseHas('administrator_applications', [
            'id' => $application->id,
            'status' => 'approved',
            'reviewed_by' => $administrator->id,
            'admin_remarks' => 'Approved after review.',
        ]);
        $this->assertNotNull($application->fresh()->reviewed_at);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $applicant->id,
            'title' => 'Administrator application approved',
            'action_url' => route('dashboard', [], false),
        ]);
        $this->assertSame(1, AuditLog::where('record_id', $applicant->id)->where('module', 'User Management')->count());
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $administrator->id,
            'record_id' => $application->id,
            'action' => 'approved',
            'module' => 'Administrator Applications',
        ]);
        $this->actingAs($administrator)->post(route('administrator-applications.approve', $application))
            ->assertSessionHasErrors('application');
    }

    public function test_admin_rejection_leaves_role_unchanged_notifies_applicant_and_allows_reapplication(): void
    {
        $administrator = User::factory()->create(['role' => 'admin']);
        $applicant = User::factory()->create(['role' => 'operator']);
        $application = AdministratorApplication::create([
            'user_id' => $applicant->id,
            'reason' => 'Request for review.',
            'status' => 'pending',
            'submitted_at' => now(),
        ]);

        $this->actingAs($administrator)->post(route('administrator-applications.reject', $application), [
            'admin_remarks' => 'Please gain more operational experience first.',
        ])->assertRedirect(route('administrator-applications.show', $application));

        $this->assertSame('operator', $applicant->fresh()->role);
        $this->assertDatabaseHas('administrator_applications', [
            'id' => $application->id,
            'status' => 'rejected',
            'reviewed_by' => $administrator->id,
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $applicant->id,
            'title' => 'Administrator application rejected',
        ]);
        $this->actingAs($applicant)->get(route('administrator-application.create'))
            ->assertOk()
            ->assertSee('may submit a new application');

        $this->actingAs($applicant)->post(route('administrator-application.store'), [
            'reason' => 'I have since gained experience.',
        ])->assertRedirect(route('administrator-application.create'));
        $this->assertSame(2, AdministratorApplication::where('user_id', $applicant->id)->count());
    }

    public function test_non_administrators_cannot_access_admin_review_or_change_user_roles(): void
    {
        $viewer = User::factory()->create(['role' => 'viewer']);
        $application = AdministratorApplication::create([
            'user_id' => User::factory()->create(['role' => 'staff'])->id,
            'reason' => 'Review request.',
            'status' => 'pending',
            'submitted_at' => now(),
        ]);

        $this->actingAs($viewer)->get(route('administrator-applications.index'))->assertForbidden();
        $this->actingAs($viewer)->get(route('administrator-applications.show', $application))->assertForbidden();
        $this->actingAs($viewer)->get(route('system-activity.index'))->assertForbidden();
        $this->actingAs($viewer)->post(route('administrator-applications.approve', $application))->assertForbidden();
        $this->actingAs($viewer)->post(route('administrator-applications.reject', $application), [
            'admin_remarks' => 'No.',
        ])->assertForbidden();
        $this->actingAs($viewer)->patch(route('users.update', $viewer), [
            'role' => 'admin',
            'status' => 'active',
        ])->assertForbidden();

        $this->assertSame('pending', $application->fresh()->status);
        $this->assertSame('viewer', $viewer->fresh()->role);
    }

    public function test_administrator_cannot_review_own_application_and_application_status_is_controlled(): void
    {
        $administrator = User::factory()->create(['role' => 'admin']);
        $application = AdministratorApplication::create([
            'user_id' => $administrator->id,
            'reason' => 'Self review attempt.',
            'status' => 'pending',
            'submitted_at' => now(),
        ]);

        $this->actingAs($administrator)->post(route('administrator-applications.approve', $application))
            ->assertForbidden();
        $this->actingAs($administrator)->post(route('administrator-applications.reject', $application), [
            'admin_remarks' => 'Self review attempt.',
        ])->assertForbidden();
        $this->actingAs($administrator)->get(route('administrator-application.create'))->assertForbidden();
        $this->actingAs($administrator)->post(route('administrator-application.store'), [
            'reason' => 'Should not apply.',
        ])->assertForbidden();

        $this->assertSame('pending', $application->fresh()->status);
    }

    public function test_administrator_cannot_remove_own_privileges(): void
    {
        $administrator = User::factory()->create(['role' => 'admin']);

        $this->actingAs($administrator)->patch(route('users.update', $administrator), [
            'role' => 'staff',
            'status' => 'active',
        ])->assertSessionHasErrors('user');

        $this->assertSame('admin', $administrator->fresh()->role);
        $this->assertSame('active', $administrator->fresh()->status);
    }

    public function test_administrator_dashboard_uses_live_database_statistics_and_activity(): void
    {
        $administrator = User::factory()->create(['role' => 'admin']);
        Report::create([
            'report_number' => 'ADMIN-DASH-1',
            'submitted_by' => User::factory()->create(['role' => 'viewer'])->id,
            'report_type' => 'Road obstruction',
            'date_submitted' => today(),
            'description' => 'Awaiting review.',
            'status' => 'Pending',
        ]);
        Report::create([
            'report_number' => 'ADMIN-DASH-2',
            'report_type' => 'Road obstruction',
            'date_submitted' => today(),
            'description' => 'Resolved report.',
            'status' => 'Resolved',
        ]);
        AdministratorApplication::create([
            'user_id' => User::factory()->create(['role' => 'viewer'])->id,
            'reason' => 'Pending request.',
            'status' => 'pending',
            'submitted_at' => now(),
        ]);
        AuditLog::create([
            'user_id' => $administrator->id,
            'action' => 'test',
            'module' => 'Test',
            'description' => 'Live activity row',
        ]);

        $this->actingAs($administrator)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Total users')
            ->assertSee('Pending reports')
            ->assertSee('Live activity row')
            ->assertViewHas('stats', fn (array $stats) => $stats['users'] === 3
                && $stats['reports'] === 2
                && $stats['pending_reports'] === 1
                && $stats['resolved_reports'] === 1
                && $stats['pending_administrator_applications'] === 1);
        $this->actingAs($administrator)->get(route('system-activity.index'))
            ->assertOk()
            ->assertSee('Live activity row');
    }

    public function test_database_seeder_creates_the_initial_admin_only_from_configured_secret_and_never_resets_it(): void
    {
        config(['admin.initial_password' => 'A-test-only-setup-secret-93']);

        Artisan::call('db:seed', [
            '--class' => DatabaseSeeder::class,
            '--no-interaction' => true,
        ]);

        $administrator = User::where('username', 'benjadmin')->firstOrFail();
        $this->assertSame('admin', $administrator->role);
        $this->assertTrue(Hash::check('A-test-only-setup-secret-93', $administrator->password));
        $this->assertNotSame('A-test-only-setup-secret-93', $administrator->password);

        $administrator->update(['password' => 'Another-test-only-secret-23']);
        config(['admin.initial_password' => 'Changed-config-secret-62']);
        app(DatabaseSeeder::class)->run();

        $this->assertSame(1, User::where('username', 'benjadmin')->count());
        $this->assertTrue(Hash::check('Another-test-only-secret-23', $administrator->fresh()->password));
    }
}
