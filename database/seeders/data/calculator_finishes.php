<?php

/**
 * The finishes a visitor can choose for a floor, a wall or a ceiling — one row
 * each, with the labour rate and the three segment material prices. The ids are
 * the engine's contract (a saved session refers to them) and must never be renamed;
 * everything else is an admin-panel edit.
 *
 * Labour rates and material prices are the DRAFT 2026 Baku research that came with
 * the client's price matrix. A finish that used to be a work-rate row plus a
 * material-fallback row keeps both of its old prices here.
 *
 * waste = technological waste in percent, inside the required material quantity
 * (never confused with the financial contingency — master spec §15).
 */

$draft = 'DRAFT — 2026 Bakı bazar araşdırması; Archi tərəfindən təsdiqlənməlidir.';

return [
    // ---- FLOOR ----
    ['id' => 'LAMINATE', 'surface' => 'FLOOR', 'family' => 'FLOATING', 'labour' => 5, 'economy' => 8, 'standard' => 14, 'premium' => 22, 'waste' => 8, 'default' => true,
        'name' => ['az' => 'Laminat', 'ru' => 'Ламинат', 'en' => 'Laminate'],
        'keywords' => 'laminat; döşəmə örtüyü'],
    ['id' => 'SPC', 'surface' => 'FLOOR', 'family' => 'FLOATING', 'labour' => 6, 'economy' => 10, 'standard' => 16, 'premium' => 26, 'waste' => 8,
        'name' => ['az' => 'SPC örtük', 'ru' => 'SPC-покрытие', 'en' => 'SPC flooring'],
        'keywords' => 'spc; vinil; döşəmə örtüyü'],
    ['id' => 'PARQUET', 'surface' => 'FLOOR', 'family' => 'FLOATING', 'labour' => 12, 'economy' => 25, 'standard' => 45, 'premium' => 80, 'waste' => 10,
        'name' => ['az' => 'Parket', 'ru' => 'Паркет', 'en' => 'Parquet'],
        'keywords' => 'parket; mühəndis taxtası'],
    ['id' => 'CARPET', 'surface' => 'FLOOR', 'family' => 'FLOATING', 'labour' => 4, 'economy' => 8, 'standard' => 15, 'premium' => 25, 'waste' => 5,
        'name' => ['az' => 'Kovrolin', 'ru' => 'Ковролин', 'en' => 'Carpet'],
        'keywords' => 'kovrolin; xalça örtük'],
    ['id' => 'FLOOR_TILE', 'surface' => 'FLOOR', 'family' => 'TILE', 'labour' => 15, 'min' => 150, 'economy' => 12, 'standard' => 22, 'premium' => 45, 'waste' => 10,
        'name' => ['az' => 'Döşəmə kafeli', 'ru' => 'Напольная плитка', 'en' => 'Floor tile'],
        'keywords' => 'kafel; keramoqranit; döşəmə kafeli'],
    ['id' => 'FLOOR_TILE_LARGE', 'surface' => 'FLOOR', 'family' => 'TILE', 'labour' => 19, 'min' => 150, 'economy' => 16, 'standard' => 28, 'premium' => 55, 'waste' => 12,
        'name' => ['az' => 'İriformatlı keramoqranit (döşəmə)', 'ru' => 'Крупноформатный керамогранит (пол)', 'en' => 'Large-format porcelain (floor)'],
        'keywords' => 'iriformatlı; keramoqranit; 60x120; 120x120'],
    ['id' => 'FLOOR_STONE', 'surface' => 'FLOOR', 'family' => 'TILE', 'labour' => 20, 'economy' => 35, 'standard' => 60, 'premium' => 120, 'waste' => 10,
        'name' => ['az' => 'Təbii daş (döşəmə)', 'ru' => 'Натуральный камень (пол)', 'en' => 'Natural stone (floor)'],
        'keywords' => 'mərmər; qranit; travertin; təbii daş'],

    // ---- WALL ----
    ['id' => 'PAINT', 'surface' => 'WALL', 'family' => 'COAT', 'labour' => 4, 'economy' => 3, 'standard' => 5, 'premium' => 9, 'waste' => 0, 'default' => true,
        'name' => ['az' => 'Boya', 'ru' => 'Краска', 'en' => 'Paint'],
        'keywords' => 'boya; divar boyası; rəngləmə'],
    ['id' => 'WALLPAPER', 'surface' => 'WALL', 'family' => 'COAT', 'labour' => 5, 'economy' => 6, 'standard' => 12, 'premium' => 25, 'waste' => 10,
        'name' => ['az' => 'Divar kağızı', 'ru' => 'Обои', 'en' => 'Wallpaper'],
        'keywords' => 'oboy; divar kağızı'],
    ['id' => 'DECOR_PLASTER', 'surface' => 'WALL', 'family' => 'COAT', 'labour' => 15, 'economy' => 10, 'standard' => 20, 'premium' => 40, 'waste' => 0,
        'name' => ['az' => 'Dekorativ suvaq', 'ru' => 'Декоративная штукатурка', 'en' => 'Decorative plaster'],
        'keywords' => 'dekorativ suvaq; venesian; mikrosement'],
    ['id' => 'WALL_TILE', 'surface' => 'WALL', 'family' => 'TILE', 'labour' => 15, 'min' => 100, 'economy' => 10, 'standard' => 18, 'premium' => 35, 'waste' => 10,
        'name' => ['az' => 'Divar kafeli', 'ru' => 'Настенная плитка', 'en' => 'Wall tile'],
        'keywords' => 'kafel; divar kafeli; hamam kafeli'],
    ['id' => 'WALL_TILE_LARGE', 'surface' => 'WALL', 'family' => 'TILE', 'labour' => 18, 'min' => 100, 'economy' => 14, 'standard' => 25, 'premium' => 45, 'waste' => 12,
        'name' => ['az' => 'İriformatlı keramoqranit (divar)', 'ru' => 'Крупноформатный керамогранит (стена)', 'en' => 'Large-format porcelain (wall)'],
        'keywords' => 'iriformatlı; keramoqranit; slab'],
    ['id' => 'MOSAIC', 'surface' => 'WALL', 'family' => 'TILE', 'labour' => 28, 'economy' => 30, 'standard' => 50, 'premium' => 90, 'waste' => 10,
        'name' => ['az' => 'Mozaika', 'ru' => 'Мозаика', 'en' => 'Mosaic'],
        'keywords' => 'mozaika'],
    ['id' => 'WALL_STONE', 'surface' => 'WALL', 'family' => 'TILE', 'labour' => 30, 'economy' => 25, 'standard' => 50, 'premium' => 100, 'waste' => 10,
        'name' => ['az' => 'Divar daşı', 'ru' => 'Камень на стене', 'en' => 'Wall stone'],
        'keywords' => 'daş; dekorativ daş; mərmər'],
    ['id' => 'WALL_PANEL', 'surface' => 'WALL', 'family' => 'PANEL', 'labour' => 20, 'economy' => 15, 'standard' => 30, 'premium' => 60, 'waste' => 5,
        'name' => ['az' => 'Divar paneli', 'ru' => 'Стеновые панели', 'en' => 'Wall panels'],
        'keywords' => 'panel; mdf panel; taxta üzlük; lamel'],

    // ---- CEILING ----
    ['id' => 'CEILING_PAINT', 'surface' => 'CEILING', 'family' => 'COAT', 'labour' => 4.5, 'economy' => 3, 'standard' => 5, 'premium' => 8, 'waste' => 0, 'default' => true,
        'name' => ['az' => 'Tavan boyası', 'ru' => 'Покраска потолка', 'en' => 'Ceiling paint'],
        'keywords' => 'tavan boyası; boya'],
    ['id' => 'GYPSUM_FLAT', 'surface' => 'CEILING', 'family' => 'SUSPENDED', 'labour' => 18, 'economy' => 12, 'standard' => 18, 'premium' => 28, 'waste' => 0,
        'name' => ['az' => 'Birsəviyyəli alçıpan', 'ru' => 'Одноуровневый ГКЛ', 'en' => 'Single-level plasterboard'],
        'keywords' => 'alçıpan; gipskarton; asma tavan'],
    ['id' => 'GYPSUM_COMPLEX', 'surface' => 'CEILING', 'family' => 'SUSPENDED', 'labour' => 35, 'economy' => 15, 'standard' => 22, 'premium' => 35, 'waste' => 0,
        'name' => ['az' => 'Çoxsəviyyəli alçıpan', 'ru' => 'Многоуровневый ГКЛ', 'en' => 'Multi-level plasterboard'],
        'keywords' => 'alçıpan; çoxsəviyyəli; dizayn tavan'],
    ['id' => 'STRETCH_CEILING', 'surface' => 'CEILING', 'family' => 'SUSPENDED', 'labour' => 14, 'min' => 200, 'economy' => 10, 'standard' => 16, 'premium' => 25, 'waste' => 0,
        'name' => ['az' => 'Gərilmə tavan', 'ru' => 'Натяжной потолок', 'en' => 'Stretch ceiling'],
        'keywords' => 'gərilmə tavan; natyajnoy'],
];
