<?php

/**
 * UI copy of the renovation calculator, in the wording of the client's TZ.
 *
 * Merged over the main TranslationsSeeder array, so a key here replaces the one of
 * the same name there — which is how the quick calculator's headings were moved from
 * the old four-parameter teaser to the three-input calculator the TZ specifies.
 */

return [
    'calculator' => [
        // TZ §4 and §41 — the exact texts the client asked for.
        'head.tag' => ['az' => 'SÜRƏTLİ KALKULYATOR', 'ru' => 'БЫСТРЫЙ КАЛЬКУЛЯТОР', 'en' => 'QUICK CALCULATOR'],
        'head.title' => ['az' => 'Təmirin qiymətini indi hesabla', 'ru' => 'Рассчитайте стоимость ремонта сейчас', 'en' => 'Calculate your renovation cost now'],
        'head.subtitle' => [
            'az' => 'Sahəni daxil edin, təmir növünü və qiymət kateqoriyasını seçin — təxmini məbləği dərhal görün.',
            'ru' => 'Укажите площадь, выберите тип ремонта и категорию — увидите примерную сумму сразу.',
            'en' => 'Enter the area, pick the renovation type and the price category — see the estimate instantly.',
        ],

        'type.label' => ['az' => 'Təmir növü', 'ru' => 'Тип ремонта', 'en' => 'Renovation type'],
        'type.white_box' => ['az' => 'White Box', 'ru' => 'White Box', 'en' => 'White Box'],
        'type.full_renovation' => ['az' => 'Tam təmir', 'ru' => 'Полный ремонт', 'en' => 'Full renovation'],

        'level.label' => ['az' => 'Qiymət kateqoriyası', 'ru' => 'Категория цены', 'en' => 'Price category'],

        'result.currency' => ['az' => 'AZN-dən', 'ru' => 'AZN и выше', 'en' => 'AZN and up'],
        'result.currency_plain' => ['az' => 'AZN', 'ru' => 'AZN', 'en' => 'AZN'],

        'details.area' => ['az' => 'Sahə', 'ru' => 'Площадь', 'en' => 'Area'],
        'details.type' => ['az' => 'Təmir növü', 'ru' => 'Тип ремонта', 'en' => 'Renovation type'],
        'details.segment' => ['az' => 'Kateqoriya', 'ru' => 'Категория', 'en' => 'Category'],
        'details.price_per_m2' => ['az' => '1 m² qiyməti', 'ru' => 'Цена за 1 м²', 'en' => 'Price per m²'],
        'details.total' => ['az' => 'Təxmini qiymət', 'ru' => 'Примерная цена', 'en' => 'Estimated price'],

        // TZ §29 — every invalid input has its own message; none of them is a price.
        'errors.required' => ['az' => 'Sahəni daxil edin', 'ru' => 'Укажите площадь', 'en' => 'Enter the area'],
        'errors.positive' => ['az' => 'Sahə 0-dan böyük olmalıdır.', 'ru' => 'Площадь должна быть больше 0.', 'en' => 'The area must be greater than 0.'],
        'errors.min' => ['az' => 'Minimum sahə :min m²-dir.', 'ru' => 'Минимальная площадь — :min м².', 'en' => 'The minimum area is :min m².'],
        'errors.max' => ['az' => 'Daha böyük obyekt üçün bizimlə əlaqə saxlayın.', 'ru' => 'Для более крупного объекта свяжитесь с нами.', 'en' => 'For a larger property, please contact us.'],
        'errors.unavailable' => ['az' => 'Qiymət dəqiqləşdirilir.', 'ru' => 'Цена уточняется.', 'en' => 'The price is being confirmed.'],

        // TZ §21 — the disclaimer under the result.
        'disclaimer' => [
            'az' => 'Hesablanan məbləğ təxminidir. Dəqiq qiymət layihə, obyektin mövcud vəziyyəti və seçilən materiallardan asılı olaraq müəyyən edilir.',
            'ru' => 'Рассчитанная сумма является ориентировочной. Точная цена определяется проектом, текущим состоянием объекта и выбранными материалами.',
            'en' => 'The calculated amount is approximate. The exact price depends on the project, the current state of the property and the chosen materials.',
        ],

        'actions.next' => ['az' => 'Dəqiq hesabla  →', 'ru' => 'Рассчитать точно  →', 'en' => 'Calculate precisely  →'],
    ],

    'home' => [
        // The mini panel now asks the same three questions as the calculator page, so
        // the old "assumed: apartment, 2 rooms" footnote is no longer true.
        'mini_calc.subtitle' => [
            'az' => 'Sahəni yazın, təmir növünü və qiymət kateqoriyasını seçin.',
            'ru' => 'Укажите площадь, выберите тип ремонта и категорию цены.',
            'en' => 'Enter the area, pick the renovation type and the price category.',
        ],
        'mini_calc.note' => [
            'az' => 'Məbləğ təxminidir. Dəqiq smeta üçün ətraflı kalkulyatordan istifadə edin.',
            'ru' => 'Сумма ориентировочная. Для точной сметы используйте подробный калькулятор.',
            'en' => 'The amount is approximate. Use the detailed calculator for an exact estimate.',
        ],
    ],

    'calculator-detailed' => [
        // Work catalog categories (master spec §5 / §10)
        'category.demolition' => ['az' => 'Demontaj', 'ru' => 'Демонтаж', 'en' => 'Demolition'],
        'category.walls' => ['az' => 'Divarlar', 'ru' => 'Стены', 'en' => 'Walls'],
        'category.floor' => ['az' => 'Döşəmə', 'ru' => 'Пол', 'en' => 'Floor'],
        'category.ceiling' => ['az' => 'Tavan', 'ru' => 'Потолок', 'en' => 'Ceiling'],
        'category.tiling' => ['az' => 'Kafel işləri', 'ru' => 'Плиточные работы', 'en' => 'Tiling'],
        'category.electrical' => ['az' => 'Elektrik', 'ru' => 'Электрика', 'en' => 'Electrical'],
        'category.plumbing' => ['az' => 'Santexnika', 'ru' => 'Сантехника', 'en' => 'Plumbing'],
        'category.hvac' => ['az' => 'İqlim sistemi', 'ru' => 'Климат-система', 'en' => 'HVAC'],
        'category.ventilation' => ['az' => 'Ventilyasiya', 'ru' => 'Вентиляция', 'en' => 'Ventilation'],
        'category.doors' => ['az' => 'Qapılar', 'ru' => 'Двери', 'en' => 'Doors'],
        'category.lighting' => ['az' => 'İşıqlandırma', 'ru' => 'Освещение', 'en' => 'Lighting'],
        'category.logistics' => ['az' => 'Logistika', 'ru' => 'Логистика', 'en' => 'Logistics'],
        'category.services' => ['az' => 'Əlavə xidmətlər', 'ru' => 'Дополнительные услуги', 'en' => 'Additional services'],
        'category.materials' => ['az' => 'Materiallar', 'ru' => 'Материалы', 'en' => 'Materials'],
        'category.reserve' => ['az' => 'Ehtiyat', 'ru' => 'Резерв', 'en' => 'Reserve'],

        // Room types (master spec §8)
        'room_types.living' => ['az' => 'Qonaq otağı', 'ru' => 'Гостиная', 'en' => 'Living room'],
        'room_types.bedroom' => ['az' => 'Yataq otağı', 'ru' => 'Спальня', 'en' => 'Bedroom'],
        'room_types.kitchen' => ['az' => 'Mətbəx', 'ru' => 'Кухня', 'en' => 'Kitchen'],
        'room_types.bathroom' => ['az' => 'Hamam otağı', 'ru' => 'Ванная комната', 'en' => 'Bathroom'],
        'room_types.wc' => ['az' => 'Sanitar qovşaq', 'ru' => 'Санузел', 'en' => 'WC'],
        'room_types.corridor' => ['az' => 'Dəhliz', 'ru' => 'Коридор', 'en' => 'Corridor'],
        'room_types.office' => ['az' => 'Kabinet', 'ru' => 'Кабинет', 'en' => 'Office'],
        'room_types.commercial' => ['az' => 'Kommersiya sahəsi', 'ru' => 'Коммерческая зона', 'en' => 'Commercial space'],
        'room_types.other' => ['az' => 'Digər otaq', 'ru' => 'Другое помещение', 'en' => 'Other room'],

        // Current surface state — the left side of the dependency engine (§9)
        'states.shell' => ['az' => 'Mayak / qara divar', 'ru' => 'Маяк / голая стена', 'en' => 'Shell / bare wall'],
        'states.plaster_ready' => ['az' => 'Suvaq hazırdır', 'ru' => 'Штукатурка готова', 'en' => 'Plaster ready'],
        'states.paint_ready' => ['az' => 'Boyaya hazırdır', 'ru' => 'Готово под покраску', 'en' => 'Ready for paint'],
        'states.old_finish' => ['az' => 'Köhnə üzlük var', 'ru' => 'Старая отделка', 'en' => 'Old finish'],
        'states.old_tile' => ['az' => 'Köhnə kafel var', 'ru' => 'Старая плитка', 'en' => 'Old tile'],
        'states.rough_floor' => ['az' => 'Qaba döşəmə', 'ru' => 'Черновой пол', 'en' => 'Rough floor'],

        // Object condition (§7)
        'conditions.shell' => ['az' => 'Təmirsiz (mayak)', 'ru' => 'Без ремонта (маяк)', 'en' => 'Unrenovated (shell)'],
        'conditions.old_renovation' => ['az' => 'Köhnə təmir var', 'ru' => 'Старый ремонт', 'en' => 'Old renovation'],
        'conditions.partial' => ['az' => 'Qismən təmirli', 'ru' => 'Частичный ремонт', 'en' => 'Partially renovated'],
        'conditions.ready_base' => ['az' => 'Hazır qara təmir', 'ru' => 'Черновая готова', 'en' => 'Rough works done'],

        // Target finishes — the right side of the dependency engine
        'finishes.paint' => ['az' => 'Boya', 'ru' => 'Краска', 'en' => 'Paint'],
        'finishes.wallpaper' => ['az' => 'Divar kağızı', 'ru' => 'Обои', 'en' => 'Wallpaper'],
        'finishes.decor_plaster' => ['az' => 'Dekorativ suvaq', 'ru' => 'Декоративная штукатурка', 'en' => 'Decorative plaster'],
        'finishes.wall_tile' => ['az' => 'Divar kafeli', 'ru' => 'Настенная плитка', 'en' => 'Wall tile'],
        'finishes.wall_panel' => ['az' => 'Divar paneli', 'ru' => 'Стеновые панели', 'en' => 'Wall panels'],
        'finishes.wall_stone' => ['az' => 'Divar daşı', 'ru' => 'Камень на стене', 'en' => 'Wall stone'],
        'finishes.floor_tile' => ['az' => 'Döşəmə kafeli', 'ru' => 'Напольная плитка', 'en' => 'Floor tile'],
        'finishes.laminate' => ['az' => 'Laminat', 'ru' => 'Ламинат', 'en' => 'Laminate'],
        'finishes.spc' => ['az' => 'SPC', 'ru' => 'SPC', 'en' => 'SPC'],
        'finishes.parquet' => ['az' => 'Parket', 'ru' => 'Паркет', 'en' => 'Parquet'],
        'finishes.floor_stone' => ['az' => 'Təbii daş', 'ru' => 'Натуральный камень', 'en' => 'Natural stone'],
        'finishes.ceiling_paint' => ['az' => 'Tavan boyası', 'ru' => 'Покраска потолка', 'en' => 'Ceiling paint'],
        'finishes.gypsum_flat' => ['az' => 'Birsəviyyəli alçıpan', 'ru' => 'Одноуровневый ГКЛ', 'en' => 'Single-level plasterboard'],
        'finishes.gypsum_complex' => ['az' => 'Çoxsəviyyəli alçıpan', 'ru' => 'Многоуровневый ГКЛ', 'en' => 'Multi-level plasterboard'],
        'finishes.stretch_ceiling' => ['az' => 'Gərilmə tavan', 'ru' => 'Натяжной потолок', 'en' => 'Stretch ceiling'],

        // HVAC system types (§11.3)
        'hvac.none' => ['az' => 'Lazım deyil', 'ru' => 'Не требуется', 'en' => 'Not required'],
        'hvac.split' => ['az' => 'Split', 'ru' => 'Сплит', 'en' => 'Split'],
        'hvac.multi_split' => ['az' => 'Multi-split', 'ru' => 'Мульти-сплит', 'en' => 'Multi-split'],
        'hvac.ducted' => ['az' => 'Kanal sistemi', 'ru' => 'Канальная система', 'en' => 'Ducted system'],
        'hvac.vrf' => ['az' => 'VRF / VRV', 'ru' => 'VRF / VRV', 'en' => 'VRF / VRV'],
        'hvac.central' => ['az' => 'Mərkəzi sistem', 'ru' => 'Центральная система', 'en' => 'Central system'],

        // Room geometry — the TZ asks for real opening sizes, not just counts (§6)
        'object.elevator' => ['az' => 'Yük lifti var', 'ru' => 'Есть грузовой лифт', 'en' => 'Freight lift available'],
        // The old label read "Dizayn layihəsi var" — "I already have one" — while the
        // switch adds the design fee. Master spec §7 asks the opposite question, and
        // the question is what the price answers.
        'object.design_needed' => ['az' => 'Dizayn layihəsi lazımdır', 'ru' => 'Нужен дизайн-проект', 'en' => 'Design project needed'],
        'object.segment' => ['az' => 'Material səviyyəsi', 'ru' => 'Уровень материалов', 'en' => 'Material level'],

        'rooms.type' => ['az' => 'Otağın növü', 'ru' => 'Тип помещения', 'en' => 'Room type'],
        'rooms.length' => ['az' => 'Uzunluq', 'ru' => 'Длина', 'en' => 'Length'],
        'rooms.width' => ['az' => 'En', 'ru' => 'Ширина', 'en' => 'Width'],
        'rooms.door_width' => ['az' => 'Qapı eni', 'ru' => 'Ширина двери', 'en' => 'Door width'],
        'rooms.door_height' => ['az' => 'Qapı hünd.', 'ru' => 'Высота двери', 'en' => 'Door height'],
        'rooms.window_width' => ['az' => 'Pəncərə eni', 'ru' => 'Ширина окна', 'en' => 'Window width'],
        'rooms.window_height' => ['az' => 'Pəncərə hünd.', 'ru' => 'Высота окна', 'en' => 'Window height'],
        'rooms.install_door' => ['az' => 'Yeni qapı quraşdırılsın', 'ru' => 'Установить новую дверь', 'en' => 'Install a new door'],
        'rooms.state' => ['az' => 'Səthin hazırkı vəziyyəti', 'ru' => 'Текущее состояние поверхности', 'en' => 'Current surface state'],
        'rooms.ceiling_cover' => ['az' => 'Tavan üzlüyü', 'ru' => 'Отделка потолка', 'en' => 'Ceiling finish'],
        'rooms.floor_short' => ['az' => 'döşəmə', 'ru' => 'пол', 'en' => 'floor'],
        'rooms.derived' => [
            'az' => 'Sistem hesabladı: döşəmə :floor m² · qapı-pəncərə çıxılmaqla divar :wall m² · plintus :skirting p.m',
            'ru' => 'Система рассчитала: пол :floor м² · стены за вычетом проёмов :wall м² · плинтус :skirting п.м',
            'en' => 'Calculated: floor :floor m² · walls minus openings :wall m² · skirting :skirting lm',
        ],

        // Works step — prices are deliberately absent here (master spec §10, §20)
        'works.suggested' => ['az' => 'Sistem tövsiyə edir', 'ru' => 'Система рекомендует', 'en' => 'Suggested by the system'],
        'works.no_prices' => [
            'az' => 'Bu addımda qiymət göstərilmir — məbləğ «Cari smeta» blokunda və yekun smetada görünür.',
            'ru' => 'На этом шаге цены не показываются — сумма видна в блоке «Текущая смета» и в итоговой смете.',
            'en' => 'Prices are not shown at this step — the amount appears in the current estimate and in the final one.',
        ],
        'works.removed_warning' => [
            'az' => 'Tövsiyə olunan mərhələni çıxardınız — texnoloji ardıcıllıq pozula bilər.',
            'ru' => 'Вы убрали рекомендованный этап — технологическая цепочка может нарушиться.',
            'en' => 'You removed a suggested step — the technological sequence may be broken.',
        ],

        // Engineering modules
        'modules.electrical' => ['az' => 'Elektrik nöqtələri', 'ru' => 'Электрические точки', 'en' => 'Electrical points'],
        'modules.plumbing' => ['az' => 'Santexnika avadanlığı', 'ru' => 'Сантехнические приборы', 'en' => 'Plumbing fixtures'],
        'modules.hvac' => ['az' => 'Kondisioner sistemi', 'ru' => 'Система кондиционирования', 'en' => 'Air conditioning'],
        'modules.hvac_units' => ['az' => 'Daxili blokların sayı', 'ru' => 'Внутренних блоков', 'en' => 'Indoor units'],
        'modules.hvac_outdoor' => ['az' => 'Xarici blokların sayı', 'ru' => 'Наружных блоков', 'en' => 'Outdoor units'],
        'modules.hvac_route' => ['az' => 'Ümumi trassa uzunluğu', 'ru' => 'Общая длина трассы', 'en' => 'Total route length'],
        'modules.hvac_area' => ['az' => 'Kondisionerlənən sahə', 'ru' => 'Кондиционируемая площадь', 'en' => 'Conditioned area'],
        'modules.lighting' => ['az' => 'İşıqlandırıcılar', 'ru' => 'Светильники', 'en' => 'Light fittings'],
        'modules.lighting_spots' => ['az' => 'Nöqtəvi işıq', 'ru' => 'Точечные светильники', 'en' => 'Spotlights'],
        'modules.lighting_pendants' => ['az' => 'Asma işıq', 'ru' => 'Подвесные светильники', 'en' => 'Pendant lights'],
        'modules.lighting_chandeliers' => ['az' => 'Çilçıraq', 'ru' => 'Люстры', 'en' => 'Chandeliers'],
        'modules.final_cleaning' => ['az' => 'Yekun təmizlik', 'ru' => 'Финальная уборка', 'en' => 'Final cleaning'],
        'modules.login_required' => [
            'az' => 'Səbətə əlavə etmək üçün hesabınıza daxil olun.',
            'ru' => 'Чтобы добавить в корзину, войдите в аккаунт.',
            'en' => 'Sign in to add these items to your cart.',
        ],
        'modules.services' => ['az' => 'Əlavə xidmətlər', 'ru' => 'Дополнительные услуги', 'en' => 'Additional services'],
        'modules.points.sockets' => ['az' => 'Rozetka', 'ru' => 'Розетки', 'en' => 'Sockets'],
        'modules.points.switches' => ['az' => 'Açar', 'ru' => 'Выключатели', 'en' => 'Switches'],
        'modules.points.lights' => ['az' => 'Tavan işığı', 'ru' => 'Потолочный свет', 'en' => 'Ceiling lights'],
        'modules.points.water_points' => ['az' => 'Su nöqtəsi', 'ru' => 'Точки воды', 'en' => 'Water points'],
        'modules.points.sewer_points' => ['az' => 'Kanalizasiya nöqtəsi', 'ru' => 'Точки канализации', 'en' => 'Sewer points'],

        // Materials step — the three modes of master spec §12
        'materials.mode.fallback' => ['az' => 'Kateqoriya üzrə orta qiymət', 'ru' => 'Средняя цена категории', 'en' => 'Category average price'],
        'materials.mode.catalog' => ['az' => 'Kataloqdan məhsul', 'ru' => 'Товар из каталога', 'en' => 'Catalog product'],
        'materials.mode.excluded' => ['az' => 'Materialı özüm alacağam', 'ru' => 'Материал куплю сам', 'en' => 'I will buy it myself'],
        'materials.required' => ['az' => 'Tələb olunan miqdar: :qty :unit', 'ru' => 'Требуется: :qty :unit', 'en' => 'Required: :qty :unit'],
        'cart.counted_as_material' => [
            'az' => 'Material kimi hesablanıb',
            'ru' => 'Учтён как материал',
            'en' => 'Counted as a material',
        ],
        'materials.packages' => ['az' => ':qty paket', 'ru' => ':qty упаковок', 'en' => ':qty packs'],

        // Estimate summary rows the engine names itself
        'summary.contingency' => ['az' => 'Maliyyə ehtiyatı', 'ru' => 'Финансовый резерв', 'en' => 'Financial contingency'],
        'summary.vat' => ['az' => 'ƏDV', 'ru' => 'НДС', 'en' => 'VAT'],
        'summary.works' => ['az' => 'İşlər', 'ru' => 'Работы', 'en' => 'Works'],
        'summary.materials' => ['az' => 'Materiallar', 'ru' => 'Материалы', 'en' => 'Materials'],
        'summary.logistics' => ['az' => 'Logistika', 'ru' => 'Логистика', 'en' => 'Logistics'],
        'summary.services' => ['az' => 'Əlavə xidmətlər', 'ru' => 'Дополнительные услуги', 'en' => 'Additional services'],
        'summary.grand_total' => ['az' => 'YEKUN', 'ru' => 'ИТОГО', 'en' => 'TOTAL'],
        'summary.by_category' => ['az' => 'Kateqoriyalar üzrə', 'ru' => 'По категориям', 'en' => 'By category'],
        'summary.by_room' => ['az' => 'Otaqlar üzrə', 'ru' => 'По помещениям', 'en' => 'By room'],

        // Precision and price-availability notices (§5.1, §19)
        'notice.estimated' => [
            'az' => 'Təxmini. Dəqiq qiymət layihə əsasında müəyyən edilir.',
            'ru' => 'Ориентировочно. Точная цена определяется по проекту.',
            'en' => 'Approximate. The exact price is set by the project.',
        ],
        'notice.price_missing' => ['az' => 'Qiymət dəqiqləşdirilir', 'ru' => 'Цена уточняется', 'en' => 'Price to be confirmed'],
        'notice.minimum_charge' => ['az' => 'minimum sifariş', 'ru' => 'минимальный заказ', 'en' => 'minimum charge'],
        'notice.logistics_incomplete' => [
            'az' => 'Bəzi məhsulların çəki/həcm məlumatı yoxdur — logistika təxminidir.',
            'ru' => 'У части товаров нет веса/объёма — логистика ориентировочная.',
            'en' => 'Some products have no weight or volume — logistics is approximate.',
        ],
        'side.materials' => ['az' => 'Materiallar', 'ru' => 'Материалы', 'en' => 'Materials'],
        'side.services' => ['az' => 'Əlavə xidmətlər', 'ru' => 'Дополнительные услуги', 'en' => 'Additional services'],
        // Covers delivery, manual lifting AND debris removal, so it stays the broad word.
        'side.logistics' => ['az' => 'Logistika', 'ru' => 'Логистика', 'en' => 'Logistics'],
        'side.logistics_unknown' => [
            'az' => 'Seçilmiş məhsulların çəkisi göstərilmədiyi üçün hesablana bilmir.',
            'ru' => 'Не рассчитывается: у выбранных товаров не указан вес.',
            'en' => 'Cannot be calculated: the chosen products have no weight on file.',
        ],
        'side.vat' => ['az' => 'ƏDV (:percent%)', 'ru' => 'НДС (:percent%)', 'en' => 'VAT (:percent%)'],
        'side.grand_total' => ['az' => 'Yekun', 'ru' => 'Итого', 'en' => 'Total'],
        'side.contingency' => ['az' => 'Maliyyə ehtiyatı (:percent%)', 'ru' => 'Финансовый резерв (:percent%)', 'en' => 'Financial contingency (:percent%)'],
        'side.calculating' => ['az' => 'Hesablanır…', 'ru' => 'Расчёт…', 'en' => 'Calculating…'],
        'side.estimate_only' => ['az' => 'Təxmini smeta — konkret məhsullar seçilməyib', 'ru' => 'Ориентировочная смета — товары не выбраны', 'en' => 'Approximate estimate — no products chosen yet'],
        'error.failed' => ['az' => 'Hesablama alınmadı. Bir qədər sonra yenidən cəhd edin.', 'ru' => 'Расчёт не выполнен. Попробуйте позже.', 'en' => 'The calculation failed. Please try again shortly.'],

        'actions.reset' => ['az' => 'Sıfırla', 'ru' => 'Сбросить', 'en' => 'Reset'],
        'alerts.turnkey_name' => ['az' => 'Adınız və soyadınız:', 'ru' => 'Ваше имя и фамилия:', 'en' => 'Your full name:'],
        'alerts.turnkey_phone' => ['az' => 'Telefon nömrəniz:', 'ru' => 'Ваш номер телефона:', 'en' => 'Your phone number:'],
        'alerts.turnkey_phone_required' => [
            'az' => 'Sizinlə əlaqə saxlaya bilməyimiz üçün telefon nömrəsi lazımdır.',
            'ru' => 'Чтобы связаться с вами, нужен номер телефона.',
            'en' => 'We need a phone number to be able to call you back.',
        ],
        'alerts.reset_confirm' => [
            'az' => 'Bütün daxil etdiyiniz məlumatlar silinsin və kalkulyator başdan başlasın?',
            'ru' => 'Удалить все введённые данные и начать расчёт заново?',
            'en' => 'Delete everything you entered and start the calculator over?',
        ],

        'notice.area_mismatch' => [
            'az' => 'Obyektin sahəsi otaqların cəmindən fərqlənir (:deviation%).',
            'ru' => 'Площадь объекта отличается от суммы комнат (:deviation%).',
            'en' => 'The stated area differs from the sum of the rooms (:deviation%).',
        ],

        // The per-room point editor on the rooms step.
        'rooms.points_label' => [
            'az' => 'Elektrik və santexnika nöqtələri',
            'ru' => 'Точки электрики и сантехники',
            'en' => 'Electrical and plumbing points',
        ],
        'points.sockets' => ['az' => 'Rozetka', 'ru' => 'Розетки', 'en' => 'Sockets'],
        'points.switches' => ['az' => 'Açar (vıklyuçatel)', 'ru' => 'Выключатели', 'en' => 'Switches'],
        'points.lights' => ['az' => 'Tavan işığı', 'ru' => 'Потолочный свет', 'en' => 'Ceiling lights'],
        'points.wall_lights' => ['az' => 'Bra / divar işığı', 'ru' => 'Бра', 'en' => 'Wall lights'],
        'points.tv' => ['az' => 'TV nöqtəsi', 'ru' => 'TV-точки', 'en' => 'TV points'],
        'points.data' => ['az' => 'İnternet nöqtəsi', 'ru' => 'Интернет-точки', 'en' => 'Data points'],
        'points.ac_power' => ['az' => 'Kondisioner xətti', 'ru' => 'Линия кондиционера', 'en' => 'AC power line'],
        'points.appliance' => ['az' => 'Məişət texnikası xətti', 'ru' => 'Линии бытовой техники', 'en' => 'Appliance lines'],
        'points.water_points' => ['az' => 'Su nöqtəsi', 'ru' => 'Точки воды', 'en' => 'Water points'],
        'points.sewer_points' => ['az' => 'Kanalizasiya nöqtəsi', 'ru' => 'Точки канализации', 'en' => 'Sewer points'],

        // The PDF button downloads a real file now, so the label says so.
        'actions.pdf' => ['az' => 'PDF yüklə', 'ru' => 'Скачать PDF', 'en' => 'Download PDF'],
        'notice.valid_until' => [
            'az' => 'Qiymətlər :date tarixinədək qüvvədədir',
            'ru' => 'Цены действительны до :date',
            'en' => 'Prices are valid until :date',
        ],
        'notice.pdf_disclaimer' => [
            'az' => 'Smeta təxminidir və ilkin məlumatlar əsasında hesablanıb. Yekun qiymət obyektə baxışdan sonra dəqiqləşdirilir.',
            'ru' => 'Смета ориентировочная и рассчитана по исходным данным. Итоговая цена уточняется после осмотра объекта.',
            'en' => 'This estimate is approximate and based on the details provided. The final price is confirmed after a site visit.',
        ],
    ],
];
