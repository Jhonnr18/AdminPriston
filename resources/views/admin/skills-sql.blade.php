@extends('layouts.admin')

@section('content')
<div class="vlh-callout mb-4">
    Esta tela usa as tabelas SQL da Fase 2 de skills. O Tier 5 permanece dormente:
    somente skills ativas no contrato do loader devem ser publicadas em produção.
</div>

<div class="grid gap-4" style="grid-template-columns: 280px 1fr;">
    <div class="vlh-card" style="max-height: 70vh; overflow:auto">
        @forelse ($skills as $skill)
            <a href="{{ route('skills.sql', ['skill' => $skill['code']]) }}"
               class="vlh-btn w-full mb-1 {{ $selected === $skill['code'] ? 'primary' : '' }}">
                <span>{{ $skill['name'] }}</span>
                <span class="vlh-badge">{{ $skill['code'] }}</span>
            </a>
        @empty
            <p class="text-sm m-0">Nenhuma definição SQL disponível. Verifique as migrations 23–25.</p>
        @endforelse
    </div>
    <div>
        @foreach ($parameters as $parameter)
            <form class="vlh-card mb-4" method="post" action="{{ route('skills.sql.save') }}">
                @csrf
                <input type="hidden" name="skill_code" value="{{ $selected }}">
                <input type="hidden" name="parameter" value="{{ $parameter['parameter'] }}">
                <div class="font-semibold">{{ $parameter['display_name'] }}</div>
                <div class="text-xs mono mb-3">{{ $parameter['parameter'] }} · {{ $parameter['cpp_type'] }} · {{ $parameter['unit'] }}</div>
                <div class="grid gap-2" style="grid-template-columns: repeat(10, minmax(0, 1fr));">
                    @for ($level = 1; $level <= 10; $level++)
                        <div>
                            <div class="text-xs mb-1">Nv {{ $level }}</div>
                            <input class="vlh-input mono" type="number" step="any"
                                   name="values[{{ $level - 1 }}]"
                                   value="{{ old('values.'.($level - 1), $parameter['values'][$level] ?? '') }}" required>
                        </div>
                    @endfor
                </div>
                <input class="vlh-input mt-3" type="text" name="reason" minlength="5" maxlength="500"
                       placeholder="Motivo da alteração" value="{{ old('reason') }}" required>
                <button class="vlh-btn primary mt-3" type="submit">Salvar parâmetro SQL</button>
            </form>
        @endforeach
        @if ($selected)
            <form class="vlh-card" method="post" action="{{ route('skills.sql.cooldown') }}">
                @csrf
                <input type="hidden" name="skill_code" value="{{ $selected }}">
                <div class="font-semibold mb-2">Cooldown por nível</div>
                <div class="grid gap-2" style="grid-template-columns: 1fr 2fr;">
                    <input class="vlh-input" type="number" name="level" min="1" max="10" placeholder="Nível" required>
                    <input class="vlh-input" type="number" name="cooldown_ms" min="0" max="600000" placeholder="Cooldown (ms)" required>
                </div>
                <input class="vlh-input mt-3" type="text" name="reason" minlength="5" maxlength="500"
                       placeholder="Motivo da alteração" required>
                <button class="vlh-btn primary mt-3" type="submit">Salvar cooldown</button>
            </form>
        @endif
    </div>
</div>
@endsection
