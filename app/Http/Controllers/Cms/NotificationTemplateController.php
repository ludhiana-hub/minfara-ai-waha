<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cms\StoreNotificationTemplateRequest;
use App\Models\NotificationTemplate;
use Illuminate\Http\Request;

class NotificationTemplateController extends Controller
{
    public function index(Request $request)
    {
        $query = NotificationTemplate::query();

        if ($request->filter === 'active') {
            $query->where('is_active', true);
        } elseif ($request->filter === 'inactive') {
            $query->where('is_active', false);
        }

        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                  ->orWhere('trigger_key', 'like', "%$search%");
            });
        }

        $templates = $query->latest()->get();

        return view('cms.notification-templates.index', compact('templates'));
    }

    public function create()
    {
        $triggerKeys = NotificationTemplate::TRIGGER_KEYS;
        return view('cms.notification-templates.form', compact('triggerKeys'));
    }

    public function store(StoreNotificationTemplateRequest $request)
    {
        NotificationTemplate::create([
            'name'         => $request->name,
            'trigger_key'  => $request->trigger_key,
            'message_body' => $request->message_body,
            'is_active'    => $request->has('is_active'),
        ]);

        return redirect()->route('cms.notification-templates.index')
            ->with('success', 'Template notifikasi berhasil ditambahkan.');
    }

    public function edit(NotificationTemplate $notificationTemplate)
    {
        return view('cms.notification-templates.form', [
            'template'    => $notificationTemplate,
            'triggerKeys' => NotificationTemplate::TRIGGER_KEYS,
        ]);
    }

    public function update(StoreNotificationTemplateRequest $request, NotificationTemplate $notificationTemplate)
    {
        $notificationTemplate->update([
            'name'         => $request->name,
            'trigger_key'  => $request->trigger_key,
            'message_body' => $request->message_body,
            'is_active'    => $request->has('is_active'),
        ]);

        return redirect()->route('cms.notification-templates.index')
            ->with('success', 'Template notifikasi berhasil diperbarui.');
    }

    public function destroy(NotificationTemplate $notificationTemplate)
    {
        $notificationTemplate->delete();

        return response()->json(['success' => true, 'message' => 'Template berhasil dihapus.']);
    }
}
