@extends('gestion.layout')
@section('title', $account->name ?: $account->phone)
@section('content')
@php
  $fmt = fn ($n) => number_format((int) $n, 0, ',', ' ').' F';
  $start = $account->trial_started_at;
  $end = $account->accessEndsAt();
  $span = max(1, $start->diffInSeconds($end));
  $used = min(100, max(0, round($start->diffInSeconds(now()) / $span * 100)));
@endphp

<a class="back" href="{{ route('gestion.comptes') }}">← Clients</a>
<div class="head">
  <div class="row" style="gap:14px">
    <div class="avatar">{{ mb_strtoupper(mb_substr($account->name ?: '#', 0, 1)) }}</div>
    <div>
      <h1>{{ $account->name ?: 'Sans nom' }}</h1>
      <p>{{ $account->phone }} · inscrit le {{ $account->created_at->translatedFormat('d F Y') }}</p>
    </div>
    @include('gestion._statut')
  </div>
</div>

@if (session('newCode'))
  <div class="flash code">
    <span>Nouveau code d'accès à communiquer au client :</span><b>{{ session('newCode') }}</b>
    <span class="sm" style="opacity:.8">Il ne sera plus affiché. Ses données restent intactes : il se reconnecte avec son numéro et ce code.</span>
  </div>
@endif

<div class="grid g4">
  <div class="card kpi dark"><small>Fin d'accès</small><b style="font-size:22px">{{ $end->translatedFormat('d M Y') }}</b>
    <i>{{ $account->isExpired() ? 'expiré' : $account->daysLeft().' jour(s) restant(s)' }}</i>
    <div class="meter" style="background:#3A3127"><i style="width:{{ $used }}%"></i></div></div>
  <div class="card kpi"><small>Bons en cours</small><b>{{ $fmt($totalDebt) }}</b><i>{{ $owing->count() }} client(s) doivent de l'argent</i></div>
  <div class="card kpi"><small>Lignes de bilan</small><b>{{ number_format($entryCount, 0, ',', ' ') }}</b><i>{{ $renamed }} nom(s) personnalisé(s)</i></div>
  <div class="card kpi"><small>Dernière synchro</small><b style="font-size:22px">{{ $account->last_sync_at?->diffForHumans() ?? 'jamais' }}</b><i>{{ $account->last_device ?: 'appareil inconnu' }}</i></div>
</div>

<div class="grid g3" style="margin-top:16px">
  <div class="stack">
    <div class="card">
      <h2>Bons en cours <span class="mut sm">{{ $owing->count() }}</span></h2>
      @if ($owing->isEmpty())
        <div class="empty">Aucune dette ouverte.</div>
      @else
        <table><tbody>
        @foreach ($owing->take(30) as $row)
          <tr><td>{{ $row['name'] }}</td><td class="num"><b>{{ $fmt($row['debt']) }}</b></td></tr>
        @endforeach
        </tbody></table>
        @if ($owing->count() > 30)<p class="mut sm">… et {{ $owing->count() - 30 }} autre(s).</p>@endif
      @endif
    </div>

    <div class="card">
      <h2>Fiche d'inventaire <span class="mut sm">synchronisée depuis le téléphone</span></h2>
      <table>
        <thead><tr><th></th><th class="num">Aujourd'hui</th><th class="num">30 jours</th></tr></thead>
        <tbody>
          <tr><td><b>Total encaissé</b></td><td class="num"><b>{{ $fmt($invToday['received']) }}</b></td><td class="num"><b>{{ $fmt($invMonth['received']) }}</b></td></tr>
          <tr><td>Ventes encaissées</td><td class="num">{{ $fmt($invToday['cash']) }} <span class="mut sm">({{ $invToday['cashCount'] }})</span></td><td class="num">{{ $fmt($invMonth['cash']) }} <span class="mut sm">({{ $invMonth['cashCount'] }})</span></td></tr>
          <tr><td>Mis en bon</td><td class="num">{{ $fmt($invToday['credit']) }}</td><td class="num">{{ $fmt($invMonth['credit']) }}</td></tr>
          <tr><td>Paiements de bons</td><td class="num">{{ $fmt($invToday['payments']) }}</td><td class="num">{{ $fmt($invMonth['payments']) }}</td></tr>
        </tbody>
      </table>
      @if (! empty($invMonth['articles']))
        <h2 style="margin-top:18px">Articles vendus sur 30 jours</h2>
        <table>
          <thead><tr><th>Article</th><th class="num">Qté</th><th class="num">Montant</th></tr></thead>
          <tbody>
          @foreach (array_slice($invMonth['articles'], 0, 15) as $a)
            <tr><td>{{ $a['n'] }}</td><td class="num"><b>{{ $a['q'] }}</b></td><td class="num">{{ $fmt($a['amount']) }}</td></tr>
          @endforeach
          </tbody>
        </table>
      @endif
    </div>

    <div class="card">
      <h2>Dernières opérations</h2>
      @if ($entries->isEmpty())
        <div class="empty">Rien n'a encore été synchronisé.</div>
      @else
        <table>
          <thead><tr><th>Date</th><th>Client</th><th>Détail</th><th class="num">Montant</th></tr></thead>
          <tbody>
          @foreach ($entries as $e)
            <tr>
              <td class="sm mut" style="white-space:nowrap">{{ \Carbon\Carbon::createFromTimestampMs($e->happened_at)->timezone(config('app.timezone'))->translatedFormat('d M · H:i') }}</td>
              <td>{{ $people[$e->person_uid] ?? \App\Http\Controllers\Gestion\ConsoleController::defaultName($e->person_uid) }}</td>
              <td class="sm">{{ $e->pay ? ($e->label ?: 'Paiement') : collect($e->items)->map(fn ($i) => $i['n'].' ×'.$i['q'])->join(', ') }}</td>
              <td class="num" style="{{ $e->pay ? 'color:var(--ok)' : '' }}"><b>{{ $e->pay ? '− ' : '' }}{{ $fmt($e->amount) }}</b></td>
            </tr>
          @endforeach
          </tbody>
        </table>
      @endif
    </div>

    <div class="card">
      <h2>Articles et prix <span class="mut sm">{{ $foods->count() }} personnalisé(s)</span></h2>
      @if ($foods->isEmpty())
        <div class="empty">Le client n'a pas encore réglé ses articles.</div>
      @else
        <div class="foods">
          @foreach ($foods as $f)
            <div class="food">
              <div class="ph">@if ($f->image_hash)<img loading="lazy" src="{{ route('gestion.compte.image', [$account, $f->uid]) }}?v={{ $f->image_hash }}" alt="">@else 📷 @endif</div>
              <p>{{ $f->name }}<span>{{ $fmt($f->price) }}</span></p>
            </div>
          @endforeach
        </div>
      @endif
    </div>
  </div>

  <div class="stack">
    <div class="card">
      <h2>Abonnement</h2>
      <dl class="dl">
        <dt>Essai démarré</dt><dd>{{ $start->translatedFormat('d M Y') }}</dd>
        <dt>Durée d'essai</dt><dd>{{ $account->effectiveTrialDays() }} j {!! $account->trial_days === null ? '<span class="mut sm">(globale)</span>' : '<span class="mut sm">(particulière)</span>' !!}</dd>
        <dt>Payé jusqu'au</dt><dd>{{ $account->paid_until?->translatedFormat('d M Y') ?? '—' }}</dd>
      </dl>
      <form method="post" action="{{ route('gestion.compte.renouveler', $account) }}" style="margin-top:16px">
        @csrf
        <label class="f">Renouveler l'abonnement</label>
        <div class="row">
          <select name="days"><option value="30">+ 1 mois</option><option value="90">+ 3 mois</option><option value="180">+ 6 mois</option><option value="365">+ 1 an</option></select>
          <button class="btn gold" type="submit">Renouveler</button>
        </div>
      </form>
      <form method="post" action="{{ route('gestion.compte.essai', $account) }}" style="margin-top:14px">
        @csrf
        <label class="f">Durée d'essai de ce client (vide = globale, {{ $globalDays }} j)</label>
        <div class="row">
          <input type="number" name="trial_days" min="0" max="3650" value="{{ $account->trial_days }}" placeholder="{{ $globalDays }}">
          <button class="btn out" type="submit">Appliquer</button>
        </div>
      </form>
      <form method="post" action="{{ route('gestion.compte.terminer', $account) }}" style="margin-top:14px" onsubmit="return confirm('Arrêter l\'accès de ce client maintenant ?')">
        @csrf
        <button class="btn bad" type="submit">Terminer l'accès maintenant</button>
      </form>
    </div>

    <div class="card">
      <h2>Code d'accès</h2>
      <p class="mut sm" style="margin-top:-6px">Le client a perdu son code ? Donnez-lui un nouveau code : il le saisit avec son numéro et retrouve son cahier, ses bons et ses prix.</p>
      <form method="post" action="{{ route('gestion.compte.code', $account) }}" onsubmit="return confirm('Remplacer le code d\'accès de ce client ?')">
        @csrf
        <div class="row">
          <input type="text" name="code" inputmode="numeric" maxlength="6" pattern="\d{4,6}" placeholder="Vide = code au hasard">
          <button class="btn" type="submit">Réinitialiser</button>
        </div>
      </form>
    </div>

    <div class="card">
      <h2>Appareils <span class="mut sm">{{ $tokens->count() }}</span></h2>
      @forelse ($tokens as $t)
        <div class="row" style="justify-content:space-between;padding:6px 0;border-bottom:1px solid var(--line)">
          <span class="sm">{{ $t->device ?: 'Appareil' }}</span>
          <span class="mut sm">{{ $t->last_used_at?->diffForHumans() ?? '—' }}</span>
        </div>
      @empty
        <p class="mut sm">Aucun appareil connecté.</p>
      @endforelse
      <div class="row" style="margin-top:12px">
        @if ($tokens->isNotEmpty())
        <form method="post" action="{{ route('gestion.compte.appareils', $account) }}" onsubmit="return confirm('Déconnecter tous les appareils de ce client ?')">@csrf<button class="btn out" type="submit">Déconnecter</button></form>
        @endif
        <form method="post" action="{{ route('gestion.compte.suspendre', $account) }}" onsubmit="return confirm('{{ $account->blocked ? 'Réactiver' : 'Suspendre' }} ce compte ?')">@csrf<button class="btn {{ $account->blocked ? 'gold' : 'bad' }}" type="submit">{{ $account->blocked ? 'Réactiver le compte' : 'Suspendre le compte' }}</button></form>
      </div>
    </div>

    <div class="card">
      <h2>Fiche</h2>
      <form method="post" action="{{ route('gestion.compte.fiche', $account) }}">
        @csrf
        <label class="f">Nom du commerce</label>
        <input type="text" name="name" maxlength="60" value="{{ $account->name }}" style="width:100%;margin-bottom:10px">
        <label class="f">Note interne</label>
        <textarea name="note" maxlength="2000" placeholder="Paiement reçu, appel, remarque…">{{ $account->note }}</textarea>
        <button class="btn out" type="submit" style="margin-top:10px">Enregistrer</button>
      </form>
    </div>
  </div>
</div>
@endsection
