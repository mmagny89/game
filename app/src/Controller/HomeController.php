<?php
declare(strict_types=1);

namespace App\Controller;

use App\Entity\Character;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\CombatSimulator;
use App\Service\NpcScaler;
use App\Entity\NPC;
use App\Service\DangerMeter;

final class HomeController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private CombatSimulator $sim,
        private NpcScaler $scaler,
        private DangerMeter $danger,
    ) {}

    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $char = $this->em->getRepository(\App\Entity\Character::class)->findOneBy(['user' => $user]);
        if (!$char) {
            return $this->redirectToRoute('character_create');
        }
        // 1) Cap total = PV base + bonus équipements
        $hpMaxT = $this->sim->healthMaxTotal($char);

        // 2) Appliquer la régén côté serveur jusqu’au cap total
        $char->applyRegen(1, $hpMaxT);
        $this->em->flush();

        // 3) Données pour le compteur
        $now  = new \DateTimeImmutable();
        $hpCur = $char->getHealthCurrent();
        $hpMissing = max(0, $hpMaxT - $hpCur);
        $secondsRemaining = $hpMissing * 60;
        $lastTs    = (int)$char->getLastHealthTs()->format('U');
        $nextTickTs= $lastTs + 60;

        // 4) (déjà existant) stats totales + équipement…
        $atkT = $this->sim->attackTotal($char);
        $defT = $this->sim->defenseTotal($char);

        // PNJ scalés
        $npcCards = [
            'easy' => $this->scaler->build($char, 'easy'),
            'even' => $this->scaler->build($char, 'even'),
            'hard' => $this->scaler->build($char, 'hard'),
        ];

        // Boss (PNJ fixes en BDD)
        $bosses = $this->em->getRepository(NPC::class)->findBy([], ['level' => 'ASC', 'id' => 'ASC']);

        // Danger pour les PNJ scalés
        $npcDanger = [
            'easy' => $this->danger->vsSnapshot($char, $npcCards['easy']),
            'even' => $this->danger->vsSnapshot($char, $npcCards['even']),
            'hard' => $this->danger->vsSnapshot($char, $npcCards['hard']),
        ];

        // Danger pour les boss (par id)
        $bossDanger = [];
        foreach ($bosses as $b) {
            $bossDanger[$b->getId()] = $this->danger->vsBoss($char, $b);
        }

        // inventaire équipé (si tu l’as déjà, garde ton code actuel)
        $equipped = $this->em->getRepository(\App\Entity\InventoryItem::class)
            ->createQueryBuilder('inv')
            ->join('inv.item', 'i')
            ->where('inv.character = :c AND inv.equipped = true')
            ->setParameter('c', $char)
            ->orderBy('i.slot', 'ASC')
            ->getQuery()->getResult();

        // Vue
        return $this->render('home.html.twig', [
            'user' => $user,
            'char' => $char,
            'atk_total' => $atkT,
            'def_total' => $defT,
            'hpmax_total' => $hpMaxT,
            'equipped' => $equipped,
            'npc_cards'  => $npcCards,
            'bosses'     => $bosses,
            'npc_danger' => $npcDanger,
            'boss_danger'=> $bossDanger,
            'regen' => [
                'per_minute'        => 1,
                'now_ts'            => (int)$now->format('U'),
                'last_ts'           => $lastTs,
                'next_tick_ts'      => $nextTickTs,
                'seconds_remaining' => $secondsRemaining,
                'is_full'           => ($secondsRemaining === 0),
                'hp_current'        => $hpCur,
            ],
        ]);
    }

    // Seed minimal sans bundle fixture
    #[Route('/dev/seed', name: 'app_seed')]
    public function seed(): Response
    {
        $repoU = $this->em->getRepository(User::class);
        if ($repoU->findOneBy([])) {
            return new Response('Seed déjà exécuté.', 200);
        }

        // --- créer user + character
        $u = (new User())->setEmail('test@example.com')->setPassword(password_hash('test', PASSWORD_BCRYPT));
        $c = (new Character())
            ->setUser($u)
            ->setName('Capitaine')
            ->setGender('F')
            ->setLevel(1)->setExp(0)->setGold(50)
            ->setAttackBase(5)->setDefenseBase(5)->setHealthMax(20)->setHealthCurrent(20);
        $this->em->persist($u);
        $this->em->persist($c);

        // --- PNJ
        $npcs = [
            ['Slime', 1, 3, 1, 10, 10, 1, 3],
            ['Gobelin', 2, 6, 3, 15, 20, 2, 6],
            ['Garde', 3, 8, 6, 22, 35, 4, 10],
        ];
        foreach ($npcs as [$name,$lvl,$atk,$def,$hp,$xp,$gmin,$gmax]) {
            $n = (new \App\Entity\NPC())
                ->setName($name)->setLevel($lvl)->setAttack($atk)->setDefense($def)
                ->setHealthMax($hp)->setExpReward($xp)->setGoldMin($gmin)->setGoldMax($gmax);
            $this->em->persist($n);
        }

        // --- Items (boutique)
        $items = [
            ['Dague',    'weapon', 2,0,0, 15],
            ['Épée',     'weapon', 4,0,0, 35],
            ['Cuir',     'armor',  0,3,0, 25],
            ['Bouclier', 'armor',  0,4,0, 40],
            ['Anneau',   'ring',   1,1,1, 30],
            ['Amulette', 'amulet', 0,0,3, 30],
        ];
        foreach ($items as [$name,$slot,$ab,$db,$hb,$price]) {
            $it = (new \App\Entity\Item())
                ->setName($name)->setSlot($slot)
                ->setAttackBonus($ab)->setDefenseBonus($db)->setHealthBonus($hb)
                ->setPrice($price);
            $this->em->persist($it);
        }

        $this->em->flush();
        return new Response('Seed OK : / (home), /shop et combats prêts.', 201);
    }

    #[Route('/dev/seed-bosses', name: 'app_seed_bosses')]
    public function seedBosses(): Response
    {
        // évite les doublons (si déjà présents)
        $repo = $this->em->getRepository(NPC::class);
        if ($repo->findOneBy(['name' => 'Chef gobelin'])) {
            return new Response('Boss déjà présents.', 200);
        }

        $bosses = [
            ['Chef gobelin', 5,  12, 10, 40,  80, 10, 20],
            ['Chevalier noir', 8, 18, 16, 65, 140, 20, 40],
            ['Dragonnet',    12, 25, 22, 95, 220, 35, 70],
        ];
        foreach ($bosses as [$name,$lvl,$atk,$def,$hp,$xp,$gmin,$gmax]) {
            $n = (new NPC())
                ->setName($name)->setLevel($lvl)->setAttack($atk)->setDefense($def)
                ->setHealthMax($hp)->setExpReward($xp)->setGoldMin($gmin)->setGoldMax($gmax);
            $this->em->persist($n);
        }
        $this->em->flush();

        return new Response('Boss créés 👍', 201);
    }
}
