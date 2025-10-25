import { appendLine, renderSystem } from './logRenderer.js';

export function initCombatReplay({ turns, logEl }){
    let idx=0, timer=null, speed=1;
    const delay = () => Math.max(100, 800/speed);

    function play(){ timer=setInterval(()=>{ if(idx<turns.length){ appendLine(logEl, turns[idx++]); } else { pause(); } }, delay()); }
    function pause(){ if(timer){clearInterval(timer); timer=null;} }
    function faster(){ speed = speed===1?2:speed===2?4:1; if(timer){ pause(); play(); } }
    function restart(){ logEl.innerHTML=''; idx=0; }

    if (!Array.isArray(turns) || !turns.length) renderSystem(logEl, 'Aucun log enregistré.');
    else play();

    return { play, pause, faster, restart };
}
