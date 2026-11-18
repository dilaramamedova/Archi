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

        // TZ 17.11.2026 «Qiymətin izahı» — what the per-m² price covers, under the sum.
        'result.includes' => [
            'az' => 'Qiymətə işlər, :level səviyyəli materiallar və podratçının idarəetməsi daxildir',
            'ru' => 'В цену входят работы, материалы уровня «:level» и управление подрядчика',
            'en' => 'The price includes labour, :level-level materials and contractor management',
        ],
        // The White Box / finishing composition approved by Archi on 17.11.2026.
        'composition.title' => ['az' => 'White Box və tam təmirin tərkibi', 'ru' => 'Состав White Box и полного ремонта', 'en' => 'What White Box and full renovation include'],
        'composition.section' => ['az' => 'Bölmə', 'ru' => 'Раздел', 'en' => 'Section'],
        'composition.white_box' => ['az' => 'White Box', 'ru' => 'White Box', 'en' => 'White Box'],
        'composition.finish' => ['az' => 'Bitirmə mərhələsi', 'ru' => 'Чистовой этап', 'en' => 'Finishing stage'],
        'composition.note' => [
            'az' => 'Tam təmir = White Box + bitirmə mərhələsi. Dizayn layihəsi və müəllif nəzarəti ayrıca xidmətdir.',
            'ru' => 'Полный ремонт = White Box + чистовой этап. Дизайн-проект и авторский надзор — отдельные услуги.',
            'en' => 'Full renovation = White Box + the finishing stage. The design project and author supervision are separate services.',
        ],
        'composition.walls.name' => ['az' => 'Divarlar', 'ru' => 'Стены', 'en' => 'Walls'],
        'composition.walls.wb' => [
            'az' => 'arakəsmələr, astar, suvaq, tor, şpaklyovka, zımparalama, son astar, rəngləmə (iş və boya)',
            'ru' => 'перегородки, грунт, штукатурка, сетка, шпаклёвка, шлифовка, финишный грунт, покраска (работа и краска)',
            'en' => 'partitions, primer, plaster, mesh, filler, sanding, final primer, painting (labour and paint)',
        ],
        'composition.walls.fin' => [
            'az' => 'divar kağızı, dekorativ örtüklər, panellər, daş, kafel (iş və material)',
            'ru' => 'обои, декоративные покрытия, панели, камень, плитка (работа и материал)',
            'en' => 'wallpaper, decorative coatings, panels, stone, tiles (labour and material)',
        ],
        'composition.floor.name' => ['az' => 'Döşəmə', 'ru' => 'Пол', 'en' => 'Floor'],
        'composition.floor.wb' => [
            'az' => 'şap, özüyayılan döşəmə, səs izolyasiyası, isti döşəmə',
            'ru' => 'стяжка, наливной пол, шумоизоляция, тёплый пол',
            'en' => 'screed, self-levelling floor, sound insulation, underfloor heating',
        ],
        'composition.floor.fin' => [
            'az' => 'döşəmə örtükləri, altlıq, plintus',
            'ru' => 'напольные покрытия, подложка, плинтус',
            'en' => 'floor coverings, underlay, skirting',
        ],
        'composition.ceiling.name' => ['az' => 'Tavan', 'ru' => 'Потолок', 'en' => 'Ceiling'],
        'composition.ceiling.wb' => [
            'az' => 'bütün işlər: gipskarton / suvaq, şpaklyovka, astar, rəngləmə',
            'ru' => 'все работы: гипсокартон / штукатурка, шпаклёвка, грунт, покраска',
            'en' => 'all works: plasterboard / plaster, filler, primer, painting',
        ],
        'composition.ceiling.fin' => [
            'az' => 'dartma tavan, karnizlər',
            'ru' => 'натяжной потолок, карнизы',
            'en' => 'stretch ceiling, cornices',
        ],
        'composition.bathroom.name' => ['az' => 'Sanitar qovşaq', 'ru' => 'Санузел', 'en' => 'Bathroom'],
        'composition.bathroom.wb' => [
            'az' => 'döşəmə və divarların hidroizolyasiyası',
            'ru' => 'гидроизоляция пола и стен',
            'en' => 'waterproofing of the floor and walls',
        ],
        'composition.bathroom.fin' => [
            'az' => 'kafel altı hazırlıq, kafel, yapışqan, ara dolğusu, germetik',
            'ru' => 'подготовка под плитку, плитка, клей, затирка, герметик',
            'en' => 'preparation for tiles, tiles, adhesive, grout, sealant',
        ],
        'composition.electrics.name' => ['az' => 'Elektrik', 'ru' => 'Электрика', 'en' => 'Electrics'],
        'composition.electrics.wb' => [
            'az' => 'bütün nöqtələr, kabel, qofro boru, avtomat açarlı elektrik paneli, işıqlandırma və kondisioner üçün çıxışlar',
            'ru' => 'все точки, кабель, гофра, электрощит с автоматами, выводы под освещение и кондиционеры',
            'en' => 'all points, cable, conduit, distribution board with breakers, outlets for lighting and air conditioning',
        ],
        'composition.electrics.fin' => [
            'az' => 'rozetka və açarların üz hissələri, işıqlandırıcılar və onların quraşdırılması',
            'ru' => 'лицевые части розеток и выключателей, светильники и их установка',
            'en' => 'socket and switch face plates, light fittings and their installation',
        ],
        'composition.plumbing.name' => ['az' => 'Santexnika', 'ru' => 'Сантехника', 'en' => 'Plumbing'],
        'composition.plumbing.wb' => [
            'az' => 'nöqtələr, borular və fitinqlər, cihazların quraşdırılması və məişət texnikasının qoşulması',
            'ru' => 'точки, трубы и фитинги, установка приборов и подключение бытовой техники',
            'en' => 'points, pipes and fittings, fixture installation and appliance connection',
        ],
        'composition.plumbing.fin' => [
            'az' => 'cihazların və qarışdırıcıların özləri',
            'ru' => 'сами приборы и смесители',
            'en' => 'the fixtures and mixer taps themselves',
        ],
        'composition.hvac.name' => ['az' => 'Kondisionerlər', 'ru' => 'Кондиционеры', 'en' => 'Air conditioning'],
        'composition.hvac.wb' => [
            'az' => 'yalnız çıxışlar (qidalanma, freon xətti, drenaj)',
            'ru' => 'только выводы (питание, фреоновая трасса, дренаж)',
            'en' => 'outlets only (power, refrigerant line, drain)',
        ],
        'composition.hvac.fin' => ['az' => 'blokların quraşdırılması', 'ru' => 'установка блоков', 'en' => 'unit installation'],
        'composition.openings.name' => ['az' => 'Qapı və pəncərələr', 'ru' => 'Двери и окна', 'en' => 'Doors and windows'],
        'composition.openings.wb' => [
            'az' => 'qapı yerlərinin hazırlanması, pəncərə yanlıqları (otkoslar)',
            'ru' => 'подготовка дверных проёмов, оконные откосы',
            'en' => 'door opening preparation, window reveals',
        ],
        'composition.openings.fin' => [
            'az' => 'çərçivə və quraşdırma ilə birlikdə qapılar, pəncərə altlıqları (podokonniklər)',
            'ru' => 'двери с коробкой и установкой, подоконники',
            'en' => 'doors with frames and installation, window sills',
        ],
        'composition.general.name' => ['az' => 'Ümumi', 'ru' => 'Общее', 'en' => 'General'],
        'composition.general.wb' => [
            'az' => 'sökülmə, tikinti tullantılarının daşınması, səthlərin mühafizəsi',
            'ru' => 'демонтаж, вывоз строительного мусора, защита поверхностей',
            'en' => 'demolition, construction waste removal, surface protection',
        ],
        'composition.general.fin' => ['az' => '—', 'ru' => '—', 'en' => '—'],
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

        // ---- Simplified calculator (2026-11): three surface states, three object
        // conditions, finishes as admin rows, modules derived from the rooms.
        'states.bare' => ['az' => 'Qara (mayak / qaba döşəmə)', 'ru' => 'Черновая (маяк / черновой пол)', 'en' => 'Bare (shell / rough floor)'],
        'states.prepared' => ['az' => 'Hazırdır (suvaq / stəjka var)', 'ru' => 'Подготовлена (штукатурка / стяжка)', 'en' => 'Prepared (plastered / screeded)'],
        'states.old' => ['az' => 'Köhnə üzlük var', 'ru' => 'Старая отделка', 'en' => 'Old finish'],
        'conditions.prepared' => ['az' => 'Qara təmir hazırdır', 'ru' => 'Черновая готова', 'en' => 'Rough works done'],
        'families.coat' => ['az' => 'Boya tipli', 'ru' => 'Покрасочный тип', 'en' => 'Coat'],
        'families.tile' => ['az' => 'Kafel tipli', 'ru' => 'Плиточный тип', 'en' => 'Tile'],
        'families.panel' => ['az' => 'Panel', 'ru' => 'Панели', 'en' => 'Panel'],
        'families.floating' => ['az' => 'Üzən döşəmə', 'ru' => 'Плавающий пол', 'en' => 'Floating floor'],
        'families.suspended' => ['az' => 'Asma tavan', 'ru' => 'Подвесной потолок', 'en' => 'Suspended ceiling'],
        'line.work' => ['az' => 'iş haqqı', 'ru' => 'работа', 'en' => 'labour'],
        'line.material' => ['az' => ':name — material', 'ru' => ':name — материал', 'en' => ':name — material'],
        'object.rewire' => ['az' => 'Elektrik tam yenilənsin', 'ru' => 'Полная замена электрики', 'en' => 'Full rewiring'],
        'object.rewire_hint' => [
            'az' => 'Şit, kabel və hər nöqtə yenidən çəkilir. Söndürsəniz yalnız nöqtələrin montajı hesablanır.',
            'ru' => 'Щит, кабель и каждая точка заново. Если выключить — считается только монтаж точек.',
            'en' => 'Panel, cable and every point anew. Switched off, only the points are installed.',
        ],
        'object.demolition_hint' => [
            'az' => 'Köhnə qapılar, santexnika, elektrik nöqtələri sökülür, tullantı daşınır.',
            'ru' => 'Снимаются старые двери, сантехника, электроточки, вывозится мусор.',
            'en' => 'Old doors, fixtures and points are removed and the debris is taken away.',
        ],
        'rooms.fixtures_label' => ['az' => 'Santexnika avadanlığı', 'ru' => 'Сантехника', 'en' => 'Sanitary fixtures'],
        'fixtures.toilet' => ['az' => 'Unitaz', 'ru' => 'Унитаз', 'en' => 'Toilet'],
        'fixtures.basin' => ['az' => 'Əlüzyuyan', 'ru' => 'Раковина', 'en' => 'Basin'],
        'fixtures.shower' => ['az' => 'Duş', 'ru' => 'Душ', 'en' => 'Shower'],
        'fixtures.bathtub' => ['az' => 'Vanna', 'ru' => 'Ванна', 'en' => 'Bathtub'],
        // "Qabyuyan" reads as the dishwasher, which is now its own fixture.
        'fixtures.kitchen_sink' => ['az' => 'Mətbəx yuyucusu', 'ru' => 'Кухонная мойка', 'en' => 'Kitchen sink'],
        'fixtures.mixer' => ['az' => 'Qarışdırıcı', 'ru' => 'Смеситель', 'en' => 'Mixer tap'],
        'points.ac' => ['az' => 'Kondisioner', 'ru' => 'Кондиционер', 'en' => 'Air conditioner'],
        'rooms.state_hint' => [
            'az' => 'Obyektin vəziyyətindən gəlir; bu otaq fərqlidirsə dəyişin.',
            'ru' => 'Берётся из состояния объекта; измените, если эта комната отличается.',
            'en' => 'Taken from the object condition; change it if this room differs.',
        ],
        'rooms.derived_short' => [
            'az' => 'döşəmə :floor m² · divar :wall m²',
            'ru' => 'пол :floor м² · стены :wall м²',
            'en' => 'floor :floor m² · walls :wall m²',
        ],
        'works.auto_hint' => [
            'az' => 'Siyahı otaqlardan avtomatik qurulub: hazırlıq, üzlük, elektrik, santexnika, kondisioner, qapılar, demontaj və çatdırılma. Lazım olmayanı söndürün.',
            'ru' => 'Список собран из комнат автоматически: подготовка, отделка, электрика, сантехника, кондиционеры, двери, демонтаж и доставка. Лишнее выключите.',
            'en' => 'Built from the rooms automatically: preparation, finishes, electrics, plumbing, air conditioning, doors, demolition and delivery. Switch off what you do not need.',
        ],
        'side.delivery_note' => [
            'az' => 'Çatdırılma sahəyə görə təxmin edilir.',
            'ru' => 'Доставка оценивается по площади.',
            'en' => 'Delivery is estimated from the area.',
        ],

        // ---- TZ 17.11.2026: renovation level, stages, contractor margin, «Sadə»
        // points, openings, extra finishes. Keys marked (backend) are also read by
        // the PHP engine / PDF, not only by calculator-detailed.js.
        'room_types.kitchen_living' => ['az' => 'Mətbəx-qonaq otağı', 'ru' => 'Кухня-гостиная', 'en' => 'Kitchen-living room'], // (backend)
        'levels.full' => ['az' => 'Tam təmir', 'ru' => 'Полный ремонт', 'en' => 'Full renovation'], // (backend)
        'levels.white_box' => ['az' => 'White Box', 'ru' => 'White Box', 'en' => 'White Box'], // (backend)
        'stages.white_box' => ['az' => 'White Box', 'ru' => 'White Box', 'en' => 'White Box'], // (backend)
        'stages.finish' => ['az' => 'Bitirmə', 'ru' => 'Чистовая', 'en' => 'Finishing'], // (backend)
        // The stage badge on a work row of step 3 — short, it sits next to the volume.
        'stages_short.white_box' => ['az' => 'WB', 'ru' => 'WB', 'en' => 'WB'],
        'stages_short.finish' => ['az' => 'Bitirmə', 'ru' => 'Чистовая', 'en' => 'Finish'],

        'summary.white_box' => ['az' => 'White Box', 'ru' => 'White Box', 'en' => 'White Box'], // (backend)
        'summary.finish_stage' => ['az' => 'Bitirmə mərhələsi', 'ru' => 'Чистовой этап', 'en' => 'Finishing stage'], // (backend)
        'summary.logistics_services' => ['az' => 'Logistika və xidmətlər', 'ru' => 'Логистика и услуги', 'en' => 'Logistics and services'], // (backend)
        'summary.margin' => ['az' => 'Podratçı əlavəsi', 'ru' => 'Надбавка подрядчика', 'en' => 'Contractor margin'], // (backend)
        'summary.own_materials' => ['az' => 'Özünüz almalı olduğunuz materiallar', 'ru' => 'Материалы, которые вы покупаете сами', 'en' => 'Materials you buy yourself'], // (backend)
        'summary.own_materials_hint' => [
            'az' => 'Yekuna daxil deyil. Məbləğ ehtiyat nəzərə alınmaqla, kateqoriyanın orta qiyməti ilə təxmin edilib.',
            'ru' => 'Не входит в итог. Сумма оценена по средней цене категории с учётом запаса.',
            'en' => 'Not part of the total. Estimated at the category average price, waste allowance included.',
        ],
        'summary.subtotal' => ['az' => 'Aralıq cəm', 'ru' => 'Промежуточный итог', 'en' => 'Subtotal'],
        'side.margin' => ['az' => 'Podratçı əlavəsi (:percent%)', 'ru' => 'Надбавка подрядчика (:percent%)', 'en' => 'Contractor margin (:percent%)'],

        'notice.points_by_norm' => [ // (backend)
            'az' => 'Elektrik və santexnika nöqtələri orta norma ilə hesablanıb, dəqiq say layihədən sonra',
            'ru' => 'Точки электрики и сантехники рассчитаны по средней норме, точное количество — после проекта',
            'en' => 'Electrical and plumbing points are estimated from average norms; the exact count follows the design project',
        ],
        'notice.valid_days' => ['az' => 'Qiymətlər :days gün qüvvədədir', 'ru' => 'Цены действительны :days дн.', 'en' => 'Prices are valid for :days days'], // (backend)

        // Step 1
        'object.level' => ['az' => 'Təmir səviyyəsi', 'ru' => 'Уровень ремонта', 'en' => 'Renovation level'],
        'object.level_hint_white_box' => [
            'az' => 'White Box: qaralama işləri, elektrik və santexnika nöqtələri, rənglənmiş divar və tavan. Bitirmə örtükləri daxil deyil — 3-cü addımda əlavə edə bilərsiniz.',
            'ru' => 'White Box: черновые работы, точки электрики и сантехники, окрашенные стены и потолок. Чистовые покрытия не входят — их можно добавить на шаге 3.',
            'en' => 'White Box: rough works, electrical and plumbing points, painted walls and ceiling. Finishing covers are not included — you can add them on step 3.',
        ],
        'object.level_hint_full' => [
            'az' => 'Tam təmir: White Box və bitirmə mərhələsi — döşəmə örtükləri, kafel, qapılar, işıqlandırıcılar, santexnika.',
            'ru' => 'Полный ремонт: White Box и чистовой этап — напольные покрытия, плитка, двери, светильники, сантехника.',
            'en' => 'Full renovation: White Box plus the finishing stage — floor coverings, tiles, doors, light fittings, sanitaryware.',
        ],
        'object.ceiling_height' => ['az' => 'Tavan hündürlüyü', 'ru' => 'Высота потолков', 'en' => 'Ceiling height'],
        'object.area_hint' => [
            'az' => 'Sahə ilkin göstəricidir: smeta otaqların sahələrinin cəmi ilə hesablanır. Tavan hündürlüyü əl ilə dəyişdirilməmiş bütün otaqlara tətbiq olunur.',
            'ru' => 'Площадь — ориентир: смета считается по сумме площадей комнат. Высота потолков применяется ко всем комнатам, где её не меняли вручную.',
            'en' => 'The area is a starting point: the estimate uses the sum of the room areas. The ceiling height applies to every room whose height you have not changed.',
        ],
        'alerts.rescale_rooms' => [
            'az' => 'Otaqların ölçüləri yeni sahəyə uyğunlaşdırılsın?',
            'ru' => 'Подогнать размеры комнат под новую площадь?',
            'en' => 'Scale the rooms to the new area?',
        ],
        'alerts.rescale_yes' => ['az' => 'Uyğunlaşdır', 'ru' => 'Подогнать', 'en' => 'Scale them'],
        'alerts.rescale_no' => ['az' => 'Olduğu kimi saxla', 'ru' => 'Оставить как есть', 'en' => 'Keep as they are'],

        // Step 2 — area counter and the mismatch dialog
        'rooms.area_counter' => ['az' => 'Otaqlar: :rooms / :object m²', 'ru' => 'Комнаты: :rooms / :object м²', 'en' => 'Rooms: :rooms / :object m²'],
        'alerts.area_title' => ['az' => 'Sahələr uyğun gəlmir', 'ru' => 'Площади не совпадают', 'en' => 'The areas do not match'],
        'alerts.area_message' => [
            'az' => 'Otaqların cəmi :rooms m², obyektin sahəsi isə :object m²-dir (:deviation% fərq). Smeta otaqların cəmi ilə hesablanır.',
            'ru' => 'Сумма комнат — :rooms м², а площадь объекта — :object м² (разница :deviation%). Смета считается по сумме комнат.',
            'en' => 'The rooms add up to :rooms m², while the property area is :object m² (:deviation% apart). The estimate uses the sum of the rooms.',
        ],
        'alerts.area_fix' => ['az' => 'Obyektin sahəsini :area m² olaraq düzəlt', 'ru' => 'Исправить площадь объекта на :area м²', 'en' => 'Set the property area to :area m²'],
        'alerts.area_back' => ['az' => 'Otaqlara qayıt', 'ru' => 'Вернуться к комнатам', 'en' => 'Back to the rooms'],
        'alerts.area_continue' => ['az' => 'Davam et', 'ru' => 'Продолжить', 'en' => 'Continue'],

        // Step 2 — «Sadə» / «Ətraflı» points
        'rooms.points_detail' => ['az' => 'Ətraflı / Dəqiqləşdir', 'ru' => 'Подробно / Уточнить', 'en' => 'Details / Adjust'],
        'rooms.points_reset' => ['az' => 'Orta normaya qaytar', 'ru' => 'Вернуть среднюю норму', 'en' => 'Back to average norms'],
        'rooms.points_hint' => [
            'az' => 'Saylar orta norma ilə doldurulub — yalnız dəqiq bildiyinizi dəyişin.',
            'ru' => 'Количества заполнены по средней норме — меняйте только то, что знаете точно.',
            'en' => 'The counts are prefilled from average norms — change only what you know for sure.',
        ],
        'rooms.by_norm' => ['az' => '(orta norma)', 'ru' => '(средняя норма)', 'en' => '(average norm)'],
        'rooms.points_none' => ['az' => 'Bu otaq üçün nöqtə nəzərdə tutulmayıb', 'ru' => 'Для этой комнаты точки не предусмотрены', 'en' => 'No points are planned for this room'],
        'points.lights_spot' => ['az' => 'Spot işıq', 'ru' => 'Точечные светильники', 'en' => 'Spotlights'],
        'points.lights_pendant' => ['az' => 'Asma işıq', 'ru' => 'Подвесные светильники', 'en' => 'Pendant lights'],
        'points.lights_chandelier' => ['az' => 'Çilçıraq', 'ru' => 'Люстры', 'en' => 'Chandeliers'],
        'points.intercom' => ['az' => 'Domofon', 'ru' => 'Домофон', 'en' => 'Intercom'],
        'fixtures.hygienic_shower' => ['az' => 'Gigiyenik duş', 'ru' => 'Гигиенический душ', 'en' => 'Hygienic shower'],
        'fixtures.washer' => ['az' => 'Paltaryuyan maşın', 'ru' => 'Стиральная машина', 'en' => 'Washing machine'],
        'fixtures.dishwasher' => ['az' => 'Qabyuyan maşın', 'ru' => 'Посудомоечная машина', 'en' => 'Dishwasher'],
        'fixtures.towel_rail' => ['az' => 'Dəsmal quruducusu', 'ru' => 'Полотенцесушитель', 'en' => 'Towel rail'],
        'fixtures.boiler' => ['az' => 'Su qızdırıcısı (boyler)', 'ru' => 'Водонагреватель (бойлер)', 'en' => 'Water heater (boiler)'],
        // The one-line «Sadə» summary: «Elektrik: 8 rozetka, 2 açar, 6 işıq».
        'points_short.sockets' => ['az' => ':n rozetka', 'ru' => 'розеток: :n', 'en' => 'sockets: :n'],
        'points_short.switches' => ['az' => ':n açar', 'ru' => 'выключателей: :n', 'en' => 'switches: :n'],
        'points_short.lights' => ['az' => ':n işıq', 'ru' => 'светильников: :n', 'en' => 'lights: :n'],
        'points_short.wall_lights' => ['az' => ':n bra', 'ru' => 'бра: :n', 'en' => 'wall lights: :n'],
        'points_short.tv' => ['az' => ':n TV', 'ru' => 'TV: :n', 'en' => 'TV: :n'],
        'points_short.data' => ['az' => ':n internet', 'ru' => 'интернет: :n', 'en' => 'data: :n'],
        'points_short.intercom' => ['az' => ':n domofon', 'ru' => 'домофон: :n', 'en' => 'intercom: :n'],
        'points_short.ac_power' => ['az' => ':n kondisioner', 'ru' => 'кондиционеров: :n', 'en' => 'air conditioners: :n'],
        'points_short.appliance' => ['az' => ':n texnika xətti', 'ru' => 'линий техники: :n', 'en' => 'appliance lines: :n'],
        'points_short.water_points' => ['az' => ':n su nöqtəsi', 'ru' => 'точек воды: :n', 'en' => 'water points: :n'],
        'points_short.sewer_points' => ['az' => ':n kanalizasiya nöqtəsi', 'ru' => 'точек канализации: :n', 'en' => 'sewer points: :n'],
        'fixtures_short.toilet' => ['az' => 'unitaz', 'ru' => 'унитаз', 'en' => 'toilet'],
        'fixtures_short.basin' => ['az' => 'lavabo', 'ru' => 'раковина', 'en' => 'basin'],
        'fixtures_short.shower' => ['az' => 'duş', 'ru' => 'душ', 'en' => 'shower'],
        'fixtures_short.bathtub' => ['az' => 'vanna', 'ru' => 'ванна', 'en' => 'bathtub'],
        'fixtures_short.hygienic_shower' => ['az' => 'gigiyenik duş', 'ru' => 'гигиенический душ', 'en' => 'hygienic shower'],
        'fixtures_short.washer' => ['az' => 'paltaryuyan maşın', 'ru' => 'стиральная машина', 'en' => 'washing machine'],
        'fixtures_short.kitchen_sink' => ['az' => 'mətbəx yuyucusu', 'ru' => 'мойка', 'en' => 'kitchen sink'],
        'fixtures_short.dishwasher' => ['az' => 'qabyuyan maşın', 'ru' => 'посудомойка', 'en' => 'dishwasher'],
        'fixtures_short.mixer' => ['az' => 'qarışdırıcı', 'ru' => 'смеситель', 'en' => 'mixer tap'],
        'fixtures_short.towel_rail' => ['az' => 'dəsmal quruducusu', 'ru' => 'полотенцесушитель', 'en' => 'towel rail'],
        'fixtures_short.boiler' => ['az' => 'boyler', 'ru' => 'бойлер', 'en' => 'water heater'],

        // Step 2 — openings, kitchen run, extra finishes
        'rooms.openings' => ['az' => 'Qapı və pəncərə yerləri', 'ru' => 'Дверные и оконные проёмы', 'en' => 'Door and window openings'],
        'openings.door' => ['az' => 'Qapı', 'ru' => 'Дверь', 'en' => 'Door'],
        'openings.window' => ['az' => 'Pəncərə', 'ru' => 'Окно', 'en' => 'Window'],
        'openings.passage' => ['az' => 'Qapısız keçid', 'ru' => 'Проём без двери', 'en' => 'Doorless opening'],
        'openings.qty' => ['az' => 'Say', 'ru' => 'Кол-во', 'en' => 'Qty'],
        'openings.width' => ['az' => 'En', 'ru' => 'Ширина', 'en' => 'Width'],
        'openings.height' => ['az' => 'Hündürlük', 'ru' => 'Высота', 'en' => 'Height'],
        'openings.entrance' => ['az' => 'Giriş qapısı', 'ru' => 'Входная дверь', 'en' => 'Entrance door'],
        'openings.add_door' => ['az' => '+ Qapı', 'ru' => '+ Дверь', 'en' => '+ Door'],
        'openings.add_window' => ['az' => '+ Pəncərə', 'ru' => '+ Окно', 'en' => '+ Window'],
        'openings.add_passage' => ['az' => '+ Qapısız keçid', 'ru' => '+ Проём без двери', 'en' => '+ Doorless opening'],
        'rooms.kitchen_furniture' => ['az' => 'Mətbəx mebelinin uzunluğu', 'ru' => 'Длина кухонной мебели', 'en' => 'Kitchen furniture length'],
        'rooms.kitchen_furniture_hint' => [
            'az' => 'Mebelin arxasında plintus vurulmur — bu uzunluq plintusdan çıxılır.',
            'ru' => 'За мебелью плинтус не ставится — эта длина вычитается из плинтуса.',
            'en' => 'No skirting goes behind the units — this length comes off the skirting.',
        ],
        'rooms.zones_add' => ['az' => '+ Əlavə örtük', 'ru' => '+ Дополнительное покрытие', 'en' => '+ Extra finish'],
        'rooms.zone_finish' => ['az' => 'Əlavə örtük', 'ru' => 'Дополнительное покрытие', 'en' => 'Extra finish'],
        'rooms.zone_area' => ['az' => 'Sahə, m²', 'ru' => 'Площадь, м²', 'en' => 'Area, m²'],
        'rooms.zone_rest' => [
            'az' => 'Əsas örtük qalan sahəni tutur: :area m²',
            'ru' => 'Основное покрытие занимает остальное: :area м²',
            'en' => 'The main finish covers the rest: :area m²',
        ],
        'rooms.not_in_white_box' => ['az' => 'White Box-a daxil deyil', 'ru' => 'Не входит в White Box', 'en' => 'Not included in White Box'],

        // Step 3 / 4
        'works.white_box_hint' => [
            'az' => 'White Box seçilib: bitirmə işləri söndürülüb. Lazım olanı üzərinə klikləyərək yenidən əlavə edin.',
            'ru' => 'Выбран White Box: чистовые работы отключены. Нужную работу можно вернуть, нажав на неё.',
            'en' => 'White Box is selected: finishing works are switched off. Click any of them to add it back.',
        ],
        'materials.excluded_estimate' => ['az' => '≈ :amount · yekuna daxil deyil', 'ru' => '≈ :amount · не входит в итог', 'en' => '≈ :amount · not in the total'],

        // WhatsApp — the margin and the reserve are part of the stage totals above them.
        'alerts.whatsapp_included' => ['az' => 'daxildir', 'ru' => 'включено', 'en' => 'included'],
    ],
];
