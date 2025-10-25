<?php
declare(strict_types=1);

namespace App\Service;

use App\Entity\Character;
use App\Entity\NPC;

final class DangerMeter
{
    public function __construct(private CombatSimulator $sim) {}

    /**
     * Estime le risque vs un "snapshot" PNJ.
     * @param array{name:string,level:int,attack:int,defense:int,health_max:int} $npc
     * @return array{label:string,emoji:string,color:string,level:int,player_ttk:float,enemy_ttk:float}
     */
    public function vsSnapshot(Character $char, array $npc): array
    {
        $atkP = $this->sim->attackTotal($char);
        $defP = $this->sim->defenseTotal($char);
        $hpP  = $this->sim->healthMaxTotal($char);

        $atkN = $npc['attack'];
        $defN = $npc['defense'];
        $hpN  = $npc['health_max'];

        return $this->estimate($atkP, $defP, $hpP, $atkN, $defN, $hpN);
    }

    /**
     * Estime le risque vs un NPC "fixe" (boss en BDD).
     */
    public function vsBoss(Character $char, NPC $boss): array
    {
        $atkP = $this->sim->attackTotal($char);
        $defP = $this->sim->defenseTotal($char);
        $hpP  = $this->sim->healthMaxTotal($char);

        $atkN = $boss->getAttack();
        $defN = $boss->getDefense();
        $hpN  = $boss->getHealthMax();

        return $this->estimate($atkP, $defP, $hpP, $atkN, $defN, $hpN);
    }

    /**
     * Heuristique simple basée sur TTK (time-to-kill) estimé des deux côtés.
     * - dégât effectif par coup ≈ max(1, ATK - DEF*0.6)
     * - TTK ≈ PV / dégât (on ignore les tours, crit/dodge équilibrent globalement)
     */
    private function estimate(int $atkP, int $defP, int $hpP, int $atkN, int $defN, int $hpN): array
    {
        $pDmg = max(1, (int)round($atkP - $defN * 0.6));
        $nDmg = max(1, (int)round($atkN - $defP * 0.6));

        $playerTTK = $hpN / $pDmg; // combien de "coups" pour tomber l’ennemi
        $enemyTTK  = $hpP / $nDmg; // combien de "coups" pour te tomber

        // ratio <1 : tu tues plus vite que lui (bien), >1 : il te tue plus vite (dangereux)
        $ratio = $playerTTK / max(0.0001, $enemyTTK);

        // Seuils (tweakables)
        if ($ratio <= 0.60)  { $label='Très sûr'; $emoji='🟢'; $color='#10b981'; $level=1; }
        elseif ($ratio <= 0.90){ $label='Favorable'; $emoji='🟩'; $color='#22c55e'; $level=2; }
        elseif ($ratio <= 1.10){ $label='Équilibré'; $emoji='🟨'; $color='#eab308'; $level=3; }
        elseif ($ratio <= 1.60){ $label='Risqué';    $emoji='🟧'; $color='#f97316'; $level=4; }
        else                   { $label='Mortel';    $emoji='🟥'; $color='#ef4444'; $level=5; }

        return [
            'label' => $label,
            'emoji' => $emoji,
            'color' => $color,
            'level' => $level,
            'player_ttk' => round($playerTTK, 1),
            'enemy_ttk'  => round($enemyTTK, 1),
        ];
    }
}
