<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Notification;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $query = Notification::query();

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by read/unread status
        if ($request->filled('status')) {
            if ($request->status === 'read') {
                $query->where('is_read', true);
            } elseif ($request->status === 'unread') {
                $query->where('is_read', false);
            }
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Search in notification data
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('type', 'like', "%{$search}%")
                  ->orWhere('data', 'like', "%{$search}%");
            });
        }

        $query->orderBy('is_read', 'asc')
              ->orderBy('created_at', 'desc');

        $notifications = $query->paginate(20)->through(function ($n) {
            $data = $n->data;
            if (is_string($data)) {
                $decoded = json_decode($data, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $data = $decoded;
                }
            }
            return [
                'id' => $n->id,
                'type' => $n->type,
                'order_id' => $n->order_id ?? null,
                'data' => $data,
                'is_read' => (bool)$n->is_read,
                'created_at' => $n->created_at,
            ];
        });

        // Get notification types for filter dropdown
        $types = Notification::select('type')
            ->distinct()
            ->pluck('type')
            ->toArray();

        return Inertia::render('Admin/Notifications/Index', [
            'notifications' => $notifications,
            'types' => $types,
            'filters' => [
                'type' => $request->type,
                'status' => $request->status,
                'date_from' => $request->date_from,
                'date_to' => $request->date_to,
                'search' => $request->search,
            ]
        ]);
    }

    /**
     * Mark a single notification as read
     */
    public function markAsRead(Notification $notification)
    {
        try {
            $notification->update(['is_read' => true]);

            return response()->json([
                'success' => true,
                'message' => 'Notification marked as read'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark notification as read'
            ], 500);
        }
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead()
    {
        try {
            Notification::where('is_read', false)->update(['is_read' => true]);

            return response()->json([
                'success' => true,
                'message' => 'All notifications marked as read'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark all notifications as read'
            ], 500);
        }
    }

    /**
     * Mark selected notifications as read (bulk action)
     */
    public function markMultipleAsRead(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:notifications,id'
        ]);

        try {
            Notification::whereIn('id', $request->ids)->update(['is_read' => true]);

            return response()->json([
                'success' => true,
                'message' => count($request->ids) . ' notifications marked as read'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark notifications as read'
            ], 500);
        }
    }

    /**
     * Delete all read notifications
     */
    public function clearRead()
    {
        try {
            $count = Notification::where('is_read', true)->delete();

            return response()->json([
                'success' => true,
                'message' => "{$count} read notifications cleared"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear notifications'
            ], 500);
        }
    }
}
