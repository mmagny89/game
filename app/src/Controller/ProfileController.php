<?php
declare(strict_types=1);

namespace App\Controller;

use App\Entity\Character;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\CombatSimulator;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

final class ProfileController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $hasher,
        private ?CombatSimulator $sim = null, // injecté auto si dispo
        private ?CsrfTokenManagerInterface $csrf = null
    ) {}

    #[Route('/profile', name: 'profile_index')]
    public function index(Request $request): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user) { return $this->redirectToRoute('app_login'); }

        $char = $this->em->getRepository(Character::class)->findOneBy(['user' => $user]);
        if (!$char) { return $this->redirectToRoute('character_create'); }

        // POST – mise à jour personnage
        if ($request->isMethod('POST') && $request->request->get('action') === 'update_char') {
            if (!$this->isCsrfTokenValid('profile_update_char', (string)$request->request->get('_token'))) {
                $this->addFlash('error', 'Action expirée (CSRF).');
                return $this->redirectToRoute('profile_index');
            }

            $name = trim((string)$request->request->get('name', ''));
            $gender = (string)$request->request->get('gender', 'M');

            $errors = [];
            if ($name === '' || mb_strlen($name) > 60) { $errors[] = 'Nom invalide (1–60 car.).'; }
            if (!in_array($gender, ['M','F'], true)) { $errors[] = 'Genre invalide.'; }

            if ($errors) {
                foreach ($errors as $e) { $this->addFlash('error', $e); }
            } else {
                $char->setName($name)->setGender($gender);
                $this->em->flush();
                $this->addFlash('success', 'Personnage mis à jour.');
            }
            return $this->redirectToRoute('profile_index');
        }

        // POST – changement mot de passe
        if ($request->isMethod('POST') && $request->request->get('action') === 'update_pwd') {
            if (!$this->isCsrfTokenValid('profile_update_pwd', (string)$request->request->get('_token'))) {
                $this->addFlash('error', 'Action expirée (CSRF).');
                return $this->redirectToRoute('profile_index');
            }

            $current = (string)$request->request->get('current_password', '');
            $new     = (string)$request->request->get('new_password', '');
            $confirm = (string)$request->request->get('confirm_password', '');

            $errors = [];
            // Si tu veux imposer l'ancien mdp :
            if ($current === '' || !$this->hasher->isPasswordValid($user, $current)) {
                $errors[] = 'Mot de passe actuel incorrect.';
            }
            if (strlen($new) < 6) { $errors[] = 'Nouveau mot de passe trop court (6+).'; }
            if ($new !== $confirm) { $errors[] = 'La confirmation ne correspond pas.'; }

            if ($errors) {
                foreach ($errors as $e) { $this->addFlash('error', $e); }
            } else {
                $user->setPassword($this->hasher->hashPassword($user, $new));
                $this->em->flush();
                $this->addFlash('success', 'Mot de passe mis à jour.');
            }
            return $this->redirectToRoute('profile_index');
        }

        return $this->render('profile/index.html.twig', [
            'user' => $user,
            'char' => $char,
        ]);
    }

    /** Forcer un check de régénération serveur, puis retour home */
    #[Route('/me/regen', name: 'me_regen', methods: ['POST'])]
    public function regen(Request $request): RedirectResponse
    {
        $user = $this->getUser();
        if (!$user) { return $this->redirectToRoute('app_login'); }

        if (!$this->isCsrfTokenValid('me_regen', (string)$request->request->get('_token'))) {
            $this->addFlash('error', 'Action expirée (CSRF).');
            return $this->redirectToRoute('app_home');
        }

        $char = $this->em->getRepository(\App\Entity\Character::class)->findOneBy(['user' => $user]);
        if (!$char) { return $this->redirectToRoute('character_create'); }

        // Cap = PV max total (bonus inclus) si le service est injecté
        $cap = $this->sim ? $this->sim->healthMaxTotal($char) : $char->getHealthMax();
        $char->applyRegen(1, $cap);
        $this->em->flush();

        return $this->redirectToRoute('app_home');
    }
}
