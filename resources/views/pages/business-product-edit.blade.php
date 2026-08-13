{{-- Business cabinet — product create/edit (Figma 1342:9813) --}}
@php
  $isEdit = $product !== null;
  $spec = $isEdit ? ($product->specifications ?? []) : [];
  $statusKey = ! $isEdit ? 'draft' : ($product->is_visible ? $product->moderation_status : 'draft');

  // Translate, falling back to a literal while a key is still missing from the
  // translations table (t() echoes the key back otherwise).
  $tOr = function (string $key, string $fallback, array $replace = []) {
      $v = t($key, $replace);
      return is_string($v) && $v !== $key ? $v : strtr($fallback, array_combine(
          array_map(fn ($k) => ':'.$k, array_keys($replace)),
          array_map('strval', array_values($replace)),
      ));
  };

  $quota ??= ['limit' => null, 'used' => 0, 'remaining' => null, 'reached' => false];
  $atLimit = ! $isEdit && $quota['reached'];

  // Combobox option payload (id + display name in the current locale).
  // Three-level classifier cascade: Bölmə (section roots) → Qrup (child groups,
  // saved into products.category_id) → Sinif (product classes, sub_category_id).
  $subCategories ??= collect();
  $groups ??= collect();
  $comboData = [
      'brands' => $brands->map(fn ($b) => ['id' => $b->id, 'name' => (string) $b->name])->values(),
      'sections' => $categories->map(fn ($c) => ['id' => $c->id, 'name' => (string) $c->name])->values(),
      'groups' => $groups->map(fn ($c) => [
          'id' => $c->id, 'parent_id' => $c->parent_id, 'name' => (string) $c->name,
      ])->values(),
      'classes' => $subCategories->map(fn ($s) => [
          'id' => $s->id, 'group_id' => $s->category_id, 'name' => (string) $s->name,
      ])->values(),
  ];

  // Edit mode: resolve the stored category into section + group combobox values.
  // Legacy products may still point at a ROOT category — it then shows as the
  // section and the seller picks a group before the next save.
  $editCategory = $isEdit ? $product->category : null;
  $editGroup = $editCategory?->parent_id ? $editCategory : null;
  $editSection = $editCategory?->parent_id ? $editCategory->parent : $editCategory;

  // Existing EAV values + applications, handed to the JS renderer once the
  // class form definition arrives (shape mirrors product_attribute_values).
  $attrInitial = [];
  if ($isEdit) {
      foreach ($product->attributeValues as $v) {
          $entry = $attrInitial[$v->attribute_id] ?? [];
          if ($v->attribute_option_id !== null) { $entry['options'][] = $v->attribute_option_id; }
          if ($v->value_text !== null) { $entry['text'] = $v->value_text; }
          if ($v->value_numeric !== null) { $entry['numeric'] = (float) $v->value_numeric; }
          if ($v->value_min !== null) { $entry['min'] = (float) $v->value_min; }
          if ($v->value_max !== null) { $entry['max'] = (float) $v->value_max; }
          if ($v->value_bool !== null) { $entry['bool'] = (bool) $v->value_bool; }
          $attrInitial[$v->attribute_id] = $entry;
      }
  }
  $appInitial = $isEdit
      ? $product->applications->map(fn ($a) => ['id' => $a->id, 'name' => (string) $a->name])->values()
      : collect();

  // Free-form specification rows = everything in the JSON column except the keys
  // owned by the fixed inputs below (barcode/shelf/dimensions/material/color/country).
  $reservedSpecKeys = ['barcode', 'shelf', 'dimensions', 'material', 'color', 'country'];
  $customSpecs = collect($spec)->except($reservedSpecKeys)
      ->map(fn ($v) => is_array($v) ? implode(', ', $v) : (string) $v);

  // Özəlliklər textarea: one bullet per line (the column is a JSON list).
  $featuresText = $isEdit ? implode("\n", array_map('strval', (array) ($product->features ?? []))) : '';
  $featuresPlaceholder = $tOr('business-product-edit.features.placeholder', "Hər sətirdə bir özəllik yazın\nMəs.: 10 il zəmanət");

  // The classes the page's native selects already use — the combobox inputs reuse them.
  $comboInputCls = 'h-[43px] w-full rounded border border-black/15 bg-white px-3.5 pr-8 text-sm text-ink outline-none transition focus:border-black/40';
  $comboListCls = 'absolute top-[calc(100%+4px)] left-0 z-30 hidden max-h-56 w-full overflow-auto rounded border border-black/15 bg-white py-1 shadow-lg';
@endphp
<x-layout page="business-product-edit" :title="t('business-product-edit.title')" bodyClass="bg-gray-soft2">

<x-cabinet.shell ns="business-product-edit" active="products" class="text-ink"
    :show-view-button="false"
    :heading="$isEdit ? t('business-product-edit.heading_edit') : t('business-product-edit.heading_new')">

  @if ($isEdit && $product->moderation_status === 'rejected')
    <div class="rounded border border-[#e5484d]/40 bg-[#fdecec] px-5 py-4">
      <p class="text-sm font-semibold text-[#e5484d]">{{ t('business-product-edit.rejected_note') }}</p>
      <p class="mt-1 text-sm text-black/70">{{ $product->rejection_reason }}</p>
    </div>
  @endif

  {{-- Product allowance. Admins have no limit, so nothing is shown for them. --}}
  @if (! $isEdit && $quota['limit'] !== null)
    <div @class([
        'rounded border px-5 py-4 text-sm',
        'border-[#e5484d]/40 bg-[#fdecec] text-[#e5484d] font-semibold' => $atLimit,
        'border-black/10 bg-white text-black/70' => ! $atLimit,
    ])>
      @if ($atLimit)
        {{ $tOr('business-product-edit.limit.reached', 'Maksimum :limit məhsul əlavə edə bilərsiniz.', ['limit' => $quota['limit']]) }}
      @else
        {{ $tOr('business-product-edit.limit.remaining', 'Daha :remaining məhsul əlavə edə bilərsiniz (:used/:limit).', [
            'remaining' => $quota['remaining'], 'used' => $quota['used'], 'limit' => $quota['limit'],
        ]) }}
      @endif
    </div>
  @endif

  <form id="productForm"
        data-action="{{ $isEdit ? route('business.products.update', $product) : route('business.products.store') }}"
        data-method="{{ $isEdit ? 'PUT' : 'POST' }}"
        data-l-image-required="{{ $tOr('business-product-edit.save.error_image', 'Ən azı 1 şəkil əlavə edin') }}"
        class="flex flex-col gap-5">

    {{-- Images --}}
    <x-cabinet.card gap="gap-4" :title="t('business-product-edit.images.title')" :desc="t('business-product-edit.images.desc')">
      <div class="flex flex-wrap gap-3" id="imageSlots">
        @if ($isEdit)
          @foreach ($product->images as $img)
            <div class="image-slot group relative h-[150px] w-[200px] overflow-hidden rounded border border-black/15" data-existing-id="{{ $img->id }}">
              <img src="{{ storage_url($img->path) }}" alt="" class="size-full object-cover">
              <button type="button" data-remove-image
                      class="absolute right-2 top-2 hidden size-7 items-center justify-center rounded-full bg-black/60 text-white group-hover:flex">✕</button>
            </div>
          @endforeach
        @endif
        <label class="flex h-[150px] w-[200px] cursor-pointer flex-col items-center justify-center gap-2 rounded border border-dashed border-black/25 text-black/40 transition hover:border-black/50" id="addImageSlot">
          <span class="text-xl">＋</span>
          <span class="text-xs font-medium">{{ t('business-product-edit.images.add') }}</span>
          <input type="file" accept="image/jpeg,image/png,image/webp" multiple class="hidden" id="imageInput">
        </label>
      </div>
    </x-cabinet.card>

    {{-- Basic info. The card/row/field wrappers ship overflow-hidden from the cabinet
         shell; the combobox dropdowns are absolutely positioned, so these three
         wrappers get overflow-visible! or the lists would be clipped. --}}
    <x-cabinet.card gap="gap-4" :title="t('business-product-edit.basic.title')" class="overflow-visible!">
      <x-cabinet.field full :label="t('business-product-edit.basic.name') . ' *'" for="pName">
        <x-ui.input variant="b2b" id="pName" name="name" :value="$isEdit ? $product->name : ''" :placeholder="t('business-product-edit.basic.name_placeholder')" required />
      </x-cabinet.field>

      {{-- Classifier cascade level 1+2: Bölmə (root section) → Qrup (child group).
           The GROUP id is what products.category_id stores; the section id rides
           along so the backend can verify the chain. --}}
      <div class="cab-field-row overflow-visible!">
        <x-cabinet.field class="overflow-visible!" :label="$tOr('product-form-attrs.cascade.section', 'Bölmə') . ' *'" for="pSection">
          <div class="relative w-full" data-combo="section"
               data-l-empty="{{ $tOr('business-product-edit.combo.empty', 'Nəticə tapılmadı') }}">
            <input type="text" id="pSection" data-combo-input autocomplete="off" role="combobox" aria-expanded="false"
                   value="{{ $editSection?->name }}"
                   placeholder="{{ t('business-product-edit.basic.select') }}"
                   class="{{ $comboInputCls }}">
            <input type="hidden" name="section_id" data-combo-id value="{{ $editSection?->id }}">
            <span class="pointer-events-none absolute top-1/2 right-3 -translate-y-1/2 text-xs text-black/40">▾</span>
            <ul data-combo-list class="{{ $comboListCls }}"></ul>
          </div>
        </x-cabinet.field>
        <x-cabinet.field class="overflow-visible!" :label="$tOr('product-form-attrs.cascade.group', 'Qrup') . ' *'" for="pGroup">
          <div class="relative w-full" data-combo="group"
               data-l-empty="{{ $tOr('business-product-edit.combo.empty', 'Nəticə tapılmadı') }}">
            <input type="text" id="pGroup" data-combo-input autocomplete="off" role="combobox" aria-expanded="false"
                   value="{{ $editGroup?->name }}"
                   placeholder="{{ t('business-product-edit.basic.select') }}"
                   class="{{ $comboInputCls }}">
            <input type="hidden" name="category_id" data-combo-id value="{{ $isEdit ? $product->category_id : '' }}">
            <span class="pointer-events-none absolute top-1/2 right-3 -translate-y-1/2 text-xs text-black/40">▾</span>
            <ul data-combo-list class="{{ $comboListCls }}"></ul>
          </div>
        </x-cabinet.field>
      </div>

      {{-- Cascade level 3 (Sinif = product class) + brand. The class row half is
           shown by JS as soon as the chosen group actually has active classes. --}}
      @php $showSubRow = $isEdit && ($product->sub_category_id || $comboData['classes']->where('group_id', $product->category_id)->isNotEmpty()); @endphp
      <div class="cab-field-row overflow-visible!">
        <x-cabinet.field class="overflow-visible!" :label="$tOr('product-form-attrs.cascade.class', 'Məhsul sinfi')" for="pSubCategory"
                         id="subCatRow" :style="$showSubRow ? '' : 'display:none'">
          <div class="relative w-full" data-combo="subcategory"
               data-l-empty="{{ $tOr('business-product-edit.combo.empty', 'Nəticə tapılmadı') }}">
            <input type="text" id="pSubCategory" data-combo-input autocomplete="off" role="combobox" aria-expanded="false"
                   value="{{ $isEdit ? $product->subCategory?->name : '' }}"
                   placeholder="{{ t('business-product-edit.basic.select') }}"
                   class="{{ $comboInputCls }}">
            <input type="hidden" name="sub_category_id" data-combo-id value="{{ $isEdit ? $product->sub_category_id : '' }}">
            <span class="pointer-events-none absolute top-1/2 right-3 -translate-y-1/2 text-xs text-black/40">▾</span>
            <ul data-combo-list class="{{ $comboListCls }}"></ul>
          </div>
        </x-cabinet.field>
        <x-cabinet.field class="overflow-visible!" :label="t('business-product-edit.basic.brand')" for="pBrand">
          <div class="relative w-full" data-combo="brand"
               data-l-empty="{{ $tOr('business-product-edit.combo.empty', 'Nəticə tapılmadı') }}"
               data-l-add="{{ $tOr('business-product-edit.combo.add_brand', '«:name» — yeni brend əlavə et') }}">
            <input type="text" id="pBrand" data-combo-input autocomplete="off" role="combobox" aria-expanded="false"
                   value="{{ $isEdit ? $product->brand?->name : '' }}"
                   placeholder="{{ t('business-product-edit.basic.select') }}"
                   class="{{ $comboInputCls }}">
            <input type="hidden" name="brand_id" data-combo-id value="{{ $isEdit ? $product->brand_id : '' }}">
            <input type="hidden" name="new_brand" data-combo-new value="">
            <span class="pointer-events-none absolute top-1/2 right-3 -translate-y-1/2 text-xs text-black/40">▾</span>
            <ul data-combo-list class="{{ $comboListCls }}"></ul>
          </div>
        </x-cabinet.field>
      </div>

      <div class="cab-field-row">
        <x-cabinet.field :label="t('business-product-edit.basic.sku')" for="pSku">
          <x-ui.input variant="b2b" id="pSku" name="sku" :value="$isEdit ? $product->sku : ''" :placeholder="t('business-product-edit.basic.sku_placeholder')" />
        </x-cabinet.field>
        <x-cabinet.field :label="t('business-product-edit.basic.barcode')" for="pBarcode">
          <x-ui.input variant="b2b" id="pBarcode" name="barcode" :value="$spec['barcode'] ?? ''" :placeholder="t('business-product-edit.basic.barcode_placeholder')" />
        </x-cabinet.field>
      </div>
    </x-cabinet.card>

    {{-- ONE place for everything a seller can say about the product, in three layers:
         1. the class's own fields (loaded from the class-definition endpoint; only
            required basic ones up front, the rest behind "Daha ətraflı doldur"),
         2. the generic fields every product may carry — each one hides itself when
            the chosen class already asks the same thing (no field twice),
         3. free-form rows for anything the two layers above do not cover. --}}
    <x-cabinet.card gap="gap-4" :title="$tOr('product-form-attrs.section.title', 'Xüsusiyyətlər')"
        id="classAttrsCard" class="overflow-visible!"
        data-endpoint="{{ url('/business/api/sub-categories') }}"
        data-l-select="{{ $tOr('product-form-attrs.field.select', 'Seçin') }}"
        data-l-yes="{{ $tOr('product-form-attrs.field.yes', 'Bəli') }}"
        data-l-no="{{ $tOr('product-form-attrs.field.no', 'Xeyr') }}"
        data-l-min="{{ $tOr('product-form-attrs.field.min', 'Min') }}"
        data-l-max="{{ $tOr('product-form-attrs.field.max', 'Maks') }}"
        data-l-req-badge="{{ $tOr('product-form-attrs.pro.required_badge', 'Nəşr üçün tövsiyə olunur') }}"
        data-l-pro-show="{{ $tOr('product-form-attrs.pro.show', 'Daha ətraflı doldur (:count əlavə sahə)') }}"
        data-l-pro-hide="{{ $tOr('product-form-attrs.pro.hide', 'Əlavə sahələri gizlət') }}"
        data-l-required="{{ $tOr('product-form-attrs.validation.required', ':name sahəsi doldurulmalıdır') }}"
        data-l-load-error="{{ $tOr('product-form-attrs.section.load_error', 'Sahələri yükləmək mümkün olmadı. Yenidən cəhd edin.') }}">
      <p id="classAttrsDesc" style="display:none" class="text-[13px] leading-[1.45] text-black/50">{{ $tOr('product-form-attrs.section.desc', 'Seçilmiş sinfə uyğun sahələri doldurun — alıcılar məhsulu bu xüsusiyyətlərə görə tapır.') }}</p>

      <div id="attrBasic" class="flex w-full flex-col gap-4"></div>

      <button type="button" id="attrProToggle" style="display:none"
              class="h-[38px] self-start rounded border border-black/20 px-4 text-[13px] font-semibold text-ink transition hover:bg-gray-soft2"></button>
      <div id="attrPro" class="hidden w-full flex-col gap-4"></div>

      {{-- Tətbiq sahəsi — cross-category application areas suggested by the class. --}}
      <div id="appBlock" style="display:none" class="flex w-full flex-col items-start gap-2 border-t border-black/10 pt-4">
        <p class="ui-label-b2b">{{ $tOr('product-form-attrs.apps.title', 'Tətbiq sahəsi') }}</p>
        <p class="text-[13px] leading-[1.45] text-black/50">{{ $tOr('product-form-attrs.apps.notice', 'Bu məhsulu eyni anda bir neçə kateqoriyada satmaq olar — bütün uyğun variantları qeyd edin') }}</p>
        <input type="hidden" name="applications_present" value="1" disabled id="appsPresent">
        <div id="appChips" class="flex w-full flex-wrap gap-2"></div>
      </div>

      {{-- Layer 2: generic fields. `data-generic-field` marks the wrapper,
           `data-generic-match` lists the words a class field must contain for this
           one to step aside and `data-generic-except` the look-alikes that must not
           count — see syncGenericFields() in the page module. --}}
      <div id="genericAttrs" class="flex w-full flex-col gap-4 border-t border-black/10 pt-4">
        <p class="text-[13px] leading-[1.45] text-black/50">{{ $tOr('product-form-attrs.generic.desc', 'Ümumi xüsusiyyətlər. Seçdiyiniz sinif eyni sahəni özü soruşursa, buradakı təkrar sahə gizlənir.') }}</p>
        <div class="grid w-full grid-cols-2 items-start gap-4 max-[640px]:grid-cols-1">
          <div data-generic-field data-generic-match="ölçü|qabarit" data-generic-except="göz ölçüsü|çip ölçüsü|yataq yerinin ölçüsü">
            <x-cabinet.field :label="t('business-product-edit.description.dimensions')" for="pDim">
              <x-ui.input variant="b2b" id="pDim" name="dimensions" :value="$spec['dimensions'] ?? ''" :placeholder="t('business-product-edit.description.dimensions_placeholder')" />
            </x-cabinet.field>
          </div>
          <div data-generic-field data-generic-match="material">
            <x-cabinet.field :label="t('business-product-edit.description.material')" for="pMaterial">
              <x-ui.input variant="b2b" id="pMaterial" name="material" :value="$spec['material'] ?? ''" :placeholder="t('business-product-edit.description.material_placeholder')" />
            </x-cabinet.field>
          </div>
          <div data-generic-field data-generic-match="rəng" data-generic-except="rəng temperaturu">
            <x-cabinet.field :label="t('business-product-edit.description.color')" for="pColor">
              <x-ui.input variant="b2b" id="pColor" name="color" :value="$spec['color'] ?? ''" :placeholder="t('business-product-edit.description.color_placeholder')" />
            </x-cabinet.field>
          </div>
          <div data-generic-field data-generic-match="ölkə">
            <x-cabinet.field :label="t('business-product-edit.description.country')" for="pCountry">
              <x-ui.input variant="b2b" id="pCountry" name="country" :value="$spec['country'] ?? ''" :placeholder="t('business-product-edit.description.country_placeholder')" />
            </x-cabinet.field>
          </div>
        </div>
      </div>

      {{-- Layer 3: free-form rows. Saved into the same `specifications` JSON the
           admin KeyValue field edits, so both surfaces stay in sync. --}}
      <div class="flex w-full flex-col items-start gap-2 border-t border-black/10 pt-4">
        <p class="ui-label-b2b">{{ $tOr('business-product-edit.attributes.title', 'Siyahıda olmayan xüsusiyyət') }}</p>
        <p class="text-[13px] leading-[1.45] text-black/50">{{ $tOr('business-product-edit.attributes.desc', 'Yuxarıdakı sahələrdə olmayan nəyisə yazmaq istəyirsinizsə — məsələn: Çəki → 5 kq.') }}</p>
        <div id="attrRows" class="flex w-full flex-col gap-3"
             data-l-key-ph="{{ $tOr('business-product-edit.attributes.key_placeholder', 'Məs.: Çəki') }}"
             data-l-value-ph="{{ $tOr('business-product-edit.attributes.value_placeholder', 'Məs.: 5 kq') }}"
             data-l-remove="{{ $tOr('business-product-edit.attributes.remove', 'Sil') }}">
          @foreach ($customSpecs as $k => $v)
            <div class="flex w-full items-center gap-4 max-[640px]:gap-2" data-attr-row>
              <input type="text" name="attributes[{{ $loop->index }}][key]" value="{{ $k }}" maxlength="60"
                     placeholder="{{ $tOr('business-product-edit.attributes.key_placeholder', 'Məs.: Çəki') }}"
                     class="h-[43px] w-full min-w-0 flex-1 rounded border border-black/15 bg-white px-3.5 text-sm text-ink outline-none transition focus:border-black/40">
              <input type="text" name="attributes[{{ $loop->index }}][value]" value="{{ $v }}" maxlength="255"
                     placeholder="{{ $tOr('business-product-edit.attributes.value_placeholder', 'Məs.: 5 kq') }}"
                     class="h-[43px] w-full min-w-0 flex-1 rounded border border-black/15 bg-white px-3.5 text-sm text-ink outline-none transition focus:border-black/40">
              <button type="button" data-attr-remove aria-label="{{ $tOr('business-product-edit.attributes.remove', 'Sil') }}"
                      class="flex size-[43px] shrink-0 items-center justify-center rounded border border-black/15 text-black/40 transition hover:border-error hover:text-error">✕</button>
            </div>
          @endforeach
        </div>
        <button type="button" id="attrAdd"
                class="mt-1 h-[38px] rounded border border-black/20 px-4 text-[13px] font-semibold text-ink transition hover:bg-gray-soft2">
          + {{ $tOr('business-product-edit.attributes.add', 'Xüsusiyyət əlavə et') }}
        </button>
      </div>
    </x-cabinet.card>

    <script type="application/json" id="attrInitial">@json(['values' => $attrInitial, 'applications' => $appInitial])</script>

    {{-- Pricing & stock --}}
    <x-cabinet.card gap="gap-4" :title="t('business-product-edit.pricing.title')">
      <div class="cab-field-row">
        <x-cabinet.field :label="t('business-product-edit.pricing.price') . ' *'" for="pPrice">
          <x-ui.input variant="b2b" id="pPrice" name="price" type="number" step="0.01" min="0" :value="$isEdit ? $product->price : ''" placeholder="23.90" required />
        </x-cabinet.field>
        <x-cabinet.field :label="t('business-product-edit.pricing.old_price')" for="pOldPrice">
          <x-ui.input variant="b2b" id="pOldPrice" name="old_price" type="number" step="0.01" min="0" :value="$isEdit ? $product->old_price : ''" :placeholder="t('business-product-edit.pricing.old_price_placeholder')" />
        </x-cabinet.field>
      </div>

      <div class="cab-field-row">
        <x-cabinet.field :label="t('business-product-edit.pricing.unit') . ' *'" for="pUnit">
          <select id="pUnit" name="unit"
                  class="h-[43px] w-full rounded border border-black/15 bg-white px-3.5 text-sm text-ink outline-none transition focus:border-black/40">
            @foreach (t('business-product-edit.pricing.units') as $uk => $uv)
              <option value="{{ $uk }}" @selected($isEdit && $product->unit === $uk)>{{ $uv }}</option>
            @endforeach
          </select>
        </x-cabinet.field>
        <x-cabinet.field :label="t('business-product-edit.pricing.stock') . ' *'" for="pStock">
          <x-ui.input variant="b2b" id="pStock" name="stock" type="number" min="0" :value="$isEdit ? $product->stock : ''" placeholder="148" required />
        </x-cabinet.field>
      </div>

      <div class="cab-field-row">
        <x-cabinet.field :label="t('business-product-edit.pricing.min_order')" for="pMinOrder">
          <x-ui.input variant="b2b" id="pMinOrder" name="min_order" type="number" min="1" :value="$isEdit ? $product->min_order : ''" :placeholder="t('business-product-edit.pricing.min_order_placeholder')" />
        </x-cabinet.field>
        <x-cabinet.field :label="t('business-product-edit.pricing.shelf')" for="pShelf">
          <x-ui.input variant="b2b" id="pShelf" name="shelf" :value="$spec['shelf'] ?? ''" :placeholder="t('business-product-edit.pricing.shelf_placeholder')" />
        </x-cabinet.field>
      </div>
    </x-cabinet.card>

    {{-- Description & specs --}}
    <x-cabinet.card gap="gap-4" :title="t('business-product-edit.description.title')">
      <x-cabinet.field full :label="t('business-product-edit.description.short')" for="pDesc">
        <x-ui.textarea variant="b2b" id="pDesc" name="description" class="h-[110px] resize-none" :placeholder="t('business-product-edit.description.short_placeholder')">{{ $isEdit ? $product->description : '' }}</x-ui.textarea>
      </x-cabinet.field>

      {{-- Özəlliklər — one bullet per line, shown as ticks next to the price on the
           product page. Left empty, the product falls back to the marketplace-wide
           defaults (product.features.default_1..3). --}}
      <x-cabinet.field full :label="$tOr('business-product-edit.features.title', 'Özəlliklər')" for="pFeatures">
        <x-ui.textarea variant="b2b" id="pFeatures" name="features" class="h-[110px] resize-none"
                       :placeholder="$featuresPlaceholder">{{ $featuresText }}</x-ui.textarea>
        <p class="mt-1.5 text-[13px] leading-[1.45] text-black/50">{{ $tOr('business-product-edit.features.desc', 'Hər sətir məhsul səhifəsində ayrıca işarə ilə göstərilir. Boş buraxsanız, ümumi özəlliklər göstəriləcək.') }}</p>
      </x-cabinet.field>
    </x-cabinet.card>

    <script type="application/json" id="comboData">@json($comboData)</script>

    {{-- Save bar (dark) --}}
    <div class="flex items-center justify-between gap-4 rounded bg-black/90 px-6 py-5 max-[900px]:flex-col max-[900px]:items-stretch">
      <p class="text-[13px] font-medium text-white/85">{{ t('business-product-edit.save.note') }}</p>
      <div class="flex gap-2.5 max-[640px]:flex-col">
        <button type="submit" data-publish="0" @disabled($atLimit)
                class="h-[41px] rounded border border-white/60 px-5 text-sm font-semibold text-white transition hover:bg-white/10 disabled:cursor-not-allowed disabled:opacity-40">{{ t('business-product-edit.save.draft') }}</button>
        <button type="submit" data-publish="1" @disabled($atLimit)
                class="h-[41px] rounded bg-yellow px-5 text-sm font-semibold text-ink transition hover:brightness-95 disabled:cursor-not-allowed disabled:opacity-40">{{ t('business-product-edit.save.publish') }}</button>
      </div>
    </div>
  </form>

</x-cabinet.shell>

</x-layout>
