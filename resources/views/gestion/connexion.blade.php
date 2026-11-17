<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>Connexion · Gestion Ardoise241</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box}body{margin:0;min-height:100vh;display:grid;place-items:center;background:#1A1612;font:15px/1.45 Inter,system-ui,sans-serif;color:#1A1612;padding:16px}
.box{background:#fff;border-radius:20px;padding:32px 28px;width:100%;max-width:380px;box-shadow:0 20px 60px rgba(0,0,0,.35)}
.logo{width:52px;height:52px;border-radius:14px;background:#EDBE2E;display:grid;place-items:center;font-weight:800;font-size:24px;margin-bottom:16px}
h1{margin:0 0 4px;font-size:22px;font-weight:800}p{margin:0 0 22px;color:#6F675C}
label{display:block;font-size:13px;font-weight:600;color:#6F675C;margin:14px 0 6px}
input[type=email],input[type=password]{width:100%;font:inherit;border:1px solid #E7E2D8;border-radius:10px;padding:11px 12px}
input:focus{outline:2px solid #EDBE2E;border-color:#1A1612}
.rem{display:flex;gap:8px;align-items:center;margin-top:14px;font-size:14px;color:#6F675C}
button{margin-top:20px;width:100%;border:0;border-radius:10px;padding:12px;background:#1A1612;color:#fff;font:inherit;font-weight:700;cursor:pointer}
.err{background:#FBE4E1;color:#A1261B;border-radius:10px;padding:10px 12px;font-weight:600;margin-bottom:10px}
</style>
</head>
<body>
<form class="box" method="post" action="{{ route('gestion.login.store') }}">
  @csrf
  <div class="logo">₣</div>
  <h1>Gestion Ardoise241</h1>
  <p>Suivi des clients de l'application.</p>
  @if (session('error'))<div class="err">{{ session('error') }}</div>@endif
  @if ($errors->any())<div class="err">{{ $errors->first() }}</div>@endif
  <label for="email">E-mail</label>
  <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
  <label for="password">Mot de passe</label>
  <input id="password" type="password" name="password" required autocomplete="current-password">
  <label class="rem"><input type="checkbox" name="remember" value="1"> Rester connecté</label>
  <button type="submit">Se connecter</button>
</form>
</body>
</html>
