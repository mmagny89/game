<?php
declare(strict_types=1);

namespace App\Controller;

use App\Entity\Character;
use App\Entity\CombatTurn;
use App\Entity\NPC;
use App\Service\CombatSimulator;
use App\Service\CombatTurnNormalizer;
use App\Service\NpcScaler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

#[Route('/combat')]
final class CombatController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private CombatSimulator $sim,
        private NpcScaler $scaler,
        private CombatTurnNormalizer $turnNormalizer,
    ) {}

    // ===========================
    //  Lecture animée (PNJ fixes)
    // ===========================
    #[Route('/play/{id}', name: 'combat_play', methods: ['GET'])]
    public function play(int $id, CsrfTokenManagerInterface $csrf): Response
    {
        $user = $this->getUser();
        if (!$user) { return $this->redirectToRoute('app_login'); }

        $char = $this->em->getRepository(Character::class)->findOneBy(['user' => $user]);
        if (!$char) { return $this->redirectToRoute('character_create'); }

        $npc = $this->em->getRepository(NPC::class)->find($id);
        if (!$npc) { throw $this->createNotFoundException(); }

        $token     = $csrf->getToken('combat_npc_'.$id)->getValue();
        $capPlayer = $this->sim->healthMaxTotal($char);

        return $this->render('combat/play.html.twig', [
            'npc'        => $npc,
            'csrf_token' => $token,
            'npc_id'     => $id,
            'player'     => ['name' => $char->getName(), 'hpmax' => $capPlayer],
            'scaled'     => false,
            'post_url'   => $this->generateUrl('combat_npc', ['id' => $id]),
        ]);
    }

    #[Route('/npc/{id}', name: 'combat_npc', methods: ['POST'])]
    public function npc(int $id, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) { return new JsonResponse(['error' => 'auth'], 401); }

        $char = $this->em->getRepository(Character::class)->findOneBy(['user' => $user]);
        if (!$char) { return new JsonResponse(['error' => 'no_char'], 400); }

        $npc = $this->em->getRepository(NPC::class)->find($id);
        if (!$npc) { return new JsonResponse(['error' => 'not_found'], 404); }

        // CSRF
        $token = (string)$request->request->get('_token', '');
        if (!$this->isCsrfTokenValid('combat_npc_'.$id, $token)) {
            return new JsonResponse(['error' => 'csrf'], 419);
        }

        try {
            $combat = $this->sim->simulateVsNpc($char, $npc);
        } catch (\RuntimeException $e) {
            return new JsonResponse(['error' => 'blocked', 'message' => $e->getMessage()], 400);
        }

        $turns = $this->em->getRepository(CombatTurn::class)->findBy(
            ['combat' => $combat],
            ['round' => 'ASC', 'id' => 'ASC']
        );

        $payloadTurns = array_map(function(CombatTurn $t) {
            return $this->turnNormalizer->toArray($t);
        }, $turns);

        return new JsonResponse([
            'title'  => 'Journal du combat',
            'turns'  => $payloadTurns,
            'player' => ['name' => $char->getName(), 'hpmax' => $this->sim->healthMaxTotal($char)],
            'npc'    => ['name' => $npc->getName(),  'hpmax' => $npc->getHealthMax()],
        ]);
    }

    // ===========================
    //  Lecture animée (PNJ scalés)
    // ===========================
    #[Route('/play/scale/{diff}', name: 'combat_play_scaled', methods: ['GET'])]
    public function playScaled(string $diff, CsrfTokenManagerInterface $csrf): Response
    {
        $user = $this->getUser();
        if (!$user) { return $this->redirectToRoute('app_login'); }

        $char = $this->em->getRepository(Character::class)->findOneBy(['user' => $user]);
        if (!$char) { return $this->redirectToRoute('character_create'); }

        $snap      = $this->scaler->build($char, $diff);
        $token     = $csrf->getToken('combat_scaled_'.$diff)->getValue();
        $capPlayer = $this->sim->healthMaxTotal($char);

        // On passe un "npc" minimal (nom + PV max) à la vue
        return $this->render('combat/play.html.twig', [
            'npc'        => (object)['name' => $snap['name'], 'healthMax' => $snap['health_max']],
            'csrf_token' => $token,
            'npc_id'     => $diff,
            'player'     => ['name' => $char->getName(), 'hpmax' => $capPlayer],
            'scaled'     => true,
            'post_url'   => $this->generateUrl('combat_scaled', ['diff' => $diff]),
        ]);
    }

    #[Route('/scale/{diff}', name: 'combat_scaled', methods: ['POST'])]
    public function npcScaled(string $diff, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) { return new JsonResponse(['error' => 'auth'], 401); }

        $char = $this->em->getRepository(Character::class)->findOneBy(['user' => $user]);
        if (!$char) { return new JsonResponse(['error' => 'no_char'], 400); }

        if (!$this->isCsrfTokenValid('combat_scaled_'.$diff, (string)$request->request->get('_token'))) {
            return new JsonResponse(['error' => 'csrf'], 419);
        }

        $snap = $this->scaler->build($char, $diff);

        try {
            $combat = $this->simulateVsSnapshot($char, $snap);
        } catch (\RuntimeException $e) {
            return new JsonResponse(['error' => 'blocked', 'message' => $e->getMessage()], 400);
        }

        $turns = $this->em->getRepository(CombatTurn::class)->findBy(
            ['combat' => $combat],
            ['round' => 'ASC', 'id' => 'ASC']
        );

        $payloadTurns = array_map(function(CombatTurn $t) {
            return $this->turnNormalizer->toArray($t);
        }, $turns);

        return new JsonResponse([
            'title'  => 'Journal du combat',
            'turns'  => $payloadTurns,
            'player' => ['name' => $char->getName(), 'hpmax' => $this->sim->healthMaxTotal($char)],
            'npc'    => ['name' => $snap['name'],      'hpmax' => $snap['health_max']],
        ]);
    }

    // ======================================================
    //  Simulation contre un PNJ "snapshot" (non persisté DB)
    // ======================================================
    /**
     * Simule un combat contre un PNJ construit à la volée (array "snapshot", non persisté).
     * @param array{name:string,level:int,attack:int,defense:int,health_max:int,exp_reward:int,gold_min:int,gold_max:int} $npc
     */
    private function simulateVsSnapshot(Character $attacker, array $npc): \App\Entity\Combat
    {
        // Régénération capée au PV total (base + bonus)
        $cap = $this->sim->healthMaxTotal($attacker);
        $attacker->applyRegen(1, $cap);
        if ($attacker->getHealthCurrent() < $cap) {
            throw new \RuntimeException("Tu es blessé·e. Reviens à 100% avant de combattre.");
        }

        $combat = (new \App\Entity\Combat())
            ->setAttacker($attacker)
            ->setIsPvp(false)
            ->setStartedAt(new \DateTimeImmutable());

        // on ne stocke pas defender_npc ici (PNJ dynamique)
        $this->em->persist($combat);

        // États initiaux
        $hpA  = $cap;
        $hpN  = $npc['health_max'];
        $atkA = $this->sim->attackTotal($attacker);
        $defA = $this->sim->defenseTotal($attacker);
        $atkN = $npc['attack'];
        $defN = $npc['defense'];

        $round = 1;
        $turnOfA = (bool)random_int(0, 1);

        while ($hpA > 0 && $hpN > 0 && $round <= 200) {
            $crit = (random_int(1, 100) <= 10);
            $dodge = (random_int(1, 100) <= 10);
            $action = 'hit';
            $dmg = 0;

            if ($turnOfA) {
                if ($dodge) {
                    $action = 'dodge';
                } else {
                    $base = max(1, ($atkA + random_int(0,2)) - ($defN + random_int(0,1)));
                    $dmg  = $crit ? $base * 2 : $base;
                    $hpN -= $dmg;
                }
                $line = sprintf(
                    "R%d: Tu attaques %s (%s%d). HP %s: %d",
                    $round,
                    $action === 'dodge' ? '→ esquive' : '→ touche',
                    $crit ? 'CRIT ' : '',
                    $dmg,
                    $npc['name'],
                    max($hpN, 0)
                );
            } else {
                if ($dodge) {
                    $action = 'dodge';
                } else {
                    $base = max(1, ($atkN + random_int(0,2)) - ($defA + random_int(0,1)));
                    $dmg  = $crit ? $base * 2 : $base;
                    $hpA -= $dmg;
                }
                $line = sprintf(
                    "R%d: %s attaque %s (%s%d). Tes HP: %d",
                    $round,
                    $npc['name'],
                    $action === 'dodge' ? '→ tu esquives' : '→ te touche',
                    $crit ? 'CRIT ' : '',
                    $dmg,
                    max($hpA, 0)
                );
            }

            $turn = (new \App\Entity\CombatTurn())
                ->setCombat($combat)
                ->setRound($round)
                ->setAttackerIsNpc(!$turnOfA)
                ->setAction($action === 'dodge' ? 'dodge' : ($crit ? 'crit' : 'hit'))
                ->setDamage($dmg)
                ->setAttackerHp($turnOfA ? $hpA : $hpN)
                ->setDefenderHp($turnOfA ? $hpN : $hpA)
                ->setAttackerName($turnOfA ? $attacker->getName() : $npc['name'])
                ->setDefenderName($turnOfA ? $npc['name']         : $attacker->getName())
                ->setLogLine($line);

            $this->em->persist($turn);

            $turnOfA = !$turnOfA;
            $round++;
        }

        $winnerIsA = $hpA > 0 && $hpN <= 0;

        $combat->setEndedAt(new \DateTimeImmutable());
        $combat->setWinnerCharacterId($winnerIsA ? $attacker->getId() : null);

        if ($winnerIsA) {
            // Récompenses
            $attacker->setHealthCurrent($hpA);
            $attacker->setGold($attacker->getGold() + random_int($npc['gold_min'], $npc['gold_max']));
            $attacker->setExp($attacker->getExp() + $npc['exp_reward']);

            // Level-ups éventuels
            while ($attacker->getExp() >= 100 * $attacker->getLevel()) {
                $attacker->setExp($attacker->getExp() - 100 * $attacker->getLevel());
                $attacker->setLevel($attacker->getLevel() + 1);
                $attacker->setAttackBase($attacker->getAttackBase() + 2);
                $attacker->setDefenseBase($attacker->getDefenseBase() + 2);
                $attacker->setHealthMax($attacker->getHealthMax() + 5);
                $attacker->setHealthCurrent($this->sim->healthMaxTotal($attacker));
            }
        } else {
            // Défaite : laisser quelques PV
            $attacker->setHealthCurrent(max(1, $hpA));
        }

        $this->em->flush();
        return $combat;
    }

    #[Route('/combat/{id}', name: 'combat_view')]
    public function view(int $id): Response
    {
        $combat = $this->em->getRepository(\App\Entity\Combat::class)->find($id);
        if (!$combat) { throw $this->createNotFoundException(); }

        $turns = $this->em->getRepository(\App\Entity\CombatTurn::class)->findBy(
            ['combat' => $combat],
            ['round' => 'ASC', 'id' => 'ASC']
        );

        // → On transforme en scalaires (pas d’entités dans le JSON)
        $payload = array_map(function(CombatTurn $t){
            return $this->turnNormalizer->toArray($t);
        }, $turns);

        // On passe une chaîne JSON déjà prête (évite json_encode côté Twig sur des objets)
        return $this->render('combat/view.html.twig', [
            'combat'     => $combat,
            'turns_json' => json_encode($payload, JSON_UNESCAPED_UNICODE),
        ]);
    }
}
