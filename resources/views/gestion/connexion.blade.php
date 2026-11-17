<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>Connexion · Ardoise241</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&family=Fraunces:opsz,wght@9..144,600;9..144,700&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box}
body{margin:0;min-height:100vh;display:grid;grid-template-columns:1.1fr 1fr;font:15px/1.5 Inter,system-ui,sans-serif;color:#16120E;background:#F5F2EC;-webkit-font-smoothing:antialiased}
.art{position:relative;overflow:hidden;background:radial-gradient(600px 400px at 85% 10%,rgba(233,185,44,.45),transparent 60%),radial-gradient(500px 500px at 0% 100%,rgba(201,151,26,.25),transparent 60%),linear-gradient(160deg,#241D16,#0E0B08);color:#fff;padding:48px;display:flex;flex-direction:column;justify-content:space-between}
.art:after{content:"";position:absolute;inset:0;background-image:radial-gradient(rgba(255,255,255,.07) 1px,transparent 1px);background-size:22px 22px;pointer-events:none}
.brand{display:flex;align-items:center;gap:12px;position:relative;z-index:1}
.logo{width:44px;height:44px;border-radius:13px;background:linear-gradient(135deg,#F6D46A,#C9971A);display:grid;place-items:center;color:#16120E;font-weight:800;font-size:21px;box-shadow:0 10px 30px -8px rgba(233,185,44,.8)}
.brand b{font-family:Fraunces,Georgia,serif;font-size:21px}
.pitch{position:relative;z-index:1;max-width:460px}
.pitch h2{font-family:Fraunces,Georgia,serif;font-size:42px;line-height:1.08;margin:0 0 16px;font-weight:700}
.pitch h2 em{font-style:normal;color:#E9B92C}
.pitch p{color:#D9CCB0;margin:0;font-size:16px}
.stats{display:flex;gap:28px;position:relative;z-index:1;color:#BFAF90;font-size:13px}
.stats b{display:block;color:#fff;font-size:20px}
.pane{display:grid;place-items:center;padding:32px 20px}
form{width:100%;max-width:380px}
h1{margin:0 0 6px;font-family:Fraunces,Georgia,serif;font-size:30px}
.sub{margin:0 0 26px;color:#7A7164}
label{display:block;font-size:13px;font-weight:600;color:#7A7164;margin:16px 0 7px}
input[type=email],input[type=password]{width:100%;font:inherit;border:1px solid #E3DCCD;background:#fff;border-radius:12px;padding:13px 14px;transition:border-color .15s,box-shadow .15s}
input:focus{outline:none;border-color:#C9971A;box-shadow:0 0 0 4px rgba(233,185,44,.18)}
.rem{display:flex;gap:8px;align-items:center;margin-top:16px;font-size:14px;color:#7A7164;font-weight:500}
button{margin-top:24px;width:100%;border:0;border-radius:12px;padding:14px;background:#16120E;color:#fff;font:inherit;font-weight:700;cursor:pointer;transition:box-shadow .15s,transform .1s}
button:hover{box-shadow:0 12px 24px -12px rgba(22,18,14,.6)}
button:active{transform:translateY(1px)}
.err{background:#FCE6E2;color:#B0281C;border-radius:12px;padding:11px 14px;font-weight:600;margin-bottom:6px}
@media(max-width:860px){body{grid-template-columns:1fr}.art{padding:28px 22px;min-height:auto;gap:30px}.pitch h2{font-size:30px}.stats{display:none}}
</style>
</head>
<body>
<section class="art">
  <div class="brand"><span class="logo">₣</span><b>Ardoise241</b></div>
  <div class="pitch">
    <h2>Le cahier de bons, <em>sans papier</em> et sans perte.</h2>
    <p>Suivez vos commerçants, leurs abonnements et leurs codes d'accès. Leurs données sont sauvegardées en ligne, même si le téléphone est perdu.</p>
  </div>
  <div class="stats"><span><b>Hors ligne</b>d'abord</span><span><b>Synchro</b>automatique</span><span><b>Restauration</b>par numéro</span></div>
</section>
<section class="pane">
  <form method="post" action="{{ route('gestion.login.store') }}">
    @csrf
    <h1>Bon retour</h1>
    <p class="sub">Connectez-vous au back-office.</p>
    @if (session('error'))<div class="err">{{ session('error') }}</div>@endif
    @if ($errors->any())<div class="err">{{ $errors->first() }}</div>@endif
    <label for="email">E-mail</label>
    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
    <label for="password">Mot de passe</label>
    <input id="password" type="password" name="password" required autocomplete="current-password">
    <label class="rem"><input type="checkbox" name="remember" value="1"> Rester connecté</label>
    <button type="submit">Se connecter</button>
  </form>
</section>
</body>
</html>
