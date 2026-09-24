@extends('layouts.admin')

@section('content')
<div class="vlh-callout mb-4">
    Valores da tabela RarityBonus. Common fica bloqueado por contrato do
    servidor. Stats gerais usam all; stats de bracelete usam lt103 ou gte103.
</div>

<div class="vlh-card mb-4 overflow-auto">
    <table class="vlh-table w-full">
        <thead><tr><th>Raridade</th><th>Faixa</th><th>StatCode</th><th>Valor</th></tr></thead>
        <tbody>
        @forelse ($rows as $row)
            <tr>
                <td>{{ $row['rarity'] }}</td>
                <td>{{ $bands[$row['band']] ?? $row['band'] }}</td>
                <td class="mono">{{ $row['stat'] }}</td>
                <td>{{ $row['value'] }}</td>
            </tr>
        @empty
            <tr><td colspan="4">Nenhum bônus carregado. Verifique a migration 35.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

<form class="vlh-card" method="post" action="{{ route('raridade.bonus.update') }}">
    @csrf
    <div class="grid gap-3" style="grid-template-columns: repeat(4, minmax(0, 1fr));">
        <label>Raridade
            <select class="vlh-input" name="rarity" required>
                @for ($rarity = 2; $rarity <= 5; $rarity++)
                    <option value="{{ $rarity }}" @selected(old('rarity') == $rarity)>{{ $rarity }}</option>
                @endfor
            </select>
        </label>
        <label>Faixa
            <select class="vlh-input" name="band" required>
                @foreach ($bands as $code => $label)
                    <option value="{{ $code }}" @selected(old('band') === $code)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <label>StatCode
            <select class="vlh-input" name="stat" required>
                @foreach ($stats as $code => $label)
                    <option value="{{ $code }}" @selected(old('stat') === $code)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <label>Valor
            <input class="vlh-input" type="number" name="value" step="any" min="0" value="{{ old('value') }}" required>
        </label>
    </div>
    <input class="vlh-input mt-3" type="text" name="reason" minlength="5" maxlength="500"
           placeholder="Motivo da alteração" required>
    @if ($errors->has('rarity_bonus'))
        <p class="text-sm mt-2" style="color: var(--color-vlh-danger, #f87171)">{{ $errors->first('rarity_bonus') }}</p>
    @endif
    <button class="vlh-btn primary mt-3" type="submit">Salvar bônus e recarregar servidor</button>
</form>
@endsection
