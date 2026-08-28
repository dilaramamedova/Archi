<?php

declare(strict_types=1);

namespace Tests\Feature;

use Database\Seeders\CalculatorSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The "PDF yüklə" button downloads a real file rendered from a frozen snapshot —
 * not a browser print dialog. The figures in the file are the snapshot's, so they
 * cannot drift from what the visitor saw even if a rate changes right after.
 */
class CalculatorPdfTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CalculatorSeeder::class);
    }

    private function makeSnapshot(): string
    {
        // Authenticated on purpose: a guest session is authorised by its browser
        // session id, which changes between test requests.
        $this->actingAs(\App\Models\User::factory()->create());

        $payload = [
            'object' => ['objectType' => 'APARTMENT', 'area' => 50, 'cityKey' => 'BAKU', 'segment' => 'STANDARD', 'condition' => 'SHELL', 'floor' => 2, 'totalFloors' => 5],
            'rooms' => [[
                'id' => 'room-1',
                'name' => 'Qonaq otağı',
                'roomType' => 'LIVING_ROOM',
                'length' => 5, 'width' => 4, 'height' => 2.8,
                'floorFinish' => 'LAMINATE',
                'wallFinish' => 'PAINT',
                'ceilingFinish' => 'CEILING_PAINT',
                'openings' => [],
            ]],
            'works' => [],
            'materials' => [],
            'modules' => [],
            'basket' => ['useCart' => false, 'excluded' => [], 'items' => []],
        ];

        $session = $this->postJson('/api/calculator/sessions', $payload)->assertCreated()->json('uuid');

        return $this->postJson("/api/calculator/sessions/{$session}/snapshot")
            ->assertCreated()
            ->json('uuid');
    }

    public function test_the_snapshot_pdf_downloads_as_a_real_pdf_file(): void
    {
        $uuid = $this->makeSnapshot();

        $response = $this->get("/api/calculator/snapshots/{$uuid}/pdf");

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString('attachment', (string) $response->headers->get('content-disposition'));
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    public function test_the_pdf_view_renders_the_snapshot_lines(): void
    {
        $uuid = $this->makeSnapshot();
        $snapshot = \App\Models\CalculatorSnapshot::where('uuid', $uuid)->firstOrFail();

        $html = view('pdf.calculator-estimate', ['snapshot' => $snapshot])->render();

        // The estimate's own content, in Azerbaijani, with the manat sign swapped
        // for "AZN" — dompdf's built-in font has the letters but not the ₼ glyph.
        $this->assertStringContainsString('Divarların suvanması', $html);
        $this->assertStringContainsString('AZN', $html);
        $this->assertStringNotContainsString('₼', $html);
        $this->assertStringContainsString('Qonaq otağı', $html);
    }
}
