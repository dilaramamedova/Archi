<?php

/**
 * UI copy of the tender section (Figma «11 · TENDERLƏR»), group `tenders`, plus the
 * three `notifications` keys of TenderAccessGrantedNotification.
 *
 * One source for TranslationsSeeder (fresh installs) and for the migration
 * 2026_10_30_100300_add_tenders_translations, which upserts these rows into an
 * existing database without touching any other key.
 */
return [
    'tenders' => [
        // ── page meta · breadcrumb · heading ─────────────────────────────────
        'meta.title' => ['az' => 'Tenderlər — ARCHİ', 'ru' => 'Тендеры — ARCHİ', 'en' => 'Tenders — ARCHİ'],
        'meta.detail_title' => ['az' => ':title — ARCHİ Tenderlər', 'ru' => ':title — Тендеры ARCHİ', 'en' => ':title — ARCHİ Tenders'],
        'crumb' => ['az' => 'Tenderlər', 'ru' => 'Тендеры', 'en' => 'Tenders'],
        'heading.title' => ['az' => 'Tenderlər', 'ru' => 'Тендеры', 'en' => 'Tenders'],
        'heading.subtitle' => ['az' => 'Layihənizə uyğun tenderi tapın və sifarişçi ilə əlaqə saxlayın.', 'ru' => 'Найдите подходящий тендер для вашего проекта и свяжитесь с заказчиком.', 'en' => 'Find the right tender for your project and get in touch with the client.'],

        // ── paid package line ────────────────────────────────────────────────
        'package.active' => ['az' => 'Ödənişli paket aktivdir', 'ru' => 'Платный пакет активен', 'en' => 'Paid package is active'],
        'package.active_sub' => ['az' => 'Bütün tenderlərə çıxışınız var.', 'ru' => 'У вас есть доступ ко всем тендерам.', 'en' => 'You have access to all tenders.'],
        'package.active_until' => ['az' => 'Bütün tenderlərə :date tarixinədək çıxışınız var.', 'ru' => 'У вас есть доступ ко всем тендерам до :date.', 'en' => 'You have access to all tenders until :date.'],

        // ── search · filters · sort · tabs ───────────────────────────────────
        'search.label' => ['az' => 'Tender axtarışı', 'ru' => 'Поиск тендеров', 'en' => 'Tender search'],
        'search.placeholder' => ['az' => 'Tender və ya şirkət axtarın', 'ru' => 'Найдите тендер или компанию', 'en' => 'Search for a tender or company'],
        'search.submit' => ['az' => 'Axtar', 'ru' => 'Найти', 'en' => 'Search'],
        'filters.title' => ['az' => 'Filtrlər', 'ru' => 'Фильтры', 'en' => 'Filters'],
        'filters.category' => ['az' => 'Kateqoriya', 'ru' => 'Категория', 'en' => 'Category'],
        'filters.city' => ['az' => 'Şəhər', 'ru' => 'Город', 'en' => 'City'],
        'filters.city_all' => ['az' => 'Bütün şəhərlər', 'ru' => 'Все города', 'en' => 'All cities'],
        'filters.status' => ['az' => 'Status', 'ru' => 'Статус', 'en' => 'Status'],
        'filters.status_all' => ['az' => 'Bütün tenderlər', 'ru' => 'Все тендеры', 'en' => 'All tenders'],
        'filters.status_open' => ['az' => 'Aktiv tenderlər', 'ru' => 'Активные тендеры', 'en' => 'Open tenders'],
        'filters.status_closed' => ['az' => 'Bağlanmış tenderlər', 'ru' => 'Закрытые тендеры', 'en' => 'Closed tenders'],
        'filters.apply' => ['az' => 'Filtrləri tətbiq et', 'ru' => 'Применить фильтры', 'en' => 'Apply filters'],
        'filters.reset' => ['az' => 'Sıfırla', 'ru' => 'Сбросить', 'en' => 'Reset'],
        'sort.label' => ['az' => 'Sıralama', 'ru' => 'Сортировка', 'en' => 'Sort by'],
        'sort.newest' => ['az' => 'Ən yeni', 'ru' => 'Сначала новые', 'en' => 'Newest'],
        'sort.deadline' => ['az' => 'Son tarix yaxın', 'ru' => 'Ближайший срок', 'en' => 'Deadline soonest'],
        'sort.budget' => ['az' => 'Büdcə (yüksək)', 'ru' => 'Бюджет (по убыванию)', 'en' => 'Budget (highest)'],
        'tabs.label' => ['az' => 'Tender siyahısı', 'ru' => 'Список тендеров', 'en' => 'Tender list'],
        'tabs.all' => ['az' => 'Bütün tenderlər', 'ru' => 'Все тендеры', 'en' => 'All tenders'],
        'tabs.saved' => ['az' => 'Yadda saxlanılanlar', 'ru' => 'Сохранённые', 'en' => 'Saved'],

        // ── tender card ──────────────────────────────────────────────────────
        'status.open' => ['az' => 'Aktiv', 'ru' => 'Активен', 'en' => 'Open'],
        'status.closed' => ['az' => 'Bağlanıb', 'ru' => 'Закрыт', 'en' => 'Closed'],
        'card.save' => ['az' => 'Yadda saxla', 'ru' => 'Сохранить', 'en' => 'Save'],
        'card.saved' => ['az' => 'Yadda saxlanılıb', 'ru' => 'Сохранено', 'en' => 'Saved'],
        'card.city' => ['az' => 'Şəhər', 'ru' => 'Город', 'en' => 'City'],
        'card.budget' => ['az' => 'Büdcə', 'ru' => 'Бюджет', 'en' => 'Budget'],
        'card.budget_value' => ['az' => 'Büdcə: :value', 'ru' => 'Бюджет: :value', 'en' => 'Budget: :value'],
        'card.deadline' => ['az' => 'Son müraciət tarixi', 'ru' => 'Срок подачи заявок', 'en' => 'Application deadline'],
        'card.deadline_value' => ['az' => 'Son tarix: :value', 'ru' => 'Срок: :value', 'en' => 'Deadline: :value'],
        'card.more' => ['az' => 'Ətraflı bax', 'ru' => 'Подробнее', 'en' => 'View details'],
        'budget.on_request' => ['az' => 'Sorğu əsasında', 'ru' => 'По запросу', 'en' => 'On request'],
        'deadline.none' => ['az' => 'Müddətsiz', 'ru' => 'Без срока', 'en' => 'No deadline'],

        // ── empty states ─────────────────────────────────────────────────────
        'empty.title' => ['az' => 'Uyğun tender tapılmadı', 'ru' => 'Подходящих тендеров не найдено', 'en' => 'No matching tenders found'],
        'empty.text' => ['az' => 'Axtarışınıza və seçdiyiniz filtrlərə uyğun tender yoxdur. Filtrləri sıfırlayın və ya axtarış sözünü dəyişin.', 'ru' => 'По вашему запросу и выбранным фильтрам тендеров нет. Сбросьте фильтры или измените поисковый запрос.', 'en' => 'No tenders match your search and the selected filters. Reset the filters or change your search term.'],
        'empty.reset' => ['az' => 'Filtrləri sıfırla', 'ru' => 'Сбросить фильтры', 'en' => 'Reset filters'],
        'empty.saved_title' => ['az' => 'Hələ yadda saxladığınız tender yoxdur', 'ru' => 'У вас пока нет сохранённых тендеров', 'en' => 'You have no saved tenders yet'],
        'empty.saved_text' => ['az' => 'Tender kartındakı əlfəcin işarəsinə basın — tender bu siyahıya əlavə olunacaq.', 'ru' => 'Нажмите на значок закладки в карточке тендера — он появится в этом списке.', 'en' => 'Tap the bookmark icon on a tender card and it will appear in this list.'],
        'empty.saved_cta' => ['az' => 'Bütün tenderlərə bax', 'ru' => 'Смотреть все тендеры', 'en' => 'Browse all tenders'],

        // ── detail ───────────────────────────────────────────────────────────
        'detail.back' => ['az' => 'Bütün tenderlər', 'ru' => 'Все тендеры', 'en' => 'All tenders'],
        'detail.closed_alert' => ['az' => 'Bu tenderin müraciət müddəti başa çatıb. Yeni müraciətlər qəbul edilmir.', 'ru' => 'Срок подачи заявок по этому тендеру истёк. Новые заявки не принимаются.', 'en' => 'The application period for this tender has ended. New applications are not accepted.'],
        'detail.number' => ['az' => 'Tender № :number', 'ru' => 'Тендер № :number', 'en' => 'Tender No. :number'],
        'detail.published' => ['az' => 'Dərc edilib: :date', 'ru' => 'Опубликован: :date', 'en' => 'Published: :date'],
        'detail.save' => ['az' => 'Yadda saxla', 'ru' => 'Сохранить', 'en' => 'Save'],
        'detail.saved' => ['az' => 'Yadda saxlanılıb', 'ru' => 'Сохранено', 'en' => 'Saved'],
        'detail.about' => ['az' => 'Tender haqqında', 'ru' => 'О тендере', 'en' => 'About the tender'],
        'detail.scope' => ['az' => 'İşin həcmi', 'ru' => 'Объём работ', 'en' => 'Scope of work'],
        'detail.requirements' => ['az' => 'İştirakçıya tələblər', 'ru' => 'Требования к участнику', 'en' => 'Participant requirements'],
        'detail.documents' => ['az' => 'Göndəriləcək sənədlər', 'ru' => 'Документы для отправки', 'en' => 'Documents to submit'],
        'detail.documents_sub' => ['az' => 'Bu sənədləri siz hazırlayıb sifarişçinin e-poçtuna göndərirsiniz.', 'ru' => 'Вы готовите эти документы и отправляете их на e-mail заказчика.', 'en' => 'You prepare these documents and send them to the client’s e-mail.'],
        'detail.files' => ['az' => 'Tender faylları', 'ru' => 'Файлы тендера', 'en' => 'Tender files'],
        'detail.files_sub' => ['az' => 'Aşağıdakı fayllar sifarişçi tərəfindən təqdim olunan input materiallardır.', 'ru' => 'Ниже — исходные материалы, предоставленные заказчиком.', 'en' => 'The files below are input materials provided by the client.'],
        'detail.download' => ['az' => 'Yüklə', 'ru' => 'Скачать', 'en' => 'Download'],
        'detail.key_facts' => ['az' => 'Əsas məlumatlar', 'ru' => 'Основные сведения', 'en' => 'Key details'],
        'detail.deadline' => ['az' => 'Son müraciət tarixi', 'ru' => 'Срок подачи заявок', 'en' => 'Application deadline'],
        'detail.deadline_passed' => ['az' => 'Müraciət müddəti bitib', 'ru' => 'Срок подачи заявок истёк', 'en' => 'Application period has ended'],
        'detail.deadline_value' => ['az' => ':date, saat :time', 'ru' => ':date, в :time', 'en' => ':date, at :time'],
        'detail.baku_time' => ['az' => '(Bakı vaxtı)', 'ru' => '(по бакинскому времени)', 'en' => '(Baku time)'],
        'detail.city' => ['az' => 'Şəhər', 'ru' => 'Город', 'en' => 'City'],
        'detail.area' => ['az' => 'Sahə', 'ru' => 'Площадь', 'en' => 'Area'],
        'detail.budget' => ['az' => 'Büdcə', 'ru' => 'Бюджет', 'en' => 'Budget'],
        'detail.duration' => ['az' => 'İcra müddəti', 'ru' => 'Срок выполнения', 'en' => 'Lead time'],
        'detail.contact' => ['az' => 'Müraciət və əlaqə', 'ru' => 'Заявка и контакты', 'en' => 'Application & contact'],
        'detail.contact_fallback' => ['az' => 'Tender şöbəsi', 'ru' => 'Тендерный отдел', 'en' => 'Tender department'],
        'detail.apply_email' => ['az' => 'E-poçtla müraciət et', 'ru' => 'Подать заявку по e-mail', 'en' => 'Apply by e-mail'],
        'detail.copy_email' => ['az' => 'E-poçtu kopyala', 'ru' => 'Скопировать e-mail', 'en' => 'Copy e-mail'],
        'detail.copied' => ['az' => 'Kopyalandı', 'ru' => 'Скопировано', 'en' => 'Copied'],
        'detail.hint_subject' => ['az' => 'Mövzu sətrində :number qeyd edin.', 'ru' => 'Укажите :number в теме письма.', 'en' => 'Put :number in the subject line.'],
        'detail.hint_send' => ['az' => 'Sənədləri göstərilən e-poçta göndərin.', 'ru' => 'Отправьте документы на указанный e-mail.', 'en' => 'Send the documents to the e-mail shown.'],
        'detail.hint_closed' => ['az' => 'Tender bağlanıb — müraciət qəbulu dayandırılıb.', 'ru' => 'Тендер закрыт — приём заявок остановлен.', 'en' => 'The tender is closed — applications are no longer accepted.'],
        'detail.hint_closed_sub' => ['az' => 'Əlaqə məlumatları yalnız məlumat üçün göstərilir.', 'ru' => 'Контактные данные показаны только для справки.', 'en' => 'Contact details are shown for reference only.'],
        'detail.direct_note' => ['az' => 'Müraciət birbaşa sifarişçiyə göndərilir.', 'ru' => 'Заявка отправляется напрямую заказчику.', 'en' => 'Your application goes directly to the client.'],

        // ── lock card ────────────────────────────────────────────────────────
        'locked.title' => ['az' => 'Tenderlərə giriş əldə edin', 'ru' => 'Получите доступ к тендерам', 'en' => 'Get access to tenders'],
        'locked.text' => ['az' => 'Bu bölmə yalnız aktiv ödənişli aylıq paketi olan istifadəçilər üçün açıqdır.', 'ru' => 'Этот раздел доступен только пользователям с активным платным месячным пакетом.', 'en' => 'This section is open only to users with an active paid monthly package.'],
        'locked.feature_terms' => ['az' => 'Tenderlərin tam şərtləri', 'ru' => 'Полные условия тендеров', 'en' => 'Full tender terms'],
        'locked.feature_files' => ['az' => 'Texniki sənədlər və fayllar', 'ru' => 'Техническая документация и файлы', 'en' => 'Technical documents and files'],
        'locked.feature_contacts' => ['az' => 'Şirkətlərin əlaqə məlumatları', 'ru' => 'Контакты компаний', 'en' => 'Company contact details'],
        'locked.choose_package' => ['az' => 'Ödənişli paketləri seç', 'ru' => 'Выбрать платный пакет', 'en' => 'Choose a paid package'],
        'locked.have_account' => ['az' => 'Hesabınız var?', 'ru' => 'Уже есть аккаунт?', 'en' => 'Already have an account?'],
        'locked.sign_in' => ['az' => 'Daxil ol', 'ru' => 'Войти', 'en' => 'Sign in'],
        'locked.already_have' => ['az' => 'Artıq ödənişli paketiniz var?', 'ru' => 'Уже есть платный пакет?', 'en' => 'Already have a paid package?'],
        'locked.check' => ['az' => 'Abunəliyi yoxla', 'ru' => 'Проверить подписку', 'en' => 'Check subscription'],
        'locked.note' => ['az' => 'Giriş aktiv abunəlik müddətində açıqdır. Tenderlərin birbaşa keçidləri, sənədlər və əlaqə məlumatları da yalnız aktiv paketlə əlçatandır.', 'ru' => 'Доступ открыт на время действия подписки. Прямые ссылки на тендеры, документы и контакты также доступны только с активным пакетом.', 'en' => 'Access is open while your subscription is active. Direct tender links, documents and contact details are also available only with an active package.'],
        'locked.expired_banner' => ['az' => 'Abunəliyinizin müddəti :date tarixində bitib. Tenderlərə giriş müvəqqəti bağlıdır.', 'ru' => 'Срок вашей подписки истёк :date. Доступ к тендерам временно закрыт.', 'en' => 'Your subscription expired on :date. Access to tenders is temporarily closed.'],
        'locked.expired_title' => ['az' => 'Abunəliyinizin müddəti bitib', 'ru' => 'Срок вашей подписки истёк', 'en' => 'Your subscription has expired'],
        'locked.expired_text' => ['az' => 'Tenderlərə girişi bərpa etmək üçün ödənişli aylıq paketi yeniləyin.', 'ru' => 'Чтобы восстановить доступ к тендерам, продлите платный месячный пакет.', 'en' => 'Renew your paid monthly package to restore access to tenders.'],
        'locked.renew' => ['az' => 'Paketi yenilə', 'ru' => 'Продлить пакет', 'en' => 'Renew package'],
        'locked.already_renewed' => ['az' => 'Artıq yeniləmisiniz?', 'ru' => 'Уже продлили?', 'en' => 'Already renewed?'],
        'locked.pending_title' => ['az' => 'Müraciətiniz baxılır', 'ru' => 'Ваша заявка на рассмотрении', 'en' => 'Your request is being reviewed'],
        'locked.pending_text' => ['az' => 'Qoşulma müraciətiniz qəbul edilib. Ödəniş və aktivləşdirmə üçün komandamız sizinlə əlaqə saxlayacaq.', 'ru' => 'Ваша заявка на подключение принята. Наша команда свяжется с вами для оплаты и активации.', 'en' => 'Your access request has been received. Our team will contact you about payment and activation.'],
        'locked.pending_check_hint' => ['az' => 'Paketiniz aktivləşdirilib?', 'ru' => 'Пакет уже активирован?', 'en' => 'Package already activated?'],

        // ── package request modal ────────────────────────────────────────────
        'request.title' => ['az' => 'Tenderlərə qoşulma müraciəti', 'ru' => 'Заявка на доступ к тендерам', 'en' => 'Tender access request'],
        'request.subtitle' => ['az' => 'Paketi seçin və əlaqə məlumatlarınızı göndərin. Ödəniş və aktivləşdirmə üçün sizinlə əlaqə saxlayacağıq.', 'ru' => 'Выберите пакет и отправьте свои контакты. Мы свяжемся с вами для оплаты и активации.', 'en' => 'Choose a package and send your contact details. We will contact you about payment and activation.'],
        'request.package' => ['az' => 'Paket', 'ru' => 'Пакет', 'en' => 'Package'],
        'request.price_on_request' => ['az' => 'Qiymət razılaşma ilə', 'ru' => 'Цена по договорённости', 'en' => 'Price on request'],
        'request.months' => ['az' => ':count ay', 'ru' => ':count мес.', 'en' => ':count mo.'],
        'request.full_name' => ['az' => 'Ad, soyad', 'ru' => 'Имя и фамилия', 'en' => 'Full name'],
        'request.company' => ['az' => 'Şirkət', 'ru' => 'Компания', 'en' => 'Company'],
        'request.phone' => ['az' => 'Telefon', 'ru' => 'Телефон', 'en' => 'Phone'],
        'request.email' => ['az' => 'E-poçt', 'ru' => 'E-mail', 'en' => 'E-mail'],
        'request.message' => ['az' => 'Mesaj', 'ru' => 'Сообщение', 'en' => 'Message'],
        'request.message_placeholder' => ['az' => 'Əlavə suallarınız və ya qeydləriniz (istəyə bağlı)', 'ru' => 'Дополнительные вопросы или комментарии (необязательно)', 'en' => 'Any questions or notes (optional)'],
        'request.submit' => ['az' => 'Müraciəti göndər', 'ru' => 'Отправить заявку', 'en' => 'Send request'],
        'request.note' => ['az' => 'Müraciət göndərdikdən sonra komandamız ödəniş və aktivləşdirmə üçün sizinlə əlaqə saxlayacaq.', 'ru' => 'После отправки заявки наша команда свяжется с вами для оплаты и активации.', 'en' => 'Once you send the request, our team will contact you about payment and activation.'],
        'request.done_title' => ['az' => 'Müraciət göndərildi', 'ru' => 'Заявка отправлена', 'en' => 'Request sent'],
        'request.done_close' => ['az' => 'Bağla', 'ru' => 'Закрыть', 'en' => 'Close'],

        // ── backend responses (TenderController) ─────────────────────────────
        'access.sent' => ['az' => 'Müraciətiniz göndərildi. Ödəniş və aktivləşdirmə üçün sizinlə əlaqə saxlayacağıq.', 'ru' => 'Ваша заявка отправлена. Мы свяжемся с вами для оплаты и активации.', 'en' => 'Your request has been sent. We will contact you about payment and activation.'],
        'access.already_pending' => ['az' => 'Müraciətiniz artıq qəbul edilib və baxılır.', 'ru' => 'Ваша заявка уже принята и находится на рассмотрении.', 'en' => 'Your request has already been received and is being reviewed.'],
        'access.already_active' => ['az' => 'Sizin artıq aktiv paketiniz var.', 'ru' => 'У вас уже есть активный пакет.', 'en' => 'You already have an active package.'],
        'save.added' => ['az' => 'Tender yadda saxlanıldı.', 'ru' => 'Тендер сохранён.', 'en' => 'Tender saved.'],
        'save.removed' => ['az' => 'Tender yadda saxlanılanlardan çıxarıldı.', 'ru' => 'Тендер удалён из сохранённых.', 'en' => 'Tender removed from saved.'],
    ],

    'notifications' => [
        'tender_access.subject' => ['az' => 'Tenderlərə giriş aktivləşdirildi', 'ru' => 'Доступ к тендерам активирован', 'en' => 'Tender access activated'],
        'tender_access.line' => ['az' => 'Ödənişli paketiniz aktivləşdirildi. Tenderlərə giriş :date tarixinədək açıqdır.', 'ru' => 'Ваш платный пакет активирован. Доступ к тендерам открыт до :date.', 'en' => 'Your paid package has been activated. Tender access is open until :date.'],
        'tender_access.action' => ['az' => 'Tenderlərə keç', 'ru' => 'Перейти к тендерам', 'en' => 'Go to tenders'],
    ],
];
