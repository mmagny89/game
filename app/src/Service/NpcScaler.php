<?php
declare(strict_types=1);

namespace App\Service;

use App\Entity\Character;

final class NpcScaler
{
    public function __construct(private CombatSimulator $sim) {}

    /**
     * @return array{name:string,level:int,attack:int,defense:int,health_max:int,exp_reward:int,gold_min:int,gold_max:int}
     */
    public function build(Character $char, string $diff): array
    {
        // Stats totales du joueur (base + équipement)
        $atkP = $this->sim->attackTotal($char);
        $defP = $this->sim->defenseTotal($char);
        $hpP  = $this->sim->healthMaxTotal($char);
        $lvl  = max(1, $char->getLevel());

        $map = [
            'easy' => ['name'=>'Sparring','atk'=>0.65,'def'=>0.65,'hp'=>0.70,'exp'=>10,'gmin'=>1,'gmax'=>2],
            'even' => ['name'=>'Rival','atk'=>1.00,'def'=>1.00,'hp'=>0.95,'exp'=>15,'gmin'=>2,'gmax'=>4],
            'hard' => ['name'=>'Brute','atk'=>1.35,'def'=>1.25,'hp'=>1.30,'exp'=>25,'gmin'=>4,'gmax'=>8],
        ];
        $cfg = $map[$diff] ?? $map['even'];

        return [
            'name'       => $cfg['name'],
            'level'      => $lvl,
            'attack'     => max(1, (int)round($atkP * $cfg['atk'])),
            'defense'    => max(0, (int)round($defP * $cfg['def'])),
            'health_max' => max(5, (int)round($hpP  * $cfg['hp'])),
            'exp_reward' => (int)($cfg['exp'] * $lvl),
            'gold_min'   => (int)($cfg['gmin'] * $lvl),
            'gold_max'   => (int)($cfg['gmax'] * $lvl),
        ];
    }
}
