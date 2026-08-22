<?php

namespace App\Http\Controllers\Webhooks;

use App\Actions\Payments\HandleMercadoPagoWebhookAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class MercadoPagoWebhookController extends Controller
{
    public function __invoke(Request $request, HandleMercadoPagoWebhookAction $action): JsonResponse
    {
        try {
            $action->execute($request->all());
        } catch (Throwable $e) {
            Log::error('MercadoPago webhook error', [
                'message' => $e->getMessage(),
                'payload' => $request->all(),
            ]);
        }

        return response()->json(['ok' => true]);
    }
}
