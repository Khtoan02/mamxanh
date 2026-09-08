<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ContactController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'all');

        $messages = ContactMessage::latest()
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->paginate(20)
            ->withQueryString();

        // Only mark what's actually rendered on this page as read — marking
        // the whole unread set (including messages on other pages the user
        // hasn't looked at yet) made the unread badge lie.
        ContactMessage::whereIn('id', $messages->pluck('id'))->where('read', false)->update(['read' => true]);

        return view('admin.contacts.index', [
            'messages' => $messages,
            'status' => $status,
            'statusCounts' => [
                'all' => ContactMessage::count(),
                ...ContactMessage::selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status')->all(),
            ],
        ]);
    }

    public function updateStatus(Request $request, ContactMessage $contact)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(ContactMessage::STATUS_LABELS))],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $contact->update($data);

        return back()->with('status', 'Đã cập nhật.');
    }

    public function destroy(ContactMessage $contact)
    {
        $contact->delete();

        return back()->with('status', 'Đã xoá.');
    }
}
