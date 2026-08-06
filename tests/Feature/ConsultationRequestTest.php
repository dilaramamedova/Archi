<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ConsultationRequestStatus;
use App\Filament\Resources\ConsultationRequestResource;
use App\Filament\Resources\ConsultationRequestResource\Pages\CreateConsultationRequest;
use App\Filament\Resources\ConsultationRequestResource\Pages\EditConsultationRequest;
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
