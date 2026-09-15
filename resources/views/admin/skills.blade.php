@extends('layouts.admin')

@section('content')
<div class="vlh-callout mb-4">
    A quantidade de níveis é fixa porque o servidor lê por posição (<span class="mono">for i = 0; i &lt; 10</span> em <span class="mono">Skills.cpp</span>). Não adicione nem remova caixas — só edite os 10 valores. Depois: <span class="mono">/reloadSkill</span>.
</div>

<div class="vlh-card mb-4">
    <div class="font-semibold mb-1">{{ $param['label'] }}</div>
    <div class="text-xs mono mb-3" style="color: var(--color-vlh-muted)">chave {{ $param['key'] }}=</div>
    <div class="grid gap-2" style="grid-template-columns: repeat(10, minmax(0, 1fr));">
        @foreach ($param['levels'] as $i => $v)
            <div>
                <div class="text-xs mb-1" style="color: var(--color-vlh-muted)">Nv {{ $i + 1 }}</div>
                <input class="vlh-input mono" type="number" value="{{ $v }}" disabled>
            </div>
        @endforeach
    </div>
</div>

<div class="vlh-callout warn">
    Dano PvE e PvP usam o mesmo número no .ini atual. Multiplicadores PvP separados exigem patch (tela Dano em PvP).
</div>
@endsection
