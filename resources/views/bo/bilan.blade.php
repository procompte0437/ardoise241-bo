@extends('bo.layout')

@section('content')
<header class="top">
  <div style="display:flex;align-items:center;min-width:0">
    <a class="back" href="{{ route('noms') }}" aria-label="Retour">←</a>
    <div style="min-width:0">
      <h1 class="grow">{{ $person->name }}</h1>
      <p>Bilan</p>
    </div>
  </div>
</header>
<div class="pad">
  @if(session('error'))
    <div class="err" style="margin-bottom:10px">{{ session('error') }}</div>
  @endif
  <div class="actions">
    <a class="btn dark" href="{{ route('session', $person) }}">Nouvelle session (images)</a>
    <button class="btn out" type="button" id="eraseBtn" data-name="{{ $person->name }}" data-debt="{{ \App\Services\LessyStore::format($person->debt) }}" data-action="{{ route('bilan.erase', $person) }}">Effacer le bilan</button>
    @if($admin)
      <button class="btn out" type="button" id="payBtn" data-action="{{ route('bilan.pay', $person) }}">Enregistrer un paiement</button>
    @endif
  </div>
  @if($person->entries->isEmpty())
    <div class="empty">Aucun montant pour l'instant.</div>
  @else
    <div class="bil">
      @foreach($person->entries as $entry)
        @if($entry->pay)
          <div class="bl pay">
            <b>− {{ \App\Services\LessyStore::format($entry->amount) }}</b>
            <small>{{ $entry->label ?: 'Paiement' }} · {{ $when($entry->happened_at) }}</small>
          </div>
        @else
          <div class="bl">
            <b>{{ \App\Services\LessyStore::format($entry->amount) }}</b>
            <small>{{ $when($entry->happened_at) }} · {{ $entry->items->map(fn ($item) => $item->name.' ×'.$item->qty)->join(', ') }}</small>
          </div>
        @endif
      @endforeach
    </div>
  @endif
  <div class="total"><span>Montant final =</span><b>{{ \App\Services\LessyStore::format($person->debt) }}</b></div>
</div>
@endsection
