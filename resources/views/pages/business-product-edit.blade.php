{{-- Business cabinet — product create/edit (Figma 1342:9813) --}}
@php
  $isEdit = $product !== null;
  $spec = $isEdit ? ($product->specifications ?? []) : [];
  $statusKey = ! $isEdit ? 'draft' : ($product->is_visible ? $product->moderation_status : 'draft');
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

  <form id="productForm"
        data-action="{{ $isEdit ? route('business.products.update', $product) : route('business.products.store') }}"
        data-method="{{ $isEdit ? 'PUT' : 'POST' }}"
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

    {{-- Basic info --}}
    <x-cabinet.card gap="gap-4" :title="t('business-product-edit.basic.title')">
      <x-cabinet.field full :label="t('business-product-edit.basic.name') . ' *'" for="pName">
        <x-ui.input variant="b2b" id="pName" name="name" :value="$isEdit ? $product->name : ''" :placeholder="t('business-product-edit.basic.name_placeholder')" required />
      </x-cabinet.field>

      <div class="cab-field-row">
        <x-cabinet.field :label="t('business-product-edit.basic.category') . ' *'" for="pCategory">
          <select id="pCategory" name="category_id" required
                  class="h-[43px] w-full rounded border border-black/15 bg-white px-3.5 text-sm text-ink outline-none transition focus:border-black/40">
            <option value="">{{ t('business-product-edit.basic.select') }}</option>
            @foreach ($categories as $cat)
              <option value="{{ $cat->id }}" @selected($isEdit && $product->category_id === $cat->id)>{{ $cat->name }}</option>
            @endforeach
          </select>
        </x-cabinet.field>
        <x-cabinet.field :label="t('business-product-edit.basic.brand')" for="pBrand">
          <select id="pBrand" name="brand_id"
                  class="h-[43px] w-full rounded border border-black/15 bg-white px-3.5 text-sm text-ink outline-none transition focus:border-black/40">
            <option value="">{{ t('business-product-edit.basic.select') }}</option>
            @foreach ($brands as $brand)
              <option value="{{ $brand->id }}" @selected($isEdit && $product->brand_id === $brand->id)>{{ $brand->name }}</option>
            @endforeach
          </select>
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

      <div class="cab-field-row">
        <x-cabinet.field :label="t('business-product-edit.description.dimensions')" for="pDim">
          <x-ui.input variant="b2b" id="pDim" name="dimensions" :value="$spec['dimensions'] ?? ''" :placeholder="t('business-product-edit.description.dimensions_placeholder')" />
        </x-cabinet.field>
        <x-cabinet.field :label="t('business-product-edit.description.material')" for="pMaterial">
          <x-ui.input variant="b2b" id="pMaterial" name="material" :value="$spec['material'] ?? ''" :placeholder="t('business-product-edit.description.material_placeholder')" />
        </x-cabinet.field>
      </div>

      <div class="cab-field-row">
        <x-cabinet.field :label="t('business-product-edit.description.color')" for="pColor">
          <x-ui.input variant="b2b" id="pColor" name="color" :value="$spec['color'] ?? ''" :placeholder="t('business-product-edit.description.color_placeholder')" />
        </x-cabinet.field>
        <x-cabinet.field :label="t('business-product-edit.description.country')" for="pCountry">
          <x-ui.input variant="b2b" id="pCountry" name="country" :value="$spec['country'] ?? ''" :placeholder="t('business-product-edit.description.country_placeholder')" />
        </x-cabinet.field>
      </div>
    </x-cabinet.card>

    {{-- Save bar (dark) --}}
    <div class="flex items-center justify-between gap-4 rounded bg-black/90 px-6 py-5 max-[900px]:flex-col max-[900px]:items-stretch">
      <p class="text-[13px] font-medium text-white/85" id="productFormMsg">{{ t('business-product-edit.save.note') }}</p>
      <div class="flex gap-2.5 max-[640px]:flex-col">
        <button type="submit" data-publish="0"
                class="h-[41px] rounded border border-white/60 px-5 text-sm font-semibold text-white transition hover:bg-white/10">{{ t('business-product-edit.save.draft') }}</button>
        <button type="submit" data-publish="1"
                class="h-[41px] rounded bg-yellow px-5 text-sm font-semibold text-ink transition hover:brightness-95">{{ t('business-product-edit.save.publish') }}</button>
      </div>
    </div>
  </form>

</x-cabinet.shell>

</x-layout>
