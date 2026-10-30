<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#153e3b">
    <title>@yield('title', 'Боги Древней Греции') — Олимп</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ mix('css/styles.css') }}">
    <link rel="stylesheet" href="{{ asset('css/validation-fallback.css') }}?v=2">
</head>

<body>
    <a class="skip-link" href="#main">Перейти к содержимому</a>
    @include('partials.header')
    @if (session('success'))
        <div class="site-container mt-4">
            <div class="alert alert-success mb-0" role="status">{{ session('success') }}</div>
        </div>
    @endif
    @yield('content')
    @include('partials.footer')
    <script src="{{ mix('js/index.js') }}" defer></script>
    <script src="{{ asset('js/validation-fallback.js') }}?v=2" defer></script>
    <script src="{{ asset('js/live-validation.js') }}?v=4" defer></script>
</body>

</html>
