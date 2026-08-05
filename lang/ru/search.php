<?php

// Search results page (RU).

return [
    'title' => 'Результаты по «:query» — ARCHİ',
    'heading' => 'Результаты по «:query»',
    'default_query' => 'плитка',
    'tag' => 'Результаты поиска',

    // Dynamic count strings (use trans_choice with :count)
    'total_count' => '{0} Ничего не найдено|{1} :count результат|[2,4] :count результата|[5,*] :count результатов',
    'result_count' => '{0} Нет результатов|{1} :count результат|[2,4] :count результата|[5,*] :count результатов',

    'tabs' => [
        'all_n' => 'Все (:count)',
        'products_n' => 'Товары (:count)',
        'masters_n' => 'Мастера (:count)',
        'articles_n' => 'Статьи (:count)',
    ],

    'sections' => [
        'view_all' => 'Смотреть все →',
        'products' => 'Товары',
        'masters' => 'Мастера',
        'articles' => 'Статьи',
    ],

    // Pluralized unit words for dynamic counts
    'review_word' => '{1} отзыв|[2,4] отзыва|[5,*] отзывов',
    'experience_years' => '{1} год опыта|[2,4] года опыта|[5,*] лет опыта',
    'project_word' => '{1} проект|[2,4] проекта|[5,*] проектов',
    'min_read' => 'мин чтения',
];
