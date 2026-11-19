<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TenderSubmissionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * «Tender yarat» — the essentials of a tender a signed-in user wants published.
 * Mailed to the admin inbox (TenderSubmissionNotification); the admin turns it into
 * a draft Tender from the panel (TenderSubmissionResource) and publishes it there.
 */
final class TenderSubmission extends Model
{
    public const CURRENCIES = ['AZN', 'USD', 'EUR'];

    protected $fillable = [
        'user_id', 'tender_category_id', 'tender_id', 'title', 'description', 'city',
        'area_sqm', 'budget', 'currency', 'deadline_at', 'organizer_name',
        'contact_name', 'phone', 'email', 'status', 'admin_note', 'processed_at',
        'ip_address', 'user_agent',
    ];

    protected $attributes = [
        'status' => 'new',
        'currency' => 'AZN',
    ];

    protected $casts = [
        'status' => TenderSubmissionStatus::class,
        'area_sqm' => 'decimal:2',
        'budget' => 'decimal:2',
        'deadline_at' => 'date',
        'processed_at' => 'immutable_datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TenderCategory::class, 'tender_category_id');
    }

    public function tender(): BelongsTo
    {
        return $this->belongsTo(Tender::class);
    }

    /** «25 000 AZN» or null when no budget was given. */
    public function budgetLabel(): ?string
    {
        return $this->budget !== null
            ? number_format((float) $this->budget, 0, '.', ' ').' '.$this->currency
            : null;
    }

    /**
     * A draft Tender with everything the user gave. It stays unpublished: the admin
     * completes and publishes it in the tender editor.
     */
    public function toDraftTender(): Tender
    {
        $tender = Tender::query()->create([
            'tender_category_id' => $this->tender_category_id,
            'title' => ['az' => $this->title],
            'summary' => ['az' => str($this->description)->squish()->limit(250)->toString()],
            'description' => ['az' => $this->description],
            'organizer_name' => $this->organizer_name ?: $this->contact_name,
            'city' => $this->city,
            'area_sqm' => $this->area_sqm,
            'budget_max' => $this->budget,
            'currency' => $this->currency,
            'contact_name' => ['az' => $this->contact_name],
            'contact_email' => $this->email,
            'contact_phone' => $this->phone,
            // The end of that working day in Baku.
            'deadline_at' => $this->deadline_at
                ? \Illuminate\Support\Carbon::parse($this->deadline_at->format('Y-m-d').' 18:00', 'Asia/Baku')->utc()
                : null,
            'is_published' => false,
        ]);

        $this->forceFill([
            'tender_id' => $tender->id,
            'status' => TenderSubmissionStatus::Converted,
            'processed_at' => now(),
        ])->save();

        return $tender;
    }
}
