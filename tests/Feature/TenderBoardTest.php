<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\GrantTenderAccess;
use App\Enums\TenderAccessRequestStatus;
use App\Models\MenuItem;
use App\Models\Setting;
use App\Models\Tender;
use App\Models\TenderAccessRequest;
use App\Models\TenderCategory;
use App\Models\TenderPackage;
use App\Models\TenderSubscription;
use App\Models\User;
use App\Notifications\TenderAccessGrantedNotification;
use App\Notifications\TenderAccessRequestNotification;
use App\Support\TenderAccess;
use App\Support\TenderNotificationRecipients;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class TenderBoardTest extends TestCase
{
    use RefreshDatabase;

    private TenderCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = TenderCategory::query()->create([
            'name' => ['az' => 'Test kateqoriya'],
            'slug' => 'test-kateqoriya',
        ]);
    }

    // --- Access gate -------------------------------------------------------

    public function test_guest_sees_the_lock_card_without_any_tender_data(): void
    {
        $tender = $this->makeTender(['organizer_name' => 'Gizli Şirkət MMC', 'contact_email' => 'secret@gizli.example']);

        $this->get(route('tenders.index'))
            ->assertOk()
            ->assertDontSee('Gizli Şirkət MMC')
            ->assertDontSee('secret@gizli.example');

        // A shared direct link reveals nothing either.
        $this->get(route('tenders.show', $tender->slug))
            ->assertOk()
            ->assertDontSee('secret@gizli.example')
            ->assertDontSee($tender->title)
            ->assertDontSee($tender->number);
    }

    public function test_signed_in_user_without_package_is_locked_out(): void
    {
        $tender = $this->makeTender(['contact_email' => 'secret@gizli.example']);
        $user = User::factory()->buyer()->create();

        $this->assertSame(TenderAccess::NONE, TenderAccess::for($user)->state);

        $this->actingAs($user)->get(route('tenders.index'))->assertOk()->assertDontSee($tender->organizer_name);
        $this->actingAs($user)->get(route('tenders.show', $tender->slug))->assertOk()->assertDontSee('secret@gizli.example');
    }

    public function test_subscriber_sees_the_board_and_the_detail(): void
    {
        $tender = $this->makeTender([
            'organizer_name' => 'Forma Development',
            'contact_email' => 'tender@forma.example',
            'scope' => ['az' => "Konsepsiya\n\n3D vizuallaşdırma"],
        ]);
        $user = $this->subscriber();

        $this->actingAs($user)->get(route('tenders.index'))
            ->assertOk()
            ->assertSee('Forma Development')
            ->assertSee($tender->title);

        $this->actingAs($user)->get(route('tenders.show', $tender->slug))
            ->assertOk()
            ->assertSee($tender->number)
            ->assertSee('tender@forma.example')
            ->assertSee('3D vizuallaşdırma');

        $this->assertSame(1, $tender->fresh()->views_count);
        $this->assertSame(['Konsepsiya', '3D vizuallaşdırma'], $tender->lines('scope'));
    }

    public function test_admin_always_has_access(): void
    {
        $this->assertTrue(TenderAccess::for(User::factory()->admin()->create())->granted());
    }

    public function test_expired_subscription_shows_the_expired_state(): void
    {
        $user = User::factory()->buyer()->create();
        TenderSubscription::query()->create([
            'user_id' => $user->id,
            'starts_at' => now()->subMonths(2),
            'ends_at' => now()->subDays(3),
        ]);

        $access = TenderAccess::for($user);

        $this->assertSame(TenderAccess::EXPIRED, $access->state);
        $this->assertTrue($access->expiredAt->isSameDay(now()->subDays(3)));
        $this->assertTrue($access->canRequest());

        $this->actingAs($user)->get(route('tenders.index'))
            ->assertOk()
            ->assertSee(now()->subDays(3)->timezone('Asia/Baku')->format('d.m.Y'));
    }

    public function test_unpublished_and_future_tenders_are_hidden_even_from_subscribers(): void
    {
        $draft = $this->makeTender(['is_published' => false]);
        $future = $this->makeTender(['published_at' => now()->addDay()]);
        $user = $this->subscriber();

        $this->actingAs($user)->get(route('tenders.show', $draft->slug))->assertNotFound();
        $this->actingAs($user)->get(route('tenders.show', $future->slug))->assertNotFound();
        $this->actingAs($user)->get(route('tenders.index'))->assertDontSee($draft->title)->assertDontSee($future->title);
    }

    // --- Board filters ------------------------------------------------------

    public function test_filters_by_category_city_status_and_search(): void
    {
        $other = TenderCategory::query()->create(['name' => ['az' => 'Mebel'], 'slug' => 'mebel-test']);

        $office = $this->makeTender(['title' => ['az' => 'Ofis interyeri'], 'city' => 'Bakı']);
        $furniture = $this->makeTender(['title' => ['az' => 'Mebel istehsalı'], 'city' => 'Gəncə', 'tender_category_id' => $other->id]);
        $closed = $this->makeTender(['title' => ['az' => 'Metal montajı'], 'city' => 'Bakı', 'deadline_at' => now()->subDay()]);

        $user = $this->subscriber();
        $titles = fn (array $query) => $this->actingAs($user)->get(route('tenders.index', $query))->assertOk()->viewData('tenders')
            ->getCollection()->pluck('id')->all();

        $this->assertEqualsCanonicalizing([$furniture->id], $titles(['category' => ['mebel-test']]));
        $this->assertEqualsCanonicalizing([$furniture->id], $titles(['city' => 'Gəncə']));
        $this->assertEqualsCanonicalizing([$closed->id], $titles(['status' => 'closed']));
        $this->assertEqualsCanonicalizing([$office->id, $furniture->id], $titles(['status' => 'open']));
        // Case- and accent-insensitive like the rest of the site's search.
        $this->assertEqualsCanonicalizing([$furniture->id], $titles(['q' => 'mebel istehsali']));
        // Open tenders are listed before closed ones.
        $this->assertSame($closed->id, last($titles([])));
    }

    public function test_save_toggle_and_saved_tab(): void
    {
        $saved = $this->makeTender();
        $notSaved = $this->makeTender();
        $user = $this->subscriber();

        $this->actingAs($user)->postJson(route('tenders.save', $saved->slug))->assertOk()->assertJson(['saved' => true]);

        $ids = $this->actingAs($user)->get(route('tenders.index', ['tab' => 'saved']))->viewData('tenders')->getCollection()->pluck('id')->all();
        $this->assertSame([$saved->id], $ids);
        $this->assertNotContains($notSaved->id, $ids);

        $this->actingAs($user)->postJson(route('tenders.save', $saved->slug))->assertOk()->assertJson(['saved' => false]);
        $this->assertSame(0, $user->savedTenders()->count());
    }

    public function test_saving_requires_access(): void
    {
        $tender = $this->makeTender();

        $this->postJson(route('tenders.save', $tender->slug))->assertUnauthorized();
        $this->actingAs(User::factory()->buyer()->create())->postJson(route('tenders.save', $tender->slug))->assertForbidden();
    }

    // --- Files --------------------------------------------------------------

    public function test_files_are_private_and_served_only_to_subscribers(): void
    {
        Storage::fake(Tender::FILES_DISK);
        $path = UploadedFile::fake()->create('plan.pdf', 120, 'application/pdf')->store('tenders', Tender::FILES_DISK);
        $tender = $this->makeTender(['files' => [$path], 'file_names' => [$path => 'Texniki_tapsiriq.pdf']]);

        $this->get(route('tenders.files.show', [$tender->slug, 0]))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->buyer()->create())
            ->get(route('tenders.files.show', [$tender->slug, 0]))
            ->assertRedirect(route('tenders.show', $tender->slug));

        $subscriber = $this->subscriber();
        $this->actingAs($subscriber)->get(route('tenders.files.show', [$tender->slug, 0]))
            ->assertOk()
            ->assertDownload('Texniki_tapsiriq.pdf');
        $this->actingAs($subscriber)->get(route('tenders.files.show', [$tender->slug, 5]))->assertNotFound();

        $this->assertSame('Texniki_tapsiriq.pdf', $tender->fileList()[0]['name']);
        $this->assertSame('pdf', $tender->fileList()[0]['extension']);

        // Removing a file in the panel deletes it from disk.
        $tender->update(['files' => []]);
        Storage::disk(Tender::FILES_DISK)->assertMissing($path);
    }

    // --- Access request → admin mail → grant → user mail ------------------

    public function test_access_request_is_stored_and_mailed_to_the_admin_inbox(): void
    {
        Notification::fake();
        Setting::set(TenderNotificationRecipients::SETTING, 'tender@archi.example, boss@archi.example');
        $package = TenderPackage::query()->create(['name' => ['az' => 'Test paket'], 'duration_months' => 1, 'price' => 49]);
        $user = User::factory()->buyer()->create();

        $this->actingAs($user)->postJson(route('tenders.access-request'), [
            'tender_package_id' => $package->id,
            'full_name' => '  Vüsal Quliyev ',
            'company_name' => 'Quliyev Build',
            'phone' => '+994 50 123 45 67',
            'email' => 'VUSAL@example.com',
            'message' => 'Aylıq paket istəyirəm.',
        ])->assertCreated()->assertJsonStructure(['message']);

        $request = TenderAccessRequest::query()->sole();
        $this->assertSame($user->id, $request->user_id);
        $this->assertSame('Vüsal Quliyev', $request->full_name);
        $this->assertSame('vusal@example.com', $request->email);
        $this->assertSame(TenderAccessRequestStatus::Pending, $request->status);

        Notification::assertSentOnDemand(
            TenderAccessRequestNotification::class,
            function (TenderAccessRequestNotification $notification, array $channels, AnonymousNotifiable $notifiable) use ($request): bool {
                $mail = $notification->toMail($notifiable);

                return $notifiable->routes['mail'] === ['tender@archi.example', 'boss@archi.example']
                    && $notification->accessRequest->is($request)
                    && $mail->replyTo === [['vusal@example.com', 'Vüsal Quliyev']]
                    && str_contains(implode("\n", $mail->introLines), 'Test paket — 49 ₼')
                    && str_contains(implode("\n", $mail->introLines), 'Quliyev Build');
            },
        );

        // The page now shows the request as being handled, and a second one is refused.
        $this->assertSame(TenderAccess::PENDING, TenderAccess::for($user)->state);
        $this->actingAs($user)->postJson(route('tenders.access-request'), [
            'tender_package_id' => $package->id,
            'full_name' => 'Vüsal Quliyev',
            'phone' => '+994 50 123 45 67',
            'email' => 'vusal@example.com',
        ])->assertStatus(422);
        $this->assertSame(1, TenderAccessRequest::query()->count());
    }

    public function test_recipients_fall_back_to_env_then_to_admin_accounts(): void
    {
        Setting::query()->where('key', TenderNotificationRecipients::SETTING)->delete();
        \Illuminate\Support\Facades\Cache::forget('settings_all');

        config(['mail.admin_address' => 'ops@archi.example']);
        $this->assertSame(['ops@archi.example'], TenderNotificationRecipients::all());

        config(['mail.admin_address' => null]);
        $admin = User::factory()->admin()->create(['email' => 'admin-x@archi.example']);
        $this->assertContains('admin-x@archi.example', TenderNotificationRecipients::all());
        $this->assertSame(['a@x.az', 'b@x.az'], TenderNotificationRecipients::parse('a@x.az; nonsense, b@x.az a@x.az'));
    }

    public function test_access_request_validation(): void
    {
        TenderPackage::query()->create(['name' => ['az' => 'Paket'], 'duration_months' => 1]);
        $inactive = TenderPackage::query()->create(['name' => ['az' => 'Köhnə'], 'duration_months' => 2, 'is_active' => false]);
        $user = User::factory()->buyer()->create();

        $this->postJson(route('tenders.access-request'), [])->assertUnauthorized();

        $this->actingAs($user)->postJson(route('tenders.access-request'), [
            'tender_package_id' => $inactive->id,
            'full_name' => 'A',
            'phone' => 'abc',
            'email' => 'not-an-email',
        ])->assertStatus(422)->assertJsonValidationErrors(['tender_package_id', 'full_name', 'phone', 'email']);

        $this->assertSame(0, TenderAccessRequest::query()->count());
    }

    public function test_subscriber_cannot_send_another_request(): void
    {
        Notification::fake();
        $user = $this->subscriber();

        $this->actingAs($user)->postJson(route('tenders.access-request'), [
            'tender_package_id' => TenderPackage::query()->create(['name' => ['az' => 'Paket'], 'duration_months' => 1])->id,
            'full_name' => 'Aktiv İstifadəçi',
            'phone' => '+994 50 000 00 00',
            'email' => 'aktiv@example.com',
        ])->assertStatus(422);

        Notification::assertNothingSent();
    }

    public function test_granting_access_opens_the_board_extends_from_current_end_and_mails_the_user(): void
    {
        Notification::fake();
        $package = TenderPackage::query()->create(['name' => ['az' => '3 ay'], 'duration_months' => 3]);
        $user = User::factory()->buyer()->create();
        $request = TenderAccessRequest::query()->create([
            'user_id' => $user->id,
            'tender_package_id' => $package->id,
            'full_name' => 'Test',
            'phone' => '+994500000000',
            'email' => 'test@example.com',
            'status' => TenderAccessRequestStatus::Pending,
        ]);

        $first = app(GrantTenderAccess::class)($user, 3, $package, $request);

        $this->assertTrue(TenderAccess::for($user)->granted());
        $this->assertSame(TenderAccessRequestStatus::Approved, $request->fresh()->status);
        $this->assertNotNull($request->fresh()->processed_at);
        $this->assertTrue($first->ends_at->isSameDay(now()->addMonthsNoOverflow(3)));
        Notification::assertSentTo($user, TenderAccessGrantedNotification::class);

        // Renewing early adds the new period after the current one.
        $second = app(GrantTenderAccess::class)($user, 1);
        $this->assertTrue($second->starts_at->equalTo($first->ends_at));
        $this->assertTrue($second->ends_at->isSameDay($first->ends_at->copy()->addMonthNoOverflow()));
    }

    // --- Model helpers ------------------------------------------------------

    public function test_number_slug_budget_and_initials(): void
    {
        $a = $this->makeTender(['title' => ['az' => 'Satış ofisi'], 'number' => null, 'slug' => null, 'organizer_name' => 'Forma Development']);
        $b = $this->makeTender(['title' => ['az' => 'Satış ofisi'], 'number' => null, 'slug' => null, 'organizer_name' => 'Terra']);

        $year = now()->year;
        $this->assertMatchesRegularExpression("/^T-{$year}-\\d{3}$/", $a->number);
        $this->assertNotSame($a->number, $b->number);
        $this->assertSame('satis-ofisi', $a->slug);
        $this->assertSame('satis-ofisi-2', $b->slug);
        $this->assertSame('FD', $a->initials());
        $this->assertSame('TE', $b->initials());

        $a->fill(['budget_min' => 180000, 'budget_max' => 220000]);
        $this->assertSame('180 000–220 000 ₼', $a->budgetLabel());
        $a->fill(['budget_min' => null, 'budget_max' => null]);
        $this->assertNull($a->budgetLabel());
        $this->assertStringStartsWith('mailto:', (string) $a->mailtoUrl());
    }

    // --- Header menu ---------------------------------------------------------

    public function test_header_menu_migration_adds_the_tender_item_once(): void
    {
        // Truncating tests elsewhere in the suite can wipe migration-inserted rows,
        // so run the migration here instead of relying on its earlier run.
        MenuItem::query()->where('route_name', 'tenders.index')->delete();
        $migration = require database_path('migrations/2026_10_30_100100_add_tenders_header_menu_item.php');

        $migration->up();
        $migration->up();

        $this->assertSame(1, MenuItem::query()->where('location', 'header_main')->where('route_name', 'tenders.index')->where('is_active', true)->count());
        $this->get('/')->assertOk()->assertSee(route('tenders.index'), false);
    }

    private function subscriber(): User
    {
        $user = User::factory()->buyer()->create();
        TenderSubscription::query()->create([
            'user_id' => $user->id,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
        ]);

        return $user;
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeTender(array $overrides = []): Tender
    {
        static $i = 0;
        $i++;

        return Tender::query()->create(array_merge([
            'tender_category_id' => $this->category->id,
            'title' => ['az' => 'Test tender '.$i],
            'summary' => ['az' => 'Qısa təsvir'],
            'description' => ['az' => 'Ətraflı təsvir'],
            'organizer_name' => 'Şirkət '.$i,
            'contact_email' => 'tender'.$i.'@example.com',
            'city' => 'Bakı',
            'deadline_at' => now()->addWeek(),
            'is_published' => true,
            'published_at' => now()->subDay(),
        ], $overrides));
    }
}
