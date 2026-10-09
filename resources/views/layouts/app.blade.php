<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name'))</title>
    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
</head>
<body>
    <div class="page">
        <header class="page__header">
            <h1 class="page__title">@yield('heading', config('app.name'))</h1>
            @hasSection('subheading')
                <p class="page__subtitle">@yield('subheading')</p>
            @endif
        </header>

        <main>
            @yield('content')
        </main>
    </div>
</body>
</html>
