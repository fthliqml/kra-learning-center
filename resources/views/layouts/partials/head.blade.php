<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="ie=edge">
@isset($title)
    <title>{{ $title }}</title>
@else
    <title>KRA</title>
@endisset
<link rel="icon" href="/images/logo_kra.png" type="image/png">

@yield('css')

@vite(['resources/css/app.css', 'resources/js/app.js'])

<!-- Preconnects to speed up third-party origins used by YouTube embeds and fonts -->
<link rel="preconnect" href="https://www.youtube-nocookie.com">
<link rel="preconnect" href="https://www.youtube.com">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
