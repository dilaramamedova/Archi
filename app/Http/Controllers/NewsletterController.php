<?php

namespace App\Http\Controllers;

use App\Models\NewsletterSubscriber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    public function subscribe(Request $request): JsonResponse
    {
        $validated = $request->validate([
            // Laravel's default `email` rule accepts "adam@localhost" and "a@b" —
            // addresses no newsletter can ever reach. `rfc` plus a required dotted
            // TLD rejects those without the network round-trip a `dns` check needs.
            'email' => [
                'required',
                'string',
                'max:255',
                'email:rfc',
                'regex:/^[^@\s]+@[^@\s.]+(\.[^@\s.]+)*\.[a-zA-Z]{2,}$/',
            ],
        ], [
            'email.regex' => t('newsletter.invalid_email'),
            'email.email' => t('newsletter.invalid_email'),
        ]);

        $email = mb_strtolower(trim($validated['email']));
        $existing = NewsletterSubscriber::where('email', $email)->first();

        if ($existing && $existing->is_active) {
            // Not a validation failure, but the frontend must not celebrate it —
            // 409 keeps `response.ok` false so it lands in the error branch.
            return response()->json([
                'success' => false,
                'message' => t('newsletter.already_subscribed'),
            ], 409);
        }

        if ($existing) {
            $existing->update(['is_active' => true, 'subscribed_at' => now(), 'unsubscribed_at' => null]);
        } else {
            NewsletterSubscriber::create([
                'email' => $email,
                'subscribed_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => t('newsletter.subscribed'),
        ]);
    }
}
