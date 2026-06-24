<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PushSubscriptionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'endpoint'                         => 'required|url',
            'keys.auth'                        => 'required|string',
            'keys.p256dh'                      => 'required|string',
        ]);

        $request->user()->updatePushSubscription(
            endpoint: $request->endpoint,
            key:      $request->input('keys.p256dh'),
            token:    $request->input('keys.auth'),
            contentEncoding: $request->input('contentEncoding', 'aesgcm'),
        );

        return response()->json(['status' => 'subscribed']);
    }

    public function destroy(Request $request): JsonResponse
    {
        $request->validate(['endpoint' => 'required|url']);

        $request->user()->deletePushSubscription($request->endpoint);

        return response()->json(['status' => 'unsubscribed']);
    }
}
