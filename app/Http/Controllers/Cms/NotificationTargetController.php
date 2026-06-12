<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cms\StoreNotificationTargetRequest;
use App\Models\NotificationTarget;
use Illuminate\Http\Request;

class NotificationTargetController extends Controller
{
    public function index(Request $request)
    {
        $query = NotificationTarget::query();

        if ($request->filter === 'active') {
            $query->where('is_active', true);
        } elseif ($request->filter === 'inactive') {
            $query->where('is_active', false);
        }

        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                  ->orWhere('phone_number', 'like', "%$search%");
            });
        }

        $targets = $query->latest()->get();

        return view('cms.notification-targets.index', compact('targets'));
    }

    public function create()
    {
        return view('cms.notification-targets.form');
    }

    public function store(StoreNotificationTargetRequest $request)
    {
        NotificationTarget::create([
            'name'         => $request->name,
            'phone_number' => NotificationTarget::normalizePhone($request->phone_number),
            'is_active'    => $request->has('is_active'),
        ]);

        return redirect()->route('cms.notification-targets.index')
            ->with('success', 'Target notifikasi berhasil ditambahkan.');
    }

    public function edit(NotificationTarget $notificationTarget)
    {
        return view('cms.notification-targets.form', ['target' => $notificationTarget]);
    }

    public function update(StoreNotificationTargetRequest $request, NotificationTarget $notificationTarget)
    {
        $notificationTarget->update([
            'name'         => $request->name,
            'phone_number' => NotificationTarget::normalizePhone($request->phone_number),
            'is_active'    => $request->has('is_active'),
        ]);

        return redirect()->route('cms.notification-targets.index')
            ->with('success', 'Target notifikasi berhasil diperbarui.');
    }

    public function destroy(NotificationTarget $notificationTarget)
    {
        $notificationTarget->delete();

        return response()->json(['success' => true, 'message' => 'Target berhasil dihapus.']);
    }

    public function toggle(NotificationTarget $notificationTarget)
    {
        $notificationTarget->update(['is_active' => !$notificationTarget->is_active]);

        return response()->json([
            'success'   => true,
            'is_active' => $notificationTarget->is_active,
            'message'   => 'Status target berhasil diubah.',
        ]);
    }
}
