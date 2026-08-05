<?php

namespace App\Http\Controllers;

use App\Models\NewsletterSubscriber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    public function subscribe(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $existing = NewsletterSubscriber::where('email', $request->email)->first();

        if ($existing && $existing->is_active) {
            return response()->json([
                'success' => false,
                'message' => __('newsletter.already_subscribed'),
            ]);
        }

        if ($existing) {
            $existing->update(['is_active' => true, 'subscribed_at' => now(), 'unsubscribed_at' => null]);
        } else {
            NewsletterSubscriber::create([
                'email' => $request->email,
                'subscribed_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => __('newsletter.subscribed'),
        ]);
    }
}
