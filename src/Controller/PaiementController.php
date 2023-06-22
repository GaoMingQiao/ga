<?php

namespace App\Controller;

use Stripe\Stripe;
use App\Entity\Produit;
use App\Entity\Commande;
use App\Service\PanierService;
use App\Entity\CommandeProduit;
use App\Repository\CommandeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Checkout\Session;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PaiementController extends AbstractController {
    /**
     * On veut vider le panier
     * Et créer une commande
     * Et emmener vers le formulaire de paiement
     * 
     * @see https://stripe.com/docs/checkout/quickstart
     */
    #[Route('/paiement', name: 'app_paiement')]
    public function pendant(PanierService $panierService, EntityManagerInterface $em): Response {

        // Stripe secret key
        $ssk = $this->getParameter('stripe.secretKey');
        Stripe::setApiKey($ssk); // On configure Stripe
        $tableauPourStripe = []; // Un tableau pour Stripe

        // Créer la commande
        $commande = new Commande;
        $commande->setEtat('En attente de paiement');
        $commande->setToken(
            hash('sha256', random_bytes(32)) // Crée une chaîne de caractères aléatoire
        );

        $panier = $panierService->recupererTousLesProduits();

        // On boucle pour remplir la commande
        // On boucle également pour remplir le formulaire Stripe
        foreach ($panier['produits'] as $id => $ligne) {

            $quantite = $ligne['quantite'];
            /** @var Produit */
            $produit = $ligne['produit'];

            // On remplit la commande
            $cp = new CommandeProduit;
            $cp->setQuantite($quantite);
            $cp->setProduit($produit);

            $commande->addCommandeProduit($cp);


            // On remplit le tableau pour Stripe
            $tableauPourStripe[] = [
                'quantity' => $quantite,
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => $produit->getNom(),
                        'images' => [$produit->getImage()] // Lien ABSOLU (qui commence par "http(s)://") ; Pas obligatoire
                    ],
                    'unit_amount' => $produit->getPrix() * 100 // Prix en CENTIMES
                ]
            ];
        }

        // On sauvegarde la commande en BDD
        $em->persist($commande);
        $em->flush();

        $checkout = Session::create([
            'mode' => 'payment',
            'line_items' => $tableauPourStripe,
            'success_url' => $this->generateUrl('app_paiement_success', [
                'token' => $commande->getToken()
            ], UrlGeneratorInterface::ABSOLUTE_URL), // Lien ABSOLU (qui commence par "http(s)://")
            'cancel_url' => $this->generateUrl('app_paiement_fail', [
                'commande' => $commande->getId()
            ], UrlGeneratorInterface::ABSOLUTE_URL),  // Lien ABSOLU (qui commence par "http(s)://")
        ]);

        // On vide le panier
        $panierService->viderPanier();

        return $this->redirect($checkout->url);
    }

    /**
     * Le paiement a réussi
     * On "valide" la commande
     */
    #[Route('/paiement/succes/{token}', name: 'app_paiement_success')]
    public function apres(string $token, CommandeRepository $commandeRepository, EntityManagerInterface $em): Response {
        $commande = $commandeRepository->findOneBy(['token' => $token]);
        $commande->setEtat('Validée');
        $em->persist($commande);
        $em->flush();

        return $this->render('payment/success.html.twig');
    }

    /**
     * Le paiement a échoué
     * On supprime la commande
     * On recrée le panier
     */
    #[Route('/paiement/echec/{commande}', name: 'app_paiement_fail')]
    public function retournerAAvant(Commande $commande, PanierService $panierService, EntityManagerInterface $em): Response {
        $panierService->remplirPanier($commande);
        $em->remove($commande);
        $em->flush();

        return $this->render('payment/cancel.html.twig');
    }
}
