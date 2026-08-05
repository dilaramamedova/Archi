<?php

// Search results page (AZ).

return [
    'title' => '«:query» üçün nəticələr — ARCHİ',
    'heading' => '«:query» üçün nəticələr',
    'default_query' => 'kafel',
    'tag' => 'Axtarış nəticələri',

    // Dynamic count strings (use trans_choice with :count)
    'total_count' => '{0} Nəticə tapılmadı|{1} :count nəticə|[2,*] :count nəticə',
    'result_count' => '{0} Nəticə yoxdur|{1} :count nəticə|[2,*] :count nəticə',

    'tabs' => [
        'all_n' => 'Hamısı (:count)',
        'products_n' => 'Məhsullar (:count)',
        'masters_n' => 'Ustalar (:count)',
        'articles_n' => 'Məqalələr (:count)',
    ],

    'sections' => [
        'view_all' => 'Hamısına bax →',
        'products' => 'Məhsullar',
        'masters' => 'Ustalar',
        'articles' => 'Məqalələr',
    ],

    // Pluralized unit words for dynamic counts
    'review_word' => '{1} rəy|[2,*] rəy',
    'experience_years' => '{1} illik təcrübə|[2,*] illik təcrübə',
    'project_word' => '{1} layihə|[2,*] layihə',
    'min_read' => 'dəq oxu',
];
