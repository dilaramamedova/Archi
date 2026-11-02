<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\TenderAccessRequestStatus;
use App\Filament\Resources\TenderAccessRequestResource;
use App\Filament\Resources\TenderAccessRequestResource\Pages\EditTenderAccessRequest;
use App\Filament\Resources\TenderAccessRequestResource\Pages\ListTenderAccessRequests;
use App\Filament\Resources\TenderCategoryResource;
use App\Filament\Resources\TenderCategoryResource\Pages\CreateTenderCategory;
use App\Filament\Resources\TenderCategoryResource\Pages\EditTenderCategory;
use App\Filament\Resources\TenderCategoryResource\Pages\ListTenderCategories;
use App\Filament\Resources\TenderPackageResource;
use App\Filament\Resources\TenderPackageResource\Pages\CreateTenderPackage;
use App\Filament\Resources\TenderPackageResource\Pages\EditTenderPackage;
use App\Filament\Resources\TenderPackageResource\Pages\ListTenderPackages;
use App\Filament\Resources\TenderResource;
use App\Filament\Resources\TenderResource\Pages\CreateTender;
use App\Filament\Resources\TenderResource\Pages\EditTender;
use App\Filament\Resources\TenderResource\Pages\ListTenders;
use App\Filament\Resources\TenderSubscriptionResource;
use App\Filament\Resources\TenderSubscriptionResource\Pages\CreateTenderSubscription;
use App\Filament\Resources\TenderSubscriptionResource\Pages\EditTenderSubscription;
use App\Filament\Resources\TenderSubscriptionResource\Pages\ListTenderSubscriptions;
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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
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

        // Some assertions check the paid access states; the section ships free.
        config(['tenders.paid_access' => true]);
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

    public function test_phone_accepts_the_usual_azerbaijani_formats(): void
    {
        $tender = $this->makeTender();

        foreach (['+994 (50) 123-45-67', '(012) 404 00 00', '+994501234567', '050 123 45 67'] as $phone) {
            Livewire::test(EditTender::class, ['record' => $tender->getRouteKey()])
                ->fillForm(['contact_phone' => $phone])
                ->call('save')
                ->assertHasNoFormErrors();

            $this->assertSame($phone, $tender->fresh()->contact_phone);
        }

        foreach (['zəng edin', '+994', '12-34'] as $phone) {
            Livewire::test(EditTender::class, ['record' => $tender->getRouteKey()])
                ->fillForm(['contact_phone' => $phone])
                ->call('save')
                ->assertHasFormErrors(['contact_phone']);
        }
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

    // --- Tenders: list ----------------------------------------------------------

    public function test_tender_list_filters_by_state_publication_and_category(): void
    {
        $category = TenderCategory::query()->create(['name' => ['az' => 'Mebel'], 'slug' => 'mebel-list']);
        $open = $this->makeTender(['title' => ['az' => 'Açıq'], 'tender_category_id' => $category->id]);
        $closed = $this->makeTender(['title' => ['az' => 'Bağlı'], 'deadline_at' => now()->subDay()]);
        $draft = $this->makeTender(['title' => ['az' => 'Qaralama'], 'is_published' => false, 'deadline_at' => null]);
        $scheduled = $this->makeTender(['title' => ['az' => 'Sabah'], 'published_at' => now()->addDay()]);
        $all = [$open, $closed, $draft, $scheduled];

        // A draft or scheduled tender is not "Aktiv" just because it has no deadline.
        Livewire::test(ListTenders::class)
            ->assertCanSeeTableRecords($all)
            ->assertTableColumnStateSet('state', 'Aktiv', $open)
            ->assertTableColumnStateSet('state', 'Bağlanıb', $closed)
            ->assertTableColumnStateSet('state', 'Qaralama', $draft)
            ->assertTableColumnStateSet('state', 'Planlaşdırılıb', $scheduled);

        foreach (['closed' => $closed, 'open' => $open, 'draft' => $draft, 'scheduled' => $scheduled] as $state => $expected) {
            Livewire::test(ListTenders::class)
                ->filterTable('state', $state)
                ->assertCanSeeTableRecords([$expected])
                ->assertCanNotSeeTableRecords(array_values(array_filter($all, fn (Tender $t): bool => $t->isNot($expected))));
        }

        Livewire::test(ListTenders::class)
            ->filterTable('is_published', false)
            ->assertCanSeeTableRecords([$draft])
            ->assertCanNotSeeTableRecords([$open, $closed]);

        Livewire::test(ListTenders::class)
            ->filterTable('tender_category_id', $category->id)
            ->assertCanSeeTableRecords([$open])
            ->assertCanNotSeeTableRecords([$closed, $draft]);
    }

    public function test_tender_list_search_finds_azerbaijani_titles_numbers_and_organizers(): void
    {
        $match = $this->makeTender(['title' => ['az' => 'Ofis mərkəzinin dizaynı'], 'number' => 'T-2026-501', 'organizer_name' => 'Şəki İnşaat']);
        $other = $this->makeTender(['title' => ['az' => 'Anbar təmiri'], 'number' => 'T-2026-502', 'organizer_name' => 'Terra']);

        foreach (['mərkəz', 'T-2026-501', 'Şəki'] as $term) {
            Livewire::test(ListTenders::class)
                ->searchTable($term)
                ->assertCanSeeTableRecords([$match])
                ->assertCanNotSeeTableRecords([$other]);
        }
    }

    public function test_view_on_site_action_shows_only_for_tenders_live_on_the_site(): void
    {
        $live = $this->makeTender();
        $draft = $this->makeTender(['is_published' => false]);
        $scheduled = $this->makeTender(['published_at' => now()->addDay()]);

        Livewire::test(ListTenders::class)
            ->assertTableActionVisible('view', $live)
            ->assertTableActionHidden('view', $draft)
            ->assertTableActionHidden('view', $scheduled);

        $this->get(route('tenders.show', $scheduled->slug))->assertNotFound();
    }

    public function test_bulk_delete_removes_tenders_and_their_files(): void
    {
        Storage::fake(Tender::FILES_DISK);
        $path = UploadedFile::fake()->create('plan.pdf', 10)->store('tenders', Tender::FILES_DISK);
        $a = $this->makeTender(['files' => [$path], 'file_names' => [$path => 'plan.pdf']]);
        $b = $this->makeTender();

        Livewire::test(ListTenders::class)->callTableBulkAction('delete', [$a, $b]);

        $this->assertModelMissing($a);
        $this->assertModelMissing($b);
        Storage::disk(Tender::FILES_DISK)->assertMissing($path);
    }

    // --- Tenders: form ----------------------------------------------------------

    public function test_tender_number_and_slug_are_unique_and_slug_is_alpha_dash(): void
    {
        $existing = $this->makeTender(['number' => 'T-2026-100', 'slug' => 'movcud-slug']);

        Livewire::test(CreateTender::class)
            ->fillForm($this->validTender(['number' => 'T-2026-100', 'slug' => 'movcud-slug']))
            ->call('create')
            ->assertHasFormErrors(['number' => 'unique', 'slug' => 'unique']);

        Livewire::test(CreateTender::class)
            ->fillForm($this->validTender(['slug' => 'boşluq var']))
            ->call('create')
            ->assertHasFormErrors(['slug' => 'alpha_dash']);

        // Saving the record itself keeps its own number and slug valid.
        Livewire::test(EditTender::class, ['record' => $existing->getRouteKey()])
            ->fillForm(['organizer_name' => 'Yenilənmiş'])
            ->call('save')
            ->assertHasNoFormErrors();
    }

    public function test_tender_budget_max_may_stand_alone_but_not_below_the_min(): void
    {
        Livewire::test(CreateTender::class)
            ->fillForm($this->validTender(['budget_min' => null, 'budget_max' => '50000']))
            ->call('create')
            ->assertHasNoFormErrors();
        $this->assertSame('50 000 ₼', Tender::query()->latest('id')->first()->budgetLabel());

        Livewire::test(CreateTender::class)
            ->fillForm($this->validTender(['budget_min' => '2000', 'budget_max' => '1000']))
            ->call('create')
            ->assertHasFormErrors(['budget_max' => 'gte']);

        Livewire::test(CreateTender::class)
            ->fillForm($this->validTender(['budget_min' => '-5', 'area_sqm' => '-1']))
            ->call('create')
            ->assertHasFormErrors(['budget_min' => 'min', 'area_sqm' => 'min']);
    }

    public function test_tender_edit_form_loads_saved_values_and_adds_a_translation(): void
    {
        $tender = $this->makeTender([
            'title' => ['az' => 'Mövcud tender'],
            'scope' => ['az' => "Bir\nİki"],
            'city' => 'Gəncə',
            'budget_min' => 1500,
        ]);
        $slug = $tender->slug;

        Livewire::test(EditTender::class, ['record' => $tender->getRouteKey()])
            ->assertFormSet([
                'title.az' => 'Mövcud tender',
                'scope.az' => "Bir\nİki",
                'organizer_name' => 'Köhnə Sifarişçi',
                'city' => 'Gəncə',
                'currency' => 'AZN',
            ])
            ->fillForm(['title' => ['az' => 'Mövcud tender', 'ru' => 'Существующий тендер']])
            ->call('save')
            ->assertHasNoFormErrors();

        $tender->refresh();
        $this->assertSame('Существующий тендер', $tender->getTranslation('title', 'ru'));
        $this->assertSame($slug, $tender->slug, 'the slug is not regenerated on edit');
    }

    public function test_tender_is_deleted_from_its_edit_page(): void
    {
        $tender = $this->makeTender();

        Livewire::test(EditTender::class, ['record' => $tender->getRouteKey()])->callAction('delete');

        $this->assertModelMissing($tender);
    }

    // --- Categories ---------------------------------------------------------------

    public function test_category_requires_az_name_and_a_unique_alpha_dash_slug(): void
    {
        TenderCategory::query()->create(['name' => ['az' => 'Mövcud'], 'slug' => 'movcud']);

        Livewire::test(CreateTenderCategory::class)
            ->fillForm(['name' => ['az' => ''], 'slug' => 'movcud'])
            ->call('create')
            ->assertHasFormErrors(['name.az' => 'required', 'slug' => 'unique']);

        Livewire::test(CreateTenderCategory::class)
            ->fillForm(['name' => ['az' => 'Yeni'], 'slug' => 'iki söz'])
            ->call('create')
            ->assertHasFormErrors(['slug' => 'alpha_dash']);
    }

    public function test_category_edit_list_and_delete_keep_its_tenders(): void
    {
        $category = TenderCategory::query()->create(['name' => ['az' => 'Köhnə ad'], 'slug' => 'kohne-ad']);
        $tender = $this->makeTender(['tender_category_id' => $category->id]);

        Livewire::test(ListTenderCategories::class)
            ->assertCanSeeTableRecords([$category])
            ->assertTableColumnStateSet('tenders_count', 1, $category);

        Livewire::test(EditTenderCategory::class, ['record' => $category->getRouteKey()])
            ->assertFormSet(['name.az' => 'Köhnə ad', 'slug' => 'kohne-ad'])
            ->fillForm(['name' => ['az' => 'Yeni ad', 'en' => 'New name'], 'is_active' => false])
            ->call('save')
            ->assertHasNoFormErrors();

        $category->refresh();
        $this->assertSame('Yeni ad', $category->getTranslation('name', 'az'));
        $this->assertSame('New name', $category->getTranslation('name', 'en'));
        $this->assertFalse($category->is_active);

        // An inactive category leaves the board's filter; its tenders stay listed.
        $board = $this->actingAs(User::factory()->admin()->create())->get(route('tenders.index'))->assertOk();
        $board->assertDontSee('value="kohne-ad"', false)->assertSee($tender->title);

        Livewire::test(ListTenderCategories::class)->callTableAction('delete', $category);

        $this->assertModelMissing($category);
        $this->assertNull($tender->fresh()->tender_category_id, 'the tender survives without a category');
    }

    public function test_category_is_deleted_from_its_edit_page(): void
    {
        $category = TenderCategory::query()->create(['name' => ['az' => 'Silinəcək'], 'slug' => 'silinecek']);

        Livewire::test(EditTenderCategory::class, ['record' => $category->getRouteKey()])->callAction('delete');

        $this->assertModelMissing($category);
    }

    // --- Packages -----------------------------------------------------------------

    public function test_package_validation(): void
    {
        Livewire::test(CreateTenderPackage::class)
            ->fillForm(['name' => ['az' => ''], 'duration_months' => null, 'price' => '-1', 'currency' => null])
            ->call('create')
            ->assertHasFormErrors(['name.az' => 'required', 'duration_months' => 'required', 'price' => 'min', 'currency' => 'required']);

        Livewire::test(CreateTenderPackage::class)
            ->fillForm(['name' => ['az' => 'Uzun'], 'duration_months' => 37, 'currency' => 'AZN'])
            ->call('create')
            ->assertHasFormErrors(['duration_months' => 'max']);

        Livewire::test(CreateTenderPackage::class)
            ->fillForm(['name' => ['az' => 'Kəsr'], 'duration_months' => '1.5', 'currency' => 'AZN'])
            ->call('create')
            ->assertHasFormErrors(['duration_months']);
    }

    public function test_package_edit_list_delete_and_what_the_request_form_offers(): void
    {
        $paid = TenderPackage::query()->create(['name' => ['az' => 'Rüblük'], 'description' => ['az' => 'Üç aylıq tam giriş'], 'duration_months' => 3, 'price' => 129.5, 'sort_order' => 2]);
        $free = TenderPackage::query()->create(['name' => ['az' => 'Fərdi'], 'duration_months' => 12, 'price' => null, 'sort_order' => 3]);
        $old = TenderPackage::query()->create(['name' => ['az' => 'Köhnə paket'], 'duration_months' => 1, 'price' => 10, 'is_active' => false]);

        Livewire::test(ListTenderPackages::class)
            ->assertCanSeeTableRecords([$paid, $free, $old])
            ->assertTableColumnStateSet('price', '129.50 ₼', $paid)
            ->assertTableColumnStateSet('price', 'Razılaşma ilə', $free);

        Livewire::test(EditTenderPackage::class, ['record' => $paid->getRouteKey()])
            ->assertFormSet(['name.az' => 'Rüblük', 'duration_months' => 3])
            ->fillForm(['price' => '120', 'currency' => 'USD'])
            ->call('save')
            ->assertHasNoFormErrors();
        $this->assertSame('120 USD', $paid->fresh()->priceLabel());

        // A user without a package sees the active packages in the request form.
        $this->actingAs(User::factory()->buyer()->create())
            ->get(route('tenders.index'))
            ->assertOk()
            ->assertSeeInOrder(['Rüblük', '120 USD', '3 ay', 'Üç aylıq tam giriş', 'Fərdi', 'Qiymət razılaşma ilə', '12 ay'])
            ->assertDontSee('Köhnə paket');

        Livewire::test(ListTenderPackages::class)->callTableAction('delete', $old);
        $this->assertModelMissing($old);

        Livewire::test(EditTenderPackage::class, ['record' => $free->getRouteKey()])->callAction('delete');
        $this->assertModelMissing($free);
    }

    public function test_deleting_a_package_keeps_requests_and_subscriptions_that_used_it(): void
    {
        $request = $this->makeRequest();
        $package = $request->package;
        $subscription = TenderSubscription::query()->create([
            'user_id' => $request->user_id, 'tender_package_id' => $package->id,
            'starts_at' => now()->subDay(), 'ends_at' => now()->addMonth(),
        ]);

        Livewire::test(ListTenderPackages::class)->callTableAction('delete', $package);

        $this->assertNull($request->fresh()->tender_package_id);
        $this->assertNull($subscription->fresh()->tender_package_id);
        $this->assertTrue(TenderAccess::for($request->user)->granted());

        // Both lists still render with the package gone.
        Livewire::test(ListTenderAccessRequests::class)->assertCanSeeTableRecords([$request]);
        Livewire::test(ListTenderSubscriptions::class)->assertCanSeeTableRecords([$subscription]);
    }

    // --- Access requests ----------------------------------------------------------

    public function test_access_requests_come_only_from_the_site(): void
    {
        $this->assertFalse(TenderAccessRequestResource::canCreate());
        $this->assertArrayNotHasKey('create', TenderAccessRequestResource::getPages());
    }

    public function test_access_request_edit_validates_and_requests_are_deleted(): void
    {
        $request = $this->makeRequest();

        Livewire::test(EditTenderAccessRequest::class, ['record' => $request->getRouteKey()])
            ->assertFormSet(['full_name' => 'Müraciət Edən', 'status' => TenderAccessRequestStatus::Pending->value])
            ->fillForm(['full_name' => '', 'phone' => '', 'email' => 'bad', 'status' => null])
            ->call('save')
            ->assertHasFormErrors(['full_name' => 'required', 'phone' => 'required', 'email' => 'email', 'status' => 'required']);

        Livewire::test(ListTenderAccessRequests::class)->callTableAction('delete', $request);
        $this->assertModelMissing($request);

        $second = $this->makeRequest();
        Livewire::test(EditTenderAccessRequest::class, ['record' => $second->getRouteKey()])->callAction('delete');
        $this->assertModelMissing($second);
    }

    public function test_access_request_list_filters_by_status_and_hides_grant_on_closed_requests(): void
    {
        $pending = $this->makeRequest();
        $contacted = $this->makeRequest(['status' => TenderAccessRequestStatus::Contacted]);
        $rejected = $this->makeRequest(['status' => TenderAccessRequestStatus::Rejected]);

        Livewire::test(ListTenderAccessRequests::class)
            ->assertTableColumnFormattedStateSet('status', 'Yeni', $pending)
            ->assertTableColumnFormattedStateSet('status', 'Rədd edilib', $rejected)
            ->assertTableActionVisible('grantAccess', $pending)
            ->assertTableActionVisible('grantAccess', $contacted)
            ->assertTableActionHidden('grantAccess', $rejected)
            ->filterTable('status', TenderAccessRequestStatus::Rejected->value)
            ->assertCanSeeTableRecords([$rejected])
            ->assertCanNotSeeTableRecords([$pending, $contacted]);
    }

    public function test_grant_form_follows_the_chosen_package_and_works_from_the_edit_page(): void
    {
        Notification::fake();
        $request = $this->makeRequest();
        $yearly = TenderPackage::query()->create(['name' => ['az' => 'İllik'], 'duration_months' => 12, 'price' => 399]);

        // Prefilled with the requested package; switching the package switches the months.
        Livewire::test(ListTenderAccessRequests::class)
            ->mountTableAction('grantAccess', $request)
            ->assertTableActionDataSet(['tender_package_id' => $request->tender_package_id, 'months' => 1])
            ->setTableActionData(['tender_package_id' => $yearly->id])
            ->assertTableActionDataSet(['months' => 12]);

        Livewire::test(EditTenderAccessRequest::class, ['record' => $request->getRouteKey()])
            ->callAction('grantAccess', ['tender_package_id' => $yearly->id, 'months' => 12])
            ->assertHasNoActionErrors()
            ->assertFormSet(['status' => TenderAccessRequestStatus::Approved->value]);

        $subscription = TenderSubscription::query()->sole();
        $this->assertSame($yearly->id, $subscription->tender_package_id);
        $this->assertTrue($subscription->ends_at->isSameDay(now()->addMonthsNoOverflow(12)));
        $this->assertNotNull($request->fresh()->processed_at);

        Livewire::test(ListTenderAccessRequests::class)
            ->callTableAction('grantAccess', $this->makeRequest(), ['months' => 0])
            ->assertHasTableActionErrors(['months' => 'min']);
    }

    public function test_approved_status_cannot_be_picked_by_hand_without_a_subscription(): void
    {
        $request = $this->makeRequest();

        // Picking "Giriş aktivləşdirilib" in the form would close the request with no access behind it.
        Livewire::test(EditTenderAccessRequest::class, ['record' => $request->getRouteKey()])
            ->fillForm(['status' => TenderAccessRequestStatus::Approved->value])
            ->call('save')
            ->assertHasFormErrors(['status']);

        $this->assertSame(TenderAccessRequestStatus::Pending, $request->fresh()->status);
        $this->assertDatabaseCount('tender_subscriptions', 0);

        // Once granted through the action, the request keeps saving normally.
        $request->update(['status' => TenderAccessRequestStatus::Approved]);

        Livewire::test(EditTenderAccessRequest::class, ['record' => $request->getRouteKey()])
            ->fillForm(['admin_note' => 'Ödəniş alındı'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Ödəniş alındı', $request->fresh()->admin_note);
    }

    public function test_closing_a_request_by_hand_stamps_the_processed_date(): void
    {
        $request = $this->makeRequest();

        Livewire::test(EditTenderAccessRequest::class, ['record' => $request->getRouteKey()])
            ->fillForm(['status' => TenderAccessRequestStatus::Contacted->value])
            ->call('save')
            ->assertHasNoFormErrors();
        $this->assertNull($request->fresh()->processed_at, 'contacted is still an open request');

        Livewire::test(EditTenderAccessRequest::class, ['record' => $request->getRouteKey()])
            ->fillForm(['status' => TenderAccessRequestStatus::Rejected->value])
            ->call('save')
            ->assertHasNoFormErrors();
        $this->assertNotNull($request->fresh()->processed_at);

        // An admin-entered date is kept.
        $other = $this->makeRequest();
        Livewire::test(EditTenderAccessRequest::class, ['record' => $other->getRouteKey()])
            ->fillForm(['status' => TenderAccessRequestStatus::Rejected->value, 'processed_at' => '2026-10-01 09:00'])
            ->call('save')
            ->assertHasNoFormErrors();
        $this->assertSame('2026-10-01 05:00', $other->fresh()->processed_at->format('Y-m-d H:i'));
    }

    // --- Subscriptions ------------------------------------------------------------

    public function test_subscription_validation(): void
    {
        Livewire::test(CreateTenderSubscription::class)
            ->fillForm(['user_id' => null, 'starts_at' => now()->format('Y-m-d H:i:s'), 'ends_at' => now()->subDay()->format('Y-m-d H:i:s')])
            ->call('create')
            ->assertHasFormErrors(['user_id' => 'required', 'ends_at' => 'after']);

        Livewire::test(CreateTenderSubscription::class)
            ->fillForm(['user_id' => User::factory()->buyer()->create()->id, 'starts_at' => null, 'ends_at' => null])
            ->call('create')
            ->assertHasFormErrors(['starts_at' => 'required', 'ends_at' => 'required']);
    }

    public function test_subscription_list_states_and_active_filter(): void
    {
        $user = User::factory()->buyer()->create();
        $active = TenderSubscription::query()->create(['user_id' => $user->id, 'starts_at' => now()->subDay(), 'ends_at' => now()->addMonth()]);
        $upcoming = TenderSubscription::query()->create(['user_id' => $user->id, 'starts_at' => now()->addMonth(), 'ends_at' => now()->addMonths(2)]);
        $ended = TenderSubscription::query()->create(['user_id' => $user->id, 'starts_at' => now()->subMonths(2), 'ends_at' => now()->subMonth()]);

        Livewire::test(ListTenderSubscriptions::class)
            ->assertCanSeeTableRecords([$active, $upcoming, $ended])
            ->assertTableColumnStateSet('state', 'Aktiv', $active)
            ->assertTableColumnStateSet('state', 'Gözləyir', $upcoming)
            ->assertTableColumnStateSet('state', 'Bitib', $ended)
            ->filterTable('active')
            ->assertCanSeeTableRecords([$active])
            ->assertCanNotSeeTableRecords([$upcoming, $ended]);
    }

    public function test_editing_and_deleting_a_subscription_changes_access(): void
    {
        $user = User::factory()->buyer()->create();
        $subscription = TenderSubscription::query()->create(['user_id' => $user->id, 'starts_at' => now()->subDay(), 'ends_at' => now()->addMonth()]);
        $this->assertTrue(TenderAccess::for($user)->granted());

        Livewire::test(EditTenderSubscription::class, ['record' => $subscription->getRouteKey()])
            ->fillForm(['ends_at' => now()->subHour()->timezone('Asia/Baku')->format('Y-m-d H:i:s'), 'note' => 'Geri qaytarıldı'])
            ->call('save')
            ->assertHasNoFormErrors();
        $this->assertSame(TenderAccess::EXPIRED, TenderAccess::for($user)->state);
        $this->assertSame('Geri qaytarıldı', $subscription->fresh()->note);

        Livewire::test(ListTenderSubscriptions::class)->callTableAction('delete', $subscription);
        $this->assertModelMissing($subscription);
        $this->assertSame(TenderAccess::NONE, TenderAccess::for($user)->state);

        $again = TenderSubscription::query()->create(['user_id' => $user->id, 'starts_at' => now()->subDay(), 'ends_at' => now()->addMonth()]);
        Livewire::test(EditTenderSubscription::class, ['record' => $again->getRouteKey()])->callAction('delete');
        $this->assertModelMissing($again);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validTender(array $overrides = []): array
    {
        return array_merge([
            'title' => ['az' => 'Etibarlı tender'],
            'organizer_name' => 'Sifarişçi',
            'contact_email' => 'valid@example.com',
            'currency' => 'AZN',
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeTender(array $overrides = []): Tender
    {
        return Tender::query()->create(array_merge([
            'title' => ['az' => 'Mövcud tender'],
            'organizer_name' => 'Köhnə Sifarişçi',
            'contact_email' => 'old@example.com',
            'is_published' => true,
            'deadline_at' => now()->addWeek(),
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeRequest(array $overrides = []): TenderAccessRequest
    {
        $user = User::factory()->buyer()->create();
        $package = TenderPackage::query()->create(['name' => ['az' => 'Aylıq'], 'duration_months' => 1, 'price' => 49]);

        return TenderAccessRequest::query()->create(array_merge([
            'user_id' => $user->id,
            'tender_package_id' => $package->id,
            'full_name' => 'Müraciət Edən',
            'phone' => '+994 50 111 22 33',
            'email' => 'apply@example.com',
            'status' => TenderAccessRequestStatus::Pending,
        ], $overrides));
    }
}
