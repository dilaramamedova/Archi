/* =============================================================================
   ARCHİ — Paylaşılan UI: navbar + footer + mega-menyu + dil/i18n + davranışlar.
   Hər səhifə bunu yükləyir; navbar/footer <div data-archi="nav|footer"></div>
   yer-tutucularına inject olunur. window.ARCHI.setLang/refresh ilə işləyir.
   ============================================================================= */
(function () {
  "use strict";

  /* ---------- NAVBAR markup (mega-menyu daxil) ---------- */
  const NAV = `
<header class="topbar">
  <div class="nav-row1">
    <a href="index.html" aria-label="ARCHİ — ana səhifə"><img class="logo" src="assets/logo.png" alt="ARCHI"></a>
    <div class="search">
      <img src="assets/ic-search.svg" alt="">
      <input type="text" id="navSearch" aria-label="Axtarış" placeholder="Məhsul, marka və ya mütəxəssis axtarın" autocomplete="off">
      <div class="search-dropdown" id="searchDrop"></div>
    </div>
    <div class="nav-menu">
      <div class="nav-icons">
        <div class="lang" id="langBtn" role="button" tabindex="0" aria-label="Dil seçimi" aria-haspopup="true">
          <span id="langLabel">AZ</span> <img src="assets/ic-caret.svg" alt="">
          <ul class="lang-menu" id="langMenu">
            <li data-lang="az">AZ</li>
            <li data-lang="ru">RUS</li>
            <li data-lang="en">ENG</li>
          </ul>
        </div>
        <img src="assets/ic-heart.svg" alt="" role="button" tabindex="0" aria-label="Sevimlilər">
        <a href="cart.html" class="nav-cart" aria-label="Səbət"><img src="assets/ic-cart.svg" alt=""><span class="cart-badge" id="navCartCount"></span></a>
      </div>
      <div class="signin">
        <span class="divider"></span>
        <span class="txt" role="button" tabindex="0">Daxil ol</span>
        <button class="btn-post"><img src="assets/ic-plus.svg" alt=""><span>Məhsul yerləşdir</span></button>
      </div>
    </div>
  </div>
  <div class="nav-row2">
    <div class="inner">
      <div class="nav-left">
        <a class="nav-item catalog" data-mega="catalog" role="button" aria-label="Kataloq"><img src="assets/ic-grip.svg" alt="">Kataloq</a>
        <a class="nav-item" data-mega="spec" role="button">Mütəxəssislər <img class="mcaret" src="assets/ic-caret.svg" alt=""></a>
        <a class="nav-item" data-mega="blog" href="blog.html">Bloq <img class="mcaret" src="assets/ic-caret.svg" alt=""></a>
        <a class="nav-item" href="#">Haqqımızda</a>
        <a class="nav-item" href="#">B2B</a>
      </div>
      <a class="nav-calc" id="openCalc"><img src="assets/ic-calculator.svg" alt="">Təmir kalkulyatoru</a>
    </div>
  </div>

  <div class="mega-panel" data-panel="catalog">
    <div class="mega-inner">
      <div class="mega-cats">
        <a class="mcat"><div class="top"><img src="assets/cat-ic-tikinti.svg" alt=""><p>Tikinti materialları</p></div><div class="desc">Sement, armatur, kərpic və digər əsas tikinti məhsulları</div></a>
        <a class="mcat"><div class="top"><img src="assets/cat-ic-santexnika.svg" alt=""><p>Santexnika</p></div><div class="desc">Hamam, mətbəx və mühəndislik sistemləri üçün məhsullar</div></a>
        <a class="mcat"><div class="top"><img src="assets/cat-ic-elektrik.svg" alt=""><p>Elektrik</p></div><div class="desc">Kabel, açar, rozetka və elektrik avadanlıqları</div></a>
        <a class="mcat"><div class="top"><img src="assets/cat-ic-dosheme.svg" alt=""><p>Döşəmə və üzlük</p></div><div class="desc">Laminat, parket, kafel və keramoqranit məhsulları</div></a>
        <a class="mcat"><div class="top"><img src="assets/cat-ic-isiq.svg" alt=""><p>İşıqlandırma</p></div><div class="desc">Ev və kommersiya məkanları üçün işıqlandırma həlləri</div></a>
        <a class="mcat"><div class="top"><img src="assets/cat-ic-dekor.svg" alt=""><p>Dekor və mebel</p></div><div class="desc">İnteryerinizi tamamlayan dekor və mebel həlləri</div></a>
      </div>
    </div>
  </div>

  <div class="mega-panel" data-panel="spec">
    <div class="mega-inner">
      <div class="mega-spec">
        <div class="grid">
          <a class="mcat"><div class="top"><img src="assets/spec-ic-memar.svg" alt=""><p>Memarlar</p></div><div class="desc">Müasir və funksional layihələrin hazırlanması, estetik və texniki dizayn</div></a>
          <a class="mcat"><div class="top"><img src="assets/spec-ic-interyer.svg" alt=""><p>İnteryer dizaynerlər</p></div><div class="desc">Məkanın estetik və funksional təşkili</div></a>
          <a class="mcat"><div class="top"><img src="assets/spec-ic-usta.svg" alt=""><p>Ustalar</p></div><div class="desc">Kafelçi, elektrik, santexnik və digər peşəkarlar</div></a>
          <a class="mcat"><div class="top"><img src="assets/spec-ic-sirket.svg" alt=""><p>Tikinti şirkətləri</p></div><div class="desc">Tikinti prosesinin peşəkar idarə olunması</div></a>
        </div>
        <div class="promo">
          <img class="ph" src="assets/mega-consult.jpg" alt="">
          <div class="card">
            <p>Haradan başlayacağını bilmirsən? Mütəxəssislərdən pulsuz məsləhət al.</p>
            <a class="pill">Pulsuz konsultasiya</a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="mega-panel" data-panel="blog">
    <div class="mega-inner">
      <div class="mega-blog">
        <a class="mblog" href="blog.html"><img class="ph" src="assets/mega-blog1.jpg" alt=""><div class="info"><h4>Hansı kafeli seçək?</h4><div class="d">Haradan başlayacağını bilmirsən? Mütəxəssislərdən pulsuz məsləhət al.</div><span class="read">Ətraflı oxu <img src="assets/ic-arrow.svg" alt=""></span></div></a>
        <a class="mblog" href="blog.html"><img class="ph" src="assets/mega-blog2.jpg" alt=""><div class="info"><h4>Blog 2</h4><div class="d">Haradan başlayacağını bilmirsən? Mütəxəssislərdən pulsuz məsləhət al.</div><span class="read">Ətraflı oxu <img src="assets/ic-arrow.svg" alt=""></span></div></a>
        <a class="mblog" href="blog.html"><img class="ph" src="assets/mega-blog2.jpg" alt=""><div class="info"><h4>Blog 3</h4><div class="d">Haradan başlayacağını bilmirsən? Mütəxəssislərdən pulsuz məsləhət al.</div><span class="pill">Daha ətraflı</span></div></a>
      </div>
    </div>
  </div>
</header>`;

  /* ---------- FOOTER markup ---------- */
  const FOOTER = `
<footer>
  <div class="inner">
    <div class="foot-top">
      <a href="index.html" aria-label="ARCHİ"><img class="foot-logo" src="assets/logo-white.png" alt="ARCHI"></a>
      <a class="foot-products" href="index.html"><p>Məhsullara keç</p><img src="assets/ic-arrow-wt.svg" alt=""></a>
    </div>
    <div class="foot-line" style="margin-top:64px"></div>
    <div class="foot-cols">
      <div class="foot-col"><h5>Məhsullar</h5>
        <a>Kafel &amp; metlax</a><a>Boya &amp; emal</a><a>Santexnika</a><a>İzolyasiya &amp; istilik</a><a>Bütün kateqoriyalara keç</a>
      </div>
      <div class="foot-col"><h5>Mütəxəssislər</h5>
        <a>Usta tap</a><a>Top reytinqli ustalar</a><a>Bütün mütəxəssislərə bax</a>
      </div>
      <div class="foot-col"><h5>ARCHİ-yə qoşul</h5>
        <a>Satıcı ol</a><a>Usta ol</a><a>Tərəfdaşlıq proqramı</a><a>Bizneslə əməkdaşlıq</a>
      </div>
      <div class="foot-col"><h5>Şirkət &amp; dəstək</h5>
        <a>Haqqımızda</a><a>Pulsuz konsultasiya</a><a>Məqalələr</a><a>Yardım mərkəzi</a><a>Əlaqə</a>
      </div>
    </div>
    <div class="foot-line"></div>
    <div class="foot-news">
      <div class="l">
        <img src="assets/ic-mail.svg" alt="">
        <div>
          <h4>Təmir dünyasında yeniliklərdən xəbərdar olun</h4>
          <p>Endirimlərdən, yeni məhsullar, faydalı məqalələr və yeniliklər barədə ilk siz məlumat alın.</p>
        </div>
      </div>
      <form class="form" onsubmit="return false">
        <input class="ip" type="email" aria-label="E-poçt ünvanın" placeholder="E-poçt ünvanın">
        <button class="sub">Abunə ol</button>
      </form>
    </div>
    <div class="foot-line"></div>
    <div class="foot-bottom">
      <div class="foot-legal">
        <div class="links">
          <a>İstifadə şərtləri</a><span class="sep">|</span>
          <a>Məxfilik siyasəti</a><span class="sep">|</span>
          <a>Çatdırılma &amp; qaytarılma</a><span class="sep">|</span>
          <a>Cookie siyasəti</a><span class="sep">|</span>
          <a>Sayt xəritəsi</a>
        </div>
        <div class="copy">©2026 ARCHI - Bütün hüquqlar qorunur.</div>
      </div>
      <div class="foot-social">
        <a aria-label="Instagram"><img src="assets/ic-instagram.svg" alt="Instagram"></a>
      </div>
    </div>
  </div>
</footer>`;

  /* ---------- LOGIN MODAL (popup) markup ---------- */
  const LOGIN_MODAL = `
<div class="lm-overlay" id="lmOverlay" role="dialog" aria-modal="true" aria-labelledby="lmTitle">
  <div class="lm-dialog">
    <button class="lm-close" id="lmClose" type="button" aria-label="Bağla">&times;</button>
    <div class="lm-head">
      <div class="tag"><span class="line"></span><p>Xoş gəlmisən</p></div>
      <h2 id="lmTitle">Daxil ol</h2>
      <p class="sub">Hesabına gir — alıcı, satıcı və ya usta kabinetinə davam et.</p>
    </div>
    <div class="lm-ok" id="lmOk">Giriş uğurlu oldu! (demo — backend qoşulmayıb)</div>
    <form class="lm-form" id="lmForm" onsubmit="return false">
      <div class="lm-field"><label>E-poçt və ya telefon</label><input type="text" placeholder="email@example.com" required></div>
      <div class="lm-field"><label>Şifrə</label><input type="password" placeholder="Şifrə" required></div>
      <div class="lm-row">
        <label class="rem"><input type="checkbox"> Məni xatırla</label>
        <a href="#">Şifrəni unutmusan?</a>
      </div>
      <button class="lm-submit" type="submit">Daxil ol</button>
      <p class="lm-alt">Hesabın yoxdur? <a href="register.html">Qeydiyyatdan keç</a></p>
    </form>
  </div>
</div>`;

  /* ---------- i18n lüğəti (AZ → RU / EN) ---------- */
  const I18N = {
    ru: {
      "Məhsul, marka və ya mütəxəssis axtarın":"Поиск товара, бренда или мастера",
      "Daxil ol":"Войти","Məhsul yerləşdir":"Разместить товар","Kataloq":"Каталог",
      "Mütəxəssislər":"Специалисты","Bloq":"Блог","Haqqımızda":"О нас","B2B":"B2B","Təmir kalkulyatoru":"Калькулятор ремонта",
      "Xüsusi sertifikatlı ustalar":"Сертифицированные мастера","reytinq və rəylərlə":"с рейтингом и отзывами",
      "Pulsuz konsultasiya":"Бесплатная консультация","Mütəxəssislər tərəfindən":"от специалистов",
      "Tikinti və təmir marketi":"Маркет стройки и ремонта","Tikinti və təmir bir yerdə":"Стройка и ремонт в одном месте",
      "Materialdan etibarlı ustaya qədər hər şey — bir platformada.":"Всё — от материалов до надёжных мастеров — на одной платформе.",
      "Hardan başlayacağınızı bilmirsiz?":"Не знаете, с чего начать?","Pulsuz konsultasiya alın":"Получите бесплатную консультацию",
      "Ətraflı bax":"Подробнее","Usta & mütəxəssis":"Мастер и специалист","ARCHİ-də usta ol":"Станьте мастером на ARCHİ",
      "Profil yarat, işlərini göstər":"Создайте профиль, покажите работы","və yeni müştərilər qazan.":"и привлекайте новых клиентов.",
      "Qeydiyyatdan keç":"Зарегистрироваться",
      "Nə qədər material lazımdır?":"Сколько нужно материала?","Boya":"Краска","Divar kağızı":"Обои",
      "Otağın ölçüsü":"Размер комнаты","Uzunluq":"Длина","En":"Ширина","Hündürlük":"Высота","Qapı":"Дверь","Say":"Кол-во","Pəncərə":"Окно",
      "litr":"литр","boyanacaq divar":"площадь стен","1 qata kifayət":"хватит на 1 слой","edir":"","Tam kalkulyatoru aç →":"Открыть полный калькулятор →",
      "Ən çox baxılan":"Самые просматриваемые","Kateqoriyalar":"Категории","Kafel & metlax":"Плитка и метлах","860 məhsul":"860 товаров","340 məhsul":"340 товаров",
      "Laminant & parket":"Ламинат и паркет","Elektrik & işıqlandırma":"Электрика и освещение","Kərpic & daş":"Кирпич и камень","Sement & qarışıqlar":"Цемент и смеси",
      "Ən çox satılan":"Самые продаваемые","Seçilmiş məhsullar":"Избранные товары",
      "Yeni məhsul":"Новинка","Stokda var":"В наличии","Keramik kafel 60×60, mat":"Керамическая плитка 60×60, мат","Məhsula keç":"К товару",
      "Nə qədər material lazımdır? Dəqiq hesabla.":"Сколько нужно материала? Точный расчёт.",
      "Otağın ölçüsünü yaz — boya, kafel, laminant və dam örtüyü üçün miqdar və təxmini qiymət dərhal çıxsın.":"Введите размеры комнаты — количество и примерная цена для краски, плитки, ламината и кровли появятся сразу.",
      "Kalkulyatoru aç →":"Открыть калькулятор →","Boya · 3 otaq":"Краска · 3 комнаты","≈ 312 ₼ · 3 banka (10 litr)":"≈ 312 ₼ · 3 банки (10 л)",
      "Dam örtüyü":"Кровля","Kafel":"Плитка","Laminant":"Ламинат",
      "Ən çox tələb olunan ustalar":"Самые востребованные мастера","Seçilmiş ustalar":"Избранные мастера",
      "Top usta":"Топ мастер","Təsdiqlənmiş":"Проверен","Kafel & metlax ustası":"Мастер плитки и метлах",
      "Rəşad Məmmədov":"Рашад Мамедов","12 illik təcrübə":"12 лет опыта","320 layihə":"320 проектов",
      "Hardan başlayacağını bilmirsən?":"Не знаешь, с чего начать?",
      "Bir neçə sual cavabla, mütəxəssis sənə pulsuz zəng edib nədən başlayacağını izah etsin.":"Ответь на пару вопросов — специалист бесплатно позвонит и подскажет, с чего начать.",
      "Sualları cavabla":"Ответь на вопросы","Pulsuz zəng al":"Получи бесплатный звонок","İşə başla":"Начни работу",
      "Nə üzərində işləyirsən?":"Над чем работаешь?","Yeni ev tikintisi":"Строительство нового дома","Mənzil təmiri":"Ремонт квартиры",
      "Həyət evi / bağ evi":"Частный / дачный дом","Hələ qərar verməmişəm":"Ещё не решил","Davam et":"Продолжить",
      "Faydalı məqalələr":"Полезные статьи","Oxu →":"Читать →","Oxu":"Читать","Ətraflı oxu":"Читать далее","Daha ətraflı":"Подробнее",
      "Təmirə hardan başlamaq lazımdır?":"С чего начать ремонт?",
      "İlk addımlar, büdcə planı və ən çox edilən səhvlər — başlamazdan əvvəl bilməli olduqlar.":"Первые шаги, план бюджета и частые ошибки — что нужно знать перед началом.",
      "Düzgün boyanı necə seçmək olar?":"Как выбрать правильную краску?",
      "Düzgün laminant necə seçilir?":"Как выбрать ламинат?",
      "Sinif, qalınlıq, su davamlılığı — otağa görə hansını seçmək lazımdır.":"Класс, толщина, влагостойкость — какой выбрать для каждой комнаты.",
      "Fasad boyası: tam bələdçi":"Фасадная краска: полный гид",
      "Hava şəraitinə davamlılıq, örtmə hesabı və tətbiq qaydaları bir məqalədə.":"Устойчивость к погоде, расчёт покрытия и правила нанесения — в одной статье.",
      "Mat, yarımmat, parlaq — hansı otaq üçün hansı boya uyğundur və nə qədər lazımdır.":"Мат, полумат, глянец — какая краска для какой комнаты и сколько нужно.",
      "Kafel döşəməsində 7 səhv":"7 ошибок при укладке плитки",
      "Peşəkar ustaların qaçındığı tipik səhvlər və onların qarşısını necə almaq olar.":"Типичные ошибки, которых избегают профессионалы, и как их предотвратить.",
      "Təmir büdcəsini necə planlamalı?":"Как спланировать бюджет ремонта?",
      "Material, işçi qüvvəsi və gözlənilməz xərclər — büdcəni doğru bölüşdürməyin yolu.":"Материалы, рабочая сила и непредвиденные расходы — как правильно распределить бюджет.",
      "Məhsullara keç":"К товарам","Məhsullar":"Товары","Boya & emal":"Краска и эмаль","Santexnika":"Сантехника",
      "İzolyasiya & istilik":"Изоляция и тепло","Bütün kateqoriyalara keç":"Все категории",
      "Usta tap":"Найти мастера","Top reytinqli ustalar":"Мастера с топ-рейтингом","Bütün mütəxəssislərə bax":"Все специалисты",
      "ARCHİ-yə qoşul":"Присоединиться к ARCHİ","Satıcı ol":"Стать продавцом","Usta ol":"Стать мастером",
      "Tərəfdaşlıq proqramı":"Партнёрская программа","Bizneslə əməkdaşlıq":"Сотрудничество с бизнесом",
      "Şirkət & dəstək":"Компания и поддержка","Məqalələr":"Статьи","Yardım mərkəzi":"Центр помощи","Əlaqə":"Контакты",
      "Təmir dünyasında yeniliklərdən xəbərdar olun":"Будьте в курсе новостей мира ремонта",
      "Endirimlərdən, yeni məhsullar, faydalı məqalələr və yeniliklər barədə ilk siz məlumat alın.":"Узнавайте первыми о скидках, новинках, полезных статьях и новостях.",
      "E-poçt ünvanın":"Ваш e-mail","Abunə ol":"Подписаться",
      "İstifadə şərtləri":"Условия использования","Məxfilik siyasəti":"Политика конфиденциальности",
      "Çatdırılma & qaytarılma":"Доставка и возврат","Cookie siyasəti":"Политика cookie","Sayt xəritəsi":"Карта сайта",
      "©2026 ARCHI - Bütün hüquqlar qorunur.":"©2026 ARCHI — Все права защищены.",
      /* mega */
      "Tikinti materialları":"Строительные материалы","Sement, armatur, kərpic və digər əsas tikinti məhsulları":"Цемент, арматура, кирпич и другие базовые стройматериалы",
      "Hamam, mətbəx və mühəndislik sistemləri üçün məhsullar":"Товары для ванной, кухни и инженерных систем",
      "Elektrik":"Электрика","Kabel, açar, rozetka və elektrik avadanlıqları":"Кабель, выключатели, розетки и электрооборудование",
      "Döşəmə və üzlük":"Полы и облицовка","Laminat, parket, kafel və keramoqranit məhsulları":"Ламинат, паркет, плитка и керамогранит",
      "İşıqlandırma":"Освещение","Ev və kommersiya məkanları üçün işıqlandırma həlləri":"Решения освещения для дома и коммерции",
      "Dekor və mebel":"Декор и мебель","İnteryerinizi tamamlayan dekor və mebel həlləri":"Декор и мебель, дополняющие ваш интерьер",
      "Memarlar":"Архитекторы","Müasir və funksional layihələrin hazırlanması, estetik və texniki dizayn":"Современные и функциональные проекты, эстетика и техника",
      "İnteryer dizaynerlər":"Дизайнеры интерьера","Məkanın estetik və funksional təşkili":"Эстетичная и функциональная организация пространства",
      "Ustalar":"Мастера","Kafelçi, elektrik, santexnik və digər peşəkarlar":"Плиточник, электрик, сантехник и другие специалисты",
      "Tikinti şirkətləri":"Строительные компании","Tikinti prosesinin peşəkar idarə olunması":"Профессиональное управление строительством",
      "Haradan başlayacağını bilmirsən? Mütəxəssislərdən pulsuz məsləhət al.":"Не знаешь, с чего начать? Получи бесплатную консультацию специалистов.",
      "Hansı kafeli seçək?":"Какую плитку выбрать?",
      /* blog page */
      "Ana səhifə":"Главная","Təmir bələdçisi":"Гид по ремонту",
      "Materialları seçməkdən büdcə planlamağa, etibarlı usta tapmaqdan ən çox edilən səhvlərə qədər — təmirinin hər mərhələsi üçün praktik məqalələr.":"От выбора материалов до планирования бюджета, от поиска надёжного мастера до частых ошибок — практичные статьи для каждого этапа ремонта.",
      "Bütün məqalələr":"Все статьи","Təmir":"Ремонт","Materiallar":"Материалы","Büdcə":"Бюджет","Dizayn":"Дизайн","San texnika":"Сантехника","İzolyasiya":"Изоляция",
      "Bələdçi":"Гид","Təmirə hardan başlamaq lazımdır? Tam yol xəritəsi":"С чего начать ремонт? Полная дорожная карта",
      "İlk addımlar, büdcə planı, ardıcıllıq və yeni başlayanların ən çox etdiyi səhvlər. Divarlardan tutmuş son təmizliyə qədər — başlamazdan əvvəl bilməli olduğun hər şeyi bir məqalədə topladıq.":"Первые шаги, план бюджета, последовательность и частые ошибки новичков. От стен до финальной уборки — всё, что нужно знать перед стартом, в одной статье.",
      "Aysel Həsənova":"Айсель Гасанова","6 dəq oxu":"6 мин чтения","4 dəq oxu":"4 мин чтения","5 dəq oxu":"5 мин чтения","12 İyun 2026":"12 июня 2026",
      /* login modal */
      "Xoş gəlmisən":"Добро пожаловать","Hesabına gir — alıcı, satıcı və ya usta kabinetinə davam et.":"Войдите в аккаунт — покупателя, продавца или мастера.",
      "Giriş uğurlu oldu! (demo — backend qoşulmayıb)":"Вход выполнен! (демо — бэкенд не подключён)",
      "E-poçt və ya telefon":"E-mail или телефон","Şifrə":"Пароль","Məni xatırla":"Запомнить меня","Şifrəni unutmusan?":"Забыли пароль?","Hesabın yoxdur?":"Нет аккаунта?",
      /* register */
      "Hesab növünü seç — alıcı, satıcı, yoxsa usta/mütəxəssis kimi davam et.":"Выберите тип аккаунта — покупатель, продавец или мастер.",
      "Qeydiyyat uğurla tamamlandı! (demo — backend qoşulmayıb)":"Регистрация завершена! (демо — бэкенд не подключён)",
      "Hesab növü":"Тип аккаунта","Alıcı":"Покупатель","Material al, usta tap, sifariş ver.":"Покупайте материалы, находите мастеров, заказывайте.",
      "Satıcı":"Продавец","Məhsullarını yerləşdir və sat.":"Размещайте товары и продавайте.","Usta / Mütəxəssis":"Мастер / специалист","Xidmət göstər, müştəri qazan.":"Оказывайте услуги, находите клиентов.",
      "Ad":"Имя","Soyad":"Фамилия","Adınız":"Ваше имя","Soyadınız":"Ваша фамилия","Şirkət / marka adı":"Компания / бренд","Məsələn: ARCHI Build MMC":"Например: ARCHI Build MMC",
      "İxtisas":"Специализация","Seçin":"Выберите","Kafelçi":"Плиточник","Santexnik":"Сантехник","Boyaçı":"Маляр","Gips-kartonçu":"Гипсокартонщик","Memar":"Архитектор","İnteryer dizayner":"Дизайнер интерьера","Digər":"Другое",
      "Şəhər":"Город","Bakı":"Баку","Sumqayıt":"Сумгаит","Gəncə":"Гянджа","Mingəçevir":"Мингячевир","Şirvan":"Ширван",
      "E-poçt":"E-mail","Telefon":"Телефон","Ən azı 6 simvol":"Минимум 6 символов","Şifrəni təkrarla":"Повторите пароль","və":"и","ilə razıyam":"— принимаю","Artıq hesabın var?":"Уже есть аккаунт?",
      /* product page */
      "Keramik kafel 60×60, mat — açıq boz":"Керамическая плитка 60×60, мат — светло-серая",
      "1,876 rəy":"1 876 отзывов","3,200+ satıldı":"3 200+ продано","qiymət 1 m² üçün · ƏDV daxil":"цена за 1 м² · с НДС",
      "Mat səth, sürüşməyə davamlı (R10)":"Матовая, противоскользящая поверхность (R10)","Şaxtaya və nəmə davamlı — daxili/xarici üçün":"Стойкая к морозу и влаге — для внутри/снаружи",
      "60×60 sm · qutuda 1.44 m² (4 ədəd)":"60×60 см · в коробке 1,44 м² (4 шт.)","Stokda var — 480 m² hazırdır":"В наличии — готово 480 м²",
      "Səbətə at":"В корзину","Səbətə əlavə olundu":"Добавлено в корзину","Pulsuz çatdırılma":"Бесплатная доставка","100 ₼-dən yuxarı sifarişlərə":"для заказов от 100 ₼","14 gün qaytarma":"Возврат 14 дней","Açılmamış qutular üçün":"для невскрытых коробок",
      "Təsdiqlənmiş satıcı":"Проверенный продавец","4.8 ★ mağaza reytinqi · 1,240 məhsul · ~2 saat ərzində cavab":"Рейтинг магазина 4.8 ★ · 1 240 товаров · ответ за ~2 часа","Mağazaya keç":"В магазин","İzlə":"Следить","Rəsmi mağaza":"Официальный магазин","Mağaza reytinqi":"Рейтинг магазина","Məhsul":"Товаров","Cavab vaxtı":"Время ответа","~2 saat":"~2 часа",
      "Məhsul haqqında":"О товаре","Təsvir & xüsusiyyətlər":"Описание и характеристики","Təsvir":"Описание","Xüsusiyyətlər":"Характеристики",
      "Keramik kafel 60×60 sm mat səthi ilə həm divar, həm də döşəmə üçün ideal seçimdir. Açıq boz çaları müasir minimalist interyerlərə, hamam, mətbəx və qonaq otaqlarına asanlıqla uyğunlaşır. Mat finiş işıq əksini azaldır və cızıqları gizlədir.":"Керамическая плитка 60×60 см с матовой поверхностью — идеальный выбор и для стен, и для пола. Светло-серый оттенок легко вписывается в современные минималистичные интерьеры: ванную, кухню и гостиную. Матовая отделка снижает блики и скрывает царапины.",
      "Yüksək sıxlıqlı keramika sayəsində nəmə, şaxtaya və kimyəvi təmizləyicilərə davamlıdır — buna görə daxili məkanlarla yanaşı balkon və terraslarda da istifadə oluna bilər. R10 sürüşməyə qarşı sinifi onu nəmli zonalar üçün təhlükəsiz edir.":"Благодаря высокоплотной керамике она устойчива к влаге, морозу и химическим средствам — поэтому подходит не только для помещений, но и для балконов и террас. Класс противоскольжения R10 делает её безопасной для влажных зон.",
      "Harada istifadə olunur?":"Где используется?",
      "Hamam və sanitar qovşaqları, mətbəx önlüyü və döşəməsi, dəhliz, qonaq otağı, ofis və kommersiya sahələri. Yerdən isitmə sistemləri ilə uyğundur.":"Ванные и санузлы, кухонный фартук и пол, коридор, гостиная, офисные и коммерческие площади. Совместима с системами тёплого пола.",
      "Ölçü":"Размер","60 × 60 sm":"60 × 60 см","Qalınlıq":"Толщина","Səth":"Поверхность","Mat":"Матовая","Material":"Материал","Keramoqranit":"Керамогранит","Rəng":"Цвет","Açıq boz":"Светло-серый","Qutudakı miqdar":"Кол-во в коробке","4 ədəd / 1.44 m²":"4 шт. / 1,44 м²","Sürüşmə sinfi":"Класс скольжения","İstifadə yeri":"Место применения","Daxili / xarici, divar & döşəmə":"Внутри / снаружи, стена и пол","Zəmanət":"Гарантия","İstehsalçı zəmanəti":"Гарантия производителя","Ölkə":"Страна","İspaniya":"Испания",
      "Reytinq":"Рейтинг","Müştəri qiymətləndirməsi":"Оценка покупателей","1,876 rəy əsasında":"на основе 1 876 отзывов",
      "Rəylər":"Отзывы","Müştərilər nə deyir?":"Что говорят покупатели?","Təsdiqlənmiş alış":"Проверенная покупка","Rəy yaz":"Написать отзыв","👍 Faydalı oldu":"👍 Полезно","Şikayət et":"Пожаловаться",
      "3 İyun 2026":"3 июня 2026","28 May 2026":"28 мая 2026","15 May 2026":"15 мая 2026",
      "Aysel H.":"Айсель Г.","Rəşad M.":"Рашад М.","Nigar Q.":"Нигяр Г.","Elvin S.":"Эльвин С.",
      "Keyfiyyət gözlədiyimdən yüksək çıxdı. Rəngi şəkildəki kimidir, hamamın döşəməsinə düzdürdük — mat səth heç sürüşmür. Tövsiyə edirəm.":"Качество выше, чем я ожидала. Цвет как на фото, уложили на пол в ванной — матовая поверхность совсем не скользит. Рекомендую.",
      "Usta kimi onlarla layihədə istifadə etmişəm. Kəsimi təmiz gedir, künclərdə çatlamır. Qiymət-keyfiyyət balansı əladır.":"Как мастер использовал на десятках проектов. Режется чисто, по углам не трескается. Отличное соотношение цены и качества.",
      "Məhsul yaxşıdır, amma bir qutuda 2 ədəd kiçik zədə vardı. Satıcı dərhal əvəzini göndərdi, ona görə təşəkkür.":"Товар хороший, но в одной коробке было 2 слегка повреждённых плитки. Продавец сразу прислал замену, спасибо.",
      "Çatdırılma sürətli oldu, qablaşdırma möhkəm idi. Mətbəx önlüyünə vurduq, çox müasir göründü.":"Доставка быстрая, упаковка крепкая. Сделали кухонный фартук — выглядит очень современно.",
      "Sənə uyğun ola bilər":"Может вам подойти","Oxşar məhsullar":"Похожие товары","Hamısına bax":"Смотреть все","Döşəmə":"Полы","Üzlük":"Облицовка",
      "Keramik kafel 60×60, parlaq":"Керамическая плитка 60×60, глянец","Laminant parket 8 mm, palıd":"Ламинат-паркет 8 мм, дуб","Mozaika kafel, ağ mat":"Мозаичная плитка, белый мат","Dekorativ kərpic üzlük, terrakot":"Декоративный кирпич, терракот",
      "(1,204 rəy)":"(1 204 отзыва)","(890 rəy)":"(890 отзывов)","(512 rəy)":"(512 отзывов)","(744 rəy)":"(744 отзыва)",
      /* sell (məhsul yerləşdir) */
      "Satıcı paneli":"Панель продавца",
      "Məhsulunu bir neçə dəqiqəyə yerləşdir — qeydiyyat tələb olunmur. Elanını dərc et, hesabı sonra yaradarsan.":"Разместите товар за пару минут — регистрация не нужна. Опубликуйте объявление, аккаунт создадите потом.",
      "Zəhmət olmasa məhsulun adını, kateqoriyasını və qiymətini doldur.":"Пожалуйста, заполните название, категорию и цену товара.",
      "Şəkil əlavə et":"Добавить фото","JPG və ya PNG · sürüklə və ya kliklə":"JPG или PNG · перетащите или нажмите",
      "Məhsulun adı":"Название товара","Məsələn: Keramik kafel 60×60, mat":"Например: керамическая плитка 60×60, мат",
      "Kateqoriya":"Категория","Vəziyyət":"Состояние","Yeni":"Новый","İşlənmiş":"Б/у","Qiymət":"Цена","Köhnə qiymət (endirim üçün)":"Старая цена (для скидки)",
      "Ölçü, material, rəng, istifadə yeri — alıcıya kömək edəcək hər şeyi yaz.":"Размер, материал, цвет, место применения — напишите всё, что поможет покупателю.",
      "Qeydiyyat tələb olunmur — məhsulu indi yerləşdir, hesabını sonra yarat. Elanın itməyəcək.":"Регистрация не нужна — разместите товар сейчас, аккаунт создадите потом. Объявление не пропадёт.",
      "Məhsulu yerləşdir":"Разместить товар",
      "Təbrik edirik —":"Поздравляем —","artıq saytda görünür və alıcılar onu tapa bilər.":"уже доступен на сайте, и покупатели могут его найти.",
      "Məhsulun yerləşdirildi!":"Ваш товар размещён!","Elanını qorumaq istəyirsən?":"Хотите сохранить объявление?",
      "Pulsuz hesab yarat — elanını idarə et, sifarişləri gör və satıcı reytinqi qazan. Cəmi bir dəqiqə çəkir, məhsulun isə artıq dərc olunub.":"Создайте бесплатный аккаунт — управляйте объявлением, смотрите заказы и зарабатывайте рейтинг продавца. Всего минута, а товар уже опубликован.",
      "Saytda bax":"Смотреть на сайте","Daha bir məhsul əlavə et":"Добавить ещё товар","İndi yox, saytda bax":"Не сейчас, смотреть на сайте","Sənin elanın":"Ваше объявление"
    },
    en: {
      "Məhsul, marka və ya mütəxəssis axtarın":"Search products, brands or specialists",
      "Daxil ol":"Sign in","Məhsul yerləşdir":"Post a product","Kataloq":"Catalog",
      "Mütəxəssislər":"Specialists","Bloq":"Blog","Haqqımızda":"About us","B2B":"B2B","Təmir kalkulyatoru":"Repair calculator",
      "Xüsusi sertifikatlı ustalar":"Certified specialists","reytinq və rəylərlə":"with ratings & reviews",
      "Pulsuz konsultasiya":"Free consultation","Mütəxəssislər tərəfindən":"by specialists",
      "Tikinti və təmir marketi":"Construction & repair market","Tikinti və təmir bir yerdə":"Construction & repair in one place",
      "Materialdan etibarlı ustaya qədər hər şey — bir platformada.":"Everything from materials to trusted specialists — on one platform.",
      "Hardan başlayacağınızı bilmirsiz?":"Not sure where to start?","Pulsuz konsultasiya alın":"Get a free consultation",
      "Ətraflı bax":"View all","Usta & mütəxəssis":"Master & specialist","ARCHİ-də usta ol":"Become a master on ARCHİ",
      "Profil yarat, işlərini göstər":"Create a profile, show your work","və yeni müştərilər qazan.":"and win new clients.",
      "Qeydiyyatdan keç":"Sign up",
      "Nə qədər material lazımdır?":"How much material do you need?","Boya":"Paint","Divar kağızı":"Wallpaper",
      "Otağın ölçüsü":"Room size","Uzunluq":"Length","En":"Width","Hündürlük":"Height","Qapı":"Door","Say":"Count","Pəncərə":"Window",
      "litr":"liters","boyanacaq divar":"wall area","1 qata kifayət":"enough for 1 coat","edir":"","Tam kalkulyatoru aç →":"Open full calculator →",
      "Ən çox baxılan":"Most viewed","Kateqoriyalar":"Categories","Kafel & metlax":"Tiles & metlakh","860 məhsul":"860 products","340 məhsul":"340 products",
      "Laminant & parket":"Laminate & parquet","Elektrik & işıqlandırma":"Electrical & lighting","Kərpic & daş":"Brick & stone","Sement & qarışıqlar":"Cement & mixes",
      "Ən çox satılan":"Best sellers","Seçilmiş məhsullar":"Featured products",
      "Yeni məhsul":"New","Stokda var":"In stock","Keramik kafel 60×60, mat":"Ceramic tile 60×60, matte","Məhsula keç":"View product",
      "Nə qədər material lazımdır? Dəqiq hesabla.":"How much material do you need? Calculate precisely.",
      "Otağın ölçüsünü yaz — boya, kafel, laminant və dam örtüyü üçün miqdar və təxmini qiymət dərhal çıxsın.":"Enter the room size — get quantity and an estimated price for paint, tile, laminate and roofing instantly.",
      "Kalkulyatoru aç →":"Open calculator →","Boya · 3 otaq":"Paint · 3 rooms","≈ 312 ₼ · 3 banka (10 litr)":"≈ 312 ₼ · 3 cans (10 L)",
      "Dam örtüyü":"Roofing","Kafel":"Tile","Laminant":"Laminate",
      "Ən çox tələb olunan ustalar":"Most requested masters","Seçilmiş ustalar":"Featured masters",
      "Top usta":"Top master","Təsdiqlənmiş":"Verified","Kafel & metlax ustası":"Tile & metlakh master",
      "Rəşad Məmmədov":"Rashad Mammadov","12 illik təcrübə":"12 years of experience","320 layihə":"320 projects",
      "Hardan başlayacağını bilmirsən?":"Don't know where to start?",
      "Bir neçə sual cavabla, mütəxəssis sənə pulsuz zəng edib nədən başlayacağını izah etsin.":"Answer a few questions and a specialist will call you for free and explain where to begin.",
      "Sualları cavabla":"Answer the questions","Pulsuz zəng al":"Get a free call","İşə başla":"Start the work",
      "Nə üzərində işləyirsən?":"What are you working on?","Yeni ev tikintisi":"New house construction","Mənzil təmiri":"Apartment renovation",
      "Həyət evi / bağ evi":"House / country house","Hələ qərar verməmişəm":"Haven't decided yet","Davam et":"Continue",
      "Faydalı məqalələr":"Useful articles","Oxu →":"Read →","Oxu":"Read","Ətraflı oxu":"Read more","Daha ətraflı":"Learn more",
      "Təmirə hardan başlamaq lazımdır?":"Where should you start a renovation?",
      "İlk addımlar, büdcə planı və ən çox edilən səhvlər — başlamazdan əvvəl bilməli olduqlar.":"First steps, budget planning and the most common mistakes — what to know before you begin.",
      "Düzgün boyanı necə seçmək olar?":"How to choose the right paint?",
      "Düzgün laminant necə seçilir?":"How to choose laminate?",
      "Sinif, qalınlıq, su davamlılığı — otağa görə hansını seçmək lazımdır.":"Class, thickness, water resistance — which to choose per room.",
      "Fasad boyası: tam bələdçi":"Facade paint: a full guide",
      "Hava şəraitinə davamlılıq, örtmə hesabı və tətbiq qaydaları bir məqalədə.":"Weather resistance, coverage calculation and application rules in one article.",
      "Mat, yarımmat, parlaq — hansı otaq üçün hansı boya uyğundur və nə qədər lazımdır.":"Matte, semi-matte, glossy — which paint suits which room and how much you need.",
      "Kafel döşəməsində 7 səhv":"7 mistakes when laying tiles",
      "Peşəkar ustaların qaçındığı tipik səhvlər və onların qarşısını necə almaq olar.":"Typical mistakes pros avoid and how to prevent them.",
      "Təmir büdcəsini necə planlamalı?":"How to plan a renovation budget?",
      "Material, işçi qüvvəsi və gözlənilməz xərclər — büdcəni doğru bölüşdürməyin yolu.":"Materials, labor and unexpected costs — how to allocate the budget correctly.",
      "Məhsullara keç":"Go to products","Məhsullar":"Products","Boya & emal":"Paint & enamel","Santexnika":"Plumbing",
      "İzolyasiya & istilik":"Insulation & heating","Bütün kateqoriyalara keç":"All categories",
      "Usta tap":"Find a master","Top reytinqli ustalar":"Top-rated masters","Bütün mütəxəssislərə bax":"See all specialists",
      "ARCHİ-yə qoşul":"Join ARCHİ","Satıcı ol":"Become a seller","Usta ol":"Become a master",
      "Tərəfdaşlıq proqramı":"Partnership program","Bizneslə əməkdaşlıq":"Business cooperation",
      "Şirkət & dəstək":"Company & support","Məqalələr":"Articles","Yardım mərkəzi":"Help center","Əlaqə":"Contact",
      "Təmir dünyasında yeniliklərdən xəbərdar olun":"Stay updated with the world of renovation",
      "Endirimlərdən, yeni məhsullar, faydalı məqalələr və yeniliklər barədə ilk siz məlumat alın.":"Be the first to know about discounts, new products, useful articles and news.",
      "E-poçt ünvanın":"Your e-mail","Abunə ol":"Subscribe",
      "İstifadə şərtləri":"Terms of use","Məxfilik siyasəti":"Privacy policy",
      "Çatdırılma & qaytarılma":"Delivery & returns","Cookie siyasəti":"Cookie policy","Sayt xəritəsi":"Sitemap",
      "©2026 ARCHI - Bütün hüquqlar qorunur.":"©2026 ARCHI — All rights reserved.",
      /* mega */
      "Tikinti materialları":"Construction materials","Sement, armatur, kərpic və digər əsas tikinti məhsulları":"Cement, rebar, brick and other core building materials",
      "Hamam, mətbəx və mühəndislik sistemləri üçün məhsullar":"Products for bathroom, kitchen and engineering systems",
      "Elektrik":"Electrical","Kabel, açar, rozetka və elektrik avadanlıqları":"Cable, switches, sockets and electrical equipment",
      "Döşəmə və üzlük":"Flooring & cladding","Laminat, parket, kafel və keramoqranit məhsulları":"Laminate, parquet, tile and porcelain stoneware",
      "İşıqlandırma":"Lighting","Ev və kommersiya məkanları üçün işıqlandırma həlləri":"Lighting solutions for home and commercial spaces",
      "Dekor və mebel":"Decor & furniture","İnteryerinizi tamamlayan dekor və mebel həlləri":"Decor and furniture that complete your interior",
      "Memarlar":"Architects","Müasir və funksional layihələrin hazırlanması, estetik və texniki dizayn":"Modern, functional projects with aesthetic and technical design",
      "İnteryer dizaynerlər":"Interior designers","Məkanın estetik və funksional təşkili":"Aesthetic and functional organization of space",
      "Ustalar":"Masters","Kafelçi, elektrik, santexnik və digər peşəkarlar":"Tiler, electrician, plumber and other professionals",
      "Tikinti şirkətləri":"Construction companies","Tikinti prosesinin peşəkar idarə olunması":"Professional management of the construction process",
      "Haradan başlayacağını bilmirsən? Mütəxəssislərdən pulsuz məsləhət al.":"Don't know where to start? Get free advice from specialists.",
      "Hansı kafeli seçək?":"Which tile to choose?",
      /* blog page */
      "Ana səhifə":"Home","Təmir bələdçisi":"Renovation guide",
      "Materialları seçməkdən büdcə planlamağa, etibarlı usta tapmaqdan ən çox edilən səhvlərə qədər — təmirinin hər mərhələsi üçün praktik məqalələr.":"From choosing materials to budget planning, from finding a trusted master to common mistakes — practical articles for every stage of your renovation.",
      "Bütün məqalələr":"All articles","Təmir":"Renovation","Materiallar":"Materials","Büdcə":"Budget","Dizayn":"Design","San texnika":"Plumbing","İzolyasiya":"Insulation",
      "Bələdçi":"Guide","Təmirə hardan başlamaq lazımdır? Tam yol xəritəsi":"Where to start a renovation? Full roadmap",
      "İlk addımlar, büdcə planı, ardıcıllıq və yeni başlayanların ən çox etdiyi səhvlər. Divarlardan tutmuş son təmizliyə qədər — başlamazdan əvvəl bilməli olduğun hər şeyi bir məqalədə topladıq.":"First steps, budget plan, sequencing and the most common beginner mistakes. From walls to the final clean-up — everything you need to know before you start, in one article.",
      "Aysel Həsənova":"Aysel Hasanova","6 dəq oxu":"6 min read","4 dəq oxu":"4 min read","5 dəq oxu":"5 min read","12 İyun 2026":"12 June 2026",
      /* login modal */
      "Xoş gəlmisən":"Welcome","Hesabına gir — alıcı, satıcı və ya usta kabinetinə davam et.":"Sign in to your account — buyer, seller or master.",
      "Giriş uğurlu oldu! (demo — backend qoşulmayıb)":"Signed in successfully! (demo — no backend connected)",
      "E-poçt və ya telefon":"E-mail or phone","Şifrə":"Password","Məni xatırla":"Remember me","Şifrəni unutmusan?":"Forgot password?","Hesabın yoxdur?":"Don't have an account?",
      /* register */
      "Hesab növünü seç — alıcı, satıcı, yoxsa usta/mütəxəssis kimi davam et.":"Choose an account type — buyer, seller or master/specialist.",
      "Qeydiyyat uğurla tamamlandı! (demo — backend qoşulmayıb)":"Registration complete! (demo — no backend connected)",
      "Hesab növü":"Account type","Alıcı":"Buyer","Material al, usta tap, sifariş ver.":"Buy materials, find masters, place orders.",
      "Satıcı":"Seller","Məhsullarını yerləşdir və sat.":"List your products and sell.","Usta / Mütəxəssis":"Master / specialist","Xidmət göstər, müştəri qazan.":"Offer services, win customers.",
      "Ad":"First name","Soyad":"Last name","Adınız":"Your first name","Soyadınız":"Your last name","Şirkət / marka adı":"Company / brand name","Məsələn: ARCHI Build MMC":"e.g. ARCHI Build LLC",
      "İxtisas":"Specialization","Seçin":"Select","Kafelçi":"Tiler","Santexnik":"Plumber","Boyaçı":"Painter","Gips-kartonçu":"Drywaller","Memar":"Architect","İnteryer dizayner":"Interior designer","Digər":"Other",
      "Şəhər":"City","Bakı":"Baku","Sumqayıt":"Sumgayit","Gəncə":"Ganja","Mingəçevir":"Mingachevir","Şirvan":"Shirvan",
      "E-poçt":"E-mail","Telefon":"Phone","Ən azı 6 simvol":"At least 6 characters","Şifrəni təkrarla":"Repeat password","və":"and","ilə razıyam":"— I agree","Artıq hesabın var?":"Already have an account?",
      /* product page */
      "Keramik kafel 60×60, mat — açıq boz":"Ceramic tile 60×60, matte — light grey",
      "1,876 rəy":"1,876 reviews","3,200+ satıldı":"3,200+ sold","qiymət 1 m² üçün · ƏDV daxil":"price per 1 m² · VAT included",
      "Mat səth, sürüşməyə davamlı (R10)":"Matte, anti-slip surface (R10)","Şaxtaya və nəmə davamlı — daxili/xarici üçün":"Frost- and moisture-resistant — indoor/outdoor",
      "60×60 sm · qutuda 1.44 m² (4 ədəd)":"60×60 cm · 1.44 m² per box (4 pcs)","Stokda var — 480 m² hazırdır":"In stock — 480 m² ready",
      "Səbətə at":"Add to cart","Səbətə əlavə olundu":"Added to cart","Pulsuz çatdırılma":"Free delivery","100 ₼-dən yuxarı sifarişlərə":"on orders over 100 ₼","14 gün qaytarma":"14-day returns","Açılmamış qutular üçün":"for unopened boxes",
      "Təsdiqlənmiş satıcı":"Verified seller","4.8 ★ mağaza reytinqi · 1,240 məhsul · ~2 saat ərzində cavab":"4.8 ★ store rating · 1,240 products · replies in ~2 hours","Mağazaya keç":"Visit store","İzlə":"Follow","Rəsmi mağaza":"Official store","Mağaza reytinqi":"Store rating","Məhsul":"Products","Cavab vaxtı":"Response time","~2 saat":"~2 hours",
      "Məhsul haqqında":"About the product","Təsvir & xüsusiyyətlər":"Description & specs","Təsvir":"Description","Xüsusiyyətlər":"Specifications",
      "Keramik kafel 60×60 sm mat səthi ilə həm divar, həm də döşəmə üçün ideal seçimdir. Açıq boz çaları müasir minimalist interyerlərə, hamam, mətbəx və qonaq otaqlarına asanlıqla uyğunlaşır. Mat finiş işıq əksini azaldır və cızıqları gizlədir.":"A 60×60 cm ceramic tile with a matte surface — an ideal choice for both walls and floors. Its light-grey tone fits easily into modern minimalist interiors: bathrooms, kitchens and living rooms. The matte finish reduces glare and hides scratches.",
      "Yüksək sıxlıqlı keramika sayəsində nəmə, şaxtaya və kimyəvi təmizləyicilərə davamlıdır — buna görə daxili məkanlarla yanaşı balkon və terraslarda da istifadə oluna bilər. R10 sürüşməyə qarşı sinifi onu nəmli zonalar üçün təhlükəsiz edir.":"Thanks to its high-density ceramic, it resists moisture, frost and chemical cleaners — so it works not only indoors but also on balconies and terraces. Its R10 anti-slip class makes it safe for wet areas.",
      "Harada istifadə olunur?":"Where is it used?",
      "Hamam və sanitar qovşaqları, mətbəx önlüyü və döşəməsi, dəhliz, qonaq otağı, ofis və kommersiya sahələri. Yerdən isitmə sistemləri ilə uyğundur.":"Bathrooms and wet rooms, kitchen backsplash and floor, hallway, living room, office and commercial spaces. Compatible with underfloor heating systems.",
      "Ölçü":"Size","60 × 60 sm":"60 × 60 cm","Qalınlıq":"Thickness","Səth":"Surface","Mat":"Matte","Material":"Material","Keramoqranit":"Porcelain stoneware","Rəng":"Color","Açıq boz":"Light grey","Qutudakı miqdar":"Pieces per box","4 ədəd / 1.44 m²":"4 pcs / 1.44 m²","Sürüşmə sinfi":"Slip class","İstifadə yeri":"Application area","Daxili / xarici, divar & döşəmə":"Indoor / outdoor, wall & floor","Zəmanət":"Warranty","İstehsalçı zəmanəti":"Manufacturer warranty","Ölkə":"Country","İspaniya":"Spain",
      "Reytinq":"Rating","Müştəri qiymətləndirməsi":"Customer ratings","1,876 rəy əsasında":"based on 1,876 reviews",
      "Rəylər":"Reviews","Müştərilər nə deyir?":"What customers say","Təsdiqlənmiş alış":"Verified purchase","Rəy yaz":"Write a review","👍 Faydalı oldu":"👍 Helpful","Şikayət et":"Report",
      "3 İyun 2026":"3 June 2026","28 May 2026":"28 May 2026","15 May 2026":"15 May 2026","Rəşad M.":"Rashad M.",
      "Keyfiyyət gözlədiyimdən yüksək çıxdı. Rəngi şəkildəki kimidir, hamamın döşəməsinə düzdürdük — mat səth heç sürüşmür. Tövsiyə edirəm.":"The quality is higher than I expected. The colour matches the photo, we laid it on the bathroom floor — the matte surface doesn't slip at all. Recommend it.",
      "Usta kimi onlarla layihədə istifadə etmişəm. Kəsimi təmiz gedir, künclərdə çatlamır. Qiymət-keyfiyyət balansı əladır.":"As a master I've used it on dozens of projects. It cuts cleanly and doesn't crack at the corners. Great value for money.",
      "Məhsul yaxşıdır, amma bir qutuda 2 ədəd kiçik zədə vardı. Satıcı dərhal əvəzini göndərdi, ona görə təşəkkür.":"Good product, but one box had 2 slightly damaged pieces. The seller sent replacements right away, so thanks for that.",
      "Çatdırılma sürətli oldu, qablaşdırma möhkəm idi. Mətbəx önlüyünə vurduq, çox müasir göründü.":"Delivery was fast and the packaging was solid. We used it as a kitchen backsplash — looks very modern.",
      "Sənə uyğun ola bilər":"You may also like","Oxşar məhsullar":"Similar products","Hamısına bax":"View all","Döşəmə":"Flooring","Üzlük":"Cladding",
      "Keramik kafel 60×60, parlaq":"Ceramic tile 60×60, glossy","Laminant parket 8 mm, palıd":"Laminate parquet 8 mm, oak","Mozaika kafel, ağ mat":"Mosaic tile, white matte","Dekorativ kərpic üzlük, terrakot":"Decorative brick cladding, terracotta",
      "(1,204 rəy)":"(1,204 reviews)","(890 rəy)":"(890 reviews)","(512 rəy)":"(512 reviews)","(744 rəy)":"(744 reviews)",
      /* sell (post a product) */
      "Satıcı paneli":"Seller panel",
      "Məhsulunu bir neçə dəqiqəyə yerləşdir — qeydiyyat tələb olunmur. Elanını dərc et, hesabı sonra yaradarsan.":"Post your product in a couple of minutes — no registration required. Publish your listing and create an account later.",
      "Zəhmət olmasa məhsulun adını, kateqoriyasını və qiymətini doldur.":"Please fill in the product name, category and price.",
      "Şəkil əlavə et":"Add a photo","JPG və ya PNG · sürüklə və ya kliklə":"JPG or PNG · drag or click",
      "Məhsulun adı":"Product name","Məsələn: Keramik kafel 60×60, mat":"e.g. Ceramic tile 60×60, matte",
      "Kateqoriya":"Category","Vəziyyət":"Condition","Yeni":"New","İşlənmiş":"Used","Qiymət":"Price","Köhnə qiymət (endirim üçün)":"Old price (for discount)",
      "Ölçü, material, rəng, istifadə yeri — alıcıya kömək edəcək hər şeyi yaz.":"Size, material, color, where it's used — write everything that helps the buyer.",
      "Qeydiyyat tələb olunmur — məhsulu indi yerləşdir, hesabını sonra yarat. Elanın itməyəcək.":"No registration required — post the product now, create your account later. Your listing won't be lost.",
      "Məhsulu yerləşdir":"Post product",
      "Təbrik edirik —":"Congratulations —","artıq saytda görünür və alıcılar onu tapa bilər.":"is now live on the site and buyers can find it.",
      "Məhsulun yerləşdirildi!":"Your product is live!","Elanını qorumaq istəyirsən?":"Want to keep your listing?",
      "Pulsuz hesab yarat — elanını idarə et, sifarişləri gör və satıcı reytinqi qazan. Cəmi bir dəqiqə çəkir, məhsulun isə artıq dərc olunub.":"Create a free account — manage your listing, see orders and earn a seller rating. It takes a minute, and your product is already published.",
      "Saytda bax":"View on site","Daha bir məhsul əlavə et":"Add another product","İndi yox, saytda bax":"Not now, view on site","Sənin elanın":"Your listing"
    }
  };

  /* ---------- inject nav/footer ---------- */
  const navMount = document.querySelector('[data-archi="nav"]');
  if (navMount) navMount.outerHTML = NAV;
  const footMount = document.querySelector('[data-archi="footer"]');
  if (footMount) footMount.outerHTML = FOOTER;

  /* səbət sayğacı (navbar) */
  (function () {
    try {
      const c = JSON.parse(localStorage.getItem("archi-cart") || "[]");
      const b = document.getElementById("navCartCount");
      if (b) { if (c.length) { b.textContent = c.length; b.style.display = "flex"; } else b.style.display = "none"; }
    } catch (e) {}
  })();

  /* aktiv səhifəni naviqasiyada işarələ */
  (function () {
    const page = (location.pathname.split("/").pop() || "index.html").toLowerCase();
    document.querySelectorAll(".nav-item[href]").forEach(a => {
      const h = (a.getAttribute("href") || "").toLowerCase();
      if (h && h !== "#" && h === page) a.classList.add("active");
    });
  })();

  /* ---------- i18n ---------- */
  const labels = { az: "AZ", ru: "RUS", en: "ENG" };
  const i18nNodes = [];
  const tracked = new Set();
  let curLang = "az";

  function collect() {
    const tw = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT);
    let tn;
    while ((tn = tw.nextNode())) {
      if (tracked.has(tn)) continue;
      const t = tn.nodeValue.trim();
      if (t && (I18N.ru[t] !== undefined || I18N.en[t] !== undefined)) {
        i18nNodes.push({ node: tn, az: tn.nodeValue });
        tracked.add(tn);
      }
    }
  }

  function apply(lang) {
    curLang = lang;
    i18nNodes.forEach(({ node, az }) => {
      const key = az.trim();
      if (lang === "az") node.nodeValue = az;
      else { const tr = I18N[lang][key]; if (tr !== undefined) node.nodeValue = az.replace(key, tr); }
    });
    // bütün input/textarea placeholder-lərini tərcümə et (axtarış da daxil)
    document.querySelectorAll("input[placeholder], textarea[placeholder]").forEach(el => {
      if (!el.dataset.az) el.dataset.az = el.placeholder;
      el.placeholder = lang === "az" ? el.dataset.az : (I18N[lang][el.dataset.az] || el.dataset.az);
    });
    const ll = document.getElementById("langLabel");
    if (ll) ll.textContent = labels[lang];
    document.documentElement.lang = lang;
    document.querySelectorAll("#langMenu li").forEach(li => li.classList.toggle("active", li.dataset.lang === lang));
    try { localStorage.setItem("archi-lang", lang); } catch (e) {}
  }

  function setLang(lang) { collect(); apply(lang); }
  window.ARCHI = { setLang, refresh() { collect(); apply(curLang); } };

  /* ---------- Search autocomplete (Figma 1105:17790 ilə 1:1) ---------- */
  (function initSearch() {
    const input = document.getElementById("navSearch");
    const drop = document.getElementById("searchDrop");
    if (!input || !drop) return;
    const overlay = document.createElement("div");
    overlay.className = "search-overlay";
    const topbar = document.querySelector(".topbar");
    if (topbar) topbar.appendChild(overlay); else document.body.appendChild(overlay);

    const esc = s => s.replace(/[&<>"']/g, c => ({ "&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;","'":"&#39;" }[c]));
    function render(qRaw) {
      const q = esc((qRaw || "").trim() || "kafel");
      drop.innerHTML =
        '<div class="sd-head">Sürətli axtarış</div>' +
        [q + "|60×60", q + "|yapışdırıcısı", q + "|ustası"].map(s => {
          const [b, rest] = s.split("|");
          return '<div class="sd-sug"><img src="assets/ic-search.svg" alt=""><span><b>' + b + '</b> ' + rest + '</span></div>';
        }).join("") +
        '<div class="sd-sug"><img src="assets/ic-search.svg" alt=""><span>metlax <b>' + q + '</b></span></div>' +
        '<div class="sd-div"></div>' +
        '<div class="sd-head">Məhsullar</div>' +
        [
          ["assets/fig/1ed736a990f0.jpg", "Keramik kafel 60×60, mat", "Kafel & metlax", "23.90 ₼"],
          ["assets/fig/bca0ec1e.jpg", "Metlax kafel 20×20, naxışlı", "Kafel & metlax", "18.50 ₼"],
          ["assets/fig/78886edf.jpg", "Mərmər effektli kafel 60×120", "Kafel & metlax", "49.90 ₼"],
        ].map(p => '<a class="sd-prod" href="product.html"><span class="im"><img src="' + p[0] + '" alt=""></span><span class="tx"><span class="t1">' + p[1] + '</span><br><span class="t2">' + p[2] + '</span></span><span class="pr">' + p[3] + '</span></a>').join("") +
        '<div class="sd-div"></div>' +
        '<div class="sd-head">Ustalar</div>' +
        [
          ["RM", "Rəşad Məmmədov", "Kafel & metlax ustası", "4.9"],
          ["TH", "Tural Həsənov", "Kafel & metlax ustası", "4.7"],
        ].map(u => '<a class="sd-usta" href="#"><span class="av">' + u[0] + '</span><span class="tx"><span class="t1">' + u[1] + '</span><br><span class="t2">' + u[2] + '</span></span><span class="rt"><span class="st">★</span>' + u[3] + '</span></a>').join("") +
        '<a class="sd-all" href="' + resultsHref(qRaw) + '">Bütün nəticələrə bax (86) →</a>';
    }
    function resultsHref(qRaw) {
      const q = (qRaw || "").trim();
      return "search.html" + (q ? "?q=" + encodeURIComponent(q) : "");
    }
    function open() { render(input.value); drop.classList.add("on"); overlay.classList.add("on"); }
    function close() { drop.classList.remove("on"); overlay.classList.remove("on"); }
    input.addEventListener("focus", open);
    input.addEventListener("input", () => { render(input.value); drop.classList.add("on"); overlay.classList.add("on"); });
    input.addEventListener("keydown", e => {
      if (e.key === "Escape") { close(); input.blur(); }
      if (e.key === "Enter") { location.href = resultsHref(input.value); }
    });
    document.addEventListener("click", e => { if (!e.target.closest(".search")) close(); });
    overlay.addEventListener("click", close);
  })();

  /* ---------- mega dropdown (hover + click + klaviatura) ---------- */
  (function initMega() {
    const triggers = document.querySelectorAll(".nav-item[data-mega]");
    const panels = document.querySelectorAll(".mega-panel");
    let hideTimer;
    function closeAll() {
      panels.forEach(p => p.classList.remove("open"));
      triggers.forEach(t => t.classList.remove("mega-active"));
    }
    function open(key) {
      closeAll();
      const p = document.querySelector('.mega-panel[data-panel="' + key + '"]');
      const t = document.querySelector('.nav-item[data-mega="' + key + '"]');
      if (p) p.classList.add("open");
      if (t) t.classList.add("mega-active");
    }
    function toggle(key) {
      const p = document.querySelector('.mega-panel[data-panel="' + key + '"]');
      if (p && p.classList.contains("open")) closeAll(); else open(key);
    }
    triggers.forEach(t => {
      t.addEventListener("mouseenter", () => { clearTimeout(hideTimer); open(t.dataset.mega); });
      t.addEventListener("mouseleave", () => { hideTimer = setTimeout(closeAll, 160); });
      t.addEventListener("click", e => { if (t.getAttribute("href")) return; e.preventDefault(); toggle(t.dataset.mega); });
      t.addEventListener("keydown", e => {
        if (e.key === "Escape") { closeAll(); return; }
        if (e.key === "Enter" || e.key === " ") { if (t.getAttribute("href")) return; e.preventDefault(); toggle(t.dataset.mega); }
      });
    });
    panels.forEach(p => {
      p.addEventListener("mouseenter", () => clearTimeout(hideTimer));
      p.addEventListener("mouseleave", () => { hideTimer = setTimeout(closeAll, 160); });
    });
    document.addEventListener("click", e => {
      if (!e.target.closest(".nav-item[data-mega]") && !e.target.closest(".mega-panel")) closeAll();
    });
  })();

  /* ---------- dil dropdown ---------- */
  (function initLang() {
    const langBtn = document.getElementById("langBtn");
    if (!langBtn) return;
    langBtn.addEventListener("click", e => { e.stopPropagation(); langBtn.classList.toggle("open"); });
    langBtn.addEventListener("keydown", e => { if (e.key === "Enter" || e.key === " ") { e.preventDefault(); langBtn.classList.toggle("open"); } });
    document.addEventListener("click", () => langBtn.classList.remove("open"));
    document.querySelectorAll("#langMenu li").forEach(li =>
      li.addEventListener("click", e => { e.stopPropagation(); setLang(li.dataset.lang); langBtn.classList.remove("open"); })
    );
  })();

  /* ---------- Kalkulyator triggerləri -> calculator.html ---------- */
  (function navCalc() {
    ["#openCalc", "#openCalc2", ".sc-full", "[data-fcalc]"].forEach(function (sel) {
      document.querySelectorAll(sel).forEach(function (el) {
        el.addEventListener("click", function (e) { e.preventDefault(); window.location.href = "calculator.html"; });
      });
    });
  })();

  /* ---------- login popup (modal) ---------- */
  (function loginModal() {
    document.body.insertAdjacentHTML("beforeend", LOGIN_MODAL);
    const overlay = document.getElementById("lmOverlay");
    if (!overlay) return;
    function open() {
      overlay.classList.add("open");
      document.body.classList.add("lm-lock");
      const f = overlay.querySelector("input");
      if (f) setTimeout(() => f.focus(), 60);
    }
    function close() {
      overlay.classList.remove("open");
      document.body.classList.remove("lm-lock");
    }
    window.ARCHI.openLogin = open;
    window.ARCHI.closeLogin = close;
    document.getElementById("lmClose").addEventListener("click", close);
    overlay.addEventListener("click", e => { if (e.target === overlay) close(); });
    document.addEventListener("keydown", e => { if (e.key === "Escape" && overlay.classList.contains("open")) close(); });
    document.getElementById("lmForm").addEventListener("submit", () => {
      document.getElementById("lmOk").classList.add("show");
    });
  })();

  /* ---------- auth triggerləri ---------- */
  (function navAuth() {
    // "Daxil ol" — popup aç (səhifə yox). [data-login] daşıyan istənilən element də açır.
    document.querySelectorAll(".signin .txt, [data-login]").forEach(el =>
      el.addEventListener("click", e => {
        e.preventDefault();
        if (window.ARCHI.openLogin) window.ARCHI.openLogin();
        else location.href = "login.html";
      })
    );
    document.querySelectorAll(".btn-post").forEach(el => el.addEventListener("click", () => { location.href = "sell.html"; }));
  })();

  /* ---------- saxlanmış dili bərpa et ---------- */
  let saved = "az";
  try { saved = localStorage.getItem("archi-lang") || "az"; } catch (e) {}
  setLang(saved);
})();
