<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function preview(Request $request)
    {
        $user = $request->user();
        $this->authorizeNotifications($user);
        $notifications = $user->notifications()->latest()->limit(5)->get();

        return response()->json([
            'notifications' => $notifications->map(fn ($notification) => [
                'id' => $notification->id,
                'title' => $notification->title,
                'message' => $notification->message,
                'type' => $notification->type,
                'read_at' => $notification->read_at?->toIso8601String(),
                'created_at' => $notification->created_at->diffForHumans(),
                'action_url' => $this->safeActionUrl($notification->action_url),
                'read_url' => route('notifications.read', $notification, false),
            ]),
            'unread_count' => $user->notifications()->whereNull('read_at')->count(),
        ]);
    }

    public function index()
    {
        $notifications = auth()->user()->notifications()->latest()->paginate(15);

        return view('report-user.notifications.index', compact('notifications'));
    }

    public function read(Request $request, int $notification)
    {
        $this->authorizeNotifications($request->user());
        $updated = $request->user()->notifications()->whereKey($notification)->whereNull('read_at')->update(['read_at' => now()]);

        if ($request->expectsJson()) {
            return response()->json([
                'updated' => $updated > 0,
                'unread_count' => $request->user()->notifications()->whereNull('read_at')->count(),
            ]);
        }

        return back();
    }

    public function readAll(Request $request)
    {
        $user = $request->user();
        $this->authorizeNotifications($user);
        $user->notifications()->whereNull('read_at')->update(['read_at' => now()]);

        if ($request->expectsJson()) {
            return response()->json(['unread_count' => 0]);
        }

        return back();
    }

    private function authorizeNotifications($user): void
    {
        abort_unless(
            $user && (
                $user->hasPermission('view notifications')
                || $user->hasPermission('operator notifications')
                || $user->hasPermission('vehicle owner notifications')
            ),
            403
        );
    }

    private function safeActionUrl(?string $url): ?string
    {
        return is_string($url)
            && str_starts_with($url, '/')
            && ! str_starts_with($url, '//')
            && ! str_contains($url, '\\')
                ? $url
                : null;
    }
}
