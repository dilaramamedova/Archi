<?php

namespace Database\Seeders;

use App\Models\LegalPage;
use Illuminate\Database\Seeder;

class LegalPageSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            [
                'slug' => 'terms',
                'title' => ['az' => 'İstifadə şərtləri', 'ru' => 'Условия использования', 'en' => 'Terms of Use'],
                'meta_description' => ['az' => 'ARCHI platformasının istifadə şərtləri', 'ru' => 'Условия использования платформы ARCHI', 'en' => 'ARCHI platform terms of use'],
                'content' => [
                    'az' => '<h2>1. Ümumi müddəalar</h2><p>Bu İstifadə Şərtləri ARCHI platformasından ("Sayt") istifadə qaydalarını tənzimləyir. Saytdan istifadə etməklə siz bu şərtləri qəbul etmiş olursunuz.</p><h2>2. Xidmətlərin təsviri</h2><p>ARCHI tikinti və təmir materiallarının satışı, mütəxəssislərin axtarışı və əlaqəli xidmətlər üçün onlayn platformadır.</p><h2>3. İstifadəçi hesabı</h2><p>Bəzi xidmətlərdən istifadə etmək üçün qeydiyyatdan keçməlisiniz. Hesab məlumatlarınızın təhlükəsizliyinə görə siz məsuliyyət daşıyırsınız.</p><h2>4. Alıcı və satıcı öhdəlikləri</h2><p>Satıcılar təqdim etdikləri məhsulların keyfiyyətinə və təsvirinə görə cavabdehdirlər. Alıcılar ödəniş öhdəliklərini yerinə yetirməyə borcludurlar.</p><h2>5. Əqli mülkiyyət</h2><p>Saytdakı bütün məzmun, dizayn və proqram təminatı ARCHI-yə məxsusdur və müəllif hüququ qanunları ilə qorunur.</p>',
                    'ru' => '<h2>1. Общие положения</h2><p>Настоящие Условия использования регулируют порядок использования платформы ARCHI ("Сайт"). Используя Сайт, вы принимаете эти условия.</p><h2>2. Описание услуг</h2><p>ARCHI — онлайн-платформа для продажи строительных и ремонтных материалов, поиска специалистов и сопутствующих услуг.</p><h2>3. Учётная запись</h2><p>Для использования некоторых услуг необходимо зарегистрироваться. Вы несёте ответственность за безопасность данных своей учётной записи.</p><h2>4. Обязанности покупателей и продавцов</h2><p>Продавцы несут ответственность за качество и описание предоставляемых товаров. Покупатели обязаны выполнять платёжные обязательства.</p><h2>5. Интеллектуальная собственность</h2><p>Весь контент, дизайн и программное обеспечение на Сайте принадлежат ARCHI и защищены законами об авторском праве.</p>',
                    'en' => '<h2>1. General Provisions</h2><p>These Terms of Use govern the rules for using the ARCHI platform ("Site"). By using the Site, you accept these terms.</p><h2>2. Service Description</h2><p>ARCHI is an online platform for the sale of construction and renovation materials, specialist search, and related services.</p><h2>3. User Account</h2><p>Registration is required to use certain services. You are responsible for the security of your account information.</p><h2>4. Buyer and Seller Obligations</h2><p>Sellers are responsible for the quality and description of the products they provide. Buyers are obligated to fulfill payment obligations.</p><h2>5. Intellectual Property</h2><p>All content, design, and software on the Site belong to ARCHI and are protected by copyright laws.</p>',
                ],
            ],
            [
                'slug' => 'privacy',
                'title' => ['az' => 'Gizlilik siyasəti', 'ru' => 'Политика конфиденциальности', 'en' => 'Privacy Policy'],
                'meta_description' => ['az' => 'ARCHI platformasının gizlilik siyasəti', 'ru' => 'Политика конфиденциальности платформы ARCHI', 'en' => 'ARCHI platform privacy policy'],
                'content' => [
                    'az' => '<h2>1. Toplanılan məlumatlar</h2><p>Biz aşağıdakı məlumatları toplaya bilərik: ad, e-poçt, telefon nömrəsi, ünvan və ödəniş məlumatları.</p><h2>2. Məlumatların istifadəsi</h2><p>Topladığımız məlumatlar sifarişlərin emalı, müştəri dəstəyi və platformanın təkmilləşdirilməsi üçün istifadə olunur.</p><h2>3. Məlumatların qorunması</h2><p>Şəxsi məlumatlarınızı qorumaq üçün müasir şifrələmə texnologiyalarından istifadə edirik.</p><h2>4. Üçüncü tərəflər</h2><p>Məlumatlarınızı sizin razılığınız olmadan üçüncü tərəflərə ötürmürük, qanunla tələb olunan hallar istisna olmaqla.</p><h2>5. Cookie faylları</h2><p>Sayt istifadəçi təcrübəsini yaxşılaşdırmaq üçün cookie fayllarından istifadə edir. Ətraflı məlumat üçün Cookie Siyasətimizə baxın.</p>',
                    'ru' => '<h2>1. Собираемые данные</h2><p>Мы можем собирать следующие данные: имя, электронная почта, номер телефона, адрес и платёжные данные.</p><h2>2. Использование данных</h2><p>Собранные данные используются для обработки заказов, поддержки клиентов и улучшения платформы.</p><h2>3. Защита данных</h2><p>Мы используем современные технологии шифрования для защиты ваших персональных данных.</p><h2>4. Третьи стороны</h2><p>Мы не передаём ваши данные третьим сторонам без вашего согласия, за исключением случаев, предусмотренных законом.</p><h2>5. Файлы cookie</h2><p>Сайт использует файлы cookie для улучшения пользовательского опыта. Подробнее см. нашу Политику cookie.</p>',
                    'en' => '<h2>1. Data Collected</h2><p>We may collect the following data: name, email, phone number, address, and payment information.</p><h2>2. Use of Data</h2><p>Collected data is used for order processing, customer support, and platform improvement.</p><h2>3. Data Protection</h2><p>We use modern encryption technologies to protect your personal data.</p><h2>4. Third Parties</h2><p>We do not share your data with third parties without your consent, except as required by law.</p><h2>5. Cookies</h2><p>The Site uses cookies to improve user experience. For more information, see our Cookie Policy.</p>',
                ],
            ],
            [
                'slug' => 'delivery',
                'title' => ['az' => 'Çatdırılma & qaytarma', 'ru' => 'Доставка и возврат', 'en' => 'Delivery & Returns'],
                'meta_description' => ['az' => 'ARCHI çatdırılma və qaytarma şərtləri', 'ru' => 'Условия доставки и возврата ARCHI', 'en' => 'ARCHI delivery and return policy'],
                'content' => [
                    'az' => '<h2>1. Çatdırılma</h2><p>Sifarişlər Bakı şəhəri daxilində 1-3 iş günü ərzində çatdırılır. Regionlara çatdırılma 3-7 iş günü çəkə bilər.</p><h2>2. Çatdırılma qiyməti</h2><p>100 ₼-dən yuxarı sifarişlərə Bakı daxilində pulsuz çatdırılma təqdim olunur. Digər hallarda çatdırılma haqqı sifariş zamanı hesablanır.</p><h2>3. Qaytarma şərtləri</h2><p>Məhsulu aldığınız tarixdən 14 gün ərzində qaytara bilərsiniz. Qaytarılan məhsul orijinal qablaşdırmasında və istifadə olunmamış vəziyyətdə olmalıdır.</p><h2>4. Qaytarma prosesi</h2><p>Qaytarma üçün müştəri dəstəyi ilə əlaqə saxlayın. Təsdiqlənmiş qaytarmalar üçün ödəniş 5-10 iş günü ərzində geri qaytarılır.</p>',
                    'ru' => '<h2>1. Доставка</h2><p>Заказы доставляются по Баку в течение 1-3 рабочих дней. Доставка в регионы может занять 3-7 рабочих дней.</p><h2>2. Стоимость доставки</h2><p>Для заказов свыше 100 ₼ по Баку предоставляется бесплатная доставка. В остальных случаях стоимость рассчитывается при оформлении.</p><h2>3. Условия возврата</h2><p>Вы можете вернуть товар в течение 14 дней с даты получения. Возвращаемый товар должен быть в оригинальной упаковке и неиспользованном состоянии.</p><h2>4. Процесс возврата</h2><p>Для возврата свяжитесь со службой поддержки. Возврат средств по подтверждённым заявкам осуществляется в течение 5-10 рабочих дней.</p>',
                    'en' => '<h2>1. Delivery</h2><p>Orders are delivered within Baku in 1-3 business days. Delivery to regions may take 3-7 business days.</p><h2>2. Delivery Cost</h2><p>Free delivery within Baku is offered for orders over 100 ₼. Otherwise, shipping costs are calculated at checkout.</p><h2>3. Return Conditions</h2><p>You may return a product within 14 days of receipt. Returned products must be in original packaging and unused condition.</p><h2>4. Return Process</h2><p>Contact customer support for returns. Refunds for approved returns are processed within 5-10 business days.</p>',
                ],
            ],
            [
                'slug' => 'cookies',
                'title' => ['az' => 'Cookie siyasəti', 'ru' => 'Политика cookie', 'en' => 'Cookie Policy'],
                'meta_description' => ['az' => 'ARCHI platformasının cookie siyasəti', 'ru' => 'Политика cookie платформы ARCHI', 'en' => 'ARCHI platform cookie policy'],
                'content' => [
                    'az' => '<h2>1. Cookie nədir?</h2><p>Cookie-lər veb saytların brauzerinizə yerləşdirdiyi kiçik mətn fayllarıdır. Onlar saytın düzgün işləməsi və istifadəçi təcrübəsinin yaxşılaşdırılması üçün istifadə olunur.</p><h2>2. İstifadə etdiyimiz cookie-lər</h2><p><strong>Zəruri cookie-lər:</strong> Saytın əsas funksiyaları üçün tələb olunur (giriş, səbət).</p><p><strong>Analitik cookie-lər:</strong> Sayt trafikini və istifadəçi davranışını təhlil etmək üçün istifadə olunur.</p><p><strong>Funksional cookie-lər:</strong> Dil seçimi və digər parametrləri yadda saxlayır.</p><h2>3. Cookie-lərin idarə edilməsi</h2><p>Brauzerin parametrlərindən cookie-ləri söndürə və ya silə bilərsiniz. Lakin bəzi cookie-lərin söndürülməsi saytın funksionallığını məhdudlaşdıra bilər.</p>',
                    'ru' => '<h2>1. Что такое cookie?</h2><p>Cookie — это небольшие текстовые файлы, которые веб-сайты размещают в вашем браузере. Они используются для корректной работы сайта и улучшения пользовательского опыта.</p><h2>2. Используемые cookie</h2><p><strong>Необходимые cookie:</strong> Требуются для основных функций сайта (вход, корзина).</p><p><strong>Аналитические cookie:</strong> Используются для анализа трафика и поведения пользователей.</p><p><strong>Функциональные cookie:</strong> Запоминают выбор языка и другие параметры.</p><h2>3. Управление cookie</h2><p>Вы можете отключить или удалить cookie в настройках браузера. Однако отключение некоторых cookie может ограничить функциональность сайта.</p>',
                    'en' => '<h2>1. What are Cookies?</h2><p>Cookies are small text files that websites place in your browser. They are used for the proper functioning of the site and to improve user experience.</p><h2>2. Cookies We Use</h2><p><strong>Essential cookies:</strong> Required for the basic functions of the site (login, cart).</p><p><strong>Analytical cookies:</strong> Used to analyze site traffic and user behavior.</p><p><strong>Functional cookies:</strong> Remember language selection and other preferences.</p><h2>3. Managing Cookies</h2><p>You can disable or delete cookies in your browser settings. However, disabling some cookies may limit the site\'s functionality.</p>',
                ],
            ],
        ];

        foreach ($pages as $page) {
            LegalPage::updateOrCreate(
                ['slug' => $page['slug']],
                $page,
            );
        }
    }
}
