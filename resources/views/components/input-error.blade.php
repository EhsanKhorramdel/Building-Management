@props(['messages'])

@if ($messages)
    <ul {{ $attributes->merge(['class' => 'mt-4 text-sm text-red-600 dark:text-red-400 space-y-1']) }}>
        @foreach ((array) $messages as $message)
            <li class="text-right">{{ $message }}</li>
        @endforeach
    </ul>
@endif
