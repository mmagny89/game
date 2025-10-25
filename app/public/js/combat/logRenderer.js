import { colorFor } from '../utils/colors.js';
import { el, text, scrollBottom } from '../utils/dom.js';

export function renderSystem(logEl, msg){
    if (!msg) return;
    const li = el('li','sys'); text(li, msg);
    logEl.appendChild(li); scrollBottom(logEl);
}

export function appendLine(logEl, t){
    if (!t || typeof t !== 'object' || !t.action) {
        renderSystem(logEl, t && typeof t==='object' ? (t.log||'') : String(t||'')); return;
    }
    const li = el('li', 'evt ' + (t.action||'hit'));
    const who = el('span','badge');  who.style.backgroundColor = colorFor(t.attacker); who.textContent = t.attacker || '—';
    const what= el('span','icon');   what.textContent = t.action==='crit'?'💥':t.action==='dodge'?'🌀':'🗡️';
    const tgt = el('span','badge');  tgt.style.backgroundColor = colorFor(t.defender); tgt.textContent = t.defender || '—';
    const dmg = el('span','dmg');    dmg.textContent = (t.action==='dodge') ? 'esquive' : (t.damage?('−'+t.damage):'');
    li.append(who, what, tgt, dmg);
    logEl.appendChild(li); scrollBottom(logEl);
}
