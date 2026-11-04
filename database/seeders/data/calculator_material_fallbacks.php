<?php

/**
 * Average prices of the materials that are not a finish: the rough consumables the
 * engine derives from norms (primer, putty, plaster, screed, adhesive, grout,
 * waterproofing) and the items bought one per unit of work (a door, a fixture, a
 * light fitting, a socket, a metre of cable). Finish materials live on their finish
 * row in calculator_finishes.
 *
 * Used only until the visitor picks a real catalog product (master spec §12).
 * Setting a price to null is a legitimate answer: the engine then emits a
 * PRICE_MISSING line ("qiymət dəqiqləşdirilir") instead of pretending the material
 * is free.
 */

$draft = 'DRAFT — 2026 Bakı bazar araşdırması; Archi tərəfindən təsdiqlənməlidir.';

return [
    ['id' => 'MAT_SKIRTING', 'group' => 'FLOOR', 'unit' => 'p.m', 'economy' => 3, 'standard' => 6, 'premium' => 12, 'notes' => $draft,
        'name' => ['az' => 'Plintus', 'ru' => 'Плинтус', 'en' => 'Skirting board']],
    ['id' => 'MAT_DOOR_INTERIOR', 'group' => 'DOORS', 'unit' => 'əd', 'economy' => 150, 'standard' => 280, 'premium' => 500, 'notes' => $draft,
        'name' => ['az' => 'Otaqarası qapı', 'ru' => 'Межкомнатная дверь', 'en' => 'Interior door']],
    ['id' => 'MAT_SANITARY_BASIC', 'group' => 'PLUMBING', 'unit' => 'əd', 'economy' => 80, 'standard' => 150, 'premium' => 300, 'notes' => 'Dəqiq smeta üçün kataloqdan real məhsul seçilməlidir.',
        'name' => ['az' => 'Santexnika — baza avadanlıq', 'ru' => 'Сантехника — базовый прибор', 'en' => 'Plumbing — basic fixture']],
    ['id' => 'MAT_LIGHTING_BASIC', 'group' => 'LIGHTING', 'unit' => 'əd', 'economy' => 15, 'standard' => 35, 'premium' => 70, 'notes' => $draft,
        'name' => ['az' => 'İşıqlandırıcı — baza', 'ru' => 'Светильник — базовый', 'en' => 'Light fitting — basic']],
    ['id' => 'MAT_ELECTRIC_ACCESSORIES', 'group' => 'ELECTRICAL', 'unit' => 'nöqtə', 'economy' => 4, 'standard' => 7, 'premium' => 12, 'notes' => $draft,
        'name' => ['az' => 'Rozetka/açar — orta qiymət', 'ru' => 'Розетки/выключатели — средняя цена', 'en' => 'Sockets/switches — average price']],
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
    ['id' => 'MAT_CABLE', 'group' => 'ELECTRICAL', 'unit' => 'm', 'economy' => 1.2, 'standard' => 1.8, 'premium' => 2.8, 'notes' => $draft,
        'name' => ['az' => 'Kabel (orta qiymət, 1 m)', 'ru' => 'Кабель (средняя цена, 1 м)', 'en' => 'Cable (average, per metre)']],
    ['id' => 'MAT_WARM_FLOOR', 'group' => 'FLOOR', 'unit' => 'm²', 'economy' => 20, 'standard' => 30, 'premium' => 45, 'notes' => $draft,
        'name' => ['az' => 'İsti döşəmə dəsti (kabel/mat, termostat)', 'ru' => 'Комплект тёплого пола (кабель/мат, термостат)', 'en' => 'Warm floor kit (cable/mat, thermostat)']],
];
