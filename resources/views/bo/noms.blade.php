@extends('bo.layout')

@section('content')
<header class="top">
  <h1 class="grow">Lessy aimé Rodrigue</h1>
  <button class="lock" id="lockbtn" type="button" aria-label="{{ $admin ? 'Quitter le mode admin' : 'Mode admin' }}">{{ $admin ? '🔓' : '🔒' }}</button>
</header>
@if($admin)
<div class="adminbar">
  <button class="chip" id="addNames" type="button" data-room="{{ $room }}">Ajouter des noms</button>
  <a class="chip" href="{{ route('aliments') }}">Gérer les images</a>
  <button class="chip alt" id="changeCode" type="button">Changer le code</button>
  <form method="post" action="{{ route('deconnexion') }}">@csrf<button class="chip alt" type="submit">Déconnexion</button></form>
</div>
@endif
<div class="pad">
  @if(session('error'))
    <div class="err" style="margin-bottom:10px">{{ session('error') }}</div>
  @endif
  <input id="q" class="search" type="search" placeholder="Chercher un nom" autocomplete="off">
  <div id="list">
    @forelse($people as $index => $person)
      <div class="item" role="button" tabindex="0" data-id="{{ $person->id }}" data-name="{{ $person->name }}" data-debt="{{ \App\Services\LessyStore::format($person->debt) }}" data-num="{{ $index + 1 }}">
        <span class="num">{{ $index + 1 }}</span>
        <span class="t">{{ $person->name }}</span>
        <button class="pen" type="button" aria-label="Modifier le nom">✎</button>
        <span class="chev">›</span>
      </div>
    @empty
      <div class="empty">{{ $admin ? 'Aucun nom pour l\'instant. Touchez « Ajouter des noms ».' : 'Aucun nom pour l\'instant. L\'admin doit en ajouter.' }}</div>
    @endforelse
  </div>
  <div class="empty" id="emptysearch" style="display:none"></div>
</div>
<form id="leaveForm" method="post" action="{{ route('admin.leave') }}" hidden>@csrf</form>
@endsection
