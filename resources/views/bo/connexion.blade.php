@extends('bo.layout')

@section('content')
<div class="lockscreen">
  <img class="flag" src="{{ asset('flag.png') }}" alt="Lessy">
  <h1>Back-office</h1>
  <p>Connectez-vous pour ouvrir Lessy aimé Rodrigue.</p>
  <form method="post" action="{{ route('connexion.store') }}" style="width:100%;max-width:320px;display:flex;flex-direction:column;align-items:stretch;gap:12px">
    @csrf
    <input class="field" type="email" name="email" value="{{ old('email') }}" placeholder="E-mail" autocomplete="username" autofocus required>
    <input class="field" type="password" name="password" placeholder="Mot de passe" autocomplete="current-password" required>
    <div class="err">{{ session('error') }}{{ $errors->first('email') }}{{ $errors->first('password') }}</div>
    <button class="btn dark" type="submit">Entrer</button>
  </form>
</div>
@endsection
