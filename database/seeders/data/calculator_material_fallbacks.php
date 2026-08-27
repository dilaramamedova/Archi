<?php

/**
 * Average category prices used only until the visitor picks a real catalog product
 * (master spec §12). Transcribed from the client's price matrix, sheet
 * Material_Fallbacks, plus the rough-material categories the engine needs to price a
 * complete estimate — those are marked DRAFT and are the first rows Archi should
 * confirm.
 *
 * Setting a price to null is a legitimate answer: the engine then emits a
 * PRICE_MISSING line ("qiymət dəqiqləşdirilir") instead of pretending the material
 * is free.
 */

$draft = 'DRAFT — 2026 Bakı bazar araşdırması; Archi tərəfindən təsdiqlənməlidir.';

return [
    // Finish materials — client's filled matrix
    ['id' => 'MAT_FLOOR_LAMINATE', 'group' => 'FLOOR', 'unit' => 'm²', 'economy' => 8, 'standard' => 14, 'premium' => 22, 'notes' => $draft,
        'name' => ['az' => 'Laminat', 'ru' => 'Ламинат', 'en' => 'Laminate']],
    ['id' => 'MAT_FLOOR_SPC', 'group' => 'FLOOR', 'unit' => 'm²', 'economy' => 10, 'standard' => 16, 'premium' => 26, 'notes' => $draft,
        'name' => ['az' => 'SPC örtük', 'ru' => 'SPC', 'en' => 'SPC']],
    ['id' => 'MAT_FLOOR_PARQUET', 'group' => 'FLOOR', 'unit' => 'm²', 'economy' => 25, 'standard' => 45, 'premium' => 80, 'notes' => $draft,
        'name' => ['az' => 'Parket/mühəndis taxtası', 'ru' => 'Паркет/инженерная доска', 'en' => 'Parquet/engineered board']],
    ['id' => 'MAT_FLOOR_TILE', 'group' => 'FLOOR', 'unit' => 'm²', 'economy' => 12, 'standard' => 22, 'premium' => 45, 'notes' => $draft,
        'name' => ['az' => 'Döşəmə kafeli/keramoqranit', 'ru' => 'Плитка/керамогранит', 'en' => 'Floor tile/porcelain']],
    ['id' => 'MAT_FLOOR_STONE', 'group' => 'FLOOR', 'unit' => 'm²', 'economy' => 35, 'standard' => 60, 'premium' => 120, 'notes' => $draft,
        'name' => ['az' => 'Təbii daş', 'ru' => 'Натуральный камень', 'en' => 'Natural stone']],
    ['id' => 'MAT_FLOOR_CARPET', 'group' => 'FLOOR', 'unit' => 'm²', 'economy' => 8, 'standard' => 15, 'premium' => 25, 'notes' => $draft,
        'name' => ['az' => 'Kovrolin', 'ru' => 'Ковролин', 'en' => 'Carpet']],

    ['id' => 'MAT_WALL_PAINT', 'group' => 'WALL', 'unit' => 'm²', 'economy' => 3, 'standard' => 5, 'premium' => 9,
        'notes' => 'm² üzrə fallback. Konkret məhsul seçildikdə sərfiyyat və tara üzrə litrlə hesablanır.',
        'name' => ['az' => 'Divar boyası', 'ru' => 'Краска', 'en' => 'Wall paint']],
    ['id' => 'MAT_WALL_WALLPAPER', 'group' => 'WALL', 'unit' => 'm²', 'economy' => 6, 'standard' => 12, 'premium' => 25,
        'notes' => 'MVP-də m² üzrə. Rulon hesabı (en/uzunluq/raport) ikinci mərhələyə saxlanılıb.',
        'name' => ['az' => 'Divar kağızı', 'ru' => 'Обои', 'en' => 'Wallpaper']],
    ['id' => 'MAT_WALL_DECOR_PLASTER', 'group' => 'WALL', 'unit' => 'm²', 'economy' => 10, 'standard' => 20, 'premium' => 40, 'notes' => $draft,
        'name' => ['az' => 'Dekorativ suvaq', 'ru' => 'Декоративная штукатурка', 'en' => 'Decorative plaster']],
    ['id' => 'MAT_WALL_TILE', 'group' => 'WALL', 'unit' => 'm²', 'economy' => 10, 'standard' => 18, 'premium' => 35, 'notes' => $draft,
        'name' => ['az' => 'Divar kafeli/keramoqranit', 'ru' => 'Настенная плитка/керамогранит', 'en' => 'Wall tile/porcelain']],
    ['id' => 'MAT_WALL_PANEL', 'group' => 'WALL', 'unit' => 'm²', 'economy' => 15, 'standard' => 30, 'premium' => 60, 'notes' => $draft,
        'name' => ['az' => 'Divar panelləri', 'ru' => 'Стеновые панели', 'en' => 'Wall panels']],
    ['id' => 'MAT_WALL_STONE', 'group' => 'WALL', 'unit' => 'm²', 'economy' => 25, 'standard' => 50, 'premium' => 100, 'notes' => $draft,
        'name' => ['az' => 'Divar üçün daş', 'ru' => 'Камень на стены', 'en' => 'Wall stone']],

    ['id' => 'MAT_CEIL_PAINT', 'group' => 'CEILING', 'unit' => 'm²', 'economy' => 3, 'standard' => 5, 'premium' => 8,
        'notes' => 'm² üzrə fallback.',
        'name' => ['az' => 'Tavan boyası', 'ru' => 'Потолочная краска', 'en' => 'Ceiling paint']],
    ['id' => 'MAT_CEIL_GYPSUM', 'group' => 'CEILING', 'unit' => 'm²', 'economy' => 12, 'standard' => 18, 'premium' => 28,
        'notes' => 'Komplekt fallback: profil, alçıpan, bərkidicilər.',
        'name' => ['az' => 'Alçıpan tavan materialları', 'ru' => 'ГКЛ-потолок: материалы', 'en' => 'Plasterboard ceiling materials']],
    ['id' => 'MAT_CEIL_STRETCH', 'group' => 'CEILING', 'unit' => 'm²', 'economy' => 10, 'standard' => 16, 'premium' => 25, 'notes' => $draft,
        'name' => ['az' => 'Gərilmə tavan materialı', 'ru' => 'Натяжной потолок: материал', 'en' => 'Stretch ceiling material']],

    ['id' => 'MAT_SKIRTING', 'group' => 'FLOOR', 'unit' => 'p.m', 'economy' => 3, 'standard' => 6, 'premium' => 12, 'notes' => $draft,
        'name' => ['az' => 'Plintus', 'ru' => 'Плинтус', 'en' => 'Skirting board']],

    ['id' => 'MAT_DOOR_INTERIOR', 'group' => 'DOORS', 'unit' => 'əd', 'economy' => 150, 'standard' => 280, 'premium' => 500, 'notes' => $draft,
        'name' => ['az' => 'Otaqarası qapı', 'ru' => 'Межкомнатная дверь', 'en' => 'Interior door']],
    ['id' => 'MAT_DOOR_HIDDEN', 'group' => 'DOORS', 'unit' => 'əd', 'economy' => 350, 'standard' => 550, 'premium' => 900, 'notes' => $draft,
        'name' => ['az' => 'Gizli qapı', 'ru' => 'Скрытая дверь', 'en' => 'Hidden door']],
    ['id' => 'MAT_DOOR_SLIDING', 'group' => 'DOORS', 'unit' => 'əd', 'economy' => 250, 'standard' => 400, 'premium' => 700, 'notes' => $draft,
        'name' => ['az' => 'Sürgülü qapı', 'ru' => 'Раздвижная дверь', 'en' => 'Sliding door']],

    ['id' => 'MAT_SANITARY_BASIC', 'group' => 'PLUMBING', 'unit' => 'əd', 'economy' => 80, 'standard' => 150, 'premium' => 300,
        'notes' => 'Dəqiq smeta üçün kataloqdan real məhsul seçilməlidir.',
        'name' => ['az' => 'Santexnika — baza avadanlıq', 'ru' => 'Сантехника — базовый прибор', 'en' => 'Plumbing — basic fixture']],
    ['id' => 'MAT_LIGHTING_BASIC', 'group' => 'LIGHTING', 'unit' => 'əd', 'economy' => 15, 'standard' => 35, 'premium' => 70, 'notes' => $draft,
        'name' => ['az' => 'İşıqlandırıcı — baza', 'ru' => 'Светильник — базовый', 'en' => 'Light fitting — basic']],
    ['id' => 'MAT_ELECTRIC_ACCESSORIES', 'group' => 'ELECTRICAL', 'unit' => 'nöqtə', 'economy' => 4, 'standard' => 7, 'premium' => 12, 'notes' => $draft,
        'name' => ['az' => 'Rozetka/açar — orta qiymət', 'ru' => 'Розетки/выключатели — средняя цена', 'en' => 'Sockets/switches — average price']],

    // Rough materials. The client's matrix does not price these, but the engine
    // computes their quantities from the consumption norms, so a price is needed or
    // the estimate silently loses several thousand manats of materials.
    ['id' => 'MAT_PRIMER', 'group' => 'ROUGH', 'unit' => 'L', 'economy' => 2, 'standard' => 3.5, 'premium' => 6, 'notes' => $draft,
        'name' => ['az' => 'Qrunt', 'ru' => 'Грунтовка', 'en' => 'Primer']],
    ['id' => 'MAT_PUTTY', 'group' => 'ROUGH', 'unit' => 'kq', 'economy' => 0.6, 'standard' => 1, 'premium' => 1.8, 'notes' => $draft,
        'name' => ['az' => 'Şpaklyovka', 'ru' => 'Шпаклёвка', 'en' => 'Putty']],
    ['id' => 'MAT_PLASTER', 'group' => 'ROUGH', 'unit' => 'kq', 'economy' => 0.25, 'standard' => 0.4, 'premium' => 0.7, 'notes' => $draft,
        'name' => ['az' => 'Suvaq qarışığı', 'ru' => 'Штукатурная смесь', 'en' => 'Plaster mix']],
    ['id' => 'MAT_TILE_ADHESIVE', 'group' => 'ROUGH', 'unit' => 'kq', 'economy' => 0.5, 'standard' => 0.8, 'premium' => 1.4, 'notes' => $draft,
        'name' => ['az' => 'Kafel yapışqanı', 'ru' => 'Клей для плитки', 'en' => 'Tile adhesive']],
    ['id' => 'MAT_GROUT', 'group' => 'ROUGH', 'unit' => 'kq', 'economy' => 2.5, 'standard' => 4.5, 'premium' => 9, 'notes' => $draft,
        'name' => ['az' => 'Dərz doldurucu', 'ru' => 'Затирка', 'en' => 'Grout']],
    ['id' => 'MAT_WATERPROOF', 'group' => 'ROUGH', 'unit' => 'kq', 'economy' => 3.5, 'standard' => 6, 'premium' => 11, 'notes' => $draft,
        'name' => ['az' => 'Hidroizolyasiya', 'ru' => 'Гидроизоляция', 'en' => 'Waterproofing']],
    ['id' => 'MAT_SCREED', 'group' => 'ROUGH', 'unit' => 'kq', 'economy' => 0.2, 'standard' => 0.3, 'premium' => 0.5, 'notes' => $draft,
        'name' => ['az' => 'Stəjka qarışığı', 'ru' => 'Смесь для стяжки', 'en' => 'Screed mix']],
    ['id' => 'MAT_SELF_LEVEL', 'group' => 'ROUGH', 'unit' => 'kq', 'economy' => 0.5, 'standard' => 0.8, 'premium' => 1.4, 'notes' => $draft,
        'name' => ['az' => 'Özüyayılan qarışıq', 'ru' => 'Наливной пол', 'en' => 'Self-levelling compound']],
];
