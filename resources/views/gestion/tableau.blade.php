@extends('gestion.layout')
@section('title', 'Tableau de bord')
@section('content')
<div class="head">
  <div><h1>Tableau de bord</h1><p>Durée d'essai globale : <b>{{ $globalDays }} jours</b> · <a href="{{ route('gestion.reglages') }}">modifier</a></p></div>
  <a class="btn out" href="{{ route('gestion.comptes') }}">Voir tous les clients</a>
</div>

<div class="grid g4">
  <div class="card kpi dark"><small>Clients inscrits</small><b>{{ $counts['total'] }}</b><i>+{{ $newWeek }} cette semaine</i></div>
  <a class="card kpi" style="text-decoration:none" href="{{ route('gestion.comptes', ['statut' => 'essai']) }}"><small>En essai</small><b>{{ $counts['essai'] }}</b><i>{{ $counts['abonne'] }} abonné(s)</i></a>
  <a class="card kpi" style="text-decoration:none" href="{{ route('gestion.comptes', ['statut' => 'expire']) }}"><small>Expirés</small><b style="color:var(--bad)">{{ $counts['expire'] }}</b><i>{{ $counts['bloque'] }} suspendu(s)</i></a>
  <div class="card kpi"><small>Synchronisés (24 h)</small><b>{{ $syncedDay }}</b><i>{{ number_format($entries, 0, ',', ' ') }} lignes de bilan sauvegardées</i></div>
</div>

<div class="grid g2" style="margin-top:16px">
  <div class="card">
    <h2>Fin d'accès dans moins de 7 jours</h2>
    @if ($soon->isEmpty())
      <div class="empty">Aucun compte n'arrive à échéance cette semaine.</div>
    @else
      <table><tbody>
      @foreach ($soon as $account)
        <tr class="link" onclick="location.href='{{ route('gestion.compte', $account) }}'">
          <td><b>{{ $account->name ?: 'Sans nom' }}</b><div class="mut sm">{{ $account->phone }}</div></td>
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
          <td><b>{{ $account->name ?: 'Sans nom' }}</b><div class="mut sm">{{ $account->phone }}</div></td>
          <td>@include('gestion._statut')</td>
          <td class="num mut sm">{{ $account->created_at->diffForHumans() }}</td>
        </tr>
      @endforeach
      </tbody></table>
    @endif
  </div>
</div>
@endsection
