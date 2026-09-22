const labels = {
    ItemLevel: 'Level',
    ItemSpirit: 'Spirit',
    ItemStrength: 'Strength',
    ItemTalent: 'Talent',
    itemAgility: 'Agility',
    Weight: 'Peso',
    AttackPowerMin1: 'Atk min',
    AttackPowerMax1: 'Atk max',
    AttackPowerMin2: 'Atk min 2',
    AttackPowerMax2: 'Atk max 2',
    AttackRatingMin: 'Rating min',
    AttackRatingMax: 'Rating max',
    AttackCritical: 'CrÃ­tico',
    AttackSpeed: 'Vel. ataque',
    AttackRange: 'Alcance',
    BlockMin: 'Block min',
    BlockMax: 'Block max',
    DurabilityMin: 'Durab. min',
    DurabilityMax: 'Durab. max',
    Defense: 'Defesa',
    Absorption: 'AbsorÃ§Ã£o',
    PrimarySpec: 'Spec primÃ¡ria',
    SecondarySpec: 'Spec secundÃ¡ria',
    SpecAttackPowerMin: 'Spec atk min',
    SpecAttackPowerMax: 'Spec atk max',
    SpecAttackRatingMin: 'Spec rating min',
    SpecAttackRatingMax: 'Spec rating max',
    SpecAttackSpeed: 'Spec velocidade',
    SpecAttackCritical: 'Spec crÃ­tico',
    SpecAttackRange: 'Spec alcance',
    Life: 'HP',
    AttackPowerMin: 'Atk min',
    AttackPowerMax: 'Atk max',
    AttackRating: 'Rating',
    MoveSpeed: 'Movimento',
    Experience: 'EXP',
    ViewRange: 'VisÃ£o',
    OrganicResistance: 'Res. orgÃ¢nica',
    IceResistance: 'Res. gelo',
    FireResistance: 'Res. fogo',
    PoisonResistance: 'Res. veneno',
    LightingResistance: 'Res. raio',
};

function esc(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;');
}

function labelOf(key) {
    return labels[key] || key;
}

function iconHtml(code, size = 48) {
    const c = String(code || '').toUpperCase();
    if (!c || c === 'GOLD' || c === 'AIR') {
        return `<div class="vlh-icon" style="width:${size}px;height:${size}px">${esc(c.slice(0, 2) || '?')}</div>`;
    }
    return `<img class="vlh-icon-img" src="/icon.php?c=${encodeURIComponent(c)}" alt="${esc(c)}" width="${size}" height="${size}" loading="lazy" style="width:${size}px;height:${size}px">`;
}

function kvGrid(obj) {
    const entries = Object.entries(obj || {});
    if (!entries.length) {
        return '<p class="vlh-modal-empty">Sem dados nesta seÃ§Ã£o.</p>';
    }
    return `<div class="vlh-kv">${entries.map(([k, v]) => `
        <div class="vlh-kv-item">
            <span class="vlh-kv-k">${esc(labelOf(k))}</span>
            <span class="vlh-kv-v mono">${esc(v)}</span>
        </div>`).join('')}</div>`;
}

function badges(list) {
    return list.filter(Boolean).map((b) => `<span class="vlh-badge ${b.tone || ''}">${esc(b.text)}</span>`).join(' ');
}

function renderItem(data) {
    const flags = badges([
        data.protected ? { text: 'protegido', tone: 'warn' } : null,
        data.missing_header ? { text: 'sem items.h', tone: 'danger' } : null,
        data.missing_icon ? { text: 'sem Ã­cone', tone: 'danger' } : null,
        data.missing_drop ? { text: 'sem drop mesh', tone: 'warn' } : null,
        data.active === false ? { text: 'inativo', tone: 'danger' } : null,
    ]);

    return `
        <div class="vlh-modal-hero">
            ${iconHtml(data.code, 64)}
            <div>
                <div class="vlh-modal-title mono">${esc(data.code)}</div>
                <div class="vlh-modal-sub">${esc(data.db || 'â€”')}</div>
                <div class="vlh-modal-meta">cliente: ${esc(data.header || 'â€”')} Â· tabela ${esc(data.table || 'â€”')}${data.folder ? ` Â· pasta ${esc(data.folder)}` : ''}</div>
                <div class="vlh-modal-badges">${flags}</div>
            </div>
        </div>
        <div class="vlh-modal-section">
            <h3>Economia</h3>
            <div class="vlh-kv">
                <div class="vlh-kv-item"><span class="vlh-kv-k">PreÃ§o</span><span class="vlh-kv-v mono">${esc(data.price ?? 'â€”')}</span></div>
                <div class="vlh-kv-item"><span class="vlh-kv-k">Level</span><span class="vlh-kv-v mono">${esc(data.level ?? 'â€”')}</span></div>
                <div class="vlh-kv-item"><span class="vlh-kv-k">Spec</span><span class="vlh-kv-v mono">${esc(data.spec ?? 'â€”')}</span></div>
            </div>
        </div>
        <div class="vlh-modal-section"><h3>Requisitos</h3>${kvGrid(data.requirements)}</div>
        <div class="vlh-modal-section"><h3>Combate</h3>${kvGrid(data.combat)}</div>
        <div class="vlh-modal-section"><h3>Spec</h3>${kvGrid(data.spec_stats)}</div>
    `;
}

function renderMonster(data) {
    const meta = data.meta || {};
    const sum = data.chance_sum ?? (data.rows || []).reduce((a, r) => a + (r.chance || 0), 0);
    const rows = (data.rows || []).map((row) => {
        const items = (row.items || []).map((it) => {
            const gold = it.gold_min != null ? ` <span class="vlh-muted">${esc(it.gold_min)}â€“${esc(it.gold_max)}</span>` : '';
            const clickable = it.code && !['GOLD', 'AIR', 'Gold', 'Air'].includes(it.code)
                ? `class="vlh-chip vlh-open" data-vlh-modal="item" data-vlh-id="${esc(it.code)}" role="button" tabindex="0"`
                : 'class="vlh-chip"';
            return `<span ${clickable}>${iconHtml(it.code, 22)}<span class="mono">${esc(it.code)}</span><span class="vlh-muted">${esc(it.name || '')}</span>${gold}</span>`;
        }).join('');
        const pct = sum > 0 ? ((row.chance / sum) * 100).toFixed(1) : '0';
        return `<div class="vlh-drop-row">
            <div class="vlh-drop-row-head">
                <span>Peso <strong class="mono">${esc(row.chance)}</strong></span>
                <span class="vlh-muted">~${pct}%</span>
            </div>
            <div class="vlh-drop-items">${items || '<span class="vlh-muted">vazio</span>'}</div>
        </div>`;
    }).join('') || '<p class="vlh-modal-empty">Sem linhas de drop.</p>';

    return `
        <div class="vlh-modal-hero">
            <div class="vlh-modal-avatar">${meta.boss ? 'BOSS' : 'MOB'}</div>
            <div>
                <div class="vlh-modal-title">${esc(meta.name)}</div>
                <div class="vlh-modal-sub">Lv ${esc(meta.level)}${meta.model ? ` Â· ${esc(meta.model)}` : ''}</div>
                <div class="vlh-modal-meta">
                    DropID <span class="mono">${esc(meta.drop_id)}</span>
                    Â· mÃ¡x. chÃ£o ${esc(meta.quantity ?? 'â€”')}
                    Â· pÃºblico ${meta.public ? 'sim' : 'nÃ£o'}
                    ${data.shared_with > 1 ? `Â· compartilhado por ${esc(data.shared_with)}` : ''}
                </div>
                <div class="vlh-modal-badges">${badges([
                    meta.boss ? { text: 'boss', tone: 'gold' } : null,
                    meta.active === false ? { text: 'inativo', tone: 'danger' } : null,
                ])}</div>
            </div>
        </div>
        <div class="vlh-modal-section"><h3>Stats</h3>${kvGrid(meta.stats)}</div>
        <div class="vlh-modal-section">
            <h3>Drop <span class="vlh-muted">soma pesos ${esc(sum)}</span></h3>
            <div class="vlh-drop-list">${rows}</div>
            <div class="vlh-modal-actions">
                <a class="vlh-btn" href="/painel/drops/${encodeURIComponent(meta.name || '')}">Abrir pÃ¡gina completa</a>
            </div>
        </div>
    `;
}

function renderNpc(data) {
    const sellTypes = data.sell_types || { 1: 'Ataque', 2: 'Defesa', 3: 'Diversos' };
    const tabs = Object.keys(sellTypes).map((id) => {
        const items = (data.tabs?.[id] || []).map((it) => `
            <span class="vlh-chip vlh-open" data-vlh-modal="item" data-vlh-id="${esc(it.code)}" role="button" tabindex="0">
                ${iconHtml(it.code, 22)}
                <span class="mono">${esc(it.code)}</span>
                <span class="vlh-muted">${esc(it.name || '')}</span>
            </span>`).join('') || '<span class="vlh-muted">Nenhum item.</span>';
        return `<div class="vlh-modal-section">
            <h3>${esc(sellTypes[id])} <span class="vlh-muted">${(data.tabs?.[id] || []).length}</span></h3>
            <div class="vlh-drop-items">${items}</div>
        </div>`;
    }).join('');

    return `
        <div class="vlh-modal-hero">
            <div class="vlh-modal-avatar">NPC</div>
            <div>
                <div class="vlh-modal-title">${esc(data.name)}</div>
                <div class="vlh-modal-sub">${esc(data.model || 'â€”')} Â· code ${esc(data.code || 'â€”')}</div>
                <div class="vlh-modal-meta">
                    SellID <span class="mono">${esc(data.sell_id)}</span>
                    Â· ${esc(data.item_count || 0)} itens
                    ${data.shared > 1 ? `Â· lista usada por ${esc(data.shared)} NPCs` : ''}
                </div>
                <div class="vlh-modal-badges">${badges([
                    data.active === false ? { text: 'inativo', tone: 'danger' } : null,
                    data.shared > 1 ? { text: 'sell compartilhado', tone: 'warn' } : null,
                ])}</div>
            </div>
        </div>
        <div class="vlh-modal-section">
            <h3>Info</h3>
            <div class="vlh-kv">
                <div class="vlh-kv-item"><span class="vlh-kv-k">UniqueID</span><span class="vlh-kv-v mono">${esc(data.id)}</span></div>
                <div class="vlh-kv-item"><span class="vlh-kv-k">Level</span><span class="vlh-kv-v mono">${esc(data.level ?? 'â€”')}</span></div>
                <div class="vlh-kv-item"><span class="vlh-kv-k">Size</span><span class="vlh-kv-v mono">${esc(data.size ?? 'â€”')}</span></div>
                <div class="vlh-kv-item"><span class="vlh-kv-k">Sound</span><span class="vlh-kv-v mono">${esc(data.sound ?? 'â€”')}</span></div>
            </div>
        </div>
        ${tabs}
    `;
}

const endpoints = {
    item: (id) => `/painel/itens/${encodeURIComponent(id)}`,
    monster: (id) => `/painel/drops/${encodeURIComponent(id)}/json`,
    npc: (id) => `/painel/npcs/${encodeURIComponent(id)}`,
};

const renderers = {
    item: renderItem,
    monster: renderMonster,
    npc: renderNpc,
};

const titles = {
    item: 'Item',
    monster: 'Monstro',
    npc: 'NPC',
};

function ensureModal() {
    let root = document.getElementById('vlh-modal');
    if (root) return root;

    root = document.createElement('div');
    root.id = 'vlh-modal';
    root.className = 'vlh-modal';
    root.hidden = true;
    root.innerHTML = `
        <div class="vlh-modal-backdrop" data-vlh-close></div>
        <div class="vlh-modal-panel" role="dialog" aria-modal="true" aria-labelledby="vlh-modal-heading">
            <header class="vlh-modal-head">
                <h2 id="vlh-modal-heading">Detalhe</h2>
                <button type="button" class="vlh-btn" data-vlh-close aria-label="Fechar">Fechar</button>
            </header>
            <div class="vlh-modal-body"><div class="vlh-modal-loading">Carregandoâ€¦</div></div>
        </div>`;
    document.body.appendChild(root);
    return root;
}

async function openModal(type, id) {
    if (!type || !id || !endpoints[type]) return;
    const root = ensureModal();
    const body = root.querySelector('.vlh-modal-body');
    const heading = root.querySelector('#vlh-modal-heading');
    heading.textContent = titles[type] || 'Detalhe';
    body.innerHTML = '<div class="vlh-modal-loading">Carregandoâ€¦</div>';
    root.hidden = false;
    document.body.classList.add('vlh-modal-open');

    try {
        const res = await fetch(endpoints[type](id), {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        if (!res.ok) {
            throw new Error((await res.json().catch(() => ({}))).error || `Erro ${res.status}`);
        }
        const data = await res.json();
        body.innerHTML = renderers[type](data);
        heading.textContent = type === 'item'
            ? (data.code || 'Item')
            : type === 'monster'
                ? (data.meta?.name || 'Monstro')
                : (data.name || 'NPC');
    } catch (err) {
        body.innerHTML = `<div class="vlh-callout danger">${esc(err.message || 'Falha ao carregar')}</div>`;
    }
}

function closeModal() {
    const root = document.getElementById('vlh-modal');
    if (!root) return;
    root.hidden = true;
    document.body.classList.remove('vlh-modal-open');
}

document.addEventListener('click', (event) => {
    const closeEl = event.target.closest('[data-vlh-close]');
    if (closeEl) {
        closeModal();
        return;
    }

    const openEl = event.target.closest('[data-vlh-modal]');
    if (!openEl) return;
    if (openEl.tagName === 'A' && (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey)) {
        return;
    }
    event.preventDefault();
    openModal(openEl.dataset.vlhModal, openEl.dataset.vlhId);
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') closeModal();
    if (event.key === 'Enter') {
        const openEl = event.target.closest?.('[data-vlh-modal]');
        if (openEl) {
            event.preventDefault();
            openModal(openEl.dataset.vlhModal, openEl.dataset.vlhId);
        }
    }
});

window.VlhModal = { open: openModal, close: closeModal };
