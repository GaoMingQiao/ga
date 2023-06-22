<?php

namespace App\Controller;

use App\Entity\Produit;
use App\Service\PanierService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PanierController extends AbstractController {
    #[Route('/panier', name: 'app_panier')]
    public function index(PanierService $panierService): Response {
        $panier = $panierService->recupererTousLesProduits();

        return $this->render('panier/index.html.twig', [
            'panier' => $panier,
        ]);
    }

    #[Route('/panier/vider', name: 'app_panier_vider')]
    public function vider(PanierService $panierService): Response {
        $panierService->viderPanier();

        return $this->redirectToRoute('app_panier');
    }

    #[Route('/panier/add/{produit}', name: 'app_panier_add')]
    public function add(Produit $produit, Request $request, PanierService $panierService): Response {
        $qtte = $request->request->get('qtte', 1);
        $panierService->ajouterAuPanier($produit, $qtte);

        return $this->redirectToRoute('app_produits');
    }

    #[Route('/panier/remove/{produit}', name: 'app_panier_remove')]
    public function remove(Produit $produit, PanierService $panierService): Response {
        $panierService->retirerDuPanier($produit);

        return $this->redirectToRoute('app_panier');
    }
}
