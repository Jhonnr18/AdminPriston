@extends('layouts.admin')

@section('content')
<div class="vlh-callout danger mb-4">
    <strong>Sistema off.</strong> Sem ciclo ativo o NPC não entrega nada — a lib
    <span class="mono">Gandalf::cRewardHandler</span> está desligada e a base
    <span class="mono">PristonDB</span> não existe. Religar a lib sem schema = crash no level-up.
</div>
<div class="vlh-card">
    <div class="font-semibold mb-2">Calendário 7 / 31 dias (protótipo)</div>
    <div class="grid gap-2" style="grid-template-columns: repeat(7, minmax(0,1fr));">
        @for ($d = 1; $d <= 7; $d++)
            <div class="p-3 text-center text-sm" style="border:1px dashed var(--color-vlh-border);border-radius:.35rem;opacity:.55">
                Dia {{ $d }}
            </div>
        @endfor
    </div>
    <p class="text-sm mt-3 m-0" style="color: var(--color-vlh-muted)">
        Arrastar item para o dia fica bloqueado até ciclo próprio (patch C++ ou endpoint novo — não NewLib).
    </p>
</div>
@endsection
