<?php
declare(strict_types=1);

namespace App\Controller;

use App\Entity\Character;
use App\Entity\InventoryItem;
use App\Entity\Item;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/shop')]
final class ShopController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em) {}

    #[Route('', name: 'shop_index')]
    public function index(): Response
    {
        $user = $this->getUser();
        if (!$user) { return $this->redirectToRoute('app_login'); }

        $char = $this->em->getRepository(Character::class)->findOneBy(['user' => $user]);
        if (!$char) { return $this->redirectToRoute('character_create'); }

        $items = $this->em->getRepository(Item::class)->findBy([], ['price' => 'ASC']);

        // ➜ Charger l’inventaire ici :
        $inv = $this->em->getRepository(InventoryItem::class)
            ->findBy(['character' => $char], ['id' => 'ASC']);

        return $this->render('shop/index.html.twig', [
            'char'  => $char,
            'items' => $items,
            'inv'   => $inv, // ➜ passer au template
        ]);
    }

    #[Route('/buy/{id}', name: 'shop_buy', methods: ['POST'])]
    public function buy(int $id, Request $request): RedirectResponse
    {
        $user = $this->getUser();
        if (!$user) { return $this->redirectToRoute('app_login'); }
        $char = $this->em->getRepository(Character::class)->findOneBy(['user' => $user]);
        if (!$char) { return $this->redirectToRoute('character_create'); }

        $item = $this->em->getRepository(Item::class)->find($id);
        if (!$item) { throw $this->createNotFoundException(); }

        // ✅ CSRF
        if (!$this->isCsrfTokenValid('shop_buy_'.$item->getId(), (string)$request->request->get('_token'))) {
            $this->addFlash('error', 'Action expirée (CSRF).');
            return $this->redirectToRoute('shop_index');
        }

        if ($char->getGold() < $item->getPrice()) {
            $this->addFlash('error', "Pas assez d'or.");
            return $this->redirectToRoute('shop_index');
        }

        $char->setGold($char->getGold() - $item->getPrice());
        $inv = (new InventoryItem())->setCharacter($char)->setItem($item)->setEquipped(false);
        $this->em->persist($inv);
        $this->em->flush();

        $this->addFlash('success', "{$item->getName()} acheté.");
        return $this->redirectToRoute('shop_index');
    }

    #[Route('/equip/{id}', name: 'shop_equip', methods: ['POST'])]
    public function equip(int $id, Request $request): RedirectResponse
    {
        $user = $this->getUser();
        if (!$user) { return $this->redirectToRoute('app_login'); }
        $char = $this->em->getRepository(Character::class)->findOneBy(['user' => $user]);
        if (!$char) { return $this->redirectToRoute('character_create'); }

        $inv = $this->em->getRepository(InventoryItem::class)->find($id);
        if (!$inv || $inv->getCharacter()->getId() !== $char->getId()) {
            throw $this->createNotFoundException();
        }

        // ✅ CSRF
        if (!$this->isCsrfTokenValid('shop_equip_'.$inv->getId(), (string)$request->request->get('_token'))) {
            $this->addFlash('error', 'Action expirée (CSRF).');
            return $this->redirectToRoute('shop_index');
        }

        $slot = $inv->getItem()->getSlot();
        $q = $this->em->createQuery('UPDATE App\Entity\InventoryItem i
        SET i.equipped = false
        WHERE i.character = :c AND i.equipped = true AND i.item IN (
          SELECT it FROM App\Entity\Item it WHERE it.slot = :slot
        )');
        $q->setParameter('c', $char)->setParameter('slot', $slot)->execute();

        $inv->setEquipped(true);
        $this->em->flush();

        $this->addFlash('success', "{$inv->getItem()->getName()} équipé.");
        return $this->redirectToRoute('shop_index');
    }

    #[Route('/unequip/{id}', name: 'shop_unequip', methods: ['POST'])]
    public function unequip(int $id, Request $request): RedirectResponse
    {
        $user = $this->getUser();
        if (!$user) { return $this->redirectToRoute('app_login'); }

        $char = $this->em->getRepository(Character::class)->findOneBy(['user' => $user]);
        if (!$char) { return $this->redirectToRoute('character_create'); }

        $inv = $this->em->getRepository(InventoryItem::class)->find($id);
        if (!$inv || $inv->getCharacter()->getId() !== $char->getId()) {
            throw $this->createNotFoundException();
        }

        // CSRF
        if (!$this->isCsrfTokenValid('shop_unequip_'.$inv->getId(), (string)$request->request->get('_token'))) {
            $this->addFlash('error', 'Action expirée (CSRF).');
            return $this->redirectToRoute('shop_index');
        }

        if ($inv->isEquipped()) {
            $inv->setEquipped(false);
            $this->em->flush();
            $this->addFlash('success', $inv->getItem()->getName().' déséquipé.');
        } else {
            $this->addFlash('error', "Cet objet n'est pas équipé.");
        }

        return $this->redirectToRoute('shop_index');
    }
}
