<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#1A1612">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Lessy aimé Rodrigue</title>
<link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@600;800&family=Inter:wght@500;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/lessy.css') }}">
</head>
<body>
<div id="app">
@yield('content')
</div>
<div id="modal"></div>
<div id="toast">{{ session('toast') }}</div>
<script>
window.LESSY = {
  csrf: @json(csrf_token()),
  admin: @json((bool) ($admin ?? session('lessy_admin'))),
  error: @json(session('error'))
};
</script>
<script src="{{ asset('js/bo.js') }}"></script>
@stack('scripts')
</body>
</html>
