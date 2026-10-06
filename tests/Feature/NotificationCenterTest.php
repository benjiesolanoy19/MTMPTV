<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationCenterTest extends TestCase
{
    use RefreshDatabase;

    private function notificationPermission(User $user): void
    {
        RolePermission::create(['role' => $user->role, 'permission' => 'view dashboard']);
        RolePermission::create(['role' => $user->role, 'permission' => 'view notifications']);
    }

    public function test_topbar_shows_only_the_authenticated_users_database_notifications_and_unread_count(): void
    {
        $viewer = User::factory()->create(['role' => 'viewer']);
        $this->notificationPermission($viewer);
        Notification::create([
            'user_id' => $viewer->id,
            'title' => 'Your report was received',
            'message' => 'The report has been added to the review queue.',
            'type' => 'info',
        ]);
        Notification::create([
            'user_id' => $viewer->id,
            'title' => 'An older notification',
            'message' => 'This notification was already read.',
            'type' => 'success',
            'read_at' => now(),
        ]);
        Notification::create([
            'user_id' => User::factory()->create(['role' => 'viewer'])->id,
            'title' => 'Private notification belonging to someone else',
            'message' => 'Must not be included.',
            'type' => 'info',
        ]);

        $this->actingAs($viewer)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('aria-label="Notifications"', false)
            ->assertSee('Your report was received')
            ->assertSee('1 unread')
            ->assertDontSee('Private notification belonging to someone else');
    }

    public function test_notification_preview_is_owned_by_the_current_user_and_only_includes_recent_five(): void
    {
        $viewer = User::factory()->create(['role' => 'viewer']);
        $this->notificationPermission($viewer);
        foreach (range(1, 6) as $number) {
            Notification::create([
                'user_id' => $viewer->id,
                'title' => 'Viewer notice '.$number,
                'message' => 'Owned message '.$number,
                'type' => 'info',
                'created_at' => now()->subMinutes($number),
            ]);
        }
        Notification::create([
            'user_id' => User::factory()->create(['role' => 'viewer'])->id,
            'title' => 'Not your notice',
            'message' => 'Private data',
            'type' => 'info',
        ]);

        $this->actingAs($viewer)->getJson(route('notifications.preview'))
            ->assertOk()
            ->assertJsonPath('unread_count', 6)
            ->assertJsonCount(5, 'notifications')
            ->assertJsonMissing(['title' => 'Not your notice'])
            ->assertJsonFragment(['title' => 'Viewer notice 1']);
    }

    public function test_mark_read_checks_ownership_and_mark_all_updates_only_the_current_users_rows(): void
    {
        $viewer = User::factory()->create(['role' => 'viewer']);
        $this->notificationPermission($viewer);
        $own = Notification::create([
            'user_id' => $viewer->id,
            'title' => 'Own',
            'message' => 'Own message',
            'type' => 'info',
        ]);
        $other = Notification::create([
            'user_id' => User::factory()->create(['role' => 'viewer'])->id,
            'title' => 'Other',
            'message' => 'Other message',
            'type' => 'info',
        ]);

        $this->actingAs($viewer)->patchJson(route('notifications.read', $other))
            ->assertOk()
            ->assertJsonPath('updated', false)
            ->assertJsonPath('unread_count', 1);
        $this->assertNull($other->fresh()->read_at);

        $this->actingAs($viewer)->patchJson(route('notifications.read', $own))
            ->assertOk()
            ->assertJsonPath('updated', true)
            ->assertJsonPath('unread_count', 0);
        $this->assertNotNull($own->fresh()->read_at);
        $this->assertNull($other->fresh()->read_at);

        $ownSecond = Notification::create([
            'user_id' => $viewer->id,
            'title' => 'Second own notice',
            'message' => 'Second message',
            'type' => 'info',
        ]);
        $this->actingAs($viewer)->postJson(route('notifications.read-all'))
            ->assertOk()
            ->assertJsonPath('unread_count', 0);
        $this->assertNotNull($ownSecond->fresh()->read_at);
        $this->assertNull($other->fresh()->read_at);
    }

    public function test_notification_endpoints_require_the_roles_notification_capability(): void
    {
        $viewer = User::factory()->create(['role' => 'viewer']);
        Notification::create([
            'user_id' => $viewer->id,
            'title' => 'Private',
            'message' => 'Message',
            'type' => 'info',
        ]);

        $this->actingAs($viewer)->getJson(route('notifications.preview'))->assertForbidden();
        $this->actingAs($viewer)->postJson(route('notifications.read-all'))->assertForbidden();
    }

    public function test_operator_and_vehicle_owner_notification_capabilities_work_with_their_existing_roles(): void
    {
        foreach ([
            ['role' => 'operator', 'permission' => 'operator notifications'],
            ['role' => 'vehicle_owner', 'permission' => 'vehicle owner notifications'],
        ] as $case) {
            $user = User::factory()->create(['role' => $case['role']]);
            RolePermission::create(['role' => $case['role'], 'permission' => $case['permission']]);
            Notification::create([
                'user_id' => $user->id,
                'title' => ucfirst(str_replace('_', ' ', $case['role'])).' notice',
                'message' => 'Role-scoped notification.',
                'type' => 'info',
            ]);

            $this->actingAs($user)->get(route('profile.show'))
                ->assertOk()
                ->assertSee('aria-label="Notifications"', false);
            $this->actingAs($user)->getJson(route('notifications.preview'))
                ->assertOk()
                ->assertJsonPath('unread_count', 1);
        }
    }

    public function test_external_notification_action_urls_are_not_returned_as_navigation_targets(): void
    {
        $viewer = User::factory()->create(['role' => 'viewer']);
        $this->notificationPermission($viewer);
        Notification::create([
            'user_id' => $viewer->id,
            'title' => 'Suspicious link',
            'message' => 'Message',
            'type' => 'info',
            'action_url' => 'https://example.invalid/phishing',
        ]);

        $this->actingAs($viewer)->getJson(route('notifications.preview'))
            ->assertOk()
            ->assertJsonPath('notifications.0.action_url', null);
    }
}
