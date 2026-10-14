<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Regressions found driving the content pages and the site chrome by hand:
 *
 *  - /about linked to "/business-register", which has never been a route (the real
 *    path is /business/register) — a hard 404 from the second "join us" card.
 *  - /about's first join card prefilled role=specialist, but RegisterController only
 *    accepts buyer|seller|master, so the prefill was silently dead.
 *  - /about's hero and mission images fell back to /assets/about-hero.jpg and
 *    /assets/about-mission.jpg, neither of which exists in public/assets.
 *  - /help rendered the literal strings "help.contact_number" and
 *    "help.contact_emailt" as its phone and e-mail button labels, in all 3 locales.
 *  - the per-locale validation.php files are a partial copy of Laravel's messages and had no entry
 *    for `regex`, so the home consultation form showed the raw key "validation.regex"
 *    when the phone failed its pattern.
 *  - <footer> used ml-[calc(50%-50vw)] + w-screen while already being a direct child
 *    of <body>: % excludes the scrollbar and vw includes it, so the footer was a
 *    scrollbar-width too wide and sat ~7.5px left of the content above it.
 */
final class ContentPagesQaTest extends TestCase
{
    public function test_about_page_has_no_links_to_nonexistent_routes(): void
    {
        $html = $this->get('/about')->assertOk()->getContent();

        $this->assertStringNotContainsString('href="/business-register"', $html);
        $this->assertStringContainsString('/business/register', $html);
    }

    public function test_about_join_card_prefills_a_role_the_register_form_accepts(): void
    {
        $html = $this->get('/about')->assertOk()->getContent();

        $this->assertStringNotContainsString('role=specialist', $html);

        // Whatever role is prefilled must be one RegisterController validates.
        preg_match_all('/\/register\?role=([a-z]+)/', $html, $m);
        foreach (array_unique($m[1]) as $role) {
            $this->assertContains($role, ['buyer', 'seller', 'master'], "Unknown prefilled role: {$role}");
        }
    }

    public function test_about_page_image_fallbacks_point_at_files_that_exist(): void
    {
        $html = $this->get('/about')->assertOk()->getContent();

        preg_match_all('#src="(/assets/[^"]+)"#', $html, $m);
        $this->assertNotEmpty($m[1], 'No /assets images found on /about');

        foreach (array_unique($m[1]) as $path) {
            $this->assertFileExists(public_path(ltrim($path, '/')), "Missing asset referenced by /about: {$path}");
        }
    }

    public function test_help_page_does_not_leak_translation_keys(): void
    {
        foreach (['az', 'ru', 'en'] as $locale) {
            $this->get("/lang/{$locale}");
            $html = $this->get('/help')->assertOk()->getContent();

            $this->assertStringNotContainsString('help.contact_number', $html, "[{$locale}] leaked key");
            $this->assertStringNotContainsString('help.contact_emailt', $html, "[{$locale}] leaked key");
            $this->assertStringNotContainsString('help.contact_email', $html, "[{$locale}] leaked key");
        }
    }

    public function test_consultation_form_returns_a_real_message_not_a_validation_key(): void
    {
        $response = $this->postJson('/consultation-requests', [
            'full_name' => 'qa-Test Lead',
            'phone' => 'abc',          // fails the regex rule
            'message' => 'qa smoke test',
        ]);

        $response->assertStatus(422);

        $errors = json_encode($response->json('errors'), JSON_UNESCAPED_UNICODE);
        $this->assertStringNotContainsString('validation.', (string) $errors);
    }

    /**
     * Every validation rule the app actually uses must have a message in all three
     * locales, otherwise Laravel prints the bare key at the user.
     */
    public function test_every_locale_defines_the_validation_rules_the_app_uses(): void
    {
        $used = [
            'accepted', 'array', 'between', 'boolean', 'confirmed', 'date_format',
            'decimal', 'dimensions', 'distinct', 'email', 'exists', 'image', 'in',
            'integer', 'max', 'mimes', 'min', 'numeric', 'present', 'regex',
            'required', 'required_if', 'size', 'string', 'unique', 'url', 'uuid',
        ];

        foreach (['az', 'ru', 'en'] as $locale) {
            $messages = require base_path("lang/{$locale}/validation.php");

            foreach ($used as $rule) {
                $this->assertArrayHasKey(
                    $rule,
                    $messages,
                    "lang/{$locale}/validation.php has no message for the '{$rule}' rule"
                );
            }
        }
    }

    public function test_footer_does_not_use_a_viewport_width_breakout(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));

        $this->assertMatchesRegularExpression(
            '/^\s*footer\s+\{[^}]*w-full/m',
            $css,
            'The footer should be w-full; a vw breakout re-introduces horizontal overflow.'
        );
        $this->assertDoesNotMatchRegularExpression(
            '/^\s*footer\s+\{[^}]*50vw/m',
            $css,
            'The footer vw breakout is back — it overflows by half the scrollbar width.'
        );
    }
}
