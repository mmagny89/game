<?php
declare(strict_types=1);

namespace App\Service;

use App\Entity\CombatTurn;

final class CombatTurnNormalizer
{
    /** @return array<string, mixed> */
    public function toArray(CombatTurn $t): array
    {
        return [
            'round'           => $t->getRound(),
            'action'          => $t->getAction(),
            'damage'          => $t->getDamage(),
            'attacker_hp'     => $t->getAttackerHp(),
            'defender_hp'     => $t->getDefenderHp(),
            'attacker'        => $t->getAttackerName(),
            'defender'        => $t->getDefenderName(),
            'log'             => $t->getLogLine(),
            'attacker_is_npc' => $t->isAttackerIsNpc(),
        ];
    }
}
