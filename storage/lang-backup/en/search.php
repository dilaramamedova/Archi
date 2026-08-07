<?php

// Search results page (EN).

return [
    'title' => 'Results for «:query» — ARCHİ',
    'heading' => 'Results for «:query»',
    'default_query' => 'tile',
    'tag' => 'Search results',

    // Dynamic count strings (use trans_choice with :count)
    'total_count' => '{0} No results|{1} :count result|[2,*] :count results',
    'result_count' => '{0} No results|{1} :count result|[2,*] :count results',

    'tabs' => [
        'all_n' => 'All (:count)',
        'products_n' => 'Products (:count)',
        'masters_n' => 'Masters (:count)',
        'articles_n' => 'Articles (:count)',
    ],

    'sections' => [
        'view_all' => 'View all →',
        'products' => 'Products',
        'masters' => 'Masters',
        'articles' => 'Articles',
    ],

    // Pluralized unit words for dynamic counts
    'review_word' => '{1} review|[2,*] reviews',
    'experience_years' => '{1} year of experience|[2,*] years of experience',
    'project_word' => '{1} project|[2,*] projects',
    'min_read' => 'min read',
];
