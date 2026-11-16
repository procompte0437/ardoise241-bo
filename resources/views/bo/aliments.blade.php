@extends('bo.layout')

@section('content')
<header class="top">
  <div style="display:flex;align-items:center">
    <a class="back" href="{{ route('noms') }}" aria-label="Retour">←</a>
    <div>
      <h1>Aliments</h1>
      <p>{{ $foods->count() }} / {{ $maxFoods }}</p>
    </div>
  </div>
</header>
<div class="pad">
  @if(session('error'))
    <div class="err" style="margin-bottom:10px">{{ session('error') }}</div>
  @endif
  <div class="actions">
    <form method="post" action="{{ route('aliments.photos') }}" enctype="multipart/form-data">
      @csrf
      <label class="btn dark" style="cursor:pointer">Ajouter des photos
        <input type="file" name="photos[]" accept="image/*" multiple hidden onchange="this.form.submit()">
      </label>
    </form>
    <form method="post" action="{{ route('aliments.store') }}">
      @csrf
      <button class="btn out" type="submit">Ajouter sans photo</button>
    </form>
  </div>
  <p style="font-weight:500;margin:0 0 12px">Touchez une photo pour la changer et saisissez le montant de chaque image (en F). « Ajouter des photos » remplit les places vides dans l'ordre : Image 1, Image 2, etc.</p>
  @if($foods->isEmpty())
    <div class="empty">Aucun aliment. Ajoutez vos photos pour commencer.</div>
  @endif
  @foreach($foods as $food)
    <form class="frow" method="post" action="{{ route('aliments.update', $food) }}" enctype="multipart/form-data">
      @csrf
      <label class="thumb">
        @if($food->image)
          <img src="{{ asset('storage/'.$food->image) }}" alt="">
        @else
          Ajouter<br>photo
        @endif
        <input type="file" name="photo" accept="image/*" hidden onchange="this.form.submit()">
      </label>
      <div class="f">
        <input class="field" name="name" value="{{ $food->name }}" maxlength="30" placeholder="Nom" onchange="this.form.submit()">
        <input class="field" name="price" type="number" inputmode="numeric" min="0" value="{{ $food->price }}" placeholder="Montant FCFA" onchange="this.form.submit()">
      </div>
      <button class="icon" style="height:64px" type="button" data-delete="{{ route('aliments.delete', $food) }}" data-name="{{ $food->name }}" aria-label="Supprimer">✕</button>
    </form>
  @endforeach
</div>
@endsection
