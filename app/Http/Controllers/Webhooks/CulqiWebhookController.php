<?php

namespace App\Http\Controllers\Webhooks;

use App\Actions\Payments\HandleCulqiWebhookAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CulqiWebhookController extends Controller
{
    public function __construct(
        private readonly HandleCulqiWebhookAction $handleWebhook,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $payload = $request->all();

        Log::info('Culqi webhook recibido', [
            'type' => $payload['type'] ?? null,
            'id' => $payload['id'] ?? null,
        ]);

        $this->handleWebhook->execute($payload);

        return response()->json(['received' => true]);
    }
}
