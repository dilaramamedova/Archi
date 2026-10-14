<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\SpecialistCategory;
use App\Enums\SpecialistCategoryGroup;
use App\Enums\UserStatus;
use App\Models\SpecialistProfile;
use App\Models\SpecialistService;
use App\Models\SpecialistSpecialty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The /specialists directory: its sort menu, its "verified" filter and the sections it
 * groups results into.
 *
 * Every one of these covers something that used to be decorative. Four of the five sort
 * options fell through to `experience_years`, so "ən yüksək reytinq", "ən çox rəy", "ən
 * ucuz" and "ən çox layihə" all produced one identical order. The "yalnız təsdiqlənmiş"
 * toggle filtered on `is_featured`, making it an exact duplicate of the featured filter.
 */
final class SpecialistDirectoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_rating_sort_orders_by_the_rating_column(): void
    {
        // Experience runs opposite to rating, so a listing still falling back to
        // experience_years would return the exact reverse of the expected order.
        $low = $this->specialist('low@example.com', ['rating_avg' => 3.1, 'experience_years' => 30]);
        $high = $this->specialist('high@example.com', ['rating_avg' => 4.9, 'experience_years' => 1]);

        $this->assertSame(
            [$high->id, $low->id],
            $this->idsFrom('/specialists?sort=rating'),
        );
    }

    public function test_reviews_sort_orders_by_the_review_count(): void
    {
        $few = $this->specialist('few@example.com', ['reviews_count' => 2, 'experience_years' => 30]);
        $many = $this->specialist('many@example.com', ['reviews_count' => 88, 'experience_years' => 1]);

        $this->assertSame([$many->id, $few->id], $this->idsFrom('/specialists?sort=reviews'));
    }

    public function test_cheap_sort_puts_the_lowest_service_price_first(): void
    {
        $pricey = $this->specialist('pricey@example.com');
        $cheap = $this->specialist('cheap@example.com');

        $this->service($pricey, 40);
        $this->service($cheap, 7);

        $this->assertSame([$cheap->id, $pricey->id], $this->idsFrom('/specialists?sort=cheap'));
    }

    public function test_cheap_sort_pushes_specialists_without_a_price_to_the_back(): void
    {
        // MySQL sorts NULL first when ascending, so a specialist who has listed no
        // service at all would otherwise win the "cheapest" sort with no price at all.
        $priced = $this->specialist('priced@example.com');
        $this->service($priced, 25);
        $unpriced = $this->specialist('unpriced@example.com');

        $this->assertSame([$priced->id, $unpriced->id], $this->idsFrom('/specialists?sort=cheap'));
    }

    public function test_cheap_sort_ignores_a_deactivated_service(): void
    {
        $profile = $this->specialist('deactivated@example.com');
        $this->service($profile, 5, isActive: false);
        $this->service($profile, 30);

        $other = $this->specialist('other@example.com');
        $this->service($other, 12);

        // The 5 ₼ service is switched off, so 30 is this profile's real floor.
        $this->assertSame([$other->id, $profile->id], $this->idsFrom('/specialists?sort=cheap'));
    }

    public function test_verified_filter_reads_the_verification_flag_not_the_featured_one(): void
    {
        $featuredOnly = $this->specialist('featured@example.com', ['is_featured' => true, 'is_verified' => false]);
        $verified = $this->specialist('verified@example.com', ['is_featured' => false, 'is_verified' => true]);

        $ids = $this->idsFrom('/specialists?verified=1');

        $this->assertContains($verified->id, $ids);
        $this->assertNotContains($featuredOnly->id, $ids, 'a merely featured profile must not count as verified');
    }

    public function test_featured_profiles_break_ties_instead_of_overriding_the_chosen_sort(): void
    {
        // Someone who asked for "cheapest first" must not get a promoted profile at the
        // top of the block holding the highest price on the page.
        $featuredExpensive = $this->specialist('promoted@example.com', ['is_featured' => true]);
        $this->service($featuredExpensive, 90);
        $plainCheap = $this->specialist('plain@example.com', ['is_featured' => false]);
        $this->service($plainCheap, 6);

        $this->assertSame([$plainCheap->id, $featuredExpensive->id], $this->idsFrom('/specialists?sort=cheap'));
    }

    public function test_results_are_ordered_by_section_before_anything_else(): void
    {
        // The tiler has both the best rating and the most experience; the architect
        // still comes first, because his section does.
        $tiler = $this->specialist('tiler@example.com', ['rating_avg' => 5.0, 'experience_years' => 40], SpecialistCategory::Master);
        $architect = $this->specialist('architect@example.com', ['rating_avg' => 1.0, 'experience_years' => 1], SpecialistCategory::Architect);

        $this->assertSame([$architect->id, $tiler->id], $this->idsFrom('/specialists?sort=rating'));
    }

    public function test_architects_and_designers_share_one_section(): void
    {
        $this->specialist('a@example.com', [], SpecialistCategory::Architect);
        $this->specialist('d@example.com', [], SpecialistCategory::Designer);

        $groups = $this->groupsFrom('/specialists');
        $design = $groups->firstWhere('group', SpecialistCategoryGroup::Design);

        $this->assertNotNull($design);
        $this->assertCount(2, $design['items']);
    }

    public function test_the_unfiltered_directory_keeps_a_section_that_has_nobody_in_it(): void
    {
        // A visitor has to be able to see that ARCHİ covers construction companies even
        // before the first one registers.
        $this->specialist('tiler@example.com', [], SpecialistCategory::Master);

        $keys = $this->groupsFrom('/specialists')->pluck('group')->all();

        $this->assertContains(SpecialistCategoryGroup::Design, $keys);
        $this->assertContains(SpecialistCategoryGroup::Master, $keys);
        $this->assertContains(SpecialistCategoryGroup::Company, $keys);
    }

    public function test_a_filtered_directory_drops_the_sections_with_no_results(): void
    {
        $this->specialist('tiler@example.com', [], SpecialistCategory::Master);

        $keys = $this->groupsFrom('/specialists?city=baku')->pluck('group')->all();

        $this->assertSame([SpecialistCategoryGroup::Master], $keys);
    }

    public function test_the_other_section_never_appears_empty(): void
    {
        $this->specialist('tiler@example.com', [], SpecialistCategory::Master);

        $this->assertNotContains(
            SpecialistCategoryGroup::Other,
            $this->groupsFrom('/specialists')->pluck('group')->all(),
        );
    }

    public function test_a_profile_whose_specialty_was_deleted_still_appears(): void
    {
        // specialist_specialty_id is nullOnDelete, so this is a real state — and the
        // person behind it is an active user, not orphan data to hide.
        $orphan = $this->specialist('orphan@example.com');
        $orphan->forceFill(['specialist_specialty_id' => null])->save();

        $groups = $this->groupsFrom('/specialists');
        $other = $groups->firstWhere('group', SpecialistCategoryGroup::Other);

        $this->assertNotNull($other, 'a profile with no specialty must still be listed');
        $this->assertCount(1, $other['items']);
        $this->assertSame(SpecialistCategoryGroup::Other, $groups->last()['group'], 'it belongs last');
    }

    public function test_a_section_counts_all_of_its_members_not_just_the_ones_on_this_page(): void
    {
        $this->specialist('architect@example.com', [], SpecialistCategory::Architect);

        // 25 craftsmen against a page size of 20: the section spans two pages.
        for ($i = 0; $i < 25; $i++) {
            $this->specialist("master{$i}@example.com");
        }

        $masters = $this->groupsFrom('/specialists')->firstWhere('group', SpecialistCategoryGroup::Master);

        $this->assertSame(25, $masters['total'], 'the heading must show the section total');
        $this->assertCount(19, $masters['items'], 'the architect takes the first slot of page 1');
    }

    public function test_a_section_whose_members_are_all_on_another_page_is_not_rendered_as_empty(): void
    {
        // The architect fills page 1 together with 19 craftsmen; by page 2 his section
        // has nothing left to show. Printing "nobody here yet" under it would be a lie.
        $this->specialist('architect@example.com', [], SpecialistCategory::Architect);

        for ($i = 0; $i < 25; $i++) {
            $this->specialist("master{$i}@example.com");
        }

        $keys = $this->groupsFrom('/specialists?page=2')->pluck('group')->all();

        $this->assertSame([SpecialistCategoryGroup::Master], $keys);
    }

    public function test_the_section_total_respects_the_active_filter(): void
    {
        $this->specialist('baku@example.com', ['city' => 'Bakı']);
        $this->specialist('ganja@example.com', ['city' => 'Gəncə']);

        $masters = $this->groupsFrom('/specialists?city=baku')->firstWhere('group', SpecialistCategoryGroup::Master);

        $this->assertSame(1, $masters['total']);
    }

    public function test_a_profile_with_nothing_on_it_is_not_offered_in_the_directory(): void
    {
        // Registration is approved automatically now, so account status no longer keeps
        // an unfinished profile out of the grid — scopeListable does.
        $empty = $this->specialist('empty@example.com', ['phone' => null, 'about' => null]);
        $ready = $this->specialist('ready@example.com');

        $ids = $this->idsFrom('/specialists');

        $this->assertContains($ready->id, $ids);
        $this->assertNotContains($empty->id, $ids);
    }

    public function test_an_unlisted_specialist_can_still_open_their_own_profile_page(): void
    {
        // The gate decides what the directory offers, not who may be seen. A master
        // has to be able to preview and share their page while they fill it in.
        $empty = $this->specialist('empty@example.com', ['phone' => null, 'about' => null]);

        // The heading is first + last name, not users.name — the factory generates the
        // three independently, so asserting on `name` would test nothing.
        $this->get("/specialist/{$empty->id}")->assertOk()->assertSee($empty->user->first_name);
    }

    public function test_a_contact_route_alone_is_not_enough_to_be_listed(): void
    {
        // Reachable, but says nothing about the work — a card a visitor cannot act on.
        $contactOnly = $this->specialist('contact@example.com', ['about' => null]);

        $this->assertNotContains($contactOnly->id, $this->idsFrom('/specialists'));

        // One active service is enough to describe the work.
        $this->service($contactOnly, 20);

        $this->assertContains($contactOnly->id, $this->idsFrom('/specialists'));
    }

    public function test_a_description_without_a_way_to_make_contact_is_not_listed(): void
    {
        $noContact = $this->specialist('nocontact@example.com', ['phone' => null]);

        $this->assertNotContains($noContact->id, $this->idsFrom('/specialists'));

        $noContact->update(['whatsapp' => '+994500000001']);

        $this->assertContains($noContact->id, $this->idsFrom('/specialists'));
    }

    public function test_search_applies_the_same_gate_as_the_directory(): void
    {
        $empty = $this->specialist('empty@example.com', ['phone' => null, 'about' => null]);
        $ready = $this->specialist('ready@example.com');

        // The specialty name is what the specialist matcher actually searches; the
        // helper names it after the category, so both profiles are candidates and the
        // gate is the only thing separating them.
        // Asserting on the profile URL rather than a name: two faker first names could
        // collide, and a link either is on the page or it is not.
        $this->get('/search?q=Master')
            ->assertOk()
            ->assertSee(route('specialist.show', $ready), false)
            ->assertDontSee(route('specialist.show', $empty), false);
    }

    public function test_the_profile_page_shows_the_verified_chip_only_for_a_verified_specialist(): void
    {
        $verified = $this->specialist('v@example.com', ['is_verified' => true]);
        // Featured but not verified: promotion is not verification, and the chip must
        // not follow the wrong flag the way the old markup would have.
        $featured = $this->specialist('f@example.com', ['is_featured' => true, 'is_verified' => false]);

        $this->get("/specialist/{$verified->id}")->assertOk()->assertSee(t('specialist.id.verified'));
        $this->get("/specialist/{$featured->id}")->assertOk()->assertDontSee(t('specialist.id.verified'));
    }

    public function test_the_card_aggregates_are_loaded_without_a_query_per_card(): void
    {
        $profile = $this->specialist('agg@example.com');
        $this->service($profile, 15);

        $listed = $this->collectionFrom('/specialists')->first();

        $this->assertSame(15.0, (float) $listed->min_service_price);
        $this->assertSame(0, (int) $listed->portfolio_count);
    }

    // ─── helpers ──────────────────────────────────────────────

    /** @return list<int> */
    private function idsFrom(string $url): array
    {
        return $this->collectionFrom($url)->pluck('id')->all();
    }

    private function collectionFrom(string $url)
    {
        return $this->viewData($url)['specialists']->getCollection();
    }

    private function groupsFrom(string $url)
    {
        return $this->viewData($url)['groups'];
    }

    private function viewData(string $url): array
    {
        $response = $this->get($url);
        $response->assertOk();

        return $response->original->getData();
    }

    private function specialist(
        string $email,
        array $attributes = [],
        SpecialistCategory $category = SpecialistCategory::Master,
    ): SpecialistProfile {
        $specialty = SpecialistSpecialty::query()->firstOrCreate(
            ['slug' => $category->value.'-trade'],
            ['name' => ucfirst($category->value), 'category' => $category],
        );

        $user = User::factory()->master()->create([
            'email' => $email,
            'status' => UserStatus::Active,
        ]);

        return SpecialistProfile::query()->create([
            'user_id' => $user->id,
            'specialist_specialty_id' => $specialty->id,
            'city' => 'Bakı',
            // Enough to clear SpecialistProfile::scopeListable, so these tests stay
            // about ordering, grouping and filtering rather than about the gate. The
            // gate has its own tests below.
            'phone' => '+994500000000',
            'about' => 'Test ustası.',
            ...$attributes,
        ]);
    }

    private function service(SpecialistProfile $profile, float $price, bool $isActive = true): SpecialistService
    {
        return SpecialistService::query()->create([
            'specialist_profile_id' => $profile->id,
            'name' => 'Xidmət '.$price,
            'price' => $price,
            'is_active' => $isActive,
        ]);
    }
}
