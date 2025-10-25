import { appendLine, renderSystem } from './logRenderer.js';

export function initCombatPlayer({ postUrl, csrf, logEl, pvRefs, playerName }) {
    let turns=[], idx=0, timer=null, speed=1;
    const delay = () => Math.max(100, 800/speed);

    const { pBar, nBar, pVal, nVal, pMax, nMax } = pvRefs;
    let hpP = pMax, hpN = nMax;

    function updateBars(){
        const pct = (v,m)=> m>0?Math.max(0,Math.min(100,100*v/m)):0;
        pBar.style.width = pct(hpP,pMax)+'%'; nBar.style.width = pct(hpN,nMax)+'%';
        pVal.textContent = hpP; nVal.textContent = hpN;
    }

    function applyTurn(t){
        if (t && t.action) {
            if (t.attacker_is_npc) hpP = t.defender_hp; else hpN = t.defender_hp;
            updateBars();
        }
        appendLine(logEl, t);
    }

    async function fetchTurns(){
        renderSystem(logEl, '… Initialisation du combat …');
        const form = new FormData(); form.append('_token', csrf);
        const res = await fetch(postUrl,{method:'POST',body:form,headers:{'X-Requested-With':'XMLHttpRequest'}});
        if (!res.ok){ renderSystem(logEl, 'Erreur '+res.status); return false; }
        const data = await res.json();
        turns = data.turns || [];
        if (!turns.length){ renderSystem(logEl, 'Aucun tour.'); return false; }
        const p = data.player?.hpmax ?? pMax, n = data.npc?.hpmax ?? nMax;
        hpP=p; hpN=n; updateBars();
        renderSystem(logEl, '✅ Combat lancé !');
        idx=0; return true;
    }

    function play(){ timer = setInterval(()=>{ if(idx<turns.length){ applyTurn(turns[idx++]); } else { pause(); renderSystem(logEl,'— Fin du combat —'); } }, delay()); }
    function pause(){ if(timer){ clearInterval(timer); timer=null; } }
    function faster(){ speed = speed===1?2:speed===2?4:1; if(timer){ pause(); play(); } }

    return { fetchTurns, play, pause, faster, skip: ()=>{ pause(); while(idx<turns.length) applyTurn(turns[idx++]); renderSystem(logEl,'— Fin du combat —'); } };
}
