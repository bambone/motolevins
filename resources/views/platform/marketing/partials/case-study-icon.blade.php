{{--
  Иконка вертикали для блока кейсов (платформа).
  @var string $icon  moto | driving | detailing | academic | services | legal | auto | другой ключ → дефолт
  @var string $svg_class  классы для svg
--}}
@php
    $i = $icon ?? '';
    $cls = $svg_class ?? '';
@endphp

@switch($i)
    @case('moto')
        <svg class="{{ $cls !== '' ? $cls : 'h-6 w-6' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="5.5" cy="17.5" r="3.25"/>
            <circle cx="18" cy="17.5" r="3.25"/>
            <path d="M8.75 17.5h5.25"/>
            <path d="M14 17.5l-1.25-6.5-3.25 1.25L8 8.25H5.75"/>
            <path d="M12.75 11l3-2.75h3.5L21 11.5V14"/>
            <path d="M17.25 11l1.5 3.25"/>
        </svg>
        @break
    @case('driving')
        <svg class="{{ $cls !== '' ? $cls : 'h-6 w-6' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="13" r="5.25"/>
            <circle cx="12" cy="13" r="1.75"/>
            <path d="M12 7.75V5.5M8.2 9.1 6.9 7.1M15.8 9.1 17.1 7.1"/>
            <path d="M5 21h14" stroke-width="1.5" opacity="0.45"/>
            <path d="M8 21v-1.5M12 21v-2M16 21v-1.5" stroke-width="1.25" opacity="0.35"/>
        </svg>
        @break
    @case('detailing')
        <svg class="{{ $cls !== '' ? $cls : 'h-6 w-6' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
            <path d="M4 16.5V14l1.2-2.2a2.2 2.2 0 0 1 2-1.3h11.6a2.2 2.2 0 0 1 2 1.3L20 14v2.5" opacity="0.9"/>
            <path d="M3.5 16.5h17" />
            <circle cx="7.5" cy="16.5" r="1.6"/>
            <circle cx="16.5" cy="16.5" r="1.6"/>
            <path d="M5.2 10.5h2.1l1.4-2.3h6.6l1.4 2.3h2.1" />
            <path d="M9 8.2c1.2-1.1 2.6-1.5 3-1.5h.2c.4 0 1.8.4 3 1.5" opacity="0.7"/>
        </svg>
        @break
    @case('academic')
        <svg class="{{ $cls !== '' ? $cls : 'h-6 w-6' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 14l9-5-9-5-9 5 9 5z"/>
            <path d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/>
            <path d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222"/>
        </svg>
        @break
    @case('services')
        <svg class="{{ $cls !== '' ? $cls : 'h-6 w-6' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
            <path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            <path d="M9 12h6M9 16h4"/>
        </svg>
        @break
    @case('legal')
        <svg class="{{ $cls !== '' ? $cls : 'h-6 w-6' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="7.5" cy="15.5" r="2.5"/>
            <circle cx="16.5" cy="15.5" r="2.5"/>
            <path d="m6 13-2-9 4.5 2L12 8l4.5 2 2 9"/>
            <path d="M15 21H9"/>
        </svg>
        @break
    @case('auto')
        <svg class="{{ $cls !== '' ? $cls : 'h-6 w-6' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
            <path d="M7 17h10l-.5-3.5h-9L7 17z"/>
            <circle cx="9.5" cy="17.5" r="1.5"/>
            <circle cx="14.5" cy="17.5" r="1.5"/>
            <path d="M6 17H5l1.2-7h11.6L19 17h-1"/>
            <path d="M12 10V8"/>
        </svg>
        @break
    @default
        <svg class="{{ $cls !== '' ? $cls : 'h-6 w-6' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
            <path d="M4 6h16M4 12h16M4 18h10"/>
        </svg>
@endswitch
