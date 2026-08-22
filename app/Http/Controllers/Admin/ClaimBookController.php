<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\ClaimBook\ReplyClaimBookEntryAction;
use App\Actions\Admin\ClaimBook\UpdateClaimBookEntryStatusAction;
use App\Enums\Claims\ClaimBookStatus;
use App\Enums\Claims\ClaimBookType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReplyClaimBookEntryRequest;
use App\Http\Requests\Admin\UpdateClaimBookEntryRequest;
use App\Models\Claims\ClaimBookEntry;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

class ClaimBookController extends Controller
{
    private const PER_PAGE = 15;

    public function complaints(Request $request): View
    {
        return $this->index($request, ClaimBookType::Complaint);
    }

    public function claims(Request $request): View
    {
        return $this->index($request, ClaimBookType::Claim);
    }

    public function show(ClaimBookEntry $claimBookEntry): View
    {
        $claimBookEntry->load(['user.customerProfile', 'handler']);

        return view('admin.claim-book.show', [
            'entry' => $claimBookEntry,
            'statuses' => ClaimBookStatus::cases(),
            'listRoute' => $claimBookEntry->claim_type === ClaimBookType::Complaint
                ? 'admin.claim-book.complaints'
                : 'admin.claim-book.claims',
        ]);
    }

    public function update(
        UpdateClaimBookEntryRequest $request,
        ClaimBookEntry $claimBookEntry,
        UpdateClaimBookEntryStatusAction $updateStatus,
    ): RedirectResponse {
        $updateStatus->execute(
            $claimBookEntry,
            $request->status(),
            $request->user(),
            $request->adminNotes(),
        );

        return redirect()
            ->route('admin.claim-book.show', $claimBookEntry)
            ->with('status', 'Estado actualizado correctamente.');
    }

    public function reply(
        ReplyClaimBookEntryRequest $request,
        ClaimBookEntry $claimBookEntry,
        ReplyClaimBookEntryAction $replyEntry,
    ): RedirectResponse {
        $status = null;
        $statusValue = $request->input('status');
        if (is_string($statusValue) && $statusValue !== '') {
            $status = ClaimBookStatus::tryFrom($statusValue);
        }

        try {
            $replyEntry->execute(
                $claimBookEntry,
                $request->reply(),
                $request->user(),
                $status,
                $request->adminNotes(),
            );
        } catch (Throwable) {
            return redirect()
                ->route('admin.claim-book.show', $claimBookEntry)
                ->withErrors(['admin_reply' => 'No se pudo enviar el correo al cliente. Revisa la configuración SMTP e inténtalo de nuevo.']);
        }

        return redirect()
            ->route('admin.claim-book.show', $claimBookEntry)
            ->with('status', 'Respuesta enviada al correo del cliente correctamente.');
    }

    private function index(Request $request, ClaimBookType $type): View
    {
        $search = trim((string) $request->query('search', ''));
        $status = ClaimBookStatus::tryFrom((string) $request->query('status', ''));

        $entries = ClaimBookEntry::query()
            ->ofType($type)
            ->with(['user.customerProfile'])
            ->when(
                $search !== '',
                fn (Builder $query) => $query->where(function (Builder $inner) use ($search): void {
                    $inner->where('code', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('document', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                }),
            )
            ->when(
                $status !== null,
                fn (Builder $query) => $query->where('status', $status->value),
            )
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('admin.claim-book.index', [
            'entries' => $entries,
            'type' => $type,
            'statuses' => ClaimBookStatus::cases(),
            'filters' => [
                'search' => $search,
                'status' => $status?->value,
            ],
            'hasActiveFilters' => $search !== '' || $status !== null,
            'indexRoute' => $type === ClaimBookType::Complaint
                ? 'admin.claim-book.complaints'
                : 'admin.claim-book.claims',
        ]);
    }
}
