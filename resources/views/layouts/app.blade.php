<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ in_array(app()->getLocale(), ['ar', 'he']) ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GSM2GO</title>

    @include('includes.link')

    @livewireStyles
</head>

<body>

    @include('partials.header')

    <main>
        {{ $slot }}
    </main>

    @include('partials.footer')

    @include('includes.script')

    @livewireScripts

   @stack('scripts')
</body>

</html>