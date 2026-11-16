@extends('bo.layout')

@section('content')
<div class="lockscreen">
  <img class="flag" src="{{ asset('flag.png') }}" alt="Lessy">
  <h1>Accès verrouillé</h1>
  <p>La période d'essai est terminée. Entrez le code utilisateur pour continuer.</p>
  <form method="post" action="{{ route('verrou.store') }}" style="width:100%;display:flex;flex-direction:column;align-items:center;gap:12px">
    @csrf
    <input id="lockcode" class="field" type="password" name="code" inputmode="numeric" maxlength="6" placeholder="Code" autofocus>
    <div class="err">{{ session('error') }}</div>
    <button class="btn dark" type="submit">Déverrouiller</button>
  </form>
</div>
@endsection
