<?php

// Quick calculator page (EN).

return [
    'title' => 'Quick renovation calculator — ARCHİ',
    'breadcrumb' => 'Quick calculator',

    'head' => [
        'tag' => 'Quick calculator',
        'title' => 'Estimated renovation cost',
        'subtitle' => 'A few parameters — 3 budget options.',
    ],

    'object' => [
        'label' => 'Property type',
        'apartment' => 'Apartment',
        'house' => 'Private house',
        'office' => 'Office',
        'commercial' => 'Commercial',
    ],

    'area' => [
        'label' => 'Property area',
        'unit' => 'm²',
    ],

    'type' => [
        'label' => 'Renovation type',
        'shell' => 'Shell',
        'cosmetic' => 'Cosmetic',
        'major' => 'Major',
        'turnkey' => 'Turnkey',
    ],

    'rooms' => [
        'label' => 'Number of rooms',
        'studio' => 'Studio',
        'one' => '1',
        'two' => '2',
        'three' => '3',
        'four_plus' => '4+',
    ],

    'level' => [
        'label' => 'Material level',
        'economy' => 'Economy',
        'standard' => 'Standard',
        'premium' => 'Premium',
    ],

    'result' => [
        'label' => 'Estimated cost for :area m²',
        'currency' => 'AZN and up',
        'recommended' => 'Recommended',
    ],

    'actions' => [
        'next' => 'Get the detailed estimate  →',
        'pdf' => 'Download PDF',
        'whatsapp' => 'Send to WhatsApp',
        'save' => 'Save',
    ],

    'messages' => [
        'saved' => 'The estimate has been saved.',
        'whatsapp' => 'ARCHİ renovation estimate: :area m² · :level · ~:price AZN',
    ],
];
