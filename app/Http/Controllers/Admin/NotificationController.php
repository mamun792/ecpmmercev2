<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Notification;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $query = Notification::orderBy('created_at', 'desc');

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

        return Inertia::render('Admin/Notifications/Index', [
            'notifications' => $notifications
        ]);
    }
}
