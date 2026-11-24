<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SiteMessageController extends Controller
{
    public function index(Request $request): Response
    {
        $status = (string) $request->query('status', '');

        $items = ContactMessage::with('handler:id,name')
            ->when(in_array($status, ['new', 'handled'], true), fn ($q) => $q->where('status', $status))
            ->orderByRaw("status = 'new' desc")->orderByDesc('id')
            ->paginate(15)->withQueryString()
            ->through(fn (ContactMessage $m) => [
                'id' => $m->id, 'name' => $m->name, 'email' => $m->email, 'phone' => $m->phone, 'subject' => $m->subject, 'message' => $m->message,
                'at' => $m->created_at->format('d/m/Y H:i'), 'status' => $m->status,
                'handled_by' => $m->handler?->name, 'handled_at' => $m->handled_at?->format('d/m/Y H:i'),
            ]);

        return Inertia::render('SiteAdmin/Messages', ['items' => $items, 'status' => $status]);
    }

    public function handle(Request $request, ContactMessage $message): RedirectResponse
    {
        $message->update(['status' => 'handled', 'handled_by' => $request->user()->id, 'handled_at' => now()]);

        return back()->with('success', 'Pesan ditandai sudah ditangani.');
    }

    public function destroy(ContactMessage $message): RedirectResponse
    {
        $message->delete();

        return back()->with('success', 'Pesan dihapus.');
    }
}
