<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ConsultationRequestSource;
use App\Enums\ConsultationRequestStatus;
use App\Filament\Resources\ConsultationRequestResource;
use App\Filament\Resources\ConsultationRequestResource\Pages\CreateConsultationRequest;
use App\Filament\Resources\ConsultationRequestResource\Pages\EditConsultationRequest;
use App\Filament\Resources\ConsultationRequestResource\Pages\ListConsultationRequests;
use App\Models\ConsultationRequest;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class ConsultationRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_home_page_contains_working_consultation_form(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee(route('consultation-requests.store'), false)
            ->assertSee('name="full_name"', false)
            ->assertSee('name="phone"', false)
            ->assertSee('name="message"', false);
    }

    public function test_guest_can_submit_a_consultation_request(): void
    {
        $this->postJson(route('consultation-requests.store'), [
            'full_name' => 'Vüsal Quliyev',
            'phone' => '+994 50 123 45 67',
            'message' => 'Mənzil təmiri üçün konsultasiya istəyirəm.',
        ])->assertCreated()->assertJsonStructure(['message']);

        $this->assertDatabaseHas('consultation_requests', [
            'full_name' => 'Vüsal Quliyev',
            'phone' => '+994 50 123 45 67',
            'status' => ConsultationRequestStatus::Pending->value,
        ]);
    }

    public function test_home_lead_is_tagged_home_by_default(): void
    {
        $this->postJson(route('consultation-requests.store'), [
            'full_name' => 'Vüsal Quliyev',
            'phone' => '+994 50 123 45 67',
        ])->assertCreated();

        $lead = ConsultationRequest::query()->sole();
        $this->assertSame(ConsultationRequestSource::Home, $lead->source);
        $this->assertNull($lead->b2b_plan);
    }

    public function test_b2b_plan_lead_is_stored_separately(): void
    {
        $this->postJson(route('consultation-requests.store'), [
            'full_name' => 'Anar Məmmədov',
            'phone' => '+994 50 123 45 67',
            'message' => 'B2B — PRO planı ilə maraqlanıram.',
            'source' => 'b2b',
            'b2b_plan' => 'local.pro',
        ])->assertCreated();

        $lead = ConsultationRequest::query()->sole();
        $this->assertSame(ConsultationRequestSource::B2b, $lead->source);
        $this->assertSame('local.pro', $lead->b2b_plan);
        $this->assertSame('PRO — daxili bazar', $lead->b2bPlanLabel());
    }

    public function test_source_and_plan_are_validated(): void
    {
        $post = fn (array $extra) => $this->postJson(route('consultation-requests.store'), [
            'full_name' => 'Anar Məmmədov', 'phone' => '+994 50 123 45 67', ...$extra,
        ]);

        $post(['source' => 'spam'])->assertJsonValidationErrors(['source']);
        $post(['source' => 'b2b', 'b2b_plan' => 'local.gold'])->assertJsonValidationErrors(['b2b_plan']);
        $post(['source' => 'home', 'b2b_plan' => 'local.pro'])->assertJsonValidationErrors(['b2b_plan']);

        $this->assertDatabaseCount('consultation_requests', 0);
    }

    public function test_admin_list_separates_b2b_leads_into_a_tab(): void
    {
        $this->actingAs(User::factory()->admin()->create(), 'admin');

        $home = ConsultationRequest::query()->create(['full_name' => 'Ev', 'phone' => '+994501234567', 'status' => ConsultationRequestStatus::Pending]);
        $b2b = ConsultationRequest::query()->create(['full_name' => 'Şirkət', 'phone' => '+994501234567', 'source' => 'b2b', 'b2b_plan' => 'local.basic', 'status' => ConsultationRequestStatus::Pending]);

        Livewire::test(ListConsultationRequests::class)
            ->set('activeTab', 'b2b')
            ->assertCanSeeTableRecords([$b2b])
            ->assertCanNotSeeTableRecords([$home])
            ->set('activeTab', 'home')
            ->assertCanSeeTableRecords([$home])
            ->assertCanNotSeeTableRecords([$b2b]);

        $this->get(ConsultationRequestResource::getUrl('edit', ['record' => $b2b]))->assertOk();
    }

    public function test_consultation_request_validates_required_and_malformed_data(): void
    {
        $this->postJson(route('consultation-requests.store'), [
            'full_name' => 'A',
            'phone' => 'abc',
            'message' => str_repeat('x', 2001),
        ])->assertUnprocessable()->assertJsonValidationErrors(['full_name', 'phone', 'message']);

        $this->assertDatabaseCount('consultation_requests', 0);
    }

    public function test_admin_resource_supports_create_update_and_delete(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin, 'admin');

        Livewire::test(CreateConsultationRequest::class)
            ->fillForm([
                'full_name' => 'Admin Created',
                'phone' => '+994501112233',
                'message' => 'Test müraciəti',
                'status' => ConsultationRequestStatus::Pending->value,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $request = ConsultationRequest::query()->where('full_name', 'Admin Created')->firstOrFail();

        Livewire::test(EditConsultationRequest::class, ['record' => $request->getRouteKey()])
            ->fillForm([
                'full_name' => 'Yenilənmiş müraciət',
                'phone' => '+994501112233',
                'message' => 'Yenilənmiş mətn',
                'status' => ConsultationRequestStatus::Contacted->value,
                'admin_note' => 'Müştəriyə zəng edildi.',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('consultation_requests', [
            'id' => $request->id,
            'full_name' => 'Yenilənmiş müraciət',
            'status' => ConsultationRequestStatus::Contacted->value,
        ]);

        Livewire::test(EditConsultationRequest::class, ['record' => $request->getRouteKey()])
            ->callAction('delete');

        $this->assertDatabaseMissing('consultation_requests', ['id' => $request->id]);
    }

    public function test_pending_requests_are_shown_in_navigation_badge(): void
    {
        ConsultationRequest::query()->create([
            'full_name' => 'Birinci müraciət',
            'phone' => '+994501234567',
            'status' => ConsultationRequestStatus::Pending,
        ]);

        $this->assertSame('1', ConsultationRequestResource::getNavigationBadge());
    }
}
