<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>@yield('title', 'Gestion') · Ardoise241</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Fraunces:opsz,wght@9..144,600;9..144,700&display=swap" rel="stylesheet">
<style>
:root{--ink:#16120E;--ink2:#241D16;--ink3:#3A2F23;--gold:#E9B92C;--gold2:#F7E2A0;--gold3:#C9971A;--bg:#F5F2EC;--card:#fff;--line:#ECE6DA;--mut:#7A7164;
--ok:#167A4A;--okbg:#E1F4EA;--warn:#946300;--warnbg:#FFF1C9;--bad:#B0281C;--badbg:#FCE6E2;--off:#5A5560;--offbg:#EEECF2;--r:18px;
--sh:0 1px 2px rgba(22,18,14,.04),0 8px 24px -12px rgba(22,18,14,.12)}
*{box-sizing:border-box}
html,body{margin:0;background:var(--bg);color:var(--ink);font:15px/1.5 Inter,system-ui,-apple-system,'Segoe UI',Roboto,sans-serif;-webkit-font-smoothing:antialiased}
body{background:radial-gradient(1200px 500px at 100% -10%,#FBEFC9 0%,transparent 60%),var(--bg)}
a{color:inherit}
svg.i{width:18px;height:18px;flex:none;stroke:currentColor;fill:none;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round}
.shell{display:grid;grid-template-columns:264px 1fr;min-height:100vh}

/* barre latérale */
.side{background:linear-gradient(180deg,#1E1812 0%,#120E0A 100%);color:#EFE5CF;padding:22px 16px;position:sticky;top:0;height:100vh;display:flex;flex-direction:column;gap:4px;border-right:1px solid #2A2219}
.brand{display:flex;align-items:center;gap:12px;padding:4px 10px 26px}
.brand .logo{width:40px;height:40px;border-radius:12px;background:linear-gradient(135deg,#F6D46A,#C9971A);display:grid;place-items:center;color:var(--ink);font-weight:800;font-size:19px;box-shadow:0 6px 18px -6px rgba(233,185,44,.7)}
.brand b{display:block;font-family:Fraunces,Georgia,serif;font-size:19px;color:#fff;letter-spacing:.01em}
.brand small{display:block;font-size:11px;letter-spacing:.14em;text-transform:uppercase;color:#A8977A}
.sect{font-size:11px;letter-spacing:.14em;text-transform:uppercase;color:#8D7F68;padding:14px 12px 6px}
.side a.nav{position:relative;display:flex;align-items:center;gap:12px;padding:11px 12px;border-radius:12px;text-decoration:none;font-weight:600;color:#D9CDB4;transition:background .15s,color .15s}
.side a.nav:hover{background:rgba(255,255,255,.05);color:#fff}
.side a.nav.on{background:linear-gradient(90deg,rgba(233,185,44,.18),rgba(233,185,44,.04));color:var(--gold)}
.side a.nav.on:before{content:"";position:absolute;left:-16px;top:8px;bottom:8px;width:3px;border-radius:0 3px 3px 0;background:var(--gold)}
.side .grow{flex:1}
.me{display:flex;align-items:center;gap:10px;padding:12px;border-radius:14px;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.06)}
.me .av{width:34px;height:34px;border-radius:10px;background:var(--ink3);display:grid;place-items:center;font-weight:700;color:var(--gold)}
.me .who{flex:1;min-width:0;font-size:12px;color:#BDB09A;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.me .who b{display:block;color:#fff;font-size:13px}
.me button{background:none;border:0;color:#BDB09A;cursor:pointer;padding:6px;border-radius:8px;display:grid;place-items:center}
.me button:hover{background:rgba(255,255,255,.08);color:#fff}

/* contenu */
main{padding:34px 40px 70px;min-width:0;max-width:1320px;width:100%;animation:in .35s ease-out}
@keyframes in{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:none}}
.head{display:flex;align-items:flex-end;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:26px}
.head h1{margin:0;font-family:Fraunces,Georgia,serif;font-size:32px;font-weight:700;letter-spacing:-.01em;line-height:1.1}
.head p{margin:6px 0 0;color:var(--mut)}
.eyebrow{font-size:12px;letter-spacing:.14em;text-transform:uppercase;color:var(--gold3);font-weight:700;margin-bottom:6px}
.grid{display:grid;gap:18px}
.g4{grid-template-columns:repeat(4,minmax(0,1fr))}
.g2{grid-template-columns:repeat(2,minmax(0,1fr))}
.g3{grid-template-columns:minmax(0,1.7fr) minmax(320px,1fr);align-items:start}
.card{background:var(--card);border:1px solid var(--line);border-radius:var(--r);padding:20px 22px;box-shadow:var(--sh)}
.card h2{margin:0 0 14px;font-size:15px;font-weight:700;display:flex;justify-content:space-between;align-items:center;gap:8px}
.card h2 a{font-size:13px;font-weight:600;color:var(--mut);text-decoration:none}
.card h2 a:hover{color:var(--ink)}
.kpi{position:relative;overflow:hidden;transition:transform .15s,box-shadow .15s}
a.kpi:hover{transform:translateY(-2px);box-shadow:0 14px 30px -14px rgba(22,18,14,.25)}
.kpi .ic{position:absolute;right:16px;top:16px;width:38px;height:38px;border-radius:12px;display:grid;place-items:center;background:var(--bg);color:var(--gold3)}
.kpi small{display:block;color:var(--mut);font-weight:600;font-size:13px}
.kpi b{display:block;font-size:32px;font-weight:800;letter-spacing:-.03em;margin-top:6px;font-variant-numeric:tabular-nums}
.kpi i{font-style:normal;font-size:12px;color:var(--mut)}
.kpi.dark{background:radial-gradient(240px 140px at 100% 0%,rgba(233,185,44,.35),transparent 70%),linear-gradient(160deg,#241D16,#110D09);color:#fff;border-color:#241D16}
.kpi.dark small,.kpi.dark i{color:#CDBF9F}
.kpi.dark b{color:var(--gold)}
.kpi.dark .ic{background:rgba(233,185,44,.15);color:var(--gold)}
table{width:100%;border-collapse:collapse}
th{text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:.08em;color:var(--mut);font-weight:700;padding:12px 14px;border-bottom:1px solid var(--line);white-space:nowrap}
td{padding:14px;border-bottom:1px solid var(--line);vertical-align:middle}
tr:last-child td{border-bottom:0}
tbody tr.link{cursor:pointer;transition:background .12s}
tbody tr.link:hover{background:#FCF8EE}
.num{text-align:right;font-variant-numeric:tabular-nums;white-space:nowrap}
.mut{color:var(--mut)}
.sm{font-size:13px}
.client{display:flex;align-items:center;gap:12px}
.client .av{width:38px;height:38px;border-radius:12px;background:linear-gradient(135deg,#FBEBB8,#EFD27A);display:grid;place-items:center;font-weight:800;color:var(--ink);flex:none}
.pill{display:inline-flex;align-items:center;gap:6px;border-radius:999px;padding:4px 11px;font-size:12px;font-weight:700;white-space:nowrap}
.pill:before{content:"";width:7px;height:7px;border-radius:50%;background:currentColor}
.p-essai{background:var(--warnbg);color:var(--warn)}
.p-abonne{background:var(--okbg);color:var(--ok)}
.p-expire{background:var(--badbg);color:var(--bad)}
.p-bloque{background:var(--offbg);color:var(--off)}
.p-client{background:#E3ECFF;color:#2350B8}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;border:1px solid var(--ink);background:var(--ink);color:#fff;border-radius:12px;padding:10px 16px;font:inherit;font-weight:700;font-size:14px;cursor:pointer;text-decoration:none;white-space:nowrap;transition:transform .1s,background .15s,box-shadow .15s}
.btn:hover{background:var(--ink2);box-shadow:0 8px 18px -10px rgba(22,18,14,.5)}
.btn:active{transform:translateY(1px)}
.btn.gold{background:linear-gradient(135deg,#F3CE55,#D9A21E);border-color:#D9A21E;color:var(--ink)}
.btn.gold:hover{box-shadow:0 8px 20px -8px rgba(217,162,30,.7)}
.btn.out{background:#fff;color:var(--ink);border-color:var(--line)}
.btn.out:hover{border-color:var(--ink)}
.btn.bad{background:#fff;color:var(--bad);border-color:#F0C8C1}
.btn.bad:hover{background:var(--badbg)}
input,select,textarea{font:inherit;border:1px solid var(--line);background:#FDFCF9;border-radius:12px;padding:10px 13px;color:var(--ink);min-width:0;transition:border-color .15s,box-shadow .15s}
input:focus,select:focus,textarea:focus{outline:none;border-color:var(--gold3);box-shadow:0 0 0 4px rgba(233,185,44,.18);background:#fff}
textarea{width:100%;min-height:80px;resize:vertical}
label.f{display:block;font-size:13px;font-weight:600;color:var(--mut);margin:0 0 7px}
.row{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
.row input{flex:1}
.filters{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:18px;background:var(--card);border:1px solid var(--line);border-radius:var(--r);padding:12px;box-shadow:var(--sh)}
.filters .search{flex:1;min-width:220px;position:relative}
.filters .search svg{position:absolute;left:13px;top:50%;transform:translateY(-50%);color:var(--mut)}
.filters .search input{width:100%;padding-left:40px}
.flash{border-radius:14px;padding:13px 18px;margin-bottom:20px;font-weight:600;display:flex;gap:10px;align-items:center;animation:in .3s ease-out}
.flash.ok{background:var(--okbg);color:var(--ok)}
.flash.err{background:var(--badbg);color:var(--bad)}
.flash.code{background:linear-gradient(135deg,#241D16,#110D09);color:#fff;flex-wrap:wrap;gap:16px;padding:18px 22px}
.flash.code b{font-size:30px;letter-spacing:.25em;color:var(--gold);font-variant-numeric:tabular-nums;background:rgba(233,185,44,.12);padding:4px 14px;border-radius:10px}
.empty{padding:34px 20px;text-align:center;color:var(--mut)}
.empty:before{content:"";display:block;width:44px;height:44px;margin:0 auto 10px;border-radius:14px;background:var(--bg)}
.dl{display:grid;grid-template-columns:auto 1fr;gap:10px 18px;margin:0}
.dl dt{color:var(--mut);font-size:13px;font-weight:600}
.dl dd{margin:0;font-weight:600;text-align:right}
.meter{height:8px;background:rgba(255,255,255,.12);border-radius:99px;overflow:hidden;margin-top:12px}
.meter i{display:block;height:100%;border-radius:99px;background:linear-gradient(90deg,#F3CE55,#D9A21E)}
.foods{display:grid;grid-template-columns:repeat(auto-fill,minmax(104px,1fr));gap:12px}
.food{border:1px solid var(--line);border-radius:14px;overflow:hidden;font-size:12px;background:#fff;transition:transform .15s,box-shadow .15s}
.food:hover{transform:translateY(-2px);box-shadow:var(--sh)}
.food .ph{aspect-ratio:1;background:linear-gradient(160deg,#B8860B,#2A2010);display:grid;place-items:center;color:#F6E3A3;font-size:22px}
.food img{width:100%;height:100%;object-fit:cover;display:block}
.food p{margin:0;padding:8px 10px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.food p span{display:block;color:var(--gold3);font-weight:700}
.stack>*+*{margin-top:18px}
.pager{display:flex;gap:6px;justify-content:flex-end;margin-top:16px}
.pager a,.pager span{padding:7px 12px;border-radius:10px;border:1px solid var(--line);background:#fff;text-decoration:none;font-weight:600;font-size:13px}
.pager span.on{background:var(--ink);color:#fff;border-color:var(--ink)}
.back{display:inline-flex;align-items:center;gap:6px;color:var(--mut);text-decoration:none;font-weight:600;margin-bottom:10px}
.back:hover{color:var(--ink)}
.avatar{width:58px;height:58px;border-radius:18px;background:linear-gradient(135deg,#F6D46A,#C9971A);display:grid;place-items:center;font-weight:800;font-size:22px;flex:none;box-shadow:0 10px 24px -12px rgba(201,151,26,.8)}
.topbar{display:none}
@media(max-width:1180px){.g4{grid-template-columns:repeat(2,minmax(0,1fr))}.g3{grid-template-columns:1fr}}
@media(max-width:860px){
  .shell{grid-template-columns:1fr}
  .side{position:fixed;inset:0 auto 0 0;width:270px;z-index:40;transform:translateX(-100%);transition:transform .25s}
  body.menu .side{transform:none;box-shadow:0 0 60px rgba(0,0,0,.5)}
  .scrim{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:30}
  body.menu .scrim{display:block}
  .topbar{display:flex;align-items:center;gap:12px;position:sticky;top:0;z-index:20;background:#16120E;color:#fff;padding:12px 16px}
  .topbar button{background:none;border:0;color:#fff;padding:6px;cursor:pointer;display:grid}
  .topbar b{font-family:Fraunces,Georgia,serif;font-size:17px}
  main{padding:22px 16px 60px}
  .g2,.g4{grid-template-columns:1fr}
  .head h1{font-size:26px}
  .hide-sm{display:none}
}
</style>
</head>
<body>
@php($u = auth()->user())
<div class="topbar">
  <button type="button" onclick="document.body.classList.toggle('menu')" aria-label="Menu"><svg class="i" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"/></svg></button>
  <b>Ardoise241</b>
</div>
<div class="scrim" onclick="document.body.classList.remove('menu')"></div>
<div class="shell">
  <aside class="side">
    <div class="brand"><span class="logo">₣</span><span><b>Ardoise241</b><small>Back-office</small></span></div>
    <div class="sect">Pilotage</div>
    <a class="nav {{ request()->routeIs('gestion.home') ? 'on' : '' }}" href="{{ route('gestion.home') }}">
      <svg class="i" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="9" rx="2"/><rect x="14" y="3" width="7" height="5" rx="2"/><rect x="14" y="12" width="7" height="9" rx="2"/><rect x="3" y="16" width="7" height="5" rx="2"/></svg>Tableau de bord</a>
    <a class="nav {{ request()->routeIs('gestion.comptes', 'gestion.compte') ? 'on' : '' }}" href="{{ route('gestion.comptes') }}">
      <svg class="i" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>Clients</a>
    <a class="nav" href="{{ route('gestion.comptes', ['statut' => 'expire']) }}">
      <svg class="i" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>Abonnements expirés</a>
    <div class="sect">Configuration</div>
    <a class="nav {{ request()->routeIs('gestion.reglages') ? 'on' : '' }}" href="{{ route('gestion.reglages') }}">
      <svg class="i" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"/></svg>Réglages</a>
    <div class="grow"></div>
    <div class="me">
      <span class="av">{{ mb_strtoupper(mb_substr($u->email, 0, 1)) }}</span>
      <span class="who"><b>Administrateur</b>{{ $u->email }}</span>
      <form method="post" action="{{ route('gestion.logout') }}">@csrf<button type="submit" title="Se déconnecter" aria-label="Se déconnecter"><svg class="i" viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg></button></form>
    </div>
  </aside>
  <main>
    @if (session('ok'))<div class="flash ok">✓ {{ session('ok') }}</div>@endif
    @if (session('error'))<div class="flash err">{{ session('error') }}</div>@endif
    @if ($errors->any())<div class="flash err">{{ $errors->first() }}</div>@endif
    @yield('content')
  </main>
</div>
</body>
</html>
