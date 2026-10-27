# Laminat kartı — texniki icmal

Mənbə: «ТЗ на карточку ламината», Archi.az, v1.0, 20.10.2026. Bu sənəd TZ-nin saytda
necə həyata keçirildiyinin xəritəsidir. Müştəri kateqoriyaları bir-bir eyni üsulla
gətirdiyinə görə, növbəti kateqoriya üçün də başlanğıc nöqtəsidir.

> **Hazırda söndürülüb (27.10.2026).** Müştəri ilə qərar: kateqoriyalar bir-bir yenidən
> qurulana qədər bütün məhsullar eyni sadə forma ilə əlavə olunur. Sinif atributları,
> «tətbiq sahəsi» və bu laminat kartı `config/catalog.php` → `classifier_fields`
> (`CATALOG_CLASSIFIER_FIELDS`, default `false`) ilə bağlıdır. Kod, cədvəllər və testlər
> yerindədir (testlərdə açar açıqdır, `phpunit.xml`); geri açmaq üçün `.env`-də
> `CATALOG_CLASSIFIER_FIELDS=true` və `php artisan config:cache`.

## Əhatə

TZ iki böyük hissədən ibarətdir və onlar fərqli işlərdir.

**Həyata keçirilib — kateqoriyanın seçimləri:** 12 əsas parametr, asılı sahələr,
yoxlama və normallaşdırma qaydaları, yayım tələbləri, sənədlər, lüğət təklifləri,
moderator görünüşü, alıcı kartı və kataloq filtrləri. TZ-nin §10 qəbul siyahısının bu
hissəyə aid bütün maddələri testlə bağlanıb.

**Ayrıca mərhələ — paylaşılan məhsul modeli:** brend → kolleksiya → model → variant →
revizya zənciri, bir neçə satıcının eyni variantı öz təklifi ilə satması
(`seller_offers`), dəyişməz təsdiqlənmiş revizyalar (yeni versiya yoxlanılarkən köhnənin
görünməsi) və dublikatların birləşdirilməsi. Bu, saytın bütün kateqoriyalarda məhsula
necə sahib olunduğunu dəyişir, ona görə laminat tapşırığının içində edilməyib.
Hazırda hər satıcı öz məhsulunu yaradır; eyni artikulun mövcud kartı tapılanda
xüsusiyyətlər köçürülür, sənədlər isə köçürülmür.

## Verilənlər

| Cədvəl | Nə saxlayır |
|---|---|
| `laminate_specs` | Məhsul başına bir kart: 12 parametr, model, kolleksiya, artikul, versiya |
| `laminate_spec_heating_types` | İsti döşəmə sistemləri, (kart, kod) cütü birincil açardır |
| `laminate_spec_warranties` | Hər tətbiq sahəsi üçün bir zəmanət |
| `laminate_spec_documents` | Pasport, təlimat, şərtlər — fayl və ya https keçid |
| `dictionary_requests` | Təsdiq gözləyən brend və «Digər» dəyərləri |
| `audit_events` | Kartın dəyişiklik tarixçəsi, əvvəl/sonra fərqi ilə |

Niyə ümumi `product_attribute_values` yox: onun rəqəm sütunu 3 onluq saxlayır, TZ 4
tələb edir; «bəli / xeyr / göstərilməyib» cavabı, asılılıq və zəmanət strukturu yoxdur;
TZ qaydaların bazada olmasını istəyir, EAV sütununa isə atributa görə fərqli
məhdudiyyət qoymaq olmur. Müsbət ədədlər, icazəli kodlar və «fayl və ya keçiddən
yalnız biri» MySQL CHECK məhdudiyyəti kimi yazılıb.

Lüğətlər (`laminate-usage-class`, `laminate-abrasion`, `laminate-connection`) mövcud
`attributes` / `attribute_options` cədvəllərindədir, amma heç bir sinfə bağlı deyil —
ümumi sahə kimi formada görünmür, admin paneldən isə idarə olunur.

## Kod

| Sinif | Məsuliyyət |
|---|---|
| `Services\Laminate\LaminateCatalog` | Sinfin açarı (`laminat`), lüğət kodları, sürətli seçimlər |
| `Services\Laminate\LaminateSpecService` | Yoxlama, normallaşdırma, asılılıqlar, yayım tələbləri, saxlama |
| `Services\Laminate\LaminateDecimal` | Dəqiq onluq rəqəm: vergül və ya nöqtə, susqun yuvarlaqlaşdırma yoxdur |
| `Services\Laminate\LaminateDocuments` | Sənədlərin yoxlanması (MIME məzmundan) və saxlanması |
| `Services\Laminate\LaminateFormSchema` | Satıcı formasının tərifi və bütün mətnlər |
| `Services\Laminate\LaminateFilters` | Kataloq filtrləri və mövcud dəyərlər (fasetlər) |
| `Services\Laminate\LaminatePresenter` | Alıcı kartı: sətirlər, ikinci qiymət, sənədlər |
| `Services\Laminate\LaminateModeration` | Moderator xülasəsi: çatışanlar, dublikatlar, təkliflər |
| `Services\Laminate\DictionaryRequestResolver` | Təklifin təsdiqi və ya rəddi |

Satıcı forması `BusinessProductController`-də laminat sinfi seçiləndə ayrıca budağa
keçir (`storeLaminate` / `updateLaminate`). Digər siniflərin yolu dəyişməyib.

## Əsas qaydalar

- Asılı sahə yalnız ana cavab «bəli» olanda mövcuddur. Ana «xeyr» və ya «göstərilməyib»
  olanda server asılıları təmizləyir. Eyni sorğuda ana «xeyr» və dolu asılı göndərilərsə
  cavab `422 DEPENDENCY_CONFLICT`-dir və heç nə yazılmır.
- Sorğu PATCH formasındadır: göndərilməyən açar saxlanılan dəyəri saxlayır, boş açar
  təmizləyir. Naməlum açar `UNKNOWN_FIELD`-dir.
- Yayım üçün mütləqdir: təsdiqlənmiş brend, model adı, istehsalçı artikulu, qalınlıq,
  uzunluq, en, qablaşdırma sahəsi və ən azı bir şəkil. Qaralama boşluqlarla saxlanılır,
  cavab hər dəfə `missing_fields` qaytarır.
- Laminat üçün yeni brend avtomatik yaradılmır — gözləyən təklif olur, təsdiqə qədər
  kart yayımlana bilmir. Digər kateqoriyalarda brend əvvəlki kimi dərhal yaradılır.
- Redaktədə `expected_version` köhnədirsə cavab `409 VERSION_CONFLICT`-dir. Yaratmada
  `Idempotency-Key` başlığı ikiqat göndərmədən ikinci məhsul yaratmır.
- Qiymət yalnız 1 m² və ya 1 qablaşdırma üçündür. İkinci qiymət göstərmək üçün
  hesablanır, saxlanılan qiymət dəyişmir.
- Admin natamam laminat kartını təsdiqləyə bilmir.

## Deploy qeydi

Sənədlər 20 MB-a qədər ola bilər. Serverdə hazırda `upload_max_filesize = 12M`,
`post_max_size = 14M` və nginx `client_max_body_size 14M`-dir — 12 MB-dan böyük sənəd
yüklənməz. Deploy zamanı bunlar qaldırılmalıdır (məsələn 20M / 64M / 64M).

## Növbəti kateqoriya üçün

1. TZ-ni oxu və «kateqoriyanın seçimləri» ilə «paylaşılan model»i ayır.
2. Mövcud sinif atributlarının başqa sinifdə istifadə olunub-olunmadığını və dəyəri
   olub-olmadığını ölç — laminatda beşi də yalnız laminatda idi və boş idi.
3. Laminat sinifləri nümunədir: tipli kart cədvəli, xidmət, sxem, filtr, presenter.
   Ortaq hissələr (`LaminateDecimal`, `dictionary_requests`, `audit_events`,
   `IsoCountries`) yenidən istifadə oluna bilər.
4. Testlər TZ-nin qəbul siyahısını bir-bir izləsin — `tests/Feature/LaminateCardTest.php`.
