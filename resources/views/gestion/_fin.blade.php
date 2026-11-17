@if ($account->blocked)
  <span class="mut">—</span>
@elseif ($account->isExpired())
  <span style="color:var(--bad);font-weight:600">depuis le {{ $account->accessEndsAt()->translatedFormat('d M Y') }}</span>
@else
  {{ $account->accessEndsAt()->translatedFormat('d M Y') }}
  <div class="mut sm">{{ $account->daysLeft() }} {{ $account->daysLeft() > 1 ? 'jours restants' : 'jour restant' }}</div>
@endif
