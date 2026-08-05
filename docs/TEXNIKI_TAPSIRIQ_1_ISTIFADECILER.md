# ARCHI Marketplace — Texniki Tapşırıq

## Bölmə 1: İstifadəçilər, Autentifikasiya və Admin

---

## 1. İstifadəçi Rolları

Sistemdə 3 istifadəçi rolu mövcuddur:

| Rol | Daxili dəyər | Təsvir |
|-----|-------------|--------|
| **Alıcı** | `buyer` | Məhsul axtarır, səbətə əlavə edir, sifariş verir, usta tapır. Şəxsi profil səhifəsi yoxdur. Login sonrası ana səhifəyə yönləndirilir. |
| **Satıcı** | `seller` | Öz mağazasını (brend) yaradır, məhsul əlavə edir (maks. 5 ədəd), showroom-lar əlavə edir. Login sonrası biznes profil səhifəsinə yönləndirilir. |
| **Usta / Mütəxəssis** | `master` | Öz ixtisasını, portfoliosunu, xidmətlərini, iş qrafikini idarə edir. Login sonrası mütəxəssis kabinetinə yönləndirilir. |

---

## 2. Qeydiyyat (Register)

### 2.1 Giriş nöqtəsi

- **Səhifə:** `/register`
- **Blade:** `resources/views/pages/register.blade.php`
- **JS:** `resources/js/pages/register.js`
- **Controller:** `App\Http\Controllers\Auth\RegisterController`
- **Metod:** `POST /register` → `RegisterController@store`

### 2.2 Qeydiyyat axını

1. İstifadəçi `/register` səhifəsinə daxil olur (istəyə uyğun `?role=buyer|seller|master` query parametri ilə rol əvvəlcədən seçilə bilər).
2. 3 rol kartından birini seçir — seçimə uyğun olaraq əlavə sahələr görünür/gizlənir.
3. Formu doldurub göndərir (JS ilə `fetch`, JSON payload, CSRF token).
4. Server validasiya edir, `User` yaradır, **status = `Pending`** təyin edir.
5. Uğurlu olduqda: forma sıfırlanır, "Hesabınız admin təsdiqi gözləyir" mesajı göstərilir. **Auto-login olmur, redirect olmur.**
6. Xəta olduqda: ümumi xəta mesajı + hər sahə altında ayrıca xəta mesajı göstərilir.

### 2.3 Ümumi sahələr (bütün rollar üçün)

| Sahə | Tip | Validasiya |
|------|-----|-----------|
| `role` | hidden | required, in:buyer,seller,master |
| `first_name` | text | required, string, max:100 |
| `last_name` | text | required, string, max:100 |
| `email` | email | required, email, max:255, unique:users |
| `phone` | tel | required, string, max:30, unique:users |
| `password` | password | required, confirmed, min:6 |
| `password_confirmation` | password | password ilə eyni olmalıdır |
| `terms` | checkbox | accepted (mütləq qəbul edilməlidir) |

### 2.4 Rol-spesifik sahələr

**Satıcı (seller) üçün:**

| Sahə | Tip | Validasiya |
|------|-----|-----------|
| `company_name` | text | required_if:role,seller, string, max:255 |

Qeydiyyat zamanı `SellerProfile` yaradılır, `brand_name` sahəsi `company_name` ilə doldurulur.

**Usta / Mütəxəssis (master) üçün:**

| Sahə | Tip | Validasiya |
|------|-----|-----------|
| `specialization` | select (dropdown) | required_if:role,master, string, max:255 |
| `city` | select (dropdown) | required_if:role,master, string, max:100 |

Seçimlərin siyahısı dil fayllarından (`__('register.specializations')`, `__('register.cities')`) gəlir. Qeydiyyat zamanı `SpecialistProfile` yaradılır, `craft` və `city` sahələri doldurulur.

### 2.5 Qeydiyyatdan sonra — status axını

```
Qeydiyyat → Status: Pending → Admin baxır → 
  ├─ Təsdiq edir → Status: Active → İstifadəçi login edə bilər
  ├─ Rədd edir → Status: Rejected → Login mümkün deyil, səbəb göstərilir
  └─ Blok edir → Status: Blocked → Login mümkün deyil
```

**Qeydiyyat zamanı istifadəçi login edilmir.** Yalnız admin statusu `Active` etdikdən sonra login mümkündür.

---

## 3. Giriş (Login)

### 3.1 Giriş nöqtələri

Login **2 yerdən** mümkündür:

| Giriş nöqtəsi | Əlçatanlıq |
|----------------|-----------|
| **Ayrıca səhifə** `/login` | Birbaşa URL ilə |
| **Modal pəncərə** | Hər səhifədə navbar-dakı "Daxil ol" düyməsi və ya `data-login` atributlu istənilən element ilə açılır |

- **Controller:** `App\Http\Controllers\Auth\LoginController`
- **Metod:** `POST /login` → `LoginController@store`

### 3.2 Login sahələri

| Sahə | Tip | Validasiya | Qeyd |
|------|-----|-----------|------|
| `identifier` | text | required, string | Email və ya telefon nömrəsi. Server avtomatik müəyyən edir: `filter_var` ilə email formatına uyğundursa `email`, deyilsə `phone` sahəsinə görə axtarır. |
| `password` | password | required, string | |
| `remember` | checkbox | boolean | "Məni yadda saxla" — session müddətini uzadır |

### 3.3 Login axını

1. İstifadəçi email **və ya** telefon nömrəsi + parol daxil edir.
2. JS `fetch` ilə `POST /login` göndərir (JSON payload).
3. Server istifadəçini tapır, parolu yoxlayır, statusu yoxlayır.
4. **Status yoxlaması:**

| Status | Nəticə |
|--------|--------|
| İstifadəçi tapılmadı / yanlış parol | Xəta: "Yanlış giriş məlumatları" |
| `Pending` | Xəta: "Hesabınız admin təsdiqi gözləyir" |
| `Rejected` | Xəta: "Hesabınız rədd edilib" |
| `Blocked` | Xəta: "Hesabınız bloklanıb" |
| `Active` | Uğurlu giriş → yönləndirmə |

5. **Uğurlu girişdən sonra yönləndirmə (rol əsaslı):**

| Rol | Yönləndirilən səhifə |
|-----|---------------------|
| Alıcı (buyer) | `/` (ana səhifə) |
| Satıcı (seller) | `/business/profile` (biznes profil kabineti) |
| Usta (master) | `/specialist/cabinet` (mütəxəssis kabineti) |

### 3.4 Çıxış (Logout)

- **Metod:** `POST /logout` → `LoginController@destroy`
- Sessiya ləğv edilir, token yenilənir, ana səhifəyə yönləndirilir.

---

## 4. İstifadəçi Statusları

`App\Enums\UserStatus` enum-u ilə idarə olunur:

| Status | Dəyər | Rəng | Təsvir |
|--------|-------|------|--------|
| **Gözləmədə** | `pending` | Sarı (warning) | Yeni qeydiyyat, admin təsdiqi gözləyir |
| **Aktiv** | `active` | Yaşıl (success) | Admin tərəfindən təsdiqlənib, login mümkündür |
| **Rədd edilib** | `rejected` | Qırmızı (danger) | Admin rədd edib, login mümkün deyil |
| **Bloklanıb** | `blocked` | Boz (gray) | Admin blok edib, login mümkün deyil |

---

## 5. İstifadəçi Modeli (User)

### 5.1 Əsas sahələr

| Sahə | Tip | Qeyd |
|------|-----|------|
| `id` | bigint (PK) | Auto-increment |
| `name` | string | `first_name + ' ' + last_name` olaraq yaradılır |
| `first_name` | string | |
| `last_name` | string | |
| `email` | string | Unikal |
| `phone` | string | Unikal |
| `password` | string | Hash-lənmiş |
| `role` | enum (UserRole) | buyer, seller, master |
| `status` | enum (UserStatus) | pending, active, rejected, blocked |
| `rejection_reason` | string (nullable) | Admin rədd etdikdə səbəb |
| `terms_accepted` | boolean | Şərtləri qəbul edib |
| `approved_at` | timestamp (nullable) | Təsdiq tarixi |
| `remember_token` | string | Laravel standart |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

### 5.2 Əlaqələr (Relationships)

| Əlaqə | Tip | Model |
|--------|-----|-------|
| `sellerProfile()` | HasOne | `SellerProfile` — yalnız Satıcı rolu üçün |
| `specialistProfile()` | HasOne | `SpecialistProfile` — yalnız Usta rolu üçün |

### 5.3 Helper metodları

- `isActive()` — status Active-dir?
- `isPending()` — status Pending-dir?
- `isBuyer()` — rol buyer-dir?
- `isSeller()` — rol seller-dir?
- `isMaster()` — rol master-dir?

---

## 6. Alıcı (Buyer) Funksionallığı

Alıcının ayrıca profil/kabinet səhifəsi yoxdur. Aşağıdakı funksionallıqlar bütün səhifələrdə mövcuddur:

### 6.1 Seçilmişlər (Wishlist / Favorites)

- **Navbar-da ürək ikonu** mövcuddur (`icon-heart-rounded.svg`), hər səhifədə görünür.
- **Məhsul kartlarında** (`<x-pcard>`) ürək düyməsi var — kliklə seçilmişlərə əlavə/çıxarma.
- **Məhsul səhifəsində** 2 yerdə ürək düyməsi var: qalereya üstündə (`#pdHeartTop`) və satın alma bölməsində (`#pdWish`), hər ikisində `data-liked` atributu ilə toggle olunur.
- **Hazırda ayrıca seçilmişlər səhifəsi yoxdur** — frontend-də yalnız toggle funksionallığı var.

**Backend tələbləri:**
- `wishlists` cədvəli: `user_id`, `product_id`, `created_at`
- Seçilmişlərə əlavə etmək / çıxarmaq üçün API endpoint-ləri
- Seçilmişlər səhifəsi (navbar-dakı ürək ikonu ilə əlçatan)
- Qeydiyyatsız istifadəçi üçün: localStorage-da saxlanılır, login olduqdan sonra serverə sinxronizasiya

### 6.2 Səbət (Cart)

- **Səhifə:** `/cart`
- **Blade:** `resources/views/pages/cart.blade.php`
- **JS:** `resources/js/pages/cart.js`
- **Navbar-da:** səbət ikonu + say badge-i (`#navCartCount`)

**Mövcud frontend funksionallığı:**
- Səbət məlumatları **localStorage-da** saxlanılır
- Miqdar artırma/azaltma
- Məhsul silmə
- Promo kod tətbiqi (ARCHI15 — 15%, YENI10 — 10%, QIS20 — 20% min 200 AZN)
- Cəm hesablanması (ara cəm, endirim, çatdırılma, yekun)
- Sifariş düyməsi

**Backend tələbləri:**
- `carts` / `cart_items` cədvəlləri
- Login olan istifadəçi üçün server-side səbət
- localStorage → server sinxronizasiyası login zamanı
- Promo kod validasiyası server tərəfdə
- Stok yoxlaması

### 6.3 Sifarişlər

**Frontend-də hələ sifariş tarixçəsi səhifəsi yoxdur.** Backend-də yaradılmalıdır:

- `orders` cədvəli: `id`, `user_id`, `status`, `total`, `discount`, `promo_code`, `delivery_address`, `delivery_fee`, `notes`, `created_at`
- `order_items` cədvəli: `order_id`, `product_id`, `quantity`, `price`, `total`
- Sifariş statusları: `pending`, `confirmed`, `shipped`, `delivered`, `cancelled`
- Satıcıya bildiriş göndərilməsi
- Alıcının sifariş tarixçəsini görə bilməsi

### 6.4 Rəylər (Reviews)

**Alıcı rəy yaza bilir:**
- Məhsula rəy (ulduz reytinq + mətn)
- Usta/mütəxəssisə rəy (ulduz reytinq + mətn)
- Satıcıya/mağazaya rəy

**Backend tələbləri:**
- `reviews` cədvəli: `id`, `user_id`, `reviewable_type`, `reviewable_id`, `rating` (1-5), `text`, `created_at`
- Polymorphic əlaqə (Product, SpecialistProfile, SellerProfile üçün)
- Yalnız sifariş vermiş / xidmət almış istifadəçi rəy yaza bilər
- Mütəxəssis öz kabinetindən rəylərə cavab yaza bilər

---

## 7. Satıcı (Seller) Profil Kabineti

### 7.1 Sidebar naviqasiya

| Bölmə | Route | Qeyd |
|-------|-------|------|
| Şirkət | `/business/profile/company` | Şirkət məlumatları |
| Əlaqə | `/business/profile/contact` | Əlaqə məlumatları |
| Showroom-lar | `/business/profile/showrooms` | Say badge-i ilə |
| Məhsullar | `/business/profile/products` | Say badge-i ilə, **maks. 5 ədəd** |
| Bildirişlər | `/business/profile/notifications` | |
| Təhlükəsizlik | `/business/profile/security` | |

### 7.2 Şirkət məlumatları (`business-profile-company`)

| Sahə | Tip | Qeyd |
|------|-----|------|
| Cover şəkil | file upload | Üst örtük şəkli |
| Logo | file upload | Şirkət logosu, initials fallback |
| `legal_name` | text | Hüquqi ad |
| `brand_name` | text | Brend adı |
| `tax_id` | text | VÖEN, "Təsdiqlənib" badge-i göstərilir |
| `city` | text | Şəhər |
| `address` | text | Tam ünvan |
| `about` | textarea | Şirkət haqqında |

### 7.3 Əlaqə məlumatları (`business-profile-contact`)

**Əlaqə sahələri:**

| Sahə | Tip |
|------|-----|
| `contact_person` | text |
| `contact_role` | text |
| `phone` | text |
| `whatsapp` | text |
| `telegram` | text |
| `email` | text |
| `website` | text |
| `hours` | text (iş saatları) |

**Sosial şəbəkələr:**

| Sahə | Tip |
|------|-----|
| `instagram` | text |
| `linkedin` | text |
| `facebook` | text |

**Dillər:** Çip seçici — AZ, RU, EN, TR, Digər (çoxlu seçim)

### 7.4 Məhsullar (`business-profile-products`)

**Limit: Satıcı maksimum 5 məhsul əlavə edə bilər.**

**Mövcud frontend funksionallığı:**
- Məhsul siyahısı (cədvəl görünüşü)
- Axtarış sahəsi
- Kateqoriya filteri (dropdown)
- Status filteri (hamısı, aktiv, az stok, gizli)
- Hər məhsul sətri: thumbnail, ad, kateqoriya, qiymət, stok badge-i, görünürlük toggle, "Redaktə" düyməsi
- Pagination
- "Əlavə et" düyməsi

**Backend tələbləri:**
- `products` cədvəli: `id`, `seller_profile_id`, `name`, `category`, `brand`, `price`, `unit`, `description`, `stock_status`, `is_visible`, `sort_order`, `created_at`
- `product_images` cədvəli: `id`, `product_id`, `path`, `is_main`, `sort_order`
- Maksimum 5 məhsul limiti (server-side enforced)
- Məhsul CRUD əməliyyatları
- Şəkil yükləmə (çoxlu şəkil, ana şəkil seçimi)
- Görünürlük toggle (aktiv/gizli)
- Stok status idarəsi

### 7.5 Showroom-lar (`business-profile-showrooms`)

**Mövcud frontend funksionallığı:**
- Showroom siyahısı
- Hər sətir: xəritə ikonu, ad, meta məlumat, status badge (aktiv/gizli), "Redaktə", "Sil" düymələri
- "Əlavə et" düyməsi

**Backend tələbləri:**
- `showrooms` cədvəli (artıq mövcuddur): `id`, `seller_profile_id`, `name`, `address`, `hours`, `status`, `sort_order`
- Showroom CRUD əməliyyatları

### 7.6 Bildirişlər (`business-profile-notifications`)

**Bildiriş növləri (toggle):**
- Sifariş bildirişləri (`order`) — defolt: ON
- Rəy bildirişləri (`reviews`) — defolt: ON
- Stok bildirişləri (`stock`) — defolt: ON
- Hesabat bildirişləri (`report`) — defolt: ON

**Bildiriş kanalları (çip seçici):**
- Email — defolt: ON
- SMS — defolt: OFF
- Push — defolt: ON
- Telegram — defolt: OFF

### 7.7 Təhlükəsizlik (`business-profile-security`)

Bax: **Bölmə 9 — Ümumi Təhlükəsizlik.**

### 7.8 Satıcı — İctimai Profil Səhifəsi

- **Səhifə:** `/product` kontekstində satıcı bölməsi və `/business/profile` (public storefront)
- Cover şəkli, logo, şirkət adı, verified badge
- Yer, üzvlük tarixi, işçi sayı, showroom sayı, reytinq, məhsul sayı
- Haqqında bölməsi
- Showroom siyahısı
- Məhsul kataloqu (ürək düyməsi ilə)
- Əlaqə məlumatları sidebar-da
- "İzlə" (Follow) düyməsi
- "Əlaqə saxla" düyməsi

---

## 8. Usta / Mütəxəssis (Master) Profil Kabineti

### 8.1 Sidebar naviqasiya

| Bölmə | Route | Qeyd |
|-------|-------|------|
| Əsas məlumatlar | `/specialist/cabinet` | Şəxsi məlumatlar |
| Portfolio | `/specialist/cabinet/portfolio` | Say badge-i, maks. 30 şəkil |
| Xidmətlər | `/specialist/cabinet/services` | Say badge-i |
| İş qrafiki | `/specialist/cabinet/schedule` | |
| Rəylər | `/specialist/cabinet/reviews` | Say badge-i |
| Bildirişlər | `/specialist/cabinet/notifications` | |
| Təhlükəsizlik | `/specialist/cabinet/security` | |

### 8.2 Əsas məlumatlar (`specialist-cabinet`)

**Profil şəkli bölməsi:**
- Avatar (initials fallback)
- "Dəyiş" düyməsi, "Sil" düyməsi

**Şəxsi məlumatlar forması:**

| Sahə | Tip | Qeyd |
|------|-----|------|
| `first_name` | text | |
| `last_name` | text | |
| `craft` | select | İxtisas (dil faylından seçimlər) |
| `experience_years` | number | Təcrübə ili |
| `city` | select | Şəhər (dil faylından seçimlər) |
| `phone` | tel | |
| `whatsapp` | tel | |
| `about` | textarea | Haqqında (simvol sayğacı ilə, maks. limit dil faylından) |

**Bacarıqlar (Skills):**
- Tag/çip görünüşü, X düyməsi ilə silmə
- Inline text input ilə yeni bacarıq əlavə etmə (maks. 32 simvol)
- "Əlavə et" düyməsi

### 8.3 Portfolio (`specialist-cabinet-portfolio`)

- Şəkil grid-i (sürükle-burax ilə sıralama)
- Maksimum **30 şəkil**
- İlk şəkil "Üz qabığı" (Cover) olaraq işarələnir
- Hər şəkildə: şəkil, "Sil" düyməsi, başlıq mətni
- Yeni şəkil əlavə etmə (dashed upload zone, çoxlu fayl seçimi, jpeg/png)

### 8.4 Xidmətlər (`specialist-cabinet-services`)

- Xidmət siyahısı (sürükle-burax ilə sıralama)
- Hər xidmət sətri:
  - Grip handle (sıralama üçün)
  - Xidmət adı + təsvir
  - Qiymət inputu (decimal)
  - Vahid seçimi: kv.m, saat, ədəd, xətti metr
  - Görünürlük toggle (aktiv/gizli)
  - "Sil" düyməsi
- "Əlavə et" düyməsi

### 8.5 İş qrafiki (`specialist-cabinet-schedule`)

**Həftəlik cədvəl:**
- 7 gün sətri (Bazar ertəsi – Bazar), hər biri:
  - Toggle açar (iş günü / istirahət)
  - Başlanğıc vaxt inputu
  - Bitmə vaxt inputu
  - "İstirahət günü" qeydi (toggle bağlı olanda)

**Boş slotlar:**
- Stepper (azalt/artır) — eyni anda neçə müştəri qəbul edə bilər

**Tətil rejimi:**
- Toggle açar (defolt: OFF) — aktiv olduqda profil "tətildə" olaraq göstərilir

### 8.6 Rəylər (`specialist-cabinet-reviews`)

**Mövcud frontend funksionallığı:**
- Reytinq xülasəsi (ulduz + dəyər)
- Filter tab-ları: hamısı, cavabsız, 5-ulduz, aşağı reytinq
- Rəy siyahısı: avatar, ad, tarix, ulduz reytinqi, mətn
- Cavab bloku (əgər cavab verilib)
- "Cavab yaz" düyməsi
- Inline cavab yazma sahəsi: textarea + göndər/ləğv düymələri
- "Daha çox yüklə" düyməsi
- Boş hal mesajı

### 8.7 Bildirişlər (`specialist-cabinet-notifications`)

**Bildiriş növləri (toggle):**
- Sorğu (`request`) — defolt: ON
- Mesaj (`message`) — defolt: ON
- Rəy (`review`) — defolt: ON
- Bron (`booking`) — defolt: ON
- Platforma xəbərləri (`platform`) — defolt: OFF
- Həftəlik icmal (`weekly`) — defolt: ON

**Bildiriş kanalları (çip seçici):**
- Push — defolt: ON
- SMS — defolt: ON
- Email — defolt: OFF
- WhatsApp — defolt: ON

### 8.8 Təhlükəsizlik (`specialist-cabinet-security`)

Bax: **Bölmə 9 — Ümumi Təhlükəsizlik.**

---

## 9. Ümumi Təhlükəsizlik Funksionallığı

Həm Satıcı, həm Usta kabinetlərində eyni təhlükəsizlik funksionallığı mövcuddur:

### 9.1 Parol dəyişdirmə

| Sahə | Tip | Validasiya |
|------|-----|-----------|
| `current_password` | password | Mövcud parol, düzgünlük yoxlanılır |
| `password` | password | Yeni parol, min:6 |
| `password_confirmation` | password | Təsdiq, password ilə eyni |

- **Endpoint:** `POST /cabinet/password` → `SecurityController@changePassword`
- Mövcud parol yoxlanılır, yeni parol hash-lənib saxlanılır.

### 9.2 İki faktorlu autentifikasiya (2FA)

- Toggle açar
- Defolt: Usta üçün OFF, Satıcı üçün ON
- **Backend-də hələ implementasiya yoxdur** — gələcək tələb olaraq nəzərdə tutulur

### 9.3 Aktiv sessiyalar

- **Endpoint:** `GET /cabinet/sessions` → `SecurityController@sessions`
- Aktiv cihaz/sessiya siyahısı göstərilir
- Hər sessiya: cihaz adı, meta məlumat, "Bu cihaz" badge-i (cari sessiya üçün)
- Digər sessiyaları sonlandırma: `DELETE /cabinet/sessions` → `SecurityController@destroySession`

### 9.4 Hesabı deaktiv etmə

- **Endpoint:** `POST /cabinet/deactivate` → `SecurityController@deactivateAccount`
- Mütəxəssis kabinetində: parol təsdiq dialoqu ilə (parol daxil etmədən deaktiv etmək olmaz)
- Satıcı kabinetində: birbaşa düymə (frontend-də hələ parol təsdiqi yoxdur — əlavə olunmalıdır)
- Hesab deaktiv edildikdə: status `Blocked` olur, sessiya sonlandırılır, login səhifəsinə yönləndirilir

---

## 10. Onboarding Axınları

### 10.1 Satıcı Onboarding (3 addım)

Qeydiyyatdan sonra satıcı addım-addım profil məlumatlarını doldurur:

**Addım 1 — Şirkət məlumatları** (`/business/onboarding/step-1`):
- `legal_name`, `brand`, `tax_id` (maks. 10 rəqəm), `city` (combobox), `address`, `phone`, `showroom`, `logo` (fayl yükləmə, .png/.svg), `about`
- Progress: 25%

**Addım 2 — Əlaqə məlumatları** (`/business/onboarding/step-2`):
- `contact_person`, `role`, `phone`, `whatsapp`, `telegram`, `email`, `website`, `hours`
- Sosial şəbəkələr: `instagram`, `linkedin`, `facebook`
- Dillər seçimi (çip qrupu)
- Progress: 50%

**Addım 3 — İlk məhsul** (`/business/onboarding/step-3`):
- Məhsul şəkilləri (4 upload slot, birincisi "əsas foto")
- `name`, `category` (combobox), `brand`, `price`, `unit`, `description`
- Progress: 75% → tamamlandıqda 100%

Hər addımda "Sonra dolduraram" (Fill later) seçimi var — onboarding-i atlamaq mümkündür.

### 10.2 Usta / Mütəxəssis Onboarding (4 addım checklist)

Qeydiyyatdan sonra mütəxəssisə onboarding səhifəsi göstərilir (`/specialist/onboarding`):

- Progress bar (40% nümunə)
- 4 addım checklist:
  1. **Əsas məlumatlar** (Basics) — tamamlanmış olaraq göstərilir ✓
  2. **İxtisas** — `specialist.cabinet` səhifəsinə yönləndirir
  3. **Portfolio** — `specialist.cabinet.portfolio` səhifəsinə yönləndirir
  4. **İş qrafiki** — `specialist.cabinet.schedule` səhifəsinə yönləndirir
- Sağ tərəfdə: Mütəxəssis kartının preview-su (45% opacity, hələ aktiv deyil)
- Bütün addımlar tamamlandıqda profil ictimai olur

---

## 11. Admin Paneli

### 11.1 Texnologiya

- **Framework:** Filament 4 (Laravel admin panel)
- **Giriş:** Yalnız `admin@archi.test` email-i ilə (`User::canAccessPanel` ilə hardcoded)
- **URL:** `/admin` (standart Filament prefix)

### 11.2 İstifadəçi İdarəetməsi

**Mövcud Filament resurs:** `App\Filament\Resources\UserResource`

**Səhifələr:**
- İstifadəçi siyahısı (ListUsers)
- İstifadəçi yaratma (CreateUser)
- İstifadəçi redaktəsi (EditUser)
- İstifadəçi baxışı (ViewUser)

**Admin-in əsas vəzifələri:**

| Əməliyyat | Təsvir |
|-----------|--------|
| **Yeni istifadəçiləri təsdiq etmək** | Status: Pending → Active, `approved_at` tarixi yazılır |
| **İstifadəçiləri rədd etmək** | Status: Pending → Rejected, `rejection_reason` yazılır |
| **İstifadəçiləri bloklamaq** | Status: Active → Blocked |
| **İstifadəçi məlumatlarını görmək** | Bütün profil məlumatları, əlaqələr |
| **İstifadəçi redaktə etmək** | Rol, status, şəxsi məlumatlar |

### 11.3 Admin üçün əlavə tələblər (gələcək)

- Məhsul moderasiyası
- Rəy moderasiyası
- Sifariş izləmə
- Statistika dashboard
- Promo kod idarəetməsi
- Bildiriş göndərmə

---

## 12. Dil Dəstəyi (i18n)

- 3 dil: **Azərbaycan (az)**, **Rus (ru)**, **İngilis (en)**
- Dil dəyişdirmə: `GET /lang/{locale}` — session-da saxlanılır, geri yönləndirilir
- **Middleware:** `SetLocale` — hər request-də session-dan dil oxuyub tətbiq edir
- Bütün mətnlər `__('key')` ilə tərcümə fayllarından gəlir
- Hər dil üçün 41+ tərcümə faylı mövcuddur (`lang/az/`, `lang/ru/`, `lang/en/`)
- Qeydiyyat formasındakı seçimlər (ixtisas, şəhər, kateqoriya) da dil fayllarından gəlir

---

## 13. Middleware və Qoruma

| Middleware | Tətbiq sahəsi | Funksiya |
|-----------|--------------|---------|
| `SetLocale` | Bütün web route-lar (qlobal) | Session-dan dil oxuyub app locale təyin edir |
| `EnsureUserIsActive` | Mövcuddur amma qlobal qeydiyyatda deyil | Aktiv olmayan istifadəçini logout edib login-ə yönləndirir |
| `auth` (Laravel built-in) | Mütəxəssis kabineti route-ları | Yalnız login olmuş istifadəçilər |

**Əlavə olunmalı middleware-lər:**
- Satıcı kabineti route-ları üçün `auth` + rol yoxlaması
- Aktiv istifadəçi yoxlaması bütün qorunan route-lara tətbiq
- Rate limiting (login cəhdləri üçün)

---

## 14. Texniki Qeydlər

### 14.1 Frontend arxitekturası
- SPA framework yoxdur — Blade + vanilla JS
- Hər səhifənin öz JS modulu var (`resources/js/pages/`)
- Hər səhifənin öz CSS faylı var (`resources/css/pages/`)
- `data-page` atributu ilə səhifə-spesifik JS modullar yüklənir
- CSRF token JS tərəfdən header-da göndərilir
- Formalar JSON payload ilə `fetch` API vasitəsilə göndərilir

### 14.2 Əsas texnologiyalar
- **Backend:** Laravel 13, PHP 8.3+
- **Frontend:** Blade templates, Tailwind CSS 4, Vite 8, Vanilla JS
- **Admin:** Filament 4
- **DB:** MySQL/PostgreSQL (Laravel standart)
- **Dev server:** `composer dev` (Laravel + Pail + Vite concurrent)
