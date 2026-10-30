<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\TenderAccessRequestStatus;
use App\Filament\Resources\TenderAccessRequestResource;
use App\Filament\Resources\TenderAccessRequestResource\Pages\EditTenderAccessRequest;
use App\Filament\Resources\TenderAccessRequestResource\Pages\ListTenderAccessRequests;
use App\Filament\Resources\TenderCategoryResource;
use App\Filament\Resources\TenderCategoryResource\Pages\CreateTenderCategory;
use App\Filament\Resources\TenderPackageResource;
use App\Filament\Resources\TenderPackageResource\Pages\CreateTenderPackage;
use App\Filament\Resources\TenderResource;
use App\Filament\Resources\TenderResource\Pages\CreateTender;
use App\Filament\Resources\TenderResource\Pages\EditTender;
use App\Filament\Resources\TenderResource\Pages\ListTenders;
use App\Filament\Resources\TenderSubscriptionResource;
use App\Filament\Resources\TenderSubscriptionResource\Pages\CreateTenderSubscription;
use App\Models\Setting;
use App\Models\Tender;
use App\Models\TenderAccessRequest;
use App\Models\TenderCategory;
use App\Models\TenderPackage;
use App\Models\TenderSubscription;
use App\Models\User;
use App\Notifications\TenderAccessGrantedNotification;
use App\Support\TenderAccess;
use App\Support\TenderNotificationRecipients;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

final class AdminTenderResourcesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs(User::factory()->admin()->create(), 'admin');
    }

    public function test_every_tender_resource_page_renders(): void
    {
        $tender = $this->makeTender();
        $request = $this->makeRequest();

        foreach ([TenderResource::class, TenderCategoryResource::class, TenderPackageResource::class, TenderAccessRequestResource::class, TenderSubscriptionResource::class] as $resource) {
            $this->get($resource::getUrl('index'))->assertOk();
        }

        $this->get(TenderResource::getUrl('create'))->assertOk();
        $this->get(TenderResource::getUrl('edit', ['record' => $tender]))->assertOk();
        $this->get(TenderAccessRequestResource::getUrl('edit', ['record' => $request]))->assertOk();
        $this->get(TenderSubscriptionResource::getUrl('create'))->assertOk();
    }

    public function test_admin_creates_a_tender_that_appears_on_the_board(): void
    {
        $category = TenderCategory::query()->create(['name' => ['az' => 'Memarlıq'], 'slug' => 'memarliq-admin']);

        Livewire::test(CreateTender::class)
            ->fillForm([
                'title' => ['az' => 'Admin tenderi'],
                'tender_category_id' => $category->id,
                'summary' => ['az' => 'Qısa təsvir'],
                'description' => ['az' => 'Ətraflı təsvir'],
                'scope' => ['az' => "Birinci\nİkinci"],
                'organizer_name' => 'Admin Şirkəti',
                'contact_email' => 'tender@admin.example',
                'city' => 'Bakı',
                'budget_min' => '1000',
                'budget_max' => '2000',
                'currency' => 'AZN',
                'deadline_at' => now()->addWeek()->format('Y-m-d H:i:s'),
                'is_published' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $tender = Tender::query()->where('organizer_name', 'Admin Şirkəti')->sole();
        $this->assertSame('admin-tenderi', $tender->slug);
        $this->assertMatchesRegularExpression('/^T-\d{4}-\d{3}$/', $tender->number);
        $this->assertNotNull($tender->published_at);
        $this->assertSame(['Birinci', 'İkinci'], $tender->lines('scope'));

        // Admins always have access, so the board shows it right away.
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('tenders.index'))
            ->assertOk()
            ->assertSee('Admin tenderi')
            ->assertSee('Admin Şirkəti');
    }

    public function test_tender_requires_az_title_organizer_and_contact_email_only(): void
    {
        Livewire::test(CreateTender::class)
            ->fillForm(['title' => ['az' => ''], 'organizer_name' => '', 'contact_email' => 'not-an-email', 'currency' => 'AZN'])
            ->call('create')
            ->assertHasFormErrors(['title.az' => 'required', 'organizer_name' => 'required', 'contact_email' => 'email'])
            ->assertHasNoFormErrors(['title.ru', 'title.en']);
    }

    public function test_admin_edits_and_deletes_a_tender(): void
    {
        $tender = $this->makeTender();

        Livewire::test(EditTender::class, ['record' => $tender->getRouteKey()])
            ->fillForm(['organizer_name' => 'Yeni Sifarişçi', 'is_published' => false])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Yeni Sifarişçi', $tender->fresh()->organizer_name);
        $this->assertFalse($tender->fresh()->is_published);

        Livewire::test(ListTenders::class)
            ->assertCanSeeTableRecords([$tender])
            ->callTableAction('delete', $tender);

        $this->assertModelMissing($tender);
    }

    public function test_admin_creates_category_and_package(): void
    {
        Livewire::test(CreateTenderCategory::class)
            ->fillForm(['name' => ['az' => 'Landşaft'], 'sort_order' => 9, 'is_active' => true])
            ->call('create')
            ->assertHasNoFormErrors();
        $this->assertSame('landsaft', TenderCategory::query()->where('sort_order', 9)->sole()->slug);

        Livewire::test(CreateTenderPackage::class)
            ->fillForm(['name' => ['az' => 'Sınaq paketi'], 'duration_months' => 2, 'price' => '79', 'currency' => 'AZN', 'is_active' => true])
            ->call('create')
            ->assertHasNoFormErrors();
        $this->assertSame('79 ₼', TenderPackage::query()->where('duration_months', 2)->latest('id')->first()->priceLabel());
    }

    public function test_grant_action_on_a_request_activates_access_and_mails_the_user(): void
    {
        Notification::fake();
        $request = $this->makeRequest();
        $package = $request->package;

        Livewire::test(ListTenderAccessRequests::class)
            ->assertCanSeeTableRecords([$request])
            ->callTableAction('grantAccess', $request, ['tender_package_id' => $package->id, 'months' => 3, 'note' => 'Nağd ödəndi'])
            ->assertHasNoTableActionErrors();

        $subscription = TenderSubscription::query()->sole();
        $this->assertSame($request->user_id, $subscription->user_id);
        $this->assertSame($request->id, $subscription->tender_access_request_id);
        $this->assertSame('Nağd ödəndi', $subscription->note);
        $this->assertTrue($subscription->ends_at->isSameDay(now()->addMonthsNoOverflow(3)));
        $this->assertSame(TenderAccessRequestStatus::Approved, $request->fresh()->status);
        $this->assertTrue(TenderAccess::for($request->user)->granted());
        Notification::assertSentTo($request->user, TenderAccessGrantedNotification::class);

        // Once approved the action is gone — no double grant.
        Livewire::test(EditTenderAccessRequest::class, ['record' => $request->getRouteKey()])
            ->assertActionHidden('grantAccess');
    }

    public function test_admin_rejects_a_request_by_status(): void
    {
        $request = $this->makeRequest();

        Livewire::test(EditTenderAccessRequest::class, ['record' => $request->getRouteKey()])
            ->fillForm(['status' => TenderAccessRequestStatus::Rejected->value, 'admin_note' => 'Ödəniş olmadı'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(TenderAccessRequestStatus::Rejected, $request->fresh()->status);
        // A rejected user may ask again.
        $this->assertSame(TenderAccess::NONE, TenderAccess::for($request->user)->state);
    }

    public function test_admin_grants_a_subscription_by_hand(): void
    {
        $user = User::factory()->buyer()->create();

        Livewire::test(CreateTenderSubscription::class)
            ->fillForm([
                'user_id' => $user->id,
                'starts_at' => now()->subHour()->format('Y-m-d H:i:s'),
                'ends_at' => now()->addMonth()->format('Y-m-d H:i:s'),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertTrue(TenderAccess::for($user)->granted());
    }

    public function test_notification_email_setting_is_saved_from_the_requests_page(): void
    {
        Livewire::test(ListTenderAccessRequests::class)
            ->callAction('notificationEmail', ['emails' => 'not-an-email'])
            ->assertHasActionErrors(['emails']);

        Livewire::test(ListTenderAccessRequests::class)
            ->callAction('notificationEmail', ['emails' => 'a@archi.example; b@archi.example'])
            ->assertHasNoActionErrors();

        $this->assertSame('a@archi.example, b@archi.example', Setting::getRaw(TenderNotificationRecipients::SETTING));
        $this->assertSame(['a@archi.example', 'b@archi.example'], TenderNotificationRecipients::all());
    }

    private function makeTender(): Tender
    {
        return Tender::query()->create([
            'title' => ['az' => 'Mövcud tender'],
            'organizer_name' => 'Köhnə Sifarişçi',
            'contact_email' => 'old@example.com',
            'is_published' => true,
            'deadline_at' => now()->addWeek(),
        ]);
    }

    private function makeRequest(): TenderAccessRequest
    {
        $user = User::factory()->buyer()->create();
        $package = TenderPackage::query()->create(['name' => ['az' => 'Aylıq'], 'duration_months' => 1, 'price' => 49]);

        return TenderAccessRequest::query()->create([
            'user_id' => $user->id,
            'tender_package_id' => $package->id,
            'full_name' => 'Müraciət Edən',
            'phone' => '+994 50 111 22 33',
            'email' => 'apply@example.com',
            'status' => TenderAccessRequestStatus::Pending,
        ]);
    }
}
