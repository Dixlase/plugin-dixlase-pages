{{-- Stand-in for the translate-button component: prints what it was given. --}}
@props(['type', 'id' => null, 'sourceLocale' => null])
<span data-test="translate-button">{{ $type }}|{{ $id ?? 'null' }}|{{ $sourceLocale }}</span>
