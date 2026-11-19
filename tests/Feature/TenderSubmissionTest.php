<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\TenderSubmissionStatus;
use App\Filament\Resources\TenderSubmissionResource\Pages\EditTenderSubmission;
use App\Filament\Resources\TenderSubmissionResource\Pages\ListTenderSubmissions;
use App\Models\Tender;
use App\Models\TenderCategory;
use App\Models\TenderSubmission;
use App\Models\User;
use App\Notifications\TenderSubmissionNotification;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * «Tender yarat» on /tenders: a signed-in user sends the essentials of a tender; it
 * lands in the admin panel and the admin inbox, and the admin turns it into a draft.
 */
class TenderSubmissionTest extends TestCase
{
    use RefreshDatabase;

    private TenderCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        config(['tenders.paid_access' => false, 'mail.admin_address' => 'tenders@archi.example']);
        $this->category = TenderCategory::query()->create(['name' => ['az' => 'Təmir işləri'], 'slug' => 'temir-test', 'is_active' => true]);
    }

    /** @return array<string, mixed> */
    private function payload(array $override = []): array
    {
        return array_merge([
            'title' => '3 otaqlı mənzilin tam təmiri',
            'tender_category_id' => $this->category->id,
            'city' => 'Bakı, Nəsimi r.',
            'description' => 'Mənzilin tam təmiri: suvaq, döşəmə, elektrik, santexnika. Materiallar podratçıdan.',
            'area_sqm' => '95,5',
            'budget' => '25 000',
            'currency' => 'AZN',
            'deadline_at' => now()->addDays(10)->toDateString(),
            'organizer_name' => 'Nümunə MMC',
            'contact_name' => 'Aysel Məmmədova',
            'phone' => '+994 50 123 45 67',
            'email' => 'Aysel@Example.com',
        ], $override);
    }

    public function test_the_board_has_a_create_button_and_guests_sign_in_first(): void
    {
        $this->get(route('tenders.index'))->assertOk()
            ->assertSee(route('tenders.create'), false)
            ->assertSee('Tender yarat');

        $this->post(route('tenders.submit'), $this->payload())->assertRedirect(route('login'));
        $this->assertSame(0, TenderSubmission::query()->count());

        // After signing in (the login form posts JSON) the visitor lands on the form.
        $this->get(route('tenders.create'))->assertRedirect(route('login'));
        User::factory()->create(['email' => 'guest@example.com', 'password' => 'secret-pass-1']);
        $this->postJson(route('login.store'), ['identifier' => 'guest@example.com', 'password' => 'secret-pass-1'])
            ->assertOk()->assertJson(['redirect' => route('tenders.create')]);
    }

    public function test_the_form_is_prefilled_from_the_account(): void
    {
        $user = User::factory()->create(['first_name' => 'Aysel', 'last_name' => 'Məmmədova', 'email' => 'aysel@example.com']);

        $this->actingAs($user)->get(route('tenders.create'))->assertOk()
            ->assertSee('Təmir işləri')
            ->assertSee('value="Aysel Məmmədova"', false)
            ->assertSee('value="aysel@example.com"', false)
            ->assertSee('Müraciət göndər');
    }

    public function test_a_submission_is_stored_and_mailed_to_the_admin_inbox(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('tenders.submit'), $this->payload())
            ->assertRedirect(route('tenders.create'))
            ->assertSessionHas('tender_submitted');

        $s = TenderSubmission::query()->sole();
        $this->assertSame($user->id, $s->user_id);
        $this->assertSame(TenderSubmissionStatus::New, $s->status);
        $this->assertSame(['95.50', '25000.00', 'aysel@example.com'], [$s->area_sqm, $s->budget, $s->email]);

        Notification::assertSentOnDemand(TenderSubmissionNotification::class, function ($notification, $channels, $notifiable) use ($s) {
            $mail = $notification->toMail($notifiable);

            return $notifiable->routes['mail'] === ['tenders@archi.example']
                && str_contains($mail->subject, $s->title)
                && $mail->replyTo[0][0] === 'aysel@example.com'
                && str_contains($mail->actionUrl, '/admin/tender-submissions/'.$s->id.'/edit');
        });

        // The confirmation replaces the form.
        $this->actingAs($user)->withSession(['tender_submitted' => $s->id])->get(route('tenders.create'))
            ->assertOk()->assertSee('Müraciətiniz göndərildi')->assertDontSee('id="tdTcForm"', false);
    }

    public function test_only_the_essentials_are_required_and_bad_values_are_rejected(): void
    {
        $user = User::factory()->create();
        $inactive = TenderCategory::query()->create(['name' => ['az' => 'Bağlı'], 'slug' => 'bagli-test', 'is_active' => false]);

        $this->actingAs($user)->post(route('tenders.submit'), [])
            ->assertSessionHasErrors(['title', 'tender_category_id', 'city', 'description', 'contact_name', 'phone', 'email']);

        $this->actingAs($user)->post(route('tenders.submit'), $this->payload([
            'tender_category_id' => $inactive->id,
            'deadline_at' => now()->subDay()->toDateString(),
            'budget' => 'çox',
            'currency' => 'GBP',
        ]))->assertSessionHasErrors(['tender_category_id', 'deadline_at', 'budget', 'currency']);

        // The optional fields may stay empty.
        $this->actingAs($user)->post(route('tenders.submit'), $this->payload([
            'area_sqm' => '', 'budget' => '', 'deadline_at' => '', 'organizer_name' => '',
        ]))->assertSessionHasNoErrors();
        $this->assertNull(TenderSubmission::query()->sole()->budget);
    }

    public function test_admin_turns_a_submission_into_an_unpublished_tender(): void
    {
        $submission = TenderSubmission::query()->create($this->payload([
            'area_sqm' => 95.5, 'budget' => 25000, 'email' => 'aysel@example.com', 'user_id' => User::factory()->create()->id,
        ]));

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs(User::factory()->admin()->create(), 'admin');

        Livewire::test(ListTenderSubmissions::class)->assertOk()->assertCanSeeTableRecords([$submission]);

        // Opening it marks it «Baxılır».
        Livewire::test(EditTenderSubmission::class, ['record' => $submission->getRouteKey()])->assertOk();
        $this->assertSame(TenderSubmissionStatus::InReview, $submission->fresh()->status);

        Livewire::test(EditTenderSubmission::class, ['record' => $submission->getRouteKey()])
            ->callAction('convert')
            ->assertRedirect();

        $tender = Tender::query()->sole();
        $submission->refresh();
        $this->assertFalse($tender->is_published);
        $this->assertSame($tender->id, $submission->tender_id);
        $this->assertSame(TenderSubmissionStatus::Converted, $submission->status);
        $this->assertSame('3 otaqlı mənzilin tam təmiri', $tender->getTranslation('title', 'az'));
        $this->assertSame(['Nümunə MMC', 'Bakı, Nəsimi r.', 'aysel@example.com', '25000.00'], [$tender->organizer_name, $tender->city, $tender->contact_email, $tender->budget_max]);
        $this->assertSame('18:00', $tender->deadline_at->timezone('Asia/Baku')->format('H:i'));

        // An unpublished draft is not on the board.
        $this->get(route('tenders.index'))->assertDontSee('3 otaqlı mənzilin tam təmiri');
    }
}
