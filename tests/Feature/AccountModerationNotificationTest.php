<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserStatus;
use App\Filament\Resources\SellerResource\Pages\ListSellers;
use App\Models\User;
use App\Notifications\AccountApprovedNotification;
use App\Notifications\AccountRejectedNotification;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class AccountModerationNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_approving_a_seller_notifies_them(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create();
        $seller = User::factory()->seller()->create(['status' => UserStatus::Pending]);

        Livewire::actingAs($admin, 'admin')
            ->test(ListSellers::class)
            ->callTableAction('approve', $seller)
            ->assertHasNoTableActionErrors();

        $this->assertSame(UserStatus::Active, $seller->fresh()->status);
        Notification::assertSentTo($seller, AccountApprovedNotification::class);
    }

    public function test_rejecting_a_seller_notifies_them_with_the_reason(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create();
        $seller = User::factory()->seller()->create(['status' => UserStatus::Pending]);

        Livewire::actingAs($admin, 'admin')
            ->test(ListSellers::class)
            ->callTableAction('reject', $seller, ['rejection_reason' => 'VÖEN təsdiqlənmədi'])
            ->assertHasNoTableActionErrors();

        $this->assertSame(UserStatus::Rejected, $seller->fresh()->status);
        Notification::assertSentTo(
            $seller,
            AccountRejectedNotification::class,
            fn (AccountRejectedNotification $notification): bool => $notification->reason === 'VÖEN təsdiqlənmədi',
        );
    }
}
