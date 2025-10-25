<?php
namespace App\Controller;

use App\Entity\PvPChallenge;
use App\Entity\Character;
use App\Enum\PvpStatus;
use App\Service\CombatSimulator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/arena')]
class ArenaController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private CombatSimulator $sim,
    ) {}

    #[Route('', name: 'arena_index')]
    public function index(Request $request): Response
    {
        $user = $this->getUser();
        $char = $this->em->getRepository(Character::class)->findOneBy(['user' => $user]);
        $others = $this->em->getRepository(Character::class)
            ->createQueryBuilder('c')
            ->where('c.id != :me')->setParameter('me', $char->getId())
            ->getQuery()->getResult();

        $challenges = $this->em->getRepository(PvPChallenge::class)
            ->findBy(['opponent' => $char, 'status' => 'pending']);

        $history = $this->em->getRepository(PvPChallenge::class)->recentHistoryFor($char);

        $autoplay = (int)$request->query->get('replay', 0);

        return $this->render('arena/index.html.twig', [
            'char'       => $char,
            'others'     => $others,
            'challenges' => $challenges,
            'history'    => $history,
            'autoplay'   => $autoplay, // 👈 on l’envoie à Twig
        ]);
    }

    #[Route('/challenge/{id}', name: 'arena_challenge', methods: ['POST'])]
    public function challenge(int $id): Response
    {
        $user = $this->getUser();
        $challenger = $this->em->getRepository(Character::class)->findOneBy(['user' => $user]);
        $opponent   = $this->em->getRepository(Character::class)->find($id);
        if (!$challenger || !$opponent || $challenger->getId() === $opponent->getId()) {
            $this->addFlash('error','Défi invalide.'); return $this->redirectToRoute('arena_index');
        }

        // ❌ refuse si un duel entre vous n’est pas "done"
        $exists = $this->em->getRepository(PvPChallenge::class)->existsOngoingBetween($opponent, $challenger);

        if ($exists) {
            $this->addFlash('error', 'Un duel entre vous n’est pas encore terminé.');
            return $this->redirectToRoute('arena_index');
        }

        $challenge = (new PvPChallenge())
            ->setChallenger($challenger)
            ->setOpponent($opponent)
            ->setStatus(PvpStatus::Pending);

        $this->em->persist($challenge);
        $this->em->flush();

        $this->addFlash('success', "Défi envoyé à {$opponent->getName()} !");
        return $this->redirectToRoute('arena_index');
    }

    #[Route('/accept/{id}', name: 'arena_accept', methods: ['POST'])]
    public function accept(int $id, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('arena_accept_'.$id, $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        $challenge = $this->em->getRepository(PvPChallenge::class)->find($id);
        if (!$challenge) { throw $this->createNotFoundException(); }

        // Lance la simu (remplit winner + associe $combat)
        $this->simulate($challenge);

        $challenge->setStatus(PvpStatus::Done);
        $challenge->setStartedAt($challenge->getStartedAt() ?? new \DateTimeImmutable());
        $challenge->setEndedAt(new \DateTimeImmutable());
        $this->em->flush();

        // ⬇️ Redirection vers la page du combat (replay)
        return $this->redirectToRoute('combat_view', [
            'id' => $challenge->getCombat()->getId(),
        ]);
    }

    private function simulate(PvPChallenge $challenge): void
    {
        $combat = $this->sim->simulatePvp(
            $challenge->getChallenger(),
            $challenge->getOpponent()
        );
        $challenge->setWinnerCharacterId($combat->getWinnerCharacterId());
        $challenge->setCombat($combat);
    }

    #[Route('/arena/_badge', name: 'arena_badge', methods: ['GET'])]
    public function badge(): Response
    {
        $user = $this->getUser();
        if (!$user) { return new Response(''); }

        $me = $this->em->getRepository(\App\Entity\Character::class)
            ->findOneBy(['user' => $user]);
        if (!$me) { return new Response(''); }

        $count = $this->em->getRepository(PvPChallenge::class)->count([
            'opponent' => $me,
            'status'   => 'pending',
        ]);

        return $this->render('arena/_badge.html.twig', ['count' => $count]);
    }
}
