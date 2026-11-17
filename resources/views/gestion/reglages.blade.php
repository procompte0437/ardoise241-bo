@extends('gestion.layout')
@section('title', 'Réglages')
@section('content')
<div class="head"><div><div class="eyebrow">Configuration</div><h1>Réglages</h1><p>Paramètres communs à tous les clients.</p></div></div>
<div class="card" style="max-width:560px">
  <h2>Durée d'essai gratuite</h2>
  <p class="mut" style="margin-top:-6px">Le compte à rebours démarre quand le client crée son code d'accès. Cette durée s'applique à tous les comptes qui n'ont pas de durée particulière (réglable sur la fiche de chaque client).</p>
  <form method="post" action="{{ route('gestion.reglages.store') }}" class="row" style="margin-top:14px">
    @csrf
    <input type="number" name="trial_days" min="0" max="3650" value="{{ $globalDays }}" required style="max-width:140px">
    <span class="mut">jours</span>
    <button class="btn gold" type="submit">Enregistrer</button>
  </form>
</div>

<div class="card" style="max-width:560px;margin-top:18px">
  <h2>Code admin Ardoise241</h2>
  <p class="mut" style="margin-top:-6px">Ce code ouvre le mode gérant de l'application sur le téléphone de n'importe quel client, pour le dépanner. Tant qu'il n'a pas été changé ici, c'est l'ancien code admin de la première version. Les téléphones reçoivent le nouveau code à leur prochaine synchronisation.</p>
  <form method="post" action="{{ route('gestion.reglages.support') }}" style="margin-top:14px">
    @csrf
    <div class="row">
      <input type="password" name="code" inputmode="numeric" maxlength="6" pattern="\d{4,6}" placeholder="Nouveau code (4 à 6 chiffres)" required autocomplete="new-password">
      <input type="password" name="code_confirmation" inputmode="numeric" maxlength="6" pattern="\d{4,6}" placeholder="Confirmer" required autocomplete="new-password">
      <button class="btn gold" type="submit">Changer</button>
    </div>
  </form>
</div>
@endsection
