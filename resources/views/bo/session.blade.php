@extends('bo.layout')

@section('content')
<header class="top">
  <a class="back" href="{{ route('noms') }}" id="leave" data-home="{{ route('noms') }}" aria-label="Retour">‹</a>
  <h1 class="grow">{{ $person->name }}</h1>
  <a class="ghost" href="{{ route('bilan', $person) }}">Bilan</a>
  <button class="lock" id="lockbtn" type="button" aria-label="{{ $admin ? 'Quitter le mode admin' : 'Mode admin' }}">{{ $admin ? '🔓' : '🔒' }}</button>
</header>
@if($admin)
<div class="pad" style="padding-bottom:0">
  <div class="empty" style="padding:10px;font-size:14px">Mode admin : touchez ✎ sous une image pour changer la photo, le nom et le montant.</div>
</div>
@endif
@if($foods->isEmpty())
  <div class="pad"><div class="empty">Aucune image pour l'instant. L'admin doit les ajouter.</div></div>
@else
  <div class="grid" id="grid" data-person="{{ $person->id }}" data-validate="{{ route('session.validate', $person) }}">
    @foreach($foods as $food)
      <div class="tile" id="t_{{ $food->id }}" data-id="{{ $food->id }}" data-price="{{ $food->price }}" data-name="{{ $food->name }}">
        <div class="ph">
          @if($food->image)
            <img src="{{ asset('storage/'.$food->image) }}" alt="">
          @else
            <span class="cam">📷</span>
          @endif
          <i class="badge">×0</i>
          <button class="minus" type="button" aria-label="Retirer un">−</button>
        </div>
        <div class="cf">
          <div class="ct">
            {{ $food->name }}
            @if($admin)<small>{{ \App\Services\LessyStore::format($food->price) }}</small>@endif
          </div>
          <button class="pen" type="button" data-id="{{ $food->id }}" aria-label="Modifier {{ $food->name }}">✎</button>
        </div>
      </div>
    @endforeach
  </div>
  <div class="bar">
    <div class="in">
      <div class="tot"><small>Montant final de la session</small><b id="tot">0 F</b></div>
      <button class="btn line" type="button" id="clearSession">Vider</button>
      <button class="btn" type="button" id="validate">Valider</button>
    </div>
  </div>
@endif
<form id="leaveForm" method="post" action="{{ route('admin.leave') }}" hidden>@csrf</form>
@endsection
