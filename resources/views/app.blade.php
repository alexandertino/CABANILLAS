<!DOCTYPE html>

<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>{{ config('app.name', 'Clínica Cabanillas') }}</title>

    @vite([
        'resources/css/app.css',
        'resources/js/app.js'
    ])

    @inertiaHead

</head>

<body class="antialiased">

    @inertia

</body>

</html>