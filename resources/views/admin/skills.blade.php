@extends('layouts.admin')

@section('content')
<div class="vlh-callout mb-4">
    A quantidade de níveis é fixa porque o servidor lê por posição (<span class="mono">for i = 0; i &lt; 10</span> em <span class="mono">Skills.cpp</span>). Não adicione nem remova caixas — só edite os 10 valores. Salvar cria um <span class="mono">.bak-&lt;timestamp&gt;</span> automático do arquivo inteiro antes de gravar. A mudança só vale no servidor depois do próximo <span class="mono">/reload_skills</span> manual, ou automaticamente pelo poller do servidor que consome a fila <span class="mono">ConfigReloadRequest</span> (em implementação em paralelo).
</div>

<div class="flex flex-wrap gap-2 mb-4">
    @foreach ($files as $name => $label)
        <a href="{{ route('skills', ['file' => $name]) }}" class="vlh-btn {{ $file === $name ? 'primary' : '' }}">{{ $label }}</a>
    @endforeach
</div>

@if (!empty($missing_file))
    <div class="vlh-callout danger mb-4">Arquivo não encontrado: <span class="mono">{{ $path }}</span></div>
@endif

<div class="grid gap-4" style="grid-template-columns: 240px 1fr;">
    <div class="vlh-card" style="max-height: 70vh; overflow:auto">
        <div class="text-xs mb-2" style="color: var(--color-vlh-muted)">Parâmetros · {{ $label }}</div>
        @forelse ($parameters as $parameter)
            <a href="{{ route('skills', ['file' => $file, 'key' => $parameter['key']]) }}"
               class="vlh-btn w-full mb-1 {{ ($selected['key'] ?? '') === $parameter['key'] ? 'primary' : '' }}">
                <span class="mono">{{ $parameter['key'] }}</span>
                @if (empty($parameter['valid']))
                    <span class="vlh-badge danger">{{ $parameter['count'] ?? 0 }}</span>
                @endif
            </a>
        @empty
            <p class="text-sm m-0" style="color: var(--color-vlh-muted)">Nenhum parâmetro lido.</p>
        @endforelse
    </div>
    <div>
        @if ($selected)
            @php
                $isOldSkill = old('key') === $selected['key'] && old('file') === $file;
            @endphp
            <form class="vlh-card mb-4" method="post" action="{{ route('skills.save') }}">
                @csrf
                <input type="hidden" name="file" value="{{ $file }}">
                <input type="hidden" name="key" value="{{ $selected['key'] }}">
                <div class="font-semibold mb-1">{{ $selected['label'] }}</div>
                <div class="text-xs mono mb-3" style="color: var(--color-vlh-muted)">chave {{ $selected['key'] }}=</div>
                <div class="grid gap-2" style="grid-template-columns: repeat(10, minmax(0, 1fr));">
                    @foreach ($selected['values'] as $i => $v)
                        <div>
                            <div class="text-xs mb-1" style="color: var(--color-vlh-muted)">Nv {{ $i + 1 }}</div>
                            <input class="vlh-input mono" type="number" step="any" name="values[{{ $i }}]"
                                   value="{{ $isOldSkill ? old('values.'.$i) : $v }}">
                        </div>
                    @endforeach
                </div>

                @if ($isOldSkill && $errors->has('skill'))
                    <p class="text-sm mt-2 mb-0" style="color: var(--color-vlh-danger, #f87171)">{{ $errors->first('skill') }}</p>
                @endif

                <button class="vlh-btn primary mt-3" type="submit">Salvar (cria backup automático)</button>
            </form>
        @endif
        <div class="vlh-callout warn">
            Dano PvE e PvP usam o mesmo número no .ini atual. Multiplicadores PvP separados exigem patch (tela Dano em PvP).
        </div>
    </div>
</div>
@endsection
