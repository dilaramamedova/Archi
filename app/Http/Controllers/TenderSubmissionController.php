<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\TenderSubmissionStatus;
use App\Http\Requests\StoreTenderSubmission;
use App\Models\TenderCategory;
use App\Models\TenderSubmission;
use App\Notifications\TenderSubmissionNotification;
use App\Support\TenderNotificationRecipients;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;

/**
 * «Tender yarat» on /tenders: a signed-in user sends the essentials of a tender. It
 * lands in the admin panel (Tender müraciətləri) and in the admin inbox; the admin
 * turns it into a draft tender and publishes it — nothing goes on the board by itself.
 */
final class TenderSubmissionController extends Controller
{
    public function create(): View
    {
        $user = request()->user();

        return view('pages.tenders-create', [
            'categories' => TenderCategory::query()->active()->ordered()->get(),
            'currencies' => TenderSubmission::CURRENCIES,
            'prefill' => [
                'contact_name' => trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: (string) $user->name,
                'organizer_name' => $user->sellerProfile?->brand_name ?? $user->sellerProfile?->legal_name ?? '',
                'phone' => (string) ($user->phone ?? ''),
                'email' => (string) ($user->email ?? ''),
            ],
        ]);
    }

    public function store(StoreTenderSubmission $request): RedirectResponse
    {
        $submission = TenderSubmission::query()->create([
            ...$request->validated(),
            'user_id' => $request->user()->id,
            'status' => TenderSubmissionStatus::New,
            'ip_address' => $request->ip(),
            'user_agent' => str((string) $request->userAgent())->limit(490)->toString(),
        ]);

        $recipients = TenderNotificationRecipients::all();

        if ($recipients !== []) {
            Notification::route('mail', $recipients)->notify(new TenderSubmissionNotification($submission));
        } else {
            report(new \RuntimeException('Tender submission #'.$submission->id.' has no admin recipient.'));
        }

        return redirect()->route('tenders.create')->with('tender_submitted', $submission->id);
    }
}
