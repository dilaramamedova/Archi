<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Resources\NewsletterSubscriberResource;
use App\Filament\Resources\NewsletterSubscriberResource\Pages\ListNewsletterSubscribers;
use App\Models\NewsletterSubscriber;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The footer newsletter box: a real address gets stored once and confirmed, a
 * malformed one is rejected before it can reach the list, and the admin panel
 * can see and manage whoever signed up.
 */
class NewsletterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    // ─── Subscribing ──────────────────────────────────────────

    public function test_a_valid_address_is_stored_and_confirmed(): void
    {
        $this->postJson('/api/newsletter/subscribe', ['email' => 'Alice@Example.COM'])
            ->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonPath('message', (string) t('newsletter.subscribed'));

        // stored lowercased, active, with a subscription date
        $row = NewsletterSubscriber::sole();
        $this->assertSame('alice@example.com', $row->email);
        $this->assertTrue($row->is_active);
        $this->assertNotNull($row->subscribed_at);
    }

    public function test_a_malformed_address_is_rejected(): void
    {
        $malformed = [
            'example.com',            // no at sign
            'adam@',                  // no domain
            'adam@localhost',         // no TLD — the default `email` rule allows this
            'adam@example.c',         // single-letter TLD
            'adam@example.',          // trailing dot
            'adam adams@example.com', // spaces
            '',                       // empty
            '@',                      // just an at
        ];

        foreach ($malformed as $email) {
            $this->postJson('/api/newsletter/subscribe', ['email' => $email])
                ->assertStatus(422)
                ->assertJsonValidationErrors('email');
        }

        $this->assertSame(0, NewsletterSubscriber::count());
    }

    public function test_the_error_message_is_the_translated_one(): void
    {
        $this->postJson('/api/newsletter/subscribe', ['email' => 'adam@localhost'])
            ->assertStatus(422)
            ->assertJsonPath('errors.email.0', (string) t('newsletter.invalid_email'));
    }

    public function test_subscribing_twice_reports_a_conflict_rather_than_success(): void
    {
        NewsletterSubscriber::create(['email' => 'alice@example.com', 'subscribed_at' => now()]);

        // 409, not 200: the footer script treats any non-ok response as an error,
        // so a 200 here would have shown the "thank you" tick for a failed signup.
        $this->postJson('/api/newsletter/subscribe', ['email' => 'alice@example.com'])
            ->assertStatus(409)
            ->assertJson(['success' => false])
            ->assertJsonPath('message', (string) t('newsletter.already_subscribed'));

        $this->assertSame(1, NewsletterSubscriber::count());
    }

    public function test_an_unsubscribed_address_can_come_back(): void
    {
        $row = NewsletterSubscriber::create([
            'email' => 'alice@example.com',
            'is_active' => false,
            'subscribed_at' => now()->subYear(),
            'unsubscribed_at' => now()->subMonth(),
        ]);

        $this->postJson('/api/newsletter/subscribe', ['email' => 'alice@example.com'])
            ->assertOk()
            ->assertJson(['success' => true]);

        $row->refresh();
        $this->assertTrue($row->is_active);
        $this->assertNull($row->unsubscribed_at);
        $this->assertSame(1, NewsletterSubscriber::count());
    }

    // ─── Footer markup ────────────────────────────────────────

    public function test_the_footer_form_carries_the_invalid_email_message(): void
    {
        // The script validates before it posts, so the copy has to be in the DOM.
        $this->get('/')
            ->assertOk()
            ->assertSee('data-invalid-email="'.e((string) t('newsletter.invalid_email')).'"', false);
    }

    // ─── Admin module ─────────────────────────────────────────

    public function test_admin_can_see_and_manage_subscribers(): void
    {
        $this->actingAs(User::factory()->admin()->create(), 'admin');

        $active = NewsletterSubscriber::create(['email' => 'active@example.com', 'subscribed_at' => now()]);
        $gone = NewsletterSubscriber::create(['email' => 'gone@example.com', 'is_active' => false]);

        $this->get(NewsletterSubscriberResource::getUrl('index'))->assertOk();

        Livewire::test(ListNewsletterSubscribers::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$active, $gone]);

        // the sidebar badge counts only people still on the list
        $this->assertSame('1', NewsletterSubscriberResource::getNavigationBadge());
    }

    public function test_admin_bulk_action_unsubscribes_without_deleting(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $row = NewsletterSubscriber::create(['email' => 'active@example.com', 'subscribed_at' => now()]);

        Livewire::test(ListNewsletterSubscribers::class)
            ->callTableBulkAction('deactivate', [$row]);

        $row->refresh();
        $this->assertFalse($row->is_active);
        $this->assertNotNull($row->unsubscribed_at);
        $this->assertSame(1, NewsletterSubscriber::count());
    }
}
