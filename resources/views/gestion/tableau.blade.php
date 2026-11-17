@extends('gestion.layout')
@section('title', 'Tableau de bord')
@section('content')
@php
  $h = (int) now()->format('G');
  $hello = $h < 12 ? 'Bonjour' : ($h < 18 ? 'Bon après-midi' : 'Bonsoir');
@endphp
<div class="head">
  <div>
    <div class="eyebrow">{{ ucfirst(now()->translatedFormat('l d F Y')) }}</div>
    <h1>{{ $hello }} 👋</h1>
    <p>Voici l'activité de vos clients. Essai gratuit : <b>{{ $globalDays }} jours</b> · <a href="{{ route('gestion.reglages') }}">modifier</a></p>
  </div>
  <a class="btn gold" href="{{ route('gestion.comptes') }}">
    <svg class="i" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>Tous les clients</a>
</div>

<div class="grid g4">
  <div class="card kpi dark">
    <span class="ic"><svg class="i" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6M22 11h-6"/></svg></span>
    <small>Clients inscrits</small><b>{{ $counts['total'] }}</b><i>+{{ $newWeek }} cette semaine</i>
  </div>
  <a class="card kpi" style="text-decoration:none" href="{{ route('gestion.comptes', ['statut' => 'essai']) }}">
    <span class="ic"><svg class="i" viewBox="0 0 24 24"><path d="M5 22h14M5 2h14M17 22v-4.2a2 2 0 0 0-.6-1.4L12 12l-4.4 4.4a2 2 0 0 0-.6 1.4V22M7 2v4.2a2 2 0 0 0 .6 1.4L12 12l4.4-4.4a2 2 0 0 0 .6-1.4V2"/></svg></span>
    <small>En essai</small><b>{{ $counts['essai'] }}</b><i>{{ $counts['abonne'] }} abonné(s)</i>
  </a>
  <a class="card kpi" style="text-decoration:none" href="{{ route('gestion.comptes', ['statut' => 'expire']) }}">
    <span class="ic" style="color:var(--bad);background:var(--badbg)"><svg class="i" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 8v4M12 16h.01"/></svg></span>
    <small>À renouveler</small><b style="color:var(--bad)">{{ $counts['expire'] }}</b><i>{{ $counts['bloque'] }} suspendu(s)</i>
  </a>
  <div class="card kpi">
    <span class="ic" style="color:var(--ok);background:var(--okbg)"><svg class="i" viewBox="0 0 24 24"><path d="M21 12a9 9 0 0 1-15 6.7L3 16M3 12a9 9 0 0 1 15-6.7L21 8M21 3v5h-5M3 21v-5h5"/></svg></span>
    <small>Synchronisés (24 h)</small><b>{{ $syncedDay }}</b><i>{{ number_format($entries, 0, ',', ' ') }} lignes sauvegardées</i>
  </div>
</div>

<div class="grid g2" style="margin-top:18px">
  <div class="card">
    <h2>Fin d'accès dans les 7 jours</h2>
    @if ($soon->isEmpty())
      <div class="empty">Aucun compte n'arrive à échéance cette semaine.</div>
    @else
      <table><tbody>
      @foreach ($soon as $account)
        <tr class="link" onclick="location.href='{{ route('gestion.compte', $account) }}'">
          <td><div class="client"><span class="av">{{ mb_strtoupper(mb_substr($account->name ?: '#', 0, 1)) }}</span><div><b>{{ $account->name ?: 'Sans nom' }}</b><div class="mut sm">{{ $account->phone }}</div></div></div></td>
          <td class="num">@include('gestion._fin')</td>
        </tr>
      @endforeach
      </tbody></table>
    @endif
  </div>
  <div class="card">
    <h2>Derniers inscrits <a href="{{ route('gestion.comptes') }}">Tout voir →</a></h2>
    @if ($recent->isEmpty())
      <div class="empty">Personne ne s'est encore inscrit.</div>
    @else
      <table><tbody>
      @foreach ($recent as $account)
        <tr class="link" onclick="location.href='{{ route('gestion.compte', $account) }}'">
          <td><div class="client"><span class="av">{{ mb_strtoupper(mb_substr($account->name ?: '#', 0, 1)) }}</span><div><b>{{ $account->name ?: 'Sans nom' }}</b><div class="mut sm">{{ $account->phone }}</div></div></div></td>
          <td>@include('gestion._statut')</td>
          <td class="num mut sm hide-sm">{{ $account->created_at->diffForHumans() }}</td>
        </tr>
      @endforeach
      </tbody></table>
    @endif
  </div>
</div>
@endsection
