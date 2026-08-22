<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\Contacts\ReplyContactMessageAction;
use App\Actions\Admin\Contacts\UpdateContactMessageStatusAction;
use App\Enums\Contacts\ContactMessageStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReplyContactMessageRequest;
use App\Http\Requests\Admin\UpdateContactMessageRequest;
use App\Models\Contacts\ContactMessage;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

class ContactMessageController extends Controller
{
    private const PER_PAGE = 15;

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $status = ContactMessageStatus::tryFrom((string) $request->query('status', ''));

        $messages = ContactMessage::query()
            ->with(['user.customerProfile'])
            ->when(
                $search !== '',
                fn (Builder $query) => $query->where(function (Builder $inner) use ($search): void {
                    $inner->where('code', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('document', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('message', 'like', "%{$search}%");
                }),
            )
            ->when(
                $status !== null,
                fn (Builder $query) => $query->where('status', $status->value),
            )
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('admin.contacts.index', [
            'messages' => $messages,
            'statuses' => ContactMessageStatus::cases(),
            'filters' => [
                'search' => $search,
                'status' => $status?->value,
            ],
            'hasActiveFilters' => $search !== '' || $status !== null,
        ]);
    }

    public function show(ContactMessage $contactMessage): View
    {
        $contactMessage->load(['user.customerProfile', 'handler']);

        return view('admin.contacts.show', [
            'contact' => $contactMessage,
            'statuses' => ContactMessageStatus::cases(),
        ]);
    }

    public function update(
        UpdateContactMessageRequest $request,
        ContactMessage $contactMessage,
        UpdateContactMessageStatusAction $updateStatus,
    ): RedirectResponse {
        $updateStatus->execute(
            $contactMessage,
            $request->status(),
            $request->user(),
            $request->adminNotes(),
        );

        return redirect()
            ->route('admin.contacts.show', $contactMessage)
            ->with('status', 'Estado actualizado correctamente.');
    }

    public function reply(
        ReplyContactMessageRequest $request,
        ContactMessage $contactMessage,
        ReplyContactMessageAction $replyMessage,
    ): RedirectResponse {
        $status = null;
        $statusValue = $request->input('status');
        if (is_string($statusValue) && $statusValue !== '') {
            $status = ContactMessageStatus::tryFrom($statusValue);
        }

        try {
            $replyMessage->execute(
                $contactMessage,
                $request->reply(),
                $request->user(),
                $status,
                $request->adminNotes(),
            );
        } catch (Throwable) {
            return redirect()
                ->route('admin.contacts.show', $contactMessage)
                ->withErrors(['admin_reply' => 'No se pudo enviar el correo al cliente. Revisa la configuración SMTP e inténtalo de nuevo.']);
        }

        return redirect()
            ->route('admin.contacts.show', $contactMessage)
            ->with('status', 'Respuesta enviada al correo del cliente correctamente.');
    }
}
