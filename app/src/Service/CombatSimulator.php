<?php
declare(strict_types=1);

namespace App\Service;

use App\Entity\Character;
use App\Entity\Combat;
use App\Entity\CombatTurn;
use App\Entity\InventoryItem;
use App\Entity\NPC;
use Doctrine\ORM\EntityManagerInterface;

final class CombatSimulator
{
    private const CRIT_RATE = 10;    // %
    private const DODGE_RATE = 10;   // %
    private const MAX_ROUNDS = 200;

    public function __construct(private EntityManagerInterface $em) {}

    private function statBonus(Character $c, string $field): int
    {
        // field ∈ attack_bonus|defense_bonus|health_bonus
        $qb = $this->em->createQueryBuilder()
            ->select('COALESCE(SUM(i.'.$field.'),0)')
            ->from(InventoryItem::class, 'inv')
            ->join('inv.item', 'i')
            ->where('inv.character = :c AND inv.equipped = true')
            ->setParameter('c', $c);
        return (int)$qb->getQuery()->getSingleScalarResult();
    }

    public function attackTotal(Character $c): int
    {
        return $c->getAttackBase() + $this->statBonus($c, 'attack_bonus');
    }

    public function defenseTotal(Character $c): int
    {
        return $c->getDefenseBase() + $this->statBonus($c, 'defense_bonus');
    }

    public function healthMaxTotal(Character $c): int
    {
        return $c->getHealthMax() + $this->statBonus($c, 'health_bonus');
    }

    /** Simule un combat contre PNJ, retourne le Combat et ses turns. */
    public function simulateVsNpc(Character $attacker, NPC $npc): Combat
    {
        // régénération à l’accès
        $cap = $this->healthMaxTotal($attacker);
        $attacker->applyRegen(1, $cap);
        if ($attacker->getHealthCurrent() < $cap) {
            throw new \RuntimeException("Tu es blessé·e. Reviens à 100% avant de combattre.");
        }

        $combat = (new Combat())
            ->setAttacker($attacker)
            ->setDefenderNpc($npc)
            ->setIsPvp(false)
            ->setStartedAt(new \DateTimeImmutable());

        $this->em->persist($combat);

        $hpA = $this->healthMaxTotal($attacker);
        $hpN = $npc->getHealthMax();

        $atkA = $this->attackTotal($attacker);
        $defA = $this->defenseTotal($attacker);

        $atkN = $npc->getAttack();
        $defN = $npc->getDefense();

        $round = 1;
        $turnOfA = (bool)random_int(0, 1);

        while ($hpA > 0 && $hpN > 0 && $round <= self::MAX_ROUNDS) {
            $crit = (random_int(1, 100) <= self::CRIT_RATE);
            $dodge = (random_int(1, 100) <= self::DODGE_RATE);
            $action = 'hit';
            $dmg = 0;

            if ($turnOfA) {
                if ($dodge) {
                    $action = 'dodge';
                } else {
                    $base = max(1, ($atkA + random_int(0,2)) - ($defN + random_int(0,1)));
                    $dmg = $crit ? $base * 2 : $base;
                    $hpN -= $dmg;
                }
                $line = sprintf("R%d: Tu attaques %s (%s%d). HP PNJ: %d",
                    $round,
                    $action === 'dodge' ? '→ le PNJ esquive' : '→ touche',
                    $crit ? 'CRIT ' : '',
                    $dmg,
                    max($hpN, 0)
                );
            } else {
                if ($dodge) {
                    $action = 'dodge';
                } else {
                    $base = max(1, ($atkN + random_int(0,2)) - ($defA + random_int(0,1)));
                    $dmg = $crit ? $base * 2 : $base;
                    $hpA -= $dmg;
                }
                $line = sprintf("R%d: PNJ attaque %s (%s%d). Tes HP: %d",
                    $round,
                    $action === 'dodge' ? '→ tu esquives' : '→ te touche',
                    $crit ? 'CRIT ' : '',
                    $dmg,
                    max($hpA, 0)
                );
            }

            $turn = (new CombatTurn())
                ->setCombat($combat)
                ->setRound($round)
                ->setAttackerIsNpc(!$turnOfA)
                ->setAction($action === 'dodge' ? 'dodge' : ($crit ? 'crit' : 'hit'))
                ->setDamage($dmg)
                ->setAttackerHp($turnOfA ? $hpA : $hpN)
                ->setDefenderHp($turnOfA ? $hpN : $hpA)
                ->setLogLine($line)
                ->setAttackerName($turnOfA ? $attacker->getName() : $npc->getName())
                ->setDefenderName($turnOfA ? $npc->getName()     : $attacker->getName());

            $this->em->persist($turn);

            $turnOfA = !$turnOfA;
            $round++;
        }

        $winnerIsA = $hpA > 0 && $hpN <= 0;
        $combat->setEndedAt(new \DateTimeImmutable());
        $combat->setWinnerCharacterId($winnerIsA ? $attacker->getId() : null);

        // Appliquer résultats
        if ($winnerIsA) {
            $attacker->setHealthCurrent($hpA);
            $attacker->setGold($attacker->getGold() + random_int($npc->getGoldMin(), $npc->getGoldMax()));
            $attacker->setExp($attacker->getExp() + $npc->getExpReward());

            // Level up(s)
            while ($attacker->getExp() >= 100 * $attacker->getLevel()) {
                $attacker->setExp($attacker->getExp() - 100 * $attacker->getLevel());
                $attacker->setLevel($attacker->getLevel() + 1);
                $attacker->setAttackBase($attacker->getAttackBase() + 2);
                $attacker->setDefenseBase($attacker->getDefenseBase() + 2);
                $attacker->setHealthMax($attacker->getHealthMax() + 5);
                $attacker->setHealthCurrent($this->healthMaxTotal($attacker));
            }
        } else {
            $attacker->setHealthCurrent(max(1, $hpA));
        }

        $this->em->flush();
        return $combat;
    }

    public function simulatePvp(\App\Entity\Character $a, \App\Entity\Character $b): \App\Entity\Combat
    {
        // 0) Snapshot des PV RÉELS au moment d’entrer dans l’arène (aucune regen ici)
        $realStartA = (int)max(0, $a->getHealthCurrent());
        $realStartB = (int)max(0, $b->getHealthCurrent());

        // Caps et totaux (pour la simulation "comme si full")
        $capA = $this->healthMaxTotal($a);
        $capB = $this->healthMaxTotal($b);
        $atkA = $this->attackTotal($a);   $defA = $this->defenseTotal($a);
        $atkB = $this->attackTotal($b);   $defB = $this->defenseTotal($b);

        // 1) Entité Combat
        $combat = (new \App\Entity\Combat())
            ->setAttacker($a)
            ->setIsPvp(true)
            ->setStartedAt(new \DateTimeImmutable());
        if (method_exists($combat, 'setDefenderCharacter')) {
            $combat->setDefenderCharacter($b);
        }
        $this->em->persist($combat);
        $this->em->flush();

        // 2) Simulation "comme si full" (HP de départ = cap)
        $hpA = $capA;
        $hpB = $capB;

        // Message système: début
        $this->em->persist((new \App\Entity\CombatTurn())
            ->setCombat($combat)->setRound(0)
            ->setAction('') // → traité comme message système par l’UI
            ->setDamage(0)->setAttackerHp($hpA)->setDefenderHp($hpB)
            ->setAttackerName(null)->setDefenderName(null)
            ->setLogLine('🟢 Début du combat (Arène JcJ — soins automatiques)'));

        $round = 1;
        $turnOfA = (bool)random_int(0, 1);

        while ($hpA > 0 && $hpB > 0 && $round <= self::MAX_ROUNDS) {
            $crit=(random_int(1,100)<=self::CRIT_RATE);
            $dodge=(random_int(1,100)<=self::DODGE_RATE);
            $action='hit'; $dmg=0;

            if ($turnOfA) {
                if ($dodge) { $action='dodge'; }
                else {
                    $base = max(1, ($atkA + random_int(0,2)) - ($defB + random_int(0,1)));
                    $dmg  = $crit ? $base*2 : $base;
                    $hpB -= $dmg;
                }
                $line = sprintf("R%d: %s attaque %s (%s%d). PV %s: %d",
                    $round, $a->getName(),
                    $dodge ? '→ esquive' : '→ est touché',
                    $crit ? 'CRIT ' : '', $dmg, $b->getName(), max($hpB,0));
            } else {
                if ($dodge) { $action='dodge'; }
                else {
                    $base = max(1, ($atkB + random_int(0,2)) - ($defA + random_int(0,1)));
                    $dmg  = $crit ? $base*2 : $base;
                    $hpA -= $dmg;
                }
                $line = sprintf("R%d: %s attaque %s (%s%d). PV %s: %d",
                    $round, $b->getName(),
                    $dodge ? '→ esquive' : '→ est touché',
                    $crit ? 'CRIT ' : '', $dmg, $a->getName(), max($hpA,0));
            }

            $turn = (new \App\Entity\CombatTurn())
                ->setCombat($combat)->setRound($round)->setAttackerIsNpc(false)
                ->setAction($action==='dodge' ? 'dodge' : ($crit ? 'crit' : 'hit'))
                ->setDamage($dmg)
                ->setAttackerHp($turnOfA ? $hpA : $hpB)
                ->setDefenderHp($turnOfA ? $hpB : $hpA)
                ->setLogLine($line)
                ->setAttackerName($turnOfA ? $a->getName() : $b->getName())
                ->setDefenderName($turnOfA ? $b->getName() : $a->getName());
            $this->em->persist($turn);

            $turnOfA = !$turnOfA; $round++;
        }

        // 3) Résultat
        $aWins = $hpA > 0 && $hpB <= 0;
        $combat->setEndedAt(new \DateTimeImmutable());
        $combat->setWinnerCharacterId($aWins ? $a->getId() : $b->getId());

        // Message système: fin (toujours part des HP simulés)
        $this->em->persist((new \App\Entity\CombatTurn())
            ->setCombat($combat)->setRound($round)
            ->setAction('')->setDamage(0)
            ->setAttackerHp($hpA)->setDefenderHp($hpB)
            ->setAttackerName(null)->setDefenderName(null)
            ->setLogLine('🏁 Fin du combat (PV réels restaurés)'));

        // 4) IMPORTANT — Restaurer exactement les PV réels d’avant combat
        $a->setHealthCurrent($realStartA);
        $b->setHealthCurrent($realStartB);

        // 5) Récompenses (si tu veux garder le gain Arène)
        $winner   = $aWins ? $a : $b;
        $loser    = $aWins ? $b : $a;
        $expGain  = 20 * max(1, $loser->getLevel());
        $goldGain = random_int(5, 15) * max(1, $loser->getLevel());

        $winner->setExp($winner->getExp() + $expGain);
        $winner->setGold($winner->getGold() + $goldGain);

        // Level-ups éventuels POUR LE VAINQUEUR (sans modifier ses PV actuels)
        while ($winner->getExp() >= 100 * $winner->getLevel()) {
            $winner->setExp($winner->getExp() - 100 * $winner->getLevel());
            $winner->setLevel($winner->getLevel() + 1);
            $winner->setAttackBase($winner->getAttackBase() + 2);
            $winner->setDefenseBase($winner->getDefenseBase() + 2);
            $winner->setHealthMax($winner->getHealthMax() + 5);
            // Pas de full heal ici → les PV restent `realStart*`
        }

        $this->em->flush();
        return $combat;
    }
}
