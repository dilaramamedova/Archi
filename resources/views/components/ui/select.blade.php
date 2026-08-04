{{--
  Native <select>. The custom, JS-driven dropdowns (catalog sort, product filters,
  the onboarding city combobox) are NOT this component — they are listbox widgets.

  Example:
    <x-ui.select :placeholder="__('register.form.select_placeholder')"
                 :options="__('register.cities')" />

  Props:
    variant     — null (consumer) · b2b
    options     — array of option labels (value = label)
    placeholder — first, empty-valued option (omitted → none)
--}}
@props([
    'variant' => null,
    'options' => [],
    'placeholder' => null,
    'value' => null,
])
<select {{ $attributes->merge(['class' => $variant === 'b2b' ? 'ui-control-b2b' : 'ui-control']) }}>
  @if ($placeholder !== null)<option value="">{{ $placeholder }}</option>@endif
  @foreach ($options as $option)
    <option @selected($value !== null && $value === $option)>{{ $option }}</option>
  @endforeach
  {{ $slot }}
</select>
