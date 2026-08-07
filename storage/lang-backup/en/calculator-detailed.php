<?php

return [
    'title' => 'Detailed renovation calculator — ARCHİ',

    'head' => [
        'tag' => 'Detailed calculator',
        'title' => 'Build an accurate renovation estimate',
        'sub' => 'Pick your rooms, works and materials — items in your cart are included in the calculation too.',
    ],

    'money' => ':amount ₼',
    'approx' => '≈ :amount',
    'currency' => '₼',
    'currency_code' => 'AZN',

    'steps' => [
        'object' => 'Property',
        'rooms' => 'Rooms',
        'works' => 'Works',
        'materials' => 'Materials',
        'cart' => 'Cart',
        'summary' => 'Summary',
    ],

    'units' => [
        'm2' => 'm²',
        'm' => 'm',
        'point' => 'point',
        'pc' => 'pc',
    ],

    'object' => [
        'k' => 'Step 1 · Property',
        'h' => 'Property details',
        'type' => 'Property type',
        'types' => [
            'apartment' => 'Apartment',
            'house' => 'Detached house',
            'office' => 'Office',
            'commercial' => 'Commercial',
        ],
        'area' => 'Area',
        'city' => 'City',
        'floor' => 'Floor',
        'property' => 'Real estate type',
        'properties' => [
            'new' => 'New building',
            'used' => 'Second-hand',
        ],
        'condition' => 'Property condition',
        'conditions' => [
            'raw' => 'Unrenovated',
            'after_demolition' => 'After demolition',
            'rough_done' => 'Rough works done',
        ],
        'extra' => 'Extra options',
        'design' => 'Design project ready',
        'drawings' => 'Drawings ready',
        'demolition' => 'Demolition needed',
        'default_city' => 'Baku',
        'default_floor' => '7 / 16',
    ],

    'rooms' => [
        'k' => 'Step 2 · Rooms',
        'h' => 'Add your rooms',
        'd' => 'Set the dimensions and the finish type for each room.',
        'remove' => 'Delete',
        'floor_area' => 'Floor area',
        'height' => 'Ceiling height',
        'perimeter' => 'Wall perimeter',
        'doors' => 'Door',
        'windows' => 'Window',
        'floor_cover' => 'Floor covering',
        'wall_cover' => 'Wall finish',
        'heating' => 'Underfloor heating',
        'edit' => 'Edit',
        'add' => '+  Add a room',
        'floors' => [
            'laminate' => 'Laminate',
            'parquet' => 'Parquet',
            'spc' => 'SPC',
            'tile' => 'Tile',
        ],
        'walls' => [
            'paint' => 'Paint',
            'wallpaper' => 'Wallpaper',
            'plaster' => 'Decorative plaster',
            'tile' => 'Tile',
        ],
        'defaults' => [
            'living' => 'Living room',
            'kitchen' => 'Kitchen',
            'bathroom' => 'Bathroom',
            'new' => 'New room',
        ],
    ],

    'works' => [
        'k' => 'Step 3 · Works',
        'h' => 'Select the works you need',
        'd' => 'Tick only the works you need — the price is calculated automatically.',
        'price_unit' => ':price ₼/:unit',
        'selected' => 'Selected works (:count)',
        'items' => [
            'demolition' => 'Demolition',
            'wall_leveling' => 'Wall levelling',
            'plaster' => 'Plastering',
            'putty' => 'Puttying',
            'screed' => 'Floor screed',
            'waterproofing' => 'Waterproofing',
            'soundproofing' => 'Soundproofing',
            'electrical' => 'Electrical works',
            'plumbing' => 'Plumbing',
            'ventilation' => 'Ventilation',
            'air_conditioning' => 'Air conditioning system',
            'tile_prep' => 'Preparation for tiling',
            'paint_prep' => 'Preparation for painting',
        ],
    ],

    'materials' => [
        'k' => 'Step 4 · Materials',
        'h' => 'Choose the finishing materials',
        'd' => 'Items from your cart are added automatically, pick the rest from the catalog.',
        'banner' => ':count items from your cart were added to the calculation automatically',
        'not_selected' => 'Not selected',
        'pick' => 'Pick from catalog',
        'from_cart' => 'From cart',
        'labels' => [
            'floor' => 'Floor covering',
            'tile' => 'Tiles & metlakh',
            'paint' => 'Paint',
            'doors' => 'Doors',
            'plumbing' => 'Plumbing',
            'lighting' => 'Lighting',
        ],
    ],

    'cats' => [
        'floor' => 'Flooring',
        'tile' => 'Tiles & metlakh',
        'paint' => 'Paint',
        'doors' => 'Doors',
        'plumbing' => 'Plumbing',
    ],

    'cart' => [
        'k' => 'Step 5 · Cart',
        'h' => 'Cart items in the calculation',
        'd' => 'Quantities are calculated automatically from the area and the reserve rate. Remove what you do not need.',
        'summary' => ':count items selected · included in the calculation',
        'stock' => [
            'in' => 'In stock',
            'order' => 'On order 3-5 days',
        ],
        'items' => [
            'laminate' => [
                'name' => 'Quick-Step laminate, class 32',
                'calc' => '80 m² + 10% = 88 m² → 22 packs',
            ],
            'tile' => [
                'name' => 'Metlakh tile 20×20',
                'calc' => '32 m² + 15% = 37 m² → 26 boxes',
            ],
            'paint' => [
                'name' => 'Caparol matte wall paint',
                'calc' => '2 coats · 120 m² → 12 buckets',
            ],
            'door' => [
                'name' => 'MDF door, white',
                'calc' => '5 rooms → 5 pcs',
            ],
            'toilet' => [
                'name' => 'Wall-hung toilet, set',
                'calc' => '2 bathrooms → 2 pcs',
            ],
            'mixer' => [
                'name' => 'Mixer tap, chrome',
                'calc' => '3 points → 3 pcs',
            ],
        ],
    ],

    'summary' => [
        'k' => 'Step 6 · Final estimate',
        'h' => 'Renovation estimate for :area m²',
        'd' => 'An accurate calculation across all materials, works and services.',
        'total' => 'Total amount',
        'rows' => [
            'rough' => 'Rough renovation materials',
            'finish' => 'Finishing materials',
            'plumbing' => 'Plumbing',
            'electrical' => 'Electrics',
            'lighting' => 'Lighting',
            'doors' => 'Doors',
            'works' => 'Works',
            'delivery' => 'Delivery and lifting',
            'services' => 'Extra services',
            'reserve' => '10% reserve',
        ],
    ],

    'side' => [
        'label' => 'Current estimate',
        'meta' => ':area m² · :rooms rooms',
        'works' => 'Works',
        'cart_materials' => 'Materials from the cart',
        'reserve' => 'Reserve (10%)',
        'cart_banner' => ':count items in your cart',
        'use_in_calc' => 'Use in the calculation',
        'back' => 'Back',
        'next' => 'Next step',
        'calculate' => 'Calculate the estimate',
    ],

    'tips' => [
        'tile' => [
            't1' => 'By replacing premium tiles with standard ones',
            't2' => 'save 8,400 ₼',
        ],
        'doors' => [
            't1' => 'By choosing mid-range doors',
            't2' => 'cut the budget by 12%',
        ],
        'top' => [
            't1' => 'The most expensive category',
            't2' => 'is the finishing materials',
        ],
    ],

    'actions' => [
        'add_all' => 'Add all materials to the cart',
        'pdf' => 'PDF',
        'whatsapp' => 'WhatsApp',
        'save' => 'Save',
        'turnkey' => 'Order a turnkey renovation',
    ],

    'alerts' => [
        'added' => 'All materials were added to the cart.',
        'saved' => 'The estimate has been saved.',
        'turnkey' => 'Your turnkey request has been sent. A manager will contact you.',
        'whatsapp' => 'ARCHİ estimate: :total AZN',
    ],

    // Regex alternatives that mark a room as a bathroom (case-insensitive).
    'bathroom_pattern' => 'bath|sanitar|wc',
];
