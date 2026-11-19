# Specialist kinds: Briqadir and Makler

Added 2026-11-19 at the client's request: «Mütəxəssislər bölməsinə iki yeni kategoriya —
Briqadirlər və Maklerlər». A **Briqadir** leads a crew of craftsmen («usta dəstəsi») and
takes whole jobs. A **Makler** is a real-estate agent (əmlakçı).

## Model

- **No new role.** Both are `UserRole::Master`. Login redirects to the specialist cabinet,
  which is unchanged.
- **Kind = specialty category.** `SpecialistCategory::Foreman` (`foreman`) and
  `SpecialistCategory::Realtor` (`realtor`). `hasOwnKind()` / `ownKinds()` mark them as the
  kinds that have their own registration card and their own profile details.
- **One specialty row per kind.** The rows are `briqadir` («Briqadir (usta dəstəsi)») and
  `makler` («Makler (əmlak agenti)»). Migration `2026_11_19_120100` creates them and
  `DemoSeeder::seedSpecialties()` has them too. `SpecialistSpecialty::forKind()` resolves the
  active row. If an admin deactivates that row, the card refuses registration with a
  `kind` error.
- **Directory sections.** `SpecialistCategoryGroup` gains `Foreman` and `Realtor`. The order
  is Design, Master, Foreman, Company, Realtor, Other (`ordered()`, `rank()`).

## Profile details (`specialist_profiles.details`, json)

The shape is owned by `App\Support\SpecialistDetails`: rules, normalize, options, summary
and completeness all live there.

| Kind    | Keys |
|---------|------|
| Foreman | `crew_size` (1–200), `crew_trades` (master-category specialty ids), `crew_members` (≤30 × `{name, specialty_id, experience_years}`), `takes_full_projects` (bool) |
| Realtor | `agency_name`, `deal_types` (`sale`/`rent`/`daily_rent`), `property_types` (`apartment`/`house`/`office`/`commercial`/`land`), `service_areas`, `license_no` |

- `normalize($kind, $input)` keeps only the keys of `$kind` and casts them. It returns
  `null` for every other kind. That rule is applied in two places:
  - the cabinet (`SpecialistProfileController::update`, validated with
    `SpecialistDetails::rules()` against the kind of the specialty being saved);
  - the admin form (`mutateRelationshipDataBeforeSave/CreateUsing`).
- A cabinet save **without** a `details` key leaves the details alone. A switch to a plain
  trade clears them.
- `ProfileCompleteness::forSpecialist` adds one item for these kinds:
  - `crew.heading` (crew size + trades);
  - `realtor.heading` (deal + property types).

## Where it shows

- **Registration.** `/register` has five cards: Alıcı, Satıcı, Usta / Mütəxəssis, Briqadir
  and Makler.
  - `register.js` posts `role=master` + `kind=foreman|realtor`. The controller also folds
    `role=foreman|realtor` into that shape.
  - The «Usta» select excludes the kind rows (`SpecialistSpecialty::withoutOwnKinds()`).
  - `?role=foreman|realtor` preselects the card.
  - `data-for` is a comma list.
- **Cabinet.** `specialist-cabinet.blade.php` renders both blocks («Briqada», «Makler
  məlumatları»).
  - The JS shows the one that matches the selected specialty and posts only that one.
  - Crew members are an add/remove list.
- **Public profile.** `specialist.blade.php` shows a `.pp-kind` section with the facts, the
  chips and the crew list.
- **Directory.** `?type=foreman|realtor` filters the list. `specialists.js` keeps `type`
  when it applies filters or sort. The cards show `SpecialistDetails::summary()`: «Briqada:
  8 nəfər» / «Agentlik: X».
- **Header mega panel.** Two `MenuItem` rows (migration `2026_11_19_120200` +
  `MenuItemSeeder`):
  - the rows are ordered so the 2-column grid reads Arxitektor/Dizayner, Ustalar/Briqadirlər,
    Şirkətlər/Maklerlər;
  - icons: `icon-bricks.svg` for Briqadirlər and `icon-house.svg` (drawn for this, on the
    40px grid of the other cards) for Maklerlər; migration `2026_11_19_120400` switched
    existing rows and bumps the chrome cache;
  - the phone drawer lists the same rows as sub-links under «Mütəxəssislər».
- **Admin.** `BaseUserResource` (Master only) has:
  - a «Növ» column and filter;
  - Briqada / Makler fieldsets, visible by the selected specialty's kind;
  - read-only entries on the view page.
  `SpecialistSpecialtyResource` picks the new categories up from `SpecialistCategory::options()`.

## Validation copy

- `SpecialistDetails::attributes()` / `messages()` give every `details.*` key a human
  name, so errors never show `details.crew size`. The cabinet JS highlights the field and
  prints the message under it (crew rows are mapped by posted index).
- A crew row is only dropped when completely empty; a row with a trade or years and no
  name is posted and rejected.
- A Makler must pick at least one deal type (unless the save carries no `details`).
- Register: `city` / `specialist_specialty_id` have plain messages and HTML `required`
  while visible.

## Translations

The strings are in `database/seeders/data/specialist_kinds_translations.php`, in these
groups: `specialists`, `register`, `specialist`, `specialist-cabinet` and
`specialist-kinds` (the option labels). The file is wired into `TranslationsSeeder`.
Migration `2026_11_19_120300` inserts them with `firstOrCreate`, so admin edits survive.
The QA follow-up lives in `specialist_kinds_qa_translations.php` (singular kind labels,
validation copy, kind-neutral cabinet headings); `2026_11_19_120400` overwrites only the
keys in its `OVERWRITE` list.

## Tests

The tests are in `tests/Feature/SpecialistKindsTest.php`. They cover:
- registration;
- details validation and save;
- `?type=`;
- sections;
- profile blocks;
- the admin filter and view.
