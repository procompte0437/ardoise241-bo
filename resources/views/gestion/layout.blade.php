<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>@yield('title', 'Gestion') · Ardoise241</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root{--ink:#1A1612;--ink2:#2B241C;--gold:#EDBE2E;--gold2:#F6E3A3;--bg:#F6F4EF;--card:#fff;--line:#E7E2D8;--mut:#6F675C;
--ok:#1F7A4D;--okbg:#E3F3EA;--warn:#9A6A00;--warnbg:#FFF2CC;--bad:#A1261B;--badbg:#FBE4E1;--off:#55505A;--offbg:#ECEAF0;--r:14px}
*{box-sizing:border-box}
html,body{margin:0;background:var(--bg);color:var(--ink);font:15px/1.45 Inter,system-ui,-apple-system,'Segoe UI',Roboto,sans-serif}
a{color:inherit}
.shell{display:grid;grid-template-columns:240px 1fr;min-height:100vh}
.side{background:var(--ink);color:#F4EBD6;padding:22px 14px;position:sticky;top:0;height:100vh;display:flex;flex-direction:column;gap:4px}
.brand{display:flex;align-items:center;gap:10px;padding:0 8px 22px;font-weight:800;font-size:17px;color:var(--gold)}
.brand span{display:grid;place-items:center;width:34px;height:34px;border-radius:10px;background:var(--gold);color:var(--ink);font-size:18px}
.side a.nav{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:10px;text-decoration:none;font-weight:600;color:#E9DFC7}
.side a.nav:hover{background:var(--ink2)}
.side a.nav.on{background:var(--gold);color:var(--ink)}
.side .grow{flex:1}
.side form button{width:100%;text-align:left;background:none;border:0;color:#C9BFA9;font:inherit;padding:10px 12px;border-radius:10px;cursor:pointer}
.side form button:hover{background:var(--ink2);color:#fff}
.who{font-size:12px;color:#A89E88;padding:0 12px 6px;overflow:hidden;text-overflow:ellipsis}
main{padding:28px 32px 60px;min-width:0}
.head{display:flex;align-items:flex-end;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:22px}
.head h1{margin:0;font-size:26px;font-weight:800;letter-spacing:-.02em}
.head p{margin:4px 0 0;color:var(--mut)}
.grid{display:grid;gap:16px}
.g4{grid-template-columns:repeat(4,minmax(0,1fr))}
.g2{grid-template-columns:repeat(2,minmax(0,1fr))}
.g3{grid-template-columns:2fr 1fr}
.card{background:var(--card);border:1px solid var(--line);border-radius:var(--r);padding:18px 20px}
.card h2{margin:0 0 14px;font-size:15px;font-weight:700;display:flex;justify-content:space-between;align-items:center;gap:8px}
.card h2 a{font-size:13px;font-weight:600;color:var(--mut)}
.kpi small{display:block;color:var(--mut);font-weight:600;font-size:13px}
.kpi b{display:block;font-size:30px;font-weight:800;letter-spacing:-.02em;margin-top:4px}
.kpi i{font-style:normal;font-size:12px;color:var(--mut)}
.kpi.dark{background:var(--ink);color:#fff;border-color:var(--ink)}
.kpi.dark small,.kpi.dark i{color:#CFC4AA}
.kpi.dark b{color:var(--gold)}
table{width:100%;border-collapse:collapse}
th{text-align:left;font-size:12px;text-transform:uppercase;letter-spacing:.04em;color:var(--mut);font-weight:700;padding:10px 12px;border-bottom:1px solid var(--line);white-space:nowrap}
td{padding:12px;border-bottom:1px solid var(--line);vertical-align:middle}
tr:last-child td{border-bottom:0}
tbody tr.link{cursor:pointer}
tbody tr.link:hover{background:#FBF8F1}
.num{text-align:right;font-variant-numeric:tabular-nums;white-space:nowrap}
.mut{color:var(--mut)}
.sm{font-size:13px}
.pill{display:inline-flex;align-items:center;gap:6px;border-radius:999px;padding:3px 10px;font-size:12px;font-weight:700;white-space:nowrap}
.pill:before{content:"";width:7px;height:7px;border-radius:50%;background:currentColor}
.p-essai{background:var(--warnbg);color:var(--warn)}
.p-abonne{background:var(--okbg);color:var(--ok)}
.p-expire{background:var(--badbg);color:var(--bad)}
.p-bloque{background:var(--offbg);color:var(--off)}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;border:1px solid var(--ink);background:var(--ink);color:#fff;border-radius:10px;padding:9px 14px;font:inherit;font-weight:700;font-size:14px;cursor:pointer;text-decoration:none;white-space:nowrap}
.btn:hover{background:var(--ink2)}
.btn.gold{background:var(--gold);border-color:var(--gold);color:var(--ink)}
.btn.out{background:#fff;color:var(--ink);border-color:var(--line)}
.btn.out:hover{border-color:var(--ink)}
.btn.bad{background:#fff;color:var(--bad);border-color:#EBC3BD}
.btn.bad:hover{background:var(--badbg)}
input,select,textarea{font:inherit;border:1px solid var(--line);background:#fff;border-radius:10px;padding:9px 12px;color:var(--ink);min-width:0}
input:focus,select:focus,textarea:focus{outline:2px solid var(--gold);border-color:var(--ink)}
textarea{width:100%;min-height:80px;resize:vertical}
label.f{display:block;font-size:13px;font-weight:600;color:var(--mut);margin:0 0 6px}
.row{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
.row input{flex:1}
.filters{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px}
.filters input{flex:1;min-width:220px}
.flash{border-radius:12px;padding:12px 16px;margin-bottom:18px;font-weight:600}
.flash.ok{background:var(--okbg);color:var(--ok)}
.flash.err{background:var(--badbg);color:var(--bad)}
.flash.code{background:var(--ink);color:#fff;display:flex;align-items:center;gap:16px;flex-wrap:wrap}
.flash.code b{font-size:28px;letter-spacing:.2em;color:var(--gold);font-variant-numeric:tabular-nums}
.empty{padding:28px;text-align:center;color:var(--mut)}
.dl{display:grid;grid-template-columns:auto 1fr;gap:8px 18px;margin:0}
.dl dt{color:var(--mut);font-size:13px;font-weight:600}
.dl dd{margin:0;font-weight:600}
.meter{height:8px;background:#EFEBE2;border-radius:99px;overflow:hidden;margin-top:10px}
.meter i{display:block;height:100%;background:var(--gold)}
.foods{display:grid;grid-template-columns:repeat(auto-fill,minmax(92px,1fr));gap:10px}
.food{border:1px solid var(--line);border-radius:12px;overflow:hidden;font-size:12px;background:#fff}
.food .ph{aspect-ratio:1;background:linear-gradient(160deg,#B8860B,#2A2010);display:grid;place-items:center;color:#F6E3A3;font-size:22px}
.food img{width:100%;height:100%;object-fit:cover;display:block}
.food p{margin:0;padding:6px 8px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.food p span{display:block;color:var(--mut);font-weight:500}
.stack>*+*{margin-top:16px}
.pager{display:flex;gap:6px;justify-content:flex-end;margin-top:14px}
.pager a,.pager span{padding:6px 11px;border-radius:8px;border:1px solid var(--line);background:#fff;text-decoration:none;font-weight:600;font-size:13px}
.pager span.on{background:var(--ink);color:#fff;border-color:var(--ink)}
.back{display:inline-flex;align-items:center;gap:6px;color:var(--mut);text-decoration:none;font-weight:600;margin-bottom:8px}
.avatar{width:52px;height:52px;border-radius:14px;background:var(--gold);display:grid;place-items:center;font-weight:800;font-size:20px;flex:none}
@media(max-width:1100px){.g4{grid-template-columns:repeat(2,minmax(0,1fr))}.g3{grid-template-columns:1fr}}
@media(max-width:820px){.shell{grid-template-columns:1fr}.side{position:static;height:auto;flex-direction:row;flex-wrap:wrap;align-items:center;padding:12px}
.brand{padding:0 8px 0 0}.side .grow,.who{display:none}.side form button{width:auto}main{padding:20px 16px 50px}.g2{grid-template-columns:1fr}
.hide-sm{display:none}}
</style>
</head>
<body>
<div class="shell">
  <aside class="side">
    <div class="brand"><span>₣</span> Ardoise241</div>
    <a class="nav {{ request()->routeIs('gestion.home') ? 'on' : '' }}" href="{{ route('gestion.home') }}">▦ Tableau de bord</a>
    <a class="nav {{ request()->routeIs('gestion.comptes', 'gestion.compte') ? 'on' : '' }}" href="{{ route('gestion.comptes') }}">☰ Clients</a>
    <a class="nav {{ request()->routeIs('gestion.reglages') ? 'on' : '' }}" href="{{ route('gestion.reglages') }}">⚙ Réglages</a>
    <div class="grow"></div>
    <div class="who">{{ auth()->user()->email }}</div>
    <form method="post" action="{{ route('gestion.logout') }}">@csrf<button type="submit">⎋ Se déconnecter</button></form>
  </aside>
  <main>
    @if (session('ok'))<div class="flash ok">{{ session('ok') }}</div>@endif
    @if (session('error'))<div class="flash err">{{ session('error') }}</div>@endif
    @if ($errors->any())<div class="flash err">{{ $errors->first() }}</div>@endif
    @yield('content')
  </main>
</div>
</body>
</html>
