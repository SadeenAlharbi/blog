@props(['size' => 40])

@php $px = (int) $size; @endphp

<span {{ $attributes->merge(['class' => 'inline-flex shrink-0 items-center justify-center rounded-xl bg-white ring-1 ring-black/5 shadow-sm overflow-hidden']) }}
      style="width: {{ $px }}px; height: {{ $px }}px;">
    <img src="{{ asset('images/logo.png') }}" alt="منصة المعرفة السعودية" class="w-full h-full object-contain p-1">
</span>
