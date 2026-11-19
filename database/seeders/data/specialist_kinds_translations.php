<?php

declare(strict_types=1);

/*
 * Copy for the two specialist kinds the client added — «Briqadir» (foreman with a crew)
 * and «Makler» (real-estate agent). See docs/specialist-kinds.md.
 *
 * Read by TranslationsSeeder (fresh installs) and by the
 * 2026_11_19_120300_add_specialist_kinds_translations migration (existing databases).
 *
 * @return array<string, array<string, array{az: string, ru: string, en: string}>>
 */
return [
    'specialists' => [
        'category.foreman' => ['az' => 'Briqadirlər', 'ru' => 'Бригадиры', 'en' => 'Foremen'],
        'category.realtor' => ['az' => 'Maklerlər', 'ru' => 'Риелторы', 'en' => 'Realtors'],
        'group.foreman' => ['az' => 'Briqadirlər', 'ru' => 'Бригадиры', 'en' => 'Foremen'],
        'group.foreman_tagline' => ['az' => 'Öz usta dəstəsi ilə işi bütövlükdə götürən briqadirlər', 'ru' => 'Бригадиры, которые берут работу целиком со своей бригадой', 'en' => 'Foremen who take the whole job with their own crew'],
        'group.realtor' => ['az' => 'Maklerlər', 'ru' => 'Риелторы', 'en' => 'Realtors'],
        'group.realtor_tagline' => ['az' => 'Mənzil, ev və obyektlərin alqı-satqısı və kirayəsi', 'ru' => 'Покупка, продажа и аренда квартир, домов и объектов', 'en' => 'Buying, selling and renting flats, houses and premises'],
        'card.crew_size' => ['az' => 'Briqada: :count nəfər', 'ru' => 'Бригада: :count чел.', 'en' => 'Crew: :count people'],
        'card.agency' => ['az' => 'Agentlik: :name', 'ru' => 'Агентство: :name', 'en' => 'Agency: :name'],
    ],

    'register' => [
        'roles.foreman.title' => ['az' => 'Briqadir', 'ru' => 'Бригадир', 'en' => 'Foreman'],
        'roles.foreman.desc' => ['az' => 'Usta dəstənlə işi bütövlükdə götür.', 'ru' => 'Берите заказы целиком со своей бригадой.', 'en' => 'Take whole jobs with your crew.'],
        'roles.realtor.title' => ['az' => 'Makler', 'ru' => 'Риелтор', 'en' => 'Realtor'],
        'roles.realtor.desc' => ['az' => 'Əmlak sat, kirayə ver, müştəri tap.', 'ru' => 'Продавайте и сдавайте недвижимость.', 'en' => 'Sell and let property, find clients.'],
        'form.kind_unavailable' => ['az' => 'Bu hesab növü üzrə qeydiyyat hazırda mümkün deyil.', 'ru' => 'Регистрация с этим типом аккаунта сейчас недоступна.', 'en' => 'Registration for this account type is not available right now.'],
    ],

    'specialist' => [
        'crew.eyebrow' => ['az' => 'Briqada', 'ru' => 'Бригада', 'en' => 'Crew'],
        'crew.title' => ['az' => 'Usta dəstəsi', 'ru' => 'Бригада мастеров', 'en' => 'The crew'],
        'crew.size_label' => ['az' => 'Heyət', 'ru' => 'Состав', 'en' => 'Size'],
        'crew.size_value' => ['az' => ':count nəfər', 'ru' => ':count чел.', 'en' => ':count people'],
        'crew.trades_label' => ['az' => 'Dəstənin ixtisasları', 'ru' => 'Специализации бригады', 'en' => 'Trades in the crew'],
        'crew.members_label' => ['az' => 'Dəstə üzvləri', 'ru' => 'Состав бригады', 'en' => 'Crew members'],
        'crew.member_years' => ['az' => ':years il təcrübə', 'ru' => 'опыт :years лет', 'en' => ':years yrs experience'],
        'crew.full_projects' => ['az' => 'Tam layihə götürür', 'ru' => 'Берёт проекты под ключ', 'en' => 'Takes whole projects'],
        'realtor.eyebrow' => ['az' => 'Makler', 'ru' => 'Риелтор', 'en' => 'Realtor'],
        'realtor.title' => ['az' => 'Makler məlumatları', 'ru' => 'Информация о риелторе', 'en' => 'Realtor details'],
        'realtor.agency' => ['az' => 'Agentlik', 'ru' => 'Агентство', 'en' => 'Agency'],
        'realtor.license' => ['az' => 'Lisenziya №', 'ru' => 'Лицензия №', 'en' => 'Licence no.'],
        'realtor.deal_types' => ['az' => 'Əməliyyat növləri', 'ru' => 'Виды сделок', 'en' => 'Deal types'],
        'realtor.property_types' => ['az' => 'Əmlak növləri', 'ru' => 'Типы недвижимости', 'en' => 'Property types'],
        'realtor.service_areas' => ['az' => 'Xidmət göstərdiyi ərazilər', 'ru' => 'Районы работы', 'en' => 'Service areas'],
    ],

    'specialist-cabinet' => [
        'crew.heading' => ['az' => 'Briqada', 'ru' => 'Бригада', 'en' => 'Crew'],
        'crew.desc' => ['az' => 'Dəstənizin tərkibi və gördüyü işlər — profilinizdə göstərilir.', 'ru' => 'Состав вашей бригады и виды работ — показываются в профиле.', 'en' => 'Who is in your crew and what it does — shown on your profile.'],
        'crew.size' => ['az' => 'Dəstədə neçə nəfər var', 'ru' => 'Сколько человек в бригаде', 'en' => 'How many people in the crew'],
        'crew.full_projects_label' => ['az' => 'İş formatı', 'ru' => 'Формат работы', 'en' => 'Way of working'],
        'crew.full_projects' => ['az' => 'Tam layihə (açar təhvil) götürürəm', 'ru' => 'Беру проекты под ключ', 'en' => 'I take whole (turnkey) projects'],
        'crew.trades' => ['az' => 'Dəstənin əhatə etdiyi ixtisaslar', 'ru' => 'Специализации бригады', 'en' => 'Trades the crew covers'],
        'crew.members' => ['az' => 'Dəstə üzvləri', 'ru' => 'Состав бригады', 'en' => 'Crew members'],
        'crew.members_hint' => ['az' => 'Ad, ixtisas və təcrübə. Ən çox :max nəfər.', 'ru' => 'Имя, специализация и опыт. Не более :max человек.', 'en' => 'Name, trade and experience. Up to :max people.'],
        'crew.member_name' => ['az' => 'Ad, soyad', 'ru' => 'Имя, фамилия', 'en' => 'Full name'],
        'crew.member_specialty' => ['az' => 'İxtisas', 'ru' => 'Специализация', 'en' => 'Trade'],
        'crew.member_years' => ['az' => 'Təcrübə (il)', 'ru' => 'Опыт (лет)', 'en' => 'Experience (yrs)'],
        'crew.member_add' => ['az' => '+ Üzv əlavə et', 'ru' => '+ Добавить участника', 'en' => '+ Add member'],
        'crew.member_remove' => ['az' => 'Üzvü sil', 'ru' => 'Удалить участника', 'en' => 'Remove member'],
        'realtor.heading' => ['az' => 'Makler məlumatları', 'ru' => 'Информация о риелторе', 'en' => 'Realtor details'],
        'realtor.desc' => ['az' => 'Hansı əmlakla və hansı ərazilərdə işlədiyinizi göstərin.', 'ru' => 'Укажите, с какой недвижимостью и в каких районах вы работаете.', 'en' => 'Show which property you handle and where.'],
        'realtor.agency' => ['az' => 'Agentlik adı', 'ru' => 'Название агентства', 'en' => 'Agency name'],
        'realtor.agency_placeholder' => ['az' => 'İstəyə görə', 'ru' => 'Необязательно', 'en' => 'Optional'],
        'realtor.license' => ['az' => 'Lisenziya nömrəsi (istəyə görə)', 'ru' => 'Номер лицензии (необязательно)', 'en' => 'Licence number (optional)'],
        'realtor.deal_types' => ['az' => 'Əməliyyat növləri', 'ru' => 'Виды сделок', 'en' => 'Deal types'],
        'realtor.property_types' => ['az' => 'Əmlak növləri', 'ru' => 'Типы недвижимости', 'en' => 'Property types'],
        'realtor.service_areas' => ['az' => 'Xidmət göstərdiyiniz ərazilər', 'ru' => 'Районы, где вы работаете', 'en' => 'Areas you cover'],
        'realtor.service_areas_placeholder' => ['az' => 'Məs. Nəsimi, Yasamal, Xətai', 'ru' => 'Напр. Насими, Ясамал, Хатаи', 'en' => 'E.g. Nasimi, Yasamal, Khatai'],
    ],

    'specialist-kinds' => [
        'deal_type.sale' => ['az' => 'Alqı-satqı', 'ru' => 'Купля-продажа', 'en' => 'Sale'],
        'deal_type.rent' => ['az' => 'Kirayə', 'ru' => 'Аренда', 'en' => 'Rent'],
        'deal_type.daily_rent' => ['az' => 'Günlük kirayə', 'ru' => 'Посуточная аренда', 'en' => 'Daily rent'],
        'property_type.apartment' => ['az' => 'Mənzil', 'ru' => 'Квартира', 'en' => 'Apartment'],
        'property_type.house' => ['az' => 'Həyət evi', 'ru' => 'Частный дом', 'en' => 'House'],
        'property_type.office' => ['az' => 'Ofis', 'ru' => 'Офис', 'en' => 'Office'],
        'property_type.commercial' => ['az' => 'Obyekt', 'ru' => 'Коммерческий объект', 'en' => 'Commercial premises'],
        'property_type.land' => ['az' => 'Torpaq', 'ru' => 'Земельный участок', 'en' => 'Land'],
    ],
];
