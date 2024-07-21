<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Confirm CSR</title>
    <link href="{{ URL::asset('styles.css') }}" rel="stylesheet" type="text/css">
</head>
<body>
<nav>
    <div class="heading">
        <h4>Onelink-CSR</h4>
    </div>
    <ul class="nav-links">
        <li><a class="active" href="{{ url('/') }}">Home</a></li>
    </ul>
</nav>
</body>
</html>
