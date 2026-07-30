# ARCHI — Sessiya Handoff (davam etdirmək üçün kontekst)

> Bu faylı yeni chat-a ver ki, harada qaldığımızı bilsin.
> Son yenilənmə: 2026-07-26

---

## 1. Layihə haqqında

| | |
|---|---|
| **Ad** | ARCHI — tikinti/təmir marketplace (B2C + B2B) |
| **Yol** | `C:\Users\mamed\OneDrive\Desktop\ARCHI` |
| **Stack** | Statik HTML + CSS + JS (framework yoxdur, build step yoxdur) |
| **Shared fayllar** | `archi.css`, `archi.js` (ortaq navbar + footer) |
| **Git** | branch `main`, son commit `83b074f` |
| **Dil** | İnterfeys **Azərbaycan dilində**, ünsiyyət də Azərbaycan dilində |

### Figma
| | |
|---|---|
| **File** | `archi-2` |
| **fileKey** | `1VQNQO1hPMNJ657B1UH8SF` |
| **Əsas canvas** | `213:4101` ("homepage") — bütün section-lar burdadır |
| **MCP server** | `mcp__af4d2ee0-c84f-4ca5-b798-76c9ca5b4fac` (işləyir) |
| **İkinci server** | `plugin:figma:figma` — OAuth tələb edir, bu sessiyada **işləmir**, istifadə etmə |

---

## 2. Bu sessiyada nə edildi

### İş #1 — Çatışmayan səhifələrin siyahısı
Figma `213:4101`-dəki bütün frame adları repo-dakı `.html` faylları ilə tutuşduruldu.

**Repo-da mövcud 23 səhifə:**
```
index.html            catalog.html          product.html          search.html
specialists.html      blog.html             cart.html             sell.html
login.html            register.html         calculator.html       calculator-detailed.html
biznes-qeydiyyat.html biznes-tamamlama-addim2.html  biznes-tamamlama-addim4.html
biznes-profil.html    biznes-profil-sirket.html     biznes-profil-elaqe.html
biznes-profil-shourumlar.html  biznes-profil-mehsullar.html
biznes-profil-bildirisler.html biznes-profil-tehlukesizlik.html
```

**Hələ də çatışmayan (HTML yazılmayıb):**
- `specialist-profile.html` (Figma `777:2779`)
- `blog-article.html`
- biznes onboarding **Addım 3 / Əlaqə** səhifəsi (addim2 və addim4 var, arası yoxdur)
- **mütəxəssis onboarding-in 4 step-i** — dizaynı bu sessiyada Figma-da hazırlandı, HTML-i **hələ yoxdur**

> Qeyd: `calculator-detailed.html` bütün 6 step-i (Obyekt / Otaqlar / İşlər / Materiallar / Səbət / Yekun) ehtiva edir — Figma-dakı "calculator 7" section-u tam bitib, ora toxunmağa ehtiyac yoxdur.

---

### İş #2 — Mütəxəssis onboarding dizaynının Figma-da generasiyası

Analiz edilən referanslar:
- `1154:15126` — qeydiyyat/auth ekranı ("Usta / Mütəxəssis" seçili)
- `1105:21827` — "biznes profile 10" onboarding section-u (**struktur donoru**)
- `777:2779` — mütəxəssis profil səhifəsi (**hansı sahələr yığılmalıdır — burdan çıxarıldı**)

**Yaradılan section: `1172:4452` — "mutexessis onboarding 5"** (x = -20924, y = 62432)

> ⚠️ **Node ID-ləri dəyişdi.** Aşağıdaki cədvəldəki `1156:*` ID-ləri artıq **mövcud deyil** (section köçürüldüyü üçün yeniləri verilib). 2026-07-26 sessiyasında yoxlanılıb və düzəldilib — `1156:*` ilə çağırış "node not found" verir.

| Node ID (aktual) | Köhnə (ölü) | Frame | Hündürlük |
|---|---|---|---|
| `1172:4453` | ~~`1156:3`~~ | Addım 1: Şəxsi məlumatlar | 1974 |
| `1172:4665` | ~~`1156:222`~~ | Addım 2: İxtisas və xidmətlər | 2008 |
| `1172:4916` | ~~`1156:441`~~ | Addım 3: Portfolio | 2141 |
| `1172:5140` | ~~`1156:879`~~ | Hazırdır (uğur ekranı) | 1735 |

Hər frame-in alt node-ları (`head` / `content` — get_design_context üçün navbar+footer-i kəsmək lazımdır):

| Frame | head | content |
|---|---|---|
| Addım 1 | `1172:4455` | `1172:4458` |
| Addım 2 | `1172:4667` | `1172:4670` |
| Addım 3 | `1172:4918` | `1172:4921` |
| Hazırdır | `1172:5142` | `1172:5145` |

---

### İş #3 — İstifadəçinin əl ilə etdiyi düzəlişlərə uyğunlaşdırma
İstifadəçi:
- **Addım 4-ü (`1156:660` — "Təsdiqlənmə və əlaqə") sildi**
- "İş qrafiki" + "Xidmət göstərdiyin rayonlar" sahələrini **Addım 2-yə köçürdü**

Buna görə bütün flow **4 step → 3 step**-ə çevrildi (stepper yenidən quruldu, progress faizləri 0→33→66→100 kimi düzəldildi).

---

### İş #4 — Input dizaynının B2B ilə eyniləşdirilməsi ✅ BİTDİ
Referans: `1105:21876` → içindəki `1105:21898` (form-card).

**Kök səbəb:** ilk build-də stroke-ları `opacity: 1` ilə qurmuşdum, çünki ilk inspeksiya paint-in **opacity** və **boundVariables** sahələrini oxumurdu → konturlar tünd qara çıxmışdı.

**Düzəliş:** 4 frame boyunca **32 input/textarea, 5 dashed upload zonası, 8 chip, 67 text node** design-system dəyişənlərinə **bind** edildi (hardcoded hex yox).

---

## 3. Design tokenləri (avtoritativ spec — `1105:21898`-dən çıxarılıb)

| Element | Dəyər |
|---|---|
| `form-card` | fill `VariableID:995:5117` (white) · stroke `VariableID:995:5129` black **@0.10** w1 INSIDE · r4 · pad 28/32 |
| `input` | fill `995:5117` · stroke `VariableID:995:5130` black **@0.14** w1 INSIDE · r4 · pad 13/16 · h43 |
| `textarea` | input ilə eyni, h96 |
| placeholder | `VariableID:995:5128` black @0.40 · Inter Regular 14 |
| field label | `VariableID:995:5116` #111111 · Inter Semi Bold 13 |
| `up` (upload) | fills `[]` · stroke black **@0.25** w1 dash `[6,4]` · **radius 0** · pad 22 · text Inter Semi Bold 14 `rgb(89,89,102)` |
| `fi` (kiçik) | fills `[]` · stroke black @0.10 · r4 · pad 12/14 · text #808085 Inter Regular 14 |
| stepper | white · stroke black w1 · pad 20 · SPACE_BETWEEN · aktiv dairə 28×28 r14 #111111 + white Inter Bold 13 · passiv #e5e8ed + #808085 · conn 20×2 #e0e3e8 |
| action bar | white r4 pad 24 · save btn **#fdfe00** r4 pad 15/28 Inter Semi Bold 15 #111111 · ikincili text Inter Medium 14 black@0.5 |
| progress-card | white r4 pad 20 gap 12 · title Inter Semi Bold 14 · bar 280×6 bg black@0.08 r4 (daxilində #fdfe00 və #ffe600) · caption Inter Regular 13 black@0.5 |
| tip | fill **#fffde0** · stroke **#ffe600** · r4 · pad 16 gap 8 · title Inter Semi Bold 13 · body Inter Regular 13 black@0.7 lh150 |
| səhifə fonu | `VariableID:995:5124` (#f5f7f9) |
| star token | `--star: #FFE600` |

### Səhifə frame strukturu (bütün onboarding frame-lərində eyni)
```
FRAME 1440×H  layoutMode = NONE   ← auto-layout YOXDUR, əl ilə reflow lazımdır
  Navbar   INSTANCE 1440×140   y=0
  head     FRAME 1440×128  VERTICAL pad 32,28,20,28 gap 20
  content  FRAME 1440×?    HORIZONTAL pad 0,28,20,28 gap 32
    left  880 VERTICAL gap 10 → [stepper 880×68, form-card, action bar 880×96]
    side  472 VERTICAL gap 16 → [progress-card 472×103, tip 472×96]
  FooteR   FRAME 1440×960
```

---

## 4. Təkrar istifadə olunan skriptlər

### Reflow (kontent hündürlüyü dəyişəndən **sonra mütləq** işlət)
```js
const order = ["1172:4453","1172:4665","1172:4916","1172:5140"];
let x = 400;
for (const id of order){
  const f = await figma.getNodeByIdAsync(id);
  const nav=f.children[0], head=f.children[1], content=f.children[2], footer=f.children[3];
  head.x=0;    head.y=nav.height;
  content.x=0; content.y=head.y+head.height;
  footer.x=0;  footer.y=content.y+content.height;
  f.resize(1440, footer.y+footer.height);
  f.x=x; f.y=400; x += 1640;
}
```

### Builder helper-ləri
```js
function C(h){return {r:parseInt(h.slice(1,3),16)/255,g:parseInt(h.slice(3,5),16)/255,b:parseInt(h.slice(5,7),16)/255};}
function fl(h,o){return [{type:'SOLID',color:C(h),opacity:o===undefined?1:o}];}
function T(t,s,st,c,o){const n=figma.createText();n.fontName={family:'Inter',style:st};n.characters=t;n.fontSize=s;n.fills=fl(c,o);return n;}
// inp(ph,caret) · field(label,ph,caret) · row(fs) · upload(label,ph) · area(label,ph,h)
```

### Dəyişənə bind etmə (rəngi hardcode ETMƏ)
```js
const v = await figma.variables.getVariableByIdAsync("VariableID:995:5130");
let p = {type:'SOLID', color:{r:0,g:0,b:0}, opacity:0.14};
p = figma.variables.setBoundVariableForPaint(p, 'color', v);  // YENİ paint qaytarır
node.strokes = [p];                                            // mütləq yenidən mənimsət
```

---

## 5. Qaydalar / tələlər (bunları bil, yenidən səhv etmə)

### Figma MCP
- **Hər `use_figma` çağırışından ƏVVƏL** `get_figma_skill` ilə skill yüklə; `skillNames: "resource:figma-use,resource:figma-generate-design"` ötür.
- Rənglər **0–1** aralığında, 0–255 deyil.
- `fills` / `strokes` **read-only array**-dir → klonla, dəyiş, geri mənimsət.
- Mətn dəyişməzdən əvvəl `figma.loadFontAsync`.
- `layoutSizingHorizontal='FILL'` yalnız `appendChild`-dan **sonra**.
- `resize()` sizing mode-ları sıfırlayır → əvvəl resize, sonra sizing.
- Skriptlərdə yaradılan/dəyişilən node ID-lərini **həmişə `return` et**.
- Skript xəta versə **atomik geri qayıdır** — heç nə yazılmır.
- `node.query('FRAME[name=input], FRAME[name=textarea]')` + `.toArray()` / `.first()` işləyir.
- Section child-larının x/y-i **section-a nisbətəndir**.

### Windows mühiti
- `jq` **quraşdırılmayıb** → `node -e` ilə JSON parse et.
- `curl` SSL xətası verir (exit 35) → PowerShell `Invoke-WebRequest -Uri ... -OutFile ...` işlət.
- `get_metadata` böyük node-larda **1.3M simvol** qaytarıb token limitini aşa bilər → nəticə `tool-results\*.txt`-ə yazılır, onu `node -e` ilə filtrləyib oxu.
- Müvəqqəti fayllar üçün scratchpad:
  `C:\Users\mamed\AppData\Local\Temp\claude\C--Users-mamed-OneDrive-Desktop-ARCHI\<session>\scratchpad`

---

## 6. Həll olunmuş sual ✅

**"✓ Təsdiqlənmiş" badge-i necə verilir?** → **(b) admin tərəfindən əl ilə** (istifadəçi 2026-07-26-da qərar verdi). Ayrıca `mutexessis-profil-tesdiq.html` səhifəsi **lazım deyil**.

> ⚠️ Bundan doğan uyğunsuzluq: **Hazırdır ekranında** (`1172:5140`, xülasə sətri node `1172:5174`) «✓ Təsdiqlənmiş» yazılıb. Profil dərc olunan anda badge hələ verilməmiş olur — HTML-ə çevirəndə bu sətir çıxarılmalı və ya "Yoxlanılır" ilə əvəz edilməlidir.

---

## 7. Növbəti addımlar

1. Mütəxəssis onboarding step-lərini HTML-ə çevirmək:
   `mutexessis-tamamlama-addim1.html` … `addim3.html` + `hazirdir` ekranı
   → **normal axınla yaz** (bax §9-dakı footer tələsi), absolyut düzümü təkrarlama
2. Rol-əsaslı homepage-lərin HTML-i (`1175:4452` / `1175:4724` — bax §10)
3. Biznes onboarding **Addım 3 / Əlaqə** səhifəsi
4. `blog-article.html`

**Artıq bitmiş** (köhnə handoff yanlış olaraq "çatışmayan" göstərirdi):
- `specialist.html` — Figma `777:2779`-un HTML-i, 341 sətir, `pp-` prefiksli (commit edilməyib)
- `catalog.html`, `specialists.html` (commit edilməyib)

---

## 8. Commit vəziyyəti

Bu sessiyada **heç nə commit edilməyib** və istifadəçi commit istəməyib.

Dəyişmiş fayllar (`git status`):
```
M  archi.css, archi.js, index.html, product.html, search.html
M  biznes-qeydiyyat.html, biznes-tamamlama-addim2.html, biznes-tamamlama-addim4.html
M  biznes-profil.html + 6 kabinet alt-səhifəsi
D  home.html
?? assets/ic-chevron-sm.svg, catalog.html, specialists.html
```

Figma-dakı bütün dəyişikliklər isə **artıq yadda saxlanılıb** (Figma real-time saxlayır).

---

## 9. ✅ HTML tələsi — absolyut düzüm footer-i örtürdü (2026-07-30-da HƏLL OLUNDU)

> **Status:** düzəldildi. Fayllar dizaynın 3 addımlıq axınına uyğun **adı dəyişdirilib**:
> `biznes-tamamlama-addim2.html` → **`biznes-tamamlama-addim1.html`** (Şirkət məlumatları, Figma `1105:21876`)
> `biznes-tamamlama-addim4.html` → **`biznes-tamamlama-addim3.html`** (İlk məhsul, Figma `1105:22589`)
> Heç bir səhifə onlara link vermirdi, ona görə ad dəyişikliyi təhlükəsiz idi.
>
> Nə edildi: `.page`-in sabit hündürlüyü və `.head/.content/.footer`-in `position:absolute`-u
> götürülüb normal axına keçirildi (`max-width:1440px;margin:0 auto`), ölü navbar/footer CSS-i
> (addim1-də ~97 sətir, addim3-də ~46) və təkrar `:root` bloku silindi. Sonra hər ikisi
> **tam Tailwind-ə çevrildi** (bax §12). `.kat-menu` `.form-card`-a nisbətən absolyutdur
> (`.form-card`-da `position:relative` var) — `.page`-dən asılı deyildi, ona görə sınmadı.
>
> Aşağıdakı təsvir tarixi kontekst üçün saxlanılır.

Köhnə vəziyyət (2026-07-26-da brauzerdə ölçülərək təsdiqlənmişdi):

- səhifədə `.head{position:absolute;top:140px}` və `.content{position:absolute;top:268px}` var
- `archi.js` isə `<div data-archi="footer">`-i **class-sız** `<footer>` ilə əvəz edir → səhifədəki `.footer{position:absolute;top:1095px}` qaydası **heç nəyə düşmür**
- nəticə: footer normal axında `top:142`-də qalır, absolyut `.content` (268→1118) onun üstünə boyanır → **footer tamamilə görünmür**, üstəlik `.page`-in sabit hündürlüyünə görə altda **~940px boş sahə** qalır
- ölçmə (1500px viewport): topbar 0–142, `.head` 140–268, `.content` 268–1118, `<footer>` 142–933, `elementFromPoint` footer-in mərkəzində `.form-card` qaytarır

**Navbar dəqiq 140px-dir** (`.nav-row1` 80 + `.nav-row2` 60) — yəni `top:140px` rəqəmi düz idi, problem yalnız footer-dədir.

➡️ **Yeni səhifələri normal axınla yaz.** `specialist.html`, `catalog.html`, `specialists.html` bunu düzgün edir (`.pp{max-width:1440px;margin:0 auto;padding:...}`). Bütün DS tokenləri artıq `archi.css`-in `:root`-undadır — səhifədə yenidən elan etməyə ehtiyac yoxdur.

---

## 10. Rol-əsaslı homepage (2026-07-26-da Figma-da quruldu)

Qeydiyyat səbəbinə görə fərqli homepage. **Section `1175:4451` — "rol-based homepage 2"**.

| Frame | Node ID | body | Hündürlük |
|---|---|---|---|
| homepage — Usta / Mütəxəssis (daxil olub) | `1175:4452` | `1175:4723` | 2653 |
| homepage — Biznes / Satıcı (daxil olub) | `1175:4724` | `1175:4995` | 2669 |

**UX prinsipi:** marketplace naviqasiyası (Navbar + FooteR) saxlanılır, lakin marketing hero-nun yerini rola uyğun **iş səthi** tutur, altda isə personallaşdırılmış marketplace blokları davam edir. Belə olanda istifadəçi nə kəşfetmə imkanını itirir, nə də öz işini görmək üçün ayrı panelə keçməli olur.

Bölmə sırası:

### ⚠️ Mütəxəssis tərəfinin tələb modeli (2026-07-26-da istifadəçi düzəltdi)

**Usta işlərə müraciət ETMİR.** Alıcı kimi qeydiyyatdan keçən insanlar ustanın **portfoliosuna baxıb özləri ustaya yazır**. Yəni bu inbound modeldir — ustanın işi tapılmaq və tez cavab verməkdir.

Buna görə ilk versiyadakı **«Sənə uyğun sorğular»** bloku (usta təklif göndərir) konseptual olaraq səhv idi və **silindi**. Yerinə mesajlar əsas səthə qaldırıldı. Bu modeli pozan heç nə əlavə etmə — usta üçün «iş elanına təklif göndər» tipli axın YOXDUR.

| # | Usta / Mütəxəssis | Biznes / Satıcı |
|---|---|---|
| 1 | welcome — avatar, salamlama, rol pill, profil hazırlığı 100%, «Profilimə bax» | welcome — mağaza avatarı, rol pill, mağaza hazırlığı 80%, «Mağazama bax» |
| 2 | KPI ×4 — profil baxışları / yeni müraciətlər / cavab vaxtı / reytinq | KPI ×4 — bugünkü satış / yeni sifarişlər / məhsul baxışları / anbarda azalan |
| 3 | **Müştəri mesajları** (əsas səth, tam enli) ×4 — oxunmamış sətir sarı fonlu, «Cavab yaz» düyməsi | **Sifarişlər** (əsas gəlir səthi) ×3 |
| 4 | **Portfolio performansı** (hansı iş müraciət gətirir) + «Profilini gücləndir» | Anbar xəbərdarlığı + «Mağaza hazırlığı» checklist |
| 5 | Ustalar üçün material endirimləri ×4 (cross-sell) — **dəyişməyib** | Top məhsullar ×4 |
| 6 | Son rəylər (reytinq xülasəsi + paylanma) + «Daha çox müraciət al» | Quraşdırma üçün usta tap (B2B cross-sell) + «Satışını artır» |

Mütəxəssis bloklarının node ID-ləri: `messages-block` `1202:4913` · `two-col` (portfolio perf + boost) `1203:4880` · `two-col-2` (rəylər + bilik) `1204:4881` · `materials-block` `1181:4621` (toxunulmayıb).

Rəqəm uzlaşması: KPI «Yeni müraciətlər 6» ↔ filtrlər «Cavabsız · 3 / Aktiv · 2 / Hamısı · 6» ↔ siyahıda 3 cavabsız + 1 cavablandı göstərilir (qalanı «Bütün mesajlar»-dadır).

**Yaradılan lokal komponentlər** (section-un sağ sütununda, x ≈ 3760):

| Komponent | Node ID | Override olunan text node adları |
|---|---|---|
| `dash/chip` | `1177:4571` | `t` |
| `dash/btn-primary` | `1177:4574` | `t` |
| `dash/btn-ghost` | `1177:4577` | `t` |
| `dash/kpi` | `1177:4582` | `label`, `value`, `delta` |
| `dash/row` | `1178:4578` | `title`, `meta` + `actions`-in 2 nested instance-i |
| `dash/row-lite` | `1178:4584` | `title`, `meta`, `value` |
| `dash/product` | `1178:4593` | `img`, `name`, `price`, `old` |

**Vacib:** bu frame-lər **auto-layout**-dur (`layoutMode='VERTICAL'`, Navbar/body/FooteR `FILL`) — köhnə onboarding frame-lərindən fərqli olaraq **§4-dəki reflow skriptinə ehtiyac yoxdur**, hündürlük özü hesablanır.

`dash/row`-un nested instance mətnini dəyişmək üçün ad toqquşmasına diqqət (chip və btn-in ikisində də text `t` adlanır):

```js
const act = inst.findOne(x => x.name === 'actions');
act.children[0].findOne(x => x.type === 'TEXT').characters = 'Yeni';        // chip
act.children[1].findOne(x => x.type === 'TEXT').characters = 'Təklif göndər'; // btn
```

---

## 11. Mütəxəssis profilinin redaktəsi (2026-07-26-da Figma-da quruldu)

`business-profile-edit`-in mütəxəssis analoqu. **Section `1191:4763` — "mutexessis profile 6"** (x = -20924, y = 66200).

Donor: **`1105:22829`** (`business-profile-edit · Şirkət məlumatları`) — klonlanaraq istifadə edildi, ona görə navbar, footer və bütün chrome birebir eynidir.

| # | Tab | Node ID | Hündürlük |
|---|---|---|---|
| 1 | Şəxsi məlumatlar | `1191:4764` | 2216 |
| 2 | Əlaqə | `1193:4788` | 1883 |
| 3 | Xidmətlər & qiymətlər | `1194:4813` | 1951 |
| 4 | Portfolio | `1195:4838` | 2183 |
| 5 | Bildirişlər | `1196:4863` | 2058 |
| 6 | Təhlükəsizlik | `1197:4888` | 2422 |

**Frame strukturu** (donor ilə eyni):

```
FRAME 1440×H   layoutMode = VERTICAL, primaryAxisSizingMode = AUTO
  navbar    FRAME 1440×160        ← lokal navbar frame (Navbar komponenti DEYİL)
  edit-page FRAME  bg #f5f7f9 · VERTICAL gap 20 · pad 32/28/56/28
    header  HORIZONTAL SPACE_BETWEEN align END
            sol: breadcrumb 13px + başlıq Bold 34
            sağ: status chip #e9f6ed (nöqtə + #229653 Semi Bold 13) · ghost btn h44
    edit-body HORIZONTAL gap 24
      settings-nav 264  white·border·r4·pad 12/14·VERTICAL gap 4
      edit-main    flex 1 · VERTICAL gap 20 → [kartlar..., save-bar]
  App       FRAME 1440×770        ← footer
```

**settings-nav elementi:** pad 12/11 · r4 · SPACE_BETWEEN · aktiv = fill `selection-bg` + text **Bold** 14 `text/secondary`; passiv = fill yox + **Medium**. Sağdaki say Semi Bold 12 `text/faint`. Altda `Profil tamlığı` boxu (fill `gray/soft-2`, bar h6 r3, 92%).

**save-bar:** fill `#111` · r4 · pad 24/16 · sol nöqtə + "Yadda saxlanmamış dəyişikliklər var" (Medium 13 white@.85) · sağ `Ləğv et` (border white@.35, h44) + `Yadda saxla` (yellow, Bold 14, h44).

**Aktiv tab-ı dəyişmək** (klonladıqdan sonra mütləq çağır — yoxsa iki tab aktiv görünür):

```js
function setActive(nav, idx){
  let i = 0;
  for (const it of nav.children){
    if (it.name !== 'nav-item') continue;
    const on = (i === idx);
    it.fills = on ? [P('selbg','#fffde0')] : [];
    it.children[0].fontName = { family:'Inter', style: on ? 'Bold' : 'Medium' };
    i++;
  }
}
```

**Qeydlər:**
- `media-card` (cover + avatar) **yalnız tab 1-dədir** — qalan tab-larda silinib. Cover şəklinin `imageHash`-i donor klonundan gəlir (use_figma xarici URL-dən şəkil çəkə bilmir).
- Tab 1-də «✓ Təsdiqlənmiş» nişanının admin tərəfindən verildiyini izah edən **note bloku** var (§6 qərarı ilə uyğun).
- Donor `1105:22829` frame-inin hündürlüyü **1697**-dir, məzmunu isə 2126 — yəni **B2B redaktə frame-lərində footer frame sərhədindən kənara çıxır** (`primaryAxisSizingMode = FIXED`). Yeni mütəxəssis frame-lərində bu `AUTO` edilib. B2B tərəfdə düzəltmək istəsən: `f.primaryAxisSizingMode = 'AUTO'`.

---

## 12. Tailwind CSS-ə köçürmə (2026-07-30-da başladı)

İstifadəçi qərarı: **CDN ilə, build step YOXDUR**. Sıra: əvvəl qüsur düzəlişləri, sonra çevirmə.

### Necə qurulub

Hər çevrilmiş səhifənin `<head>`-inə bu iki sətir bu SIRA ilə əlavə olunur:

```html
<script src="archi-tw.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
```

`archi-tw.js` — paylaşılan tema (`@theme` tokenləri). Yeni fayldır.

### Üç qərar və onların səbəbi (bunları pozma)

1. **Tema JS ilə inject olunur, ayrı `.css` faylı DEYİL.** Browser build-in mənbə kodu
   yoxlanıldı: yalnız `document.querySelectorAll('style[type="text/tailwindcss"]')` oxuyur —
   `<link>` görmür və xarici faylı `@import` edə bilmir («The browser build does not support
   @import for ...»). Yalnız `tailwindcss`, `tailwindcss/preflight`, `tailwindcss/theme`,
   `tailwindcss/utilities` daxili id-ləri həll olunur.

2. **Utilities LAYER-SİZ import olunur.** CSS cascade qaydası: layer-siz bəyanlar layer-li
   bəyanları üstələyir. `archi.css` layer-siz olduğu üçün `@layer utilities` variantında
   onun `body{background:#fff}` qaydası `bg-gray-soft2`-ni sındırırdı. Layer-siz olanda
   müqayisə specificity ilə gedir (`.bg-gray-soft2` 0,1,0 > `body` 0,0,1) və utility qazanır.

3. **Preflight qəsdən YÜKLƏNMİR.** `archi.css`-in öz reset-i var. Preflight `h1`–`h6`-nı
   `font-size:inherit`-ə salıb hələ çevrilməmiş səhifələrin başlıqlarını sındıracaqdı.
   Qeyd: `@property --tw-border-style{initial-value:solid}` preflight olmadan da çıxışa
   düşür — yoxlanıldı, `border-[1.5px]` işləyir.

### Token uyğunluğu

| Köhnə CSS dəyişəni | Tailwind |
|---|---|
| `--yellow` / `--yellow-line` / `--sel-bg` | `bg-yellow` / `bg-yellow-line` / `bg-sel-bg` |
| `--black` (#111, #000 DEYİL) | `text-ink` / `bg-ink` |
| `--gray-soft` / `--gray-soft2` | `bg-gray-soft` / `bg-gray-soft2` |
| `--radius` 4 / `--radius-md` 8 / `--radius-pill` | `rounded-ds` / `rounded-ds-md` / `rounded-pill` |
| `--text-primary/secondary/muted/faint` | `text-black/90` `/70` `/50` `/40` |
| `--border` / `--border-strong` / `--border-hover` | `border-black/10` `/14` `/35` |

Şəffaflıqlı tokenlər ayrıca token DEYİL — opacity modifikatoru ilə yazılır.
Boşluq şkalası (4/8/12/16/20/24/28/32/40/48) Tailwind-in standartı ilə üst-üstə düşür
(`--spacing`=4px → `p-1`…`p-12`), ayrıca spacing tokeni lazım deyil.
`line-height:normal` üçün `leading-normal` YOX, **`leading-[normal]`** işlət (`leading-normal`=1.5).

### Çevirmə necə yoxlanılır (brauzer olmadan)

Scratchpad-də Tailwind CLI quraşdırılıb (**layihəyə build step əlavə etmir**), `verify-tw.js`
`archi-tw.js`-dən temanı çıxarır, verilən HTML-lərə qarşı kompilyasiya edir və HTML-dəki hər
class-ın çıxışda qarşılığı olduğunu yoxlayır — yazı səhvi olan utility dərhal görünür:

```
node verify-tw.js <html> [<html> ...]     # → "HEÇ YERDƏ TAPILMAYAN: (yoxdur) ✅"
node check.js "size-7" "rounded-ds" ...   # → kompilyasiya olunmuş dəyəri göstərir
```

### Vəziyyət (2026-07-30 sessiyasının sonu)

**Tam çevrilmiş 5 səhifə** (öz `<style>` bloku SIFIR sətir):
`biznes-tamamlama-addim1` · `biznes-tamamlama-addim3` · `login` · `register` · `cart`

**Paylaşılan kart komponentləri çevrildi** — `archi.css`-dən `archi-tw.js`-in
`@layer components` blokuna köçürüldü (`.grid4`, `.pcard`, `.scard`, `.post`,
`.blog-grid`, `.prod-cursor`, `.hascur`). `archi.css` 685 → 623 sətir.
Ona görə **`index`, `product`, `catalog`, `search`, `blog`-a da Tailwind sətirləri
qoşuldu** — yoxsa kart stilini itirərdilər. Bu 5 səhifənin öz `<style>` bloku hələ qalır.

⚠️ `archi.css`-də kart class-larına istinad edən qaydalar QALDI və qalmalıdır:
sətir ~442/457 (responsive wrap), ~489 (reveal animasiyası), ~500-503 (hover keçidi),
~597-599 (reduced-motion / touch). Onlar layer-siz olduğu üçün `@layer components`
bazasını düzgün üstələyir — hover/responsive override belə işləməlidir. Sonra
animasiya/responsive bölmələri çevriləndə bunlar da köçürüləcək.

| | |
|---|---|
| ✅ Tam çevrildi (köhnə `<style>` yox) | **9**: `addim1`, `addim3`, `login`, `register`, `cart`, `search`, `calculator`, `blog`, `sell` |
| 🟡 Tailwind qoşuldu, öz CSS-i qalır | **3**: `index` (10 sətir), `catalog` (134), `product` (270) |
| ⏳ Toxunulmadı | **11**: biznes kabineti ×7, `biznes-qeydiyyat` (113), `calculator-detailed` (112), `specialist` (138), `specialists` (152) |

> ⚠️ **biznes-profil qrupu qrup kimi çevrilə bilməz.** Yeddi səhifə oxşar görünür, amma
> CSS blokları ayrı-ayrı qurulub — `sirket` ilə `elaqe` arasında 295 sətir fərq var.
> Hər birində ölü navbar CSS və sabit `width:1440px`/`min-width:1440px` var (yəni
> `addim1`/`addim3`-dəki eyni problem sinfi). Tək-tək çevirmək lazımdır.
| ⏳ Sonda | `archi.js`-dəki navbar/footer markup-ı + `archi.css`-in silinməsi |

### `archi-tw.js`-dəki paylaşılan komponentlər (archi.css-dən köçürülüb)

| Qrup | Class-lar |
|---|---|
| şəbəkə | `.grid4`, `.blog-grid` |
| bölmə başlığı | `.section`, `.sec-head`, `.sec-tag`, `.sec-title`, `.sec-more` |
| məhsul kartı | `.pcard` + alt elementləri, `.prod-cursor`, `.hascur` |
| mütəxəssis kartı | `.scard` + alt elementləri |
| bloq kartı | `.post` + alt elementləri |

### Səhifəyə xas komponent naxışı

Bir səhifədə çox təkrarlanan element üçün (məs. `calculator.html`-də `.qc-chip` 19 dəfə,
`blog.html`-də `.fchip` 8 dəfə) utility sətrini təkrarlamaq yerinə səhifənin `<head>`-inə
**öz `<style type="text/tailwindcss">` bloku** qoyulur. Browser build BÜTÜN belə
elementləri birləşdirdiyi üçün bu da tam Tailwind-dir. **Blok CDN skriptindən ƏVVƏL
gəlməlidir**, yoxsa compiler ilk qaçışda onu görmür.

### JS naxışı — `.on`/`.active`/`.sel` class-ları → `data-*` atributları

Vəziyyət class-ları (`.sel`, `.on`, `.active`, `.show`) Tailwind-də utility ilə idarə
olunmur, ona görə `data-on` / `data-sel` atributuna keçirilib və markup-da
`data-[on=true]:` / `group-data-[sel=true]:` variantları işlədilir. Dəyişdirilmiş
JS-lər: `register` (rol seçimi), `search` (tab), `calculator` (chip + tier),
`blog` (filtr), `cart` (`.ok-msg.show` → `hidden`).

Bir tələ: `flex` utility-si UA-nın `[hidden]{display:none}` qaydasını üstələyir, ona görə
JS `el.hidden` ilə gizlətdiyi flex elementlərə **`[&[hidden]]:hidden`** əlavə olunmalıdır
(`register.html`-dəki şərtli sahələr). Specificity 0,2,0 verir və `flex`-i üstələyir.

**Qeyd — `verify-tw.js` yanlış siqnalları:** (a) `class="${...}"` kimi JS interpolyasiyaları
class adı deyil, ona görə atlanır (əlavə edildi); (b) səhifənin ÖZ `<style>` blokundaki
class-lar (`pd-*`, `fs-*`, `sr-*`, `blog-*`) «tapılmadı» kimi görünür — normaldır, onlar
hələ çevrilməyib. Skript yalnız `archi.css` ilə Tailwind-i ayırd edir.

**Navbar/footer niyə SONDA:** onlar bütün səhifələrdə paylaşılır. Markup-ı Tailwind-ə
çevirən an 23 səhifənin hamısı eyni vaxtda Tailwind-dən asılı olur. Ona görə əvvəl bütün
səhifələr tək-tək çevrilir (bu müddətdə `archi.css` navbar/footer üçün qalır), navbar/footer
ən sonda çevrilir və `archi.css` yalnız o zaman silinir. Belə olanda sayt hər addımda işlək qalır.
