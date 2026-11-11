@props(['name', 'user' => null, 'visible' => true])
<span {{ $attributes->class('user-avatar') }} aria-hidden="true">
    {{ mb_substr($name, 0, 1) }}
    @if ($visible && $user?->avatar_path)
        <img src="{{ $user->avatarUrl() }}" alt="" loading="lazy">
    @endif
</span>
