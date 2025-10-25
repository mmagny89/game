<?php
declare(strict_types=1);

namespace App\Controller;

use App\Entity\Character;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class CharacterController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em) {}

    #[Route('/character/create', name: 'character_create')]
    public function create(Request $request): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Si un perso existe déjà → go home
        $repoC = $this->em->getRepository(Character::class);
        $existing = $repoC->findOneBy(['user' => $user]);
        if ($existing) {
            return $this->redirectToRoute('app_home');
        }

        if ($request->isMethod('POST')) {
            $name = trim((string)$request->request->get('name', ''));
            $gender = (string)$request->request->get('gender', 'M');

            $errors = [];
            if ($name === '' || mb_strlen($name) > 60) { $errors[] = "Nom invalide."; }
            if (!in_array($gender, ['M','F'], true)) { $errors[] = "Genre invalide."; }

            if (!$errors) {
                $c = (new Character())
                    ->setUser($user)
                    ->setName($name)
                    ->setGender($gender)
                    ->setLevel(1)->setExp(0)->setGold(50)
                    ->setAttackBase(5)->setDefenseBase(5)->setHealthMax(20)->setHealthCurrent(20);
                $this->em->persist($c);
                $this->em->flush();

                $this->addFlash('success', 'Personnage créé !');
                return $this->redirectToRoute('app_home');
            }

            return $this->render('character/create.html.twig', [
                'errors' => $errors,
                'old' => ['name' => $name, 'gender' => $gender],
            ]);
        }

        return $this->render('character/create.html.twig');
    }
}
