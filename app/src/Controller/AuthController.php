<?php
declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AuthController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em) {}

    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $utils): Response
    {
        $error = $utils->getLastAuthenticationError();
        $last = $utils->getLastUsername();

        return $this->render('auth/login.html.twig', [
            'last_username' => $last,
            'error' => $error instanceof AuthenticationException ? $error->getMessageKey() : null,
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        // Intercepté par le firewall
        throw new \LogicException('Handled by Symfony security.');
    }

    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $hasher,
        \Symfony\Bundle\SecurityBundle\Security $security, // <— injecté
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        if ($request->isMethod('POST')) {
            $email = trim((string)$request->request->get('email', ''));
            $plain = (string)$request->request->get('password', '');

            $errors = [];
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = "Email invalide."; }
            if (strlen($plain) < 6) { $errors[] = "Mot de passe trop court (6+)."; }
            if ($this->em->getRepository(User::class)->findOneBy(['email' => strtolower($email)])) {
                $errors[] = "Un compte existe déjà avec cet email.";
            }

            if (!$errors) {
                $u = (new User())->setEmail($email);
                $u->setPassword($hasher->hashPassword($u, $plain));
                $this->em->persist($u);
                $this->em->flush();

                // ✅ Auto-login
                $security->login($u);

                // S’il n’a pas encore de personnage, on l’envoie le créer
                return $this->redirectToRoute('character_create');
            }

            return $this->render('auth/register.html.twig', [
                'errors' => $errors,
                'last_email' => $email,
            ]);
        }

        return $this->render('auth/register.html.twig');
    }
}
