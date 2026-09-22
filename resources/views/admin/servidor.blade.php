@extends('layouts.admin')

@section('content')
<div class="vlh-callout mb-4">
    O cliente monta <span class="mono">Image\sinImage\Items\&lt;pasta&gt;\it&lt;código&gt;.bmp</span>.
    Se o ícone não aparecer, o caminho ou o template está errado — não o Laravel. O painel <strong>não</strong> roda migrate em GameServer.
</div>

<form class="grid gap-4" style="max-width:40rem" onsubmit="return false">
    <div class="vlh-card grid gap-3">
        <div class="font-semibold" style="color: var(--color-vlh-gold)">SQL Server</div>
        <label class="text-xs" style="color: var(--color-vlh-muted)">Host</label>
        <input class="vlh-input" value="{{ $config['host'] }}" readonly>
        <label class="text-xs" style="color: var(--color-vlh-muted)">Porta</label>
        <input class="vlh-input" value="{{ $config['port'] }}" readonly>
        <label class="text-xs" style="color: var(--color-vlh-muted)">GameServer</label>
        <input class="vlh-input" value="{{ $config['database'] }} · {{ !empty($config['gameserver_online']) ? 'online' : 'offline' }}" readonly>
        <label class="text-xs" style="color: var(--color-vlh-muted)">UserDB</label>
        <input class="vlh-input" value="{{ $config['userdb'] }} · {{ !empty($config['userdb_online']) ? 'online' : 'offline' }}" readonly>
        <label class="text-xs" style="color: var(--color-vlh-muted)">ShopCoin</label>
        <input class="vlh-input" value="{{ $config['shopcoin'] }} · {{ !empty($config['shopcoin_online']) ? 'online' : 'offline' }}" readonly>
        <p class="text-xs m-0" style="color: var(--color-vlh-muted)">Edite no <span class="mono">.env</span> (<span class="mono">VALHALLA_DB_*</span>). Senha nunca vai para o git.</p>
    </div>

    <div class="vlh-card grid gap-3">
        <div class="font-semibold" style="color: var(--color-vlh-gold)">Cliente / source</div>
        <label class="text-xs" style="color: var(--color-vlh-muted)">Ícones</label>
        <input class="vlh-input mono text-xs" value="{{ $config['client_items'] }}" readonly>
        <label class="text-xs" style="color: var(--color-vlh-muted)">DropItem</label>
        <input class="vlh-input mono text-xs" value="{{ $config['dropitem'] }}" readonly>
        <label class="text-xs" style="color: var(--color-vlh-muted)">items.h</label>
        <input class="vlh-input mono text-xs" value="{{ $config['items_h'] }} {{ !empty($config['items_h_exists']) ? '· ok' : '· ausente' }}" readonly>
        <label class="text-xs" style="color: var(--color-vlh-muted)">Skills .ini</label>
        <input class="vlh-input mono text-xs" value="{{ $config['skills'] }}" readonly>
        <label class="text-xs" style="color: var(--color-vlh-muted)">Templates</label>
        <input class="vlh-input mono" value="{{ $config['icon_tpl'] }} · {{ $config['drop_tpl'] }}" readonly>
        <label class="text-xs" style="color: var(--color-vlh-muted)">Subpastas</label>
        <input class="vlh-input" value="{{ implode(', ', $config['subfolders']) }}" readonly>
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" {{ $config['protect_wv'] ? 'checked' : '' }} disabled>
            Bloquear edição de WV* (Marcial)
        </label>
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" {{ $config['demo'] ? 'checked' : '' }} disabled>
            Modo demo (fixtures sem SQL)
        </label>
    </div>
</form>
@endsection
