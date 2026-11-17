@extends('gestion.layout')
@section('title', 'Clients')
@section('content')
<div class="head"><div><div class="eyebrow">Suivi</div><h1>Clients</h1><p>{{ $accounts->total() }} compte(s){{ $q !== '' || $status !== '' ? ' trouvé(s)' : '' }}</p></div></div>

<form class="filters" method="get">
  <div class="search"><svg class="i" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg><input type="search" name="q" value="{{ $q }}" placeholder="Rechercher un numéro ou un nom…" autofocus></div>
  <select name="statut" onchange="this.form.submit()">
    <option value="">Tous les statuts</option>
    @foreach (\App\Http\Controllers\Gestion\ConsoleController::STATUSES as $key => $label)
      <option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>
    @endforeach
  </select>
  <button class="btn" type="submit">Rechercher</button>
  @if ($q !== '' || $status !== '')<a class="btn out" href="{{ route('gestion.comptes') }}">Effacer</a>@endif
</form>

<div class="card" style="padding:4px 8px">
  @if ($accounts->isEmpty())
    <div class="empty">Aucun compte ne correspond.</div>
  @else
  <table>
    <thead><tr><th>Client</th><th>Statut</th><th>Fin d'accès</th><th class="hide-sm">Dernière synchro</th><th class="hide-sm">Inscrit le</th></tr></thead>
    <tbody>
    @foreach ($accounts as $account)
      <tr class="link" onclick="location.href='{{ route('gestion.compte', $account) }}'">
        <td><div class="client"><span class="av">{{ mb_strtoupper(mb_substr($account->name ?: '#', 0, 1)) }}</span><div><a href="{{ route('gestion.compte', $account) }}" style="text-decoration:none"><b>{{ $account->name ?: 'Sans nom' }}</b></a><div class="mut sm">{{ $account->phone }}</div></div></div></td>
        <td>@include('gestion._statut')@if ($account->trial_days !== null)<div class="mut sm">essai : {{ $account->trial_days }} j</div>@endif</td>
        <td>@include('gestion._fin')</td>
        <td class="hide-sm mut sm">{{ $account->last_sync_at?->diffForHumans() ?? 'jamais' }}</td>
        <td class="hide-sm mut sm">{{ $account->created_at->translatedFormat('d M Y') }}</td>
      </tr>
    @endforeach
    </tbody>
  </table>
  @endif
</div>

@if ($accounts->lastPage() > 1)
<div class="pager">
  @for ($p = 1; $p <= $accounts->lastPage(); $p++)
    @if ($p === $accounts->currentPage())<span class="on">{{ $p }}</span>@else<a href="{{ $accounts->url($p) }}">{{ $p }}</a>@endif
  @endfor
</div>
@endif
@endsection
