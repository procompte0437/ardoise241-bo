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
    <button class="btn out" type="button" id="payBtn" data-action="{{ route('bilan.pay', $person) }}">Enregistrer un paiement</button>
  </div>
  @if($days === [])
    <div class="empty">Aucun montant pour l'instant.</div>
  @else
    <div class="bil">
      @foreach($days as $day)
        <section class="day">
          <h4>
            <span>{{ $day['label'] }}</span>
            <small>{{ $day['sum'] >= 0 ? '+ '.\App\Services\LessyStore::format($day['sum']) : '− '.\App\Services\LessyStore::format(abs($day['sum'])) }}</small>
          </h4>
          @foreach($day['rows'] as $entry)
            <div class="ses {{ $entry->pay ? 'pay' : '' }}">
              <div class="sl">
                <time>{{ $entry->happened_at->format('H:i') }}</time>
                <span class="lab">{{ $entry->pay ? ($entry->label ?: 'Paiement') : 'Session' }}</span>
                <b>{{ $entry->pay ? '− '.\App\Services\LessyStore::format($entry->amount) : \App\Services\LessyStore::format($entry->amount) }}</b>
              </div>
              @if(! $entry->pay && $entry->items->isNotEmpty())
                <ul class="its">
                  @foreach($entry->items as $item)
                    <li>
                      <span>{{ $item->name }}</span>
                      <span class="q">×{{ $item->qty }}</span>
                      <span class="p">{{ $item->price ? \App\Services\LessyStore::format($item->price * $item->qty) : '' }}</span>
                    </li>
                  @endforeach
                </ul>
              @endif
            </div>
          @endforeach
        </section>
      @endforeach
    </div>
  @endif
  <div class="total"><span>Montant final =</span><b>{{ \App\Services\LessyStore::format($person->debt) }}</b></div>
</div>
@endsection
