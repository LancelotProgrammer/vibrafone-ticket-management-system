<!DOCTYPE html>
<html>

<head>
    @include('partials.head')
</head>

<body>
    @include('partials.nav')

    @yield('content')

    @include('partials.footer')

    @include('partials.script')
</body>

</html>
