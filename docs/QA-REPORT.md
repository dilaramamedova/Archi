# QA Hesabatı — Usta və Satıcı səyahətləri (18.08.2026)

İki tam istifadəçi səyahəti real brauzerdə, real formlarla icra olundu — kod oxumaqla yox,
sayta girib istifadəçi kimi. Hər addımın nəticəsi bazada yoxlanıldı.

**Usta (Elvin Qasımov):** qeydiyyat → admin təsdiqi → profil (təcrübə, haqqında, bacarıqlar)
→ portfolio (2 şəkil) → xidmət + qiymət → iş qrafiki → şifrə dəyişmə → ictimai profil →
portfolio moderasiyası → məhsul alışı (səbət → checkout → sifariş) → seçilmişlər.

**Satıcı (TikintiPro MMC):** qeydiyyat → admin təsdiqi → şirkət məlumatları → əlaqə →
showroom → məhsul yerləşdirmə (klassifikator atributları ilə) → moderasiya → inventar/stok →
sifariş qəbulu → status idarəetməsi → şifrə dəyişmə → ictimai mağaza səhifəsi.

> **Qeyd:** rəy yazma, şərh və bildiriş funksiyaları v1-ə daxil deyil, ona görə də bu
> hesabatda onlara aid tapıntılar (usta rəylər səhifəsinin demo-datası, bildiriş
> ayarlarının saxlanmaması, `is_verified_purchase`) qəsdən kənarda saxlanılıb.

---

## İşləyənlər (biznes məntiqi düzgündür)

- **Moderasiya dövrü tam işləyir.** Pending istifadəçi daxil ola bilmir (aydın mesajla),
  admin təsdiqindən sonra girir. Pending portfolio ictimai profildə gizlidir, təsdiqdən
  sonra görünür. Təsdiqsiz məhsul kataloqda/axtarışda çıxmır, təsdiqdən sonra çıxır.
- **Sifariş axını düzgündür.** Qiymət bazadan götürülür (request-dən yox), stok azalır,
  sold_count artır, səbət təmizlənir, sifariş alıcının tarixçəsində və satıcının
  panelində düzgün məbləğlə görünür. Satıcı status pilləsini keçirə bilir, qaimə çapı var.
- **Rol izolyasiyası.** Satıcı usta kabinetinə girəndə 403; admin panel ayrı `admin`
  guard-dadır və `canAccessPanel` yalnız aktiv adminə icazə verir; qonaq bütün kabinet
  səhifələrindən login-ə yönləndirilir.
- **Şifrə dəyişmə.** Yanlış cari şifrə düzgün rədd olunur; yeni şifrə ilə giriş işləyir.
- **Profil tamlığı göstəricisi** hər addımda real artır (18% → 91%).
- **Klassifikator məhsul forması.** Bölmə → qrup → sinif kaskadı, sinif seçiləndə dinamik
  atribut sahələri yüklənir, seçilmiş atributlar EAV-a yazılır, `search_context` avtomatik dolur.

---

## Düzəldilən problemlər

### 1. Səssiz save uğursuzluğu — usta kabineti *(yüksək prioritet)*

Portfolio, xidmətlər və iş qrafiki səhifələrində `try { … } finally { … }` var idi, amma
**heç bir `catch` yox idi**. Nəticə: server JSON qaytarmayanda (500 HTML səhifəsi) və ya
şəbəkə qırılanda `await res.json()` istisna atırdı, handler-dən çıxırdı və istifadəçiyə
**heç nə göstərilmirdi** — düymə yenidən aktivləşirdi, sanki heç nə olmamışdı.

Düzəliş (biznes səhifələrində artıq mövcud olan idioma uyğun):
- `res.json()` → `res.json().catch(() => ({}))` — parse edilə bilməyən cavab handler-i öldürmür;
- `catch { error(errNetwork) }` — şəbəkə xətası "Şəbəkə xətası." kimi göstərilir;
- boş cavabda mesaj boş sətir olmasın deyə `|| errGeneric` fallback-i.

Yoxlanıldı: 500/HTML → "Xəta baş verdi", şəbəkə qırılması → "Şəbəkə xətası.", düymə
hər iki halda yenidən aktivləşir.

**Düzəliş:** `resources/js/pages/specialist-cabinet-{portfolio,schedule,services}.js`

### 2. Showroom səhifəsində ikiqat "Yadda saxla" *(yüksək prioritet)*

Səhifədə eyni anda iki "Yadda saxla" görünürdü: modalınkı (real POST/PUT edir) və
səhifəninki. Səhifəninki `setSaved(true)`-dan başqa heç nə etmirdi — sorğu göndərmədən
"saxlanıldı" göstərirdi. QA-da showroom məhz buna görə ilk cəhddə itdi.

Bu səhifədə hər yazma modal vasitəsilə gedir və uğurda reload olur, yəni səhifə
səviyyəsində save-bar-ın göndərəcəyi heç nə yoxdur. Ona görə də bar tamamilə silindi
(blade + JS handler). Regressiya olmasın deyə əlavə test yazıldı: səhifədə `cab-save-bar`
olmamalıdır.

**Düzəliş:** `resources/views/pages/business-profile-showrooms.blade.php`,
`resources/js/pages/business-profile-showrooms.js`, `tests/Feature/CabinetSaveBarTest.php`

### 3. Səhifə başlıqlarında daxili view adları *(orta)*

Brauzer tab-ında `specialist-profile-edit · Təhlükəsizlik` və
`business-profile-edit · Əlaqə` görünürdü. Dəyərlər tərcümə cədvəlində belə yazılmışdı.
7 başlıq digər səhifələrin konvensiyasına (`<Ad> — ARCHİ`) uyğunlaşdırıldı — həm bazada,
həm də `TranslationsSeeder`-də (seeder yenidən işlədilsə köhnəsi qayıtmasın deyə).

**Düzəliş:** `database/seeders/TranslationsSeeder.php` + `translations` cədvəli

### 4. Lokalizə olunmamış validasiya sahə adları *(orta)*

Validasiya xətaları xam sahə adlarını istifadəçiyə göstərirdi: "items sahəsi mütləqdir",
"images.0 yüklənmədi", "delivery_city sahəsi mütləqdir". Bütün controller və form
request-lərdəki 114 validasiya qaydası yığıldı, etiketi olmayan **63 sahə** üç dildə
(az/ru/en) əlavə edildi. İç-içə açarlar (`items.*.qty`, `services.*.name`,
`days.*.day_of_week`) ayrıca yazıldı — Laravel valideyn massivdən etiket miras almır.

Nəticə: "Sətirlər sahəsi mütləqdir", "Miqdar sahəsi mütləqdir", "VÖEN sahəsi mütləqdir",
"Ölçü vahidi sahəsi mütləqdir".

**Düzəliş:** `lang/{az,ru,en}/validation.php`

### 5. Məhsul formunda ölçü vahidi səssizcə "Kq" yazılırdı *(orta)*

Diaqnozu dəqiqləşdirdim: anbar səhifəsi düzgün göstərirdi — məhsulun `unit` dəyəri
həqiqətən `kg` idi. Əsl səbəb formada idi: select məcburi kimi işarələnib (`*`), amma boş
placeholder option-u yox idi, ona görə brauzer siyahıda birinci gələni (`kg`) avtomatik
seçirdi. Toxunulmamış sahə → parket kiloqramla dərc olunurdu.

Düzəliş: boş `Seçin` option-u + `required` (brauzer seçim etməyə məcbur edir), serverdə
`Rule::in(self::UNITS)` ilə yalnız tanınan vahidlərə icazə, fallback isə neytral `piece`.

**Serveri məcburi etməkdən qəsdən çəkindim:** `unit => required` 31 testi sındırdı —
kodun mövcud kontraktı bu sahənin opsional olmasıdır. Onu məcburi etmək spec dəyişikliyi
olardı, bug fix deyil. Real problem (səhv dəyər) formada həll olundu, server isə artıq
zibil qəbul etmir.

**Düzəliş:** `resources/views/pages/business-product-edit.blade.php`,
`app/Http/Controllers/Cabinet/BusinessProductController.php`

### 6. Axtarış ifadəsinin sıra bugı *(yüksək — əvvəlki optimallaşdırmanın regressiyası)*

Çoxsözlü axtarışda yeni dərc olunmuş məhsul bütün sözlərə uyğun gəlsə də tapılmırdı.
Səbəb: MySQL boolean mode-da öndə duran dırnaqlı ifadə söz-qrupu uyğunluqlarını
sıxışdırır. Ölçüldü: `"parket premium" (+(parket*) +(premium*))` → 1 sətir,
`(+(parket*) +(premium*)) "parket premium"` → düzgün 2 sətir. İfadə sırası dəyişdirildi
(söz qrupu əvvəl, ifadələr sonra).

**Düzəliş:** `app/Services/SearchService.php`

### 7. Brendsiz xəta səhifələri *(orta)*

Yalnız 404 üçün brendli səhifə var idi. Usta köhnə satıcı linkinə klikləyəndə xam ingilis
**"403 Forbidden"** görürdü — naviqasiya yoxdur, geri qayıtmaq yolu yoxdur, saytın bir
hissəsinə bənzəmir. Sessiya vaxtı bitəndə (419) və server xətasında (500) da eynisi.

Əlavə olundu: `403`, `419`, `500`, `503` + ortaq `<x-error-page>` komponenti (404-dən
çıxarıldı; 404 öz görünüşünü saxlayır, çünki əlavə "populyar axtarışlar" bloku var).

**Vacib dizayn qərarı:** `500` və `503` **qəsdən özü-yetərlidir** — nə `<x-layout>`,
nə `t()`, nə `route()`. Səbəb: layout menyuları bazadan/keşdən oxuyur, `t()` isə
tərcümələr cədvəlindən. Əgər çökən şey məhz baza idisə, normal chrome-u render etmək
ikinci istisna atardı və Laravel yenə də öz xam ingilis səhifəsinə qayıdardı — yəni bu
səhifələr məhz lazım olduğu anda işləməzdi. Yoxlandı: hər ikisi render olunur və heç bir
asılılığı yoxdur.

---

## Düzəlişlərdən sonra

- **269 test keçir, 0 uğursuz** (1311 assertion).
- Bütün düzəlişlər brauzerdə əl ilə yenidən yoxlanıldı.

---

## Düzəldilməyən, amma bilinməli

**Sifariş statusu dəyişəndə alıcıya bildiriş getmir.** Satıcı "Yığılır"a keçirir, alıcı
heç nə öyrənmir. Bildiriş sistemi v1-də olmadığı üçün toxunulmadı, amma email bildirişi
üçün queue infrastrukturu artıq hazırdır — v1-dən sonra ucuz əlavədir.

## Yoxlanıb təsdiqlənməyən iki "tapıntı"

Bunları hesabata salıram, çünki hər ikisi ilk baxışdan problem kimi göründü və yalnız
yoxlamadan sonra aydın oldu ki, deyil — səhv diaqnozu qeyd etməmək yanıltıcı olardı.

**1. Portfolio yükləmə xətası "səssiz" deyil.** Əvvəlki hesabatda 422 cavabında heç bir
mesaj göstərilmədiyini yazmışdım. Əslində popup **göstərilir** — mən onu yalnız `main`
daxilində axtarmışdım, popup isə `body` sonunda render olunur. Real problem daha dar idi
(JSON olmayan cavab və şəbəkə xətası) və düzəldildi — №1.

**2. CSRF qorunması işləyir.** Yoxlama zamanı token olmadan `PUT /specialist/cabinet/services`
sorğusu 200 qaytardı və xidmətləri sildi — ilk baxışda ciddi zəiflik. Araşdırma göstərdi ki,
Laravel 13-ün `PreventRequestForgery` middleware-i `Sec-Fetch-Site: same-origin` başlığını
brauzer-təminatlı müdafiə kimi qəbul edir; mənim sorğum eyni mənbədən idi, ona görə
qanuni olaraq keçdi. Middleware-i birbaşa çağırıb bütün halları yoxladım:

| Sorğu | Nəticə |
|---|---|
| `same-origin`, token yox | icazə verilir *(Laravel 13 dizaynı)* |
| `same-site`, token yox | **bloklanır** |
| `cross-site`, token yox | **bloklanır** |
| `Sec-Fetch-Site` başlığı yox | **bloklanır** |
| `cross-site` + zibil token | **bloklanır** |

Yəni real çarpaz-sayt hücumu bloklanır. Zəiflik yoxdur.

*(Qeyd: mənim `Csrf` sözü ilə axtarışım middleware-i tapmamışdı, çünki Laravel 13-də adı
`PreventRequestForgery`-yə dəyişdirilib.)*

**Prod qeydi:** ilk cəhddə portfolio yükləməsi PHP-nin `upload_tmp_dir` parametri təyin
olunmadığı üçün kəsildi. Prod serverdə bu qovluğun mövcud və yazıla bilən olmasını deploy
çeklistinə salın (`docs/PRODUCTION.md`).

---

*Test hesabları:* `elvin.usta.qa@example.test` (YeniSifre123!),
`aynur.satici.qa@example.test` (SaticiYeni123!) — hər ikisi admin tərəfindən təsdiqlənib.
