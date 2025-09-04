<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="ie=edge">
@isset($title)
    <title>{{ $title }}</title>
@else
    <title>KRA</title>
@endisset
<link rel="icon" href="/images/logo.avif" type="image/avif">

@yield('css')

@vite(['resources/css/app.css', 'resources/js/app.js'])
