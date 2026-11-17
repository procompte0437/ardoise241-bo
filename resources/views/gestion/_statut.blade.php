@php($s = $account->status())
<span class="pill p-{{ $s }}">{{ \App\Http\Controllers\Gestion\ConsoleController::STATUSES[$s] }}</span>
