@extends('gestion.layout')
@section('title', 'Réglages')
@section('content')
<div class="head"><div><h1>Réglages</h1><p>Paramètres communs à tous les clients.</p></div></div>
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
@endsection
