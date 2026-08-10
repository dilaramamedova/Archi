{{--
  Detailed renovation calculator. In the old page every screen was built by an inline
  script from ~60 `dc-*` templates, so the markup stays in JS (resources/js/pages/
  calculator-detailed.js) and only the shell is server-rendered here. All strings and
  all demo data are handed to the module on #dcPage as data-* JSON, which keeps the
  page fully translatable without touching the template strings.
--}}
@php
    $tr = t('calculator-detailed');

    $cats = $tr['cats'];
    $floors = $tr['rooms']['floors'];
    $walls = $tr['rooms']['walls'];
    $stock = $tr['cart']['stock'];
    $cartItems = $tr['cart']['items'];

    // Prices, units and default flags are data, not text — they live here, the labels
    // come from the translation file.
    // TODO: Replace with dynamic data from controller (e.g. WorkType model or config)
    $works = [
        ['key' => 'demolition', 'price' => 8, 'unit' => 'm2', 'on' => true],
        ['key' => 'wall_leveling', 'price' => 14, 'unit' => 'm2', 'on' => true],
        ['key' => 'plaster', 'price' => 11, 'unit' => 'm2', 'on' => true],
        ['key' => 'putty', 'price' => 9, 'unit' => 'm2', 'on' => true],
        ['key' => 'screed', 'price' => 13, 'unit' => 'm2', 'on' => true],
        ['key' => 'waterproofing', 'price' => 10, 'unit' => 'm2', 'on' => false],
        ['key' => 'soundproofing', 'price' => 16, 'unit' => 'm2', 'on' => false],
        ['key' => 'electrical', 'price' => 28, 'unit' => 'point', 'on' => true],
        ['key' => 'plumbing', 'price' => 35, 'unit' => 'point', 'on' => true],
        ['key' => 'ventilation', 'price' => 22, 'unit' => 'm2', 'on' => false],
        ['key' => 'air_conditioning', 'price' => 180, 'unit' => 'pc', 'on' => false],
        ['key' => 'tile_prep', 'price' => 12, 'unit' => 'm2', 'on' => true],
        ['key' => 'paint_prep', 'price' => 7, 'unit' => 'm2', 'on' => true],
    ];

    $defaults = [
        'area' => 80,
        'city' => $tr['object']['default_city'],
        'floor' => $tr['object']['default_floor'],

        'objectTypes' => array_values($tr['object']['types']),
        'propertyTypes' => array_values($tr['object']['properties']),
        'conditions' => array_values($tr['object']['conditions']),
        'floorCovers' => array_values($floors),
        'wallCovers' => array_values($walls),

        // Finish rates per m² for each floor and wall cover, keyed by translated label.
        // computeEstimate() uses these when the cart has no matching finish materials.
        'floorRates' => array_combine(array_values($floors), [180, 350, 220, 250]),
        'wallRates'  => array_combine(array_values($walls),  [80, 120, 200, 250]),

        // Labour multipliers, positionally parallel to the three chip groups above.
        // The object-type values match the quick calculator (calculator.blade.php).
        'objectFactors' => [1, 1.1, 0.95, 1.05],
        'propertyFactors' => [1, 1.06],
        'conditionFactors' => [1, 0.95, 0.88],

        'rooms' => [
            ['name' => $tr['rooms']['defaults']['living'], 'area' => 24, 'h' => 2.9, 'per' => 20, 'doors' => 1, 'win' => 2, 'floor' => $floors['laminate'], 'wall' => $walls['paint'], 'heat' => true],
            ['name' => $tr['rooms']['defaults']['kitchen'], 'area' => 12, 'h' => 2.9, 'per' => 16, 'doors' => 1, 'win' => 1, 'floor' => $floors['tile'], 'wall' => $walls['paint'], 'heat' => false],
            ['name' => $tr['rooms']['defaults']['bathroom'], 'area' => 6, 'h' => 2.7, 'per' => 11, 'doors' => 1, 'win' => 0, 'floor' => $floors['tile'], 'wall' => $walls['tile'], 'heat' => true],
        ],
        'newRoom' => ['name' => $tr['rooms']['defaults']['new'], 'area' => 12, 'h' => 2.8, 'per' => 14, 'doors' => 1, 'win' => 1, 'floor' => $floors['laminate'], 'wall' => $walls['paint'], 'heat' => false],

        'works' => array_map(fn ($w) => [
            'key' => $w['key'],
            'name' => $tr['works']['items'][$w['key']],
            'price' => $w['price'],
            'unit' => $w['unit'],
            'unitLabel' => $tr['units'][$w['unit']],
            'on' => $w['on'],
        ], $works),

        // Fees behind the step-1 switches. Both labels read "I already have it", so the
        // fee lands in the estimate while the switch is OFF and disappears when it is on.
        // TODO: Replace with dynamic data from controller (service pricing config)
        'services' => ['design' => 2400, 'drawings' => 1500],

        // Fallback cart, used when localStorage has no 'archi-cart' entry.
        // TODO: Replace with dynamic data from controller (product catalog / default cart items)
        'cart' => [
            ['name' => $cartItems['laminate']['name'], 'brand' => 'Quick-Step', 'cat' => $cats['floor'], 'calc' => $cartItems['laminate']['calc'], 'price' => 1840, 'stock' => $stock['in'], 'inStock' => true],
            ['name' => $cartItems['tile']['name'], 'brand' => 'Marca Corona', 'cat' => $cats['tile'], 'calc' => $cartItems['tile']['calc'], 'price' => 765, 'stock' => $stock['in'], 'inStock' => true],
            ['name' => $cartItems['paint']['name'], 'brand' => 'Caparol', 'cat' => $cats['paint'], 'calc' => $cartItems['paint']['calc'], 'price' => 420, 'stock' => $stock['in'], 'inStock' => true],
            ['name' => $cartItems['door']['name'], 'brand' => 'Portalux', 'cat' => $cats['doors'], 'calc' => $cartItems['door']['calc'], 'price' => 2000, 'stock' => $stock['order'], 'inStock' => false],
            ['name' => $cartItems['toilet']['name'], 'brand' => 'Roca', 'cat' => $cats['plumbing'], 'calc' => $cartItems['toilet']['calc'], 'price' => 640, 'stock' => $stock['in'], 'inStock' => true],
            ['name' => $cartItems['mixer']['name'], 'brand' => 'Grohe', 'cat' => $cats['plumbing'], 'calc' => $cartItems['mixer']['calc'], 'price' => 390, 'stock' => $stock['in'], 'inStock' => true],
        ],

        // Step 4 rows: visible label + the cart category it is matched against.
        'materials' => [
            ['label' => $tr['materials']['labels']['floor'], 'cat' => $cats['floor']],
            ['label' => $tr['materials']['labels']['tile'], 'cat' => $cats['tile']],
            ['label' => $tr['materials']['labels']['paint'], 'cat' => $cats['paint']],
            ['label' => $tr['materials']['labels']['doors'], 'cat' => $cats['doors']],
            ['label' => $tr['materials']['labels']['plumbing'], 'cat' => $cats['plumbing']],
            ['label' => $tr['materials']['labels']['lighting'], 'cat' => ''],
        ],

        'cats' => $cats,
    ];
@endphp
<x-layout page="calculator-detailed" :title="t('calculator-detailed.title')">

<section class="dc-page" id="dcPage"
         data-url-catalog="{{ route('catalog') }}"
         {{-- popup buttons shown after "add all to cart" (shared/popup.js) --}}
         data-goto-cart="{{ t('common.go_to_cart') }}"
         data-continue="{{ t('common.continue') }}"
         data-i18n="{{ json_encode($tr) }}"
         data-defaults="{{ json_encode($defaults) }}">
  <div class="wrap-narrow dc-inner">
    <div class="dc-head">
      <div class="tag"><span class="line"></span><p>{{ t('calculator-detailed.head.tag') }}</p></div>
      <h1>{{ t('calculator-detailed.head.title') }}</h1>
      <div class="sub">{{ t('calculator-detailed.head.sub') }}</div>
    </div>

    {{-- stepper, current step and side panel are all rendered by calculator-detailed.js --}}
    <div class="dc-stepper" id="dcStepper"></div>

    <div class="dc-body">
      <div class="dc-main" id="dcMain"></div>
      <aside class="dc-side" id="dcSide"></aside>
    </div>
  </div>
</section>

{{-- check mark sprite referenced by the JS templates --}}
<svg class="hidden"><symbol id="chk" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="3" fill="none" stroke-linecap="round" stroke-linejoin="round"/></symbol></svg>

</x-layout>
