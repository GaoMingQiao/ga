<?php

namespace App\Service;

use App\Entity\Commande;
use App\Entity\Produit;
use App\Repository\ProduitRepository;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Service qui GERE le panier
 * Càd qui fait un CRUD sur les produits du panier
 * (Le panier étant stocké en session)
 */
class PanierService {

    protected $session;
    protected $produitRepository;

    public function __construct(SessionInterface $session, ProduitRepository $produitRepository) {
        $this->session = $session;
        $this->produitRepository = $produitRepository;
    }

    /**
     * Le panier dans la session aura cette forme :
     * $panier = [
     *      'total' => [
     *          'quantite' => ???,
     *          'ht' => ???,
     *          'ttc' => ???,
     *          'tva' => ???,
     *      ],
     *      'produits' => [
     *          $id => [
     *              'id_produit' => $id,
     *              'quantite' => $qtte
     *          ],
     *          (répété potentiellement plusieurs fois)
     *      ]
     * ];
     */

    public function ilExisteCeProduitDansLePanier(Produit $produit): bool {
        $id = $produit->getId();
        $panier = $this->session->get('panier', []);

        $existe = isset($panier['produits'][$id]);
        return $existe;
    }

    public function mettreAJourLePanier(array $nouveauPanier) {
        $this->session->set('panier', $nouveauPanier);
        $tousLesProduits = $this->recupererTousLesProduits();

        $nouveauPanier['total'] = $tousLesProduits['total'];
        $this->session->set('panier', $nouveauPanier);
    }

    // Le CREATE
    public function ajouterAuPanier(Produit $produit, float $quantite) {
        // Grâce à la session
        // On récupère le panier
        // (Ou bien un tableau vide s'il n'existe pas encore)
        $panier = $this->session->get('panier', []);

        $id = $produit->getId();

        if (!$this->ilExisteCeProduitDansLePanier($produit)) { // Si la ligne n'existe pas, on l'ajoute
            $nouvelleLigne = [
                'id_produit' => $id,
                'quantite' => $quantite
            ];
            $panier['produits'][$id] = $nouvelleLigne; // On ajoute notre nouvelle ligne (la clef est l'ID du produit)
        } else { // Sinon on la modifie juste
            $this->modifierQuantiteDuPanier($produit, $quantite);
        }


        // On sauvegarde notre panier dans la session
        $this->mettreAJourLePanier($panier);
    }

    /**
     * Le panier au retour de la méthode aura cette forme :
     * $panier = [
     *      'total' => [
     *          'quantite' => ???,
     *          'ht' => ???,
     *          'ttc' => ???,
     *          'tva' => ???,
     *      ],
     *      'produits' => [
     *          $id => [
     *              'produit' => $produit,
     *              'quantite' => $qtte
     *          ],
     *          (répété potentiellement plusieurs fois)
     *      ]
     * ];
     */
    // Le RETRIEVE
    public function recupererTousLesProduits(): array {
        $panierAvecLesProduits = [
            'produits' => [],
            'total' => []
        ];
        $panierDeLaSession = $this->session->get('panier', []);
        $totalHt = 0;
        $totalTtc = 0;
        $qtteTotal = 0;

        foreach ($panierDeLaSession['produits'] as $id => $ligne) {
            $produit = $this->produitRepository->find($id);

            $panierAvecLesProduits['produits'][$id] = [
                'produit' => $produit,
                'quantite' => $ligne['quantite']
            ];

            $totalLigneTtc = $ligne['quantite'] * $produit->getPrix();
            $totalLigneHt = $totalLigneTtc / (1 + $produit->getTauxTva() / 100);

            $totalHt += $totalLigneHt;
            $totalTtc += $totalLigneTtc;

            $qtteTotal += $ligne['quantite'];
        }

        $panierAvecLesProduits['total'] = [
            'quantite' => $qtteTotal,
            'ht' => number_format($totalHt, 2, ',', ' '),
            'ttc' => number_format($totalTtc, 2, ',', ' '),
            'tva' => number_format($totalTtc - $totalHt, 2, ',', ' '),
        ];

        return $panierAvecLesProduits;
    }

    // Le UPDATE
    public function modifierQuantiteDuPanier(Produit $produit, float $quantite) {
        $id = $produit->getId();
        $panier = $this->session->get('panier', []);

        if (!$this->ilExisteCeProduitDansLePanier($produit)) { // Si la ligne n'existe pas, on l'ajoute
            $this->ajouterAuPanier($produit, $quantite); // On ajoute notre nouvelle ligne
        } else { // Sinon on la modifie juste
            $panier['produits'][$id]['quantite'] += $quantite;
        }

        // On sauvegarde notre panier dans la session
        $this->mettreAJourLePanier($panier);
    }

    // Le DELETE
    public function retirerDuPanier(Produit $produit) {
        $id = $produit->getId();
        $panier = $this->session->get('panier', []);

        if ($this->ilExisteCeProduitDansLePanier($produit)) {
            unset($panier['produits'][$id]);
        }

        // On sauvegarde notre panier dans la session
        $this->mettreAJourLePanier($panier);
    }

    public function viderPanier() {
        $this->mettreAJourLePanier([
            'total' => [],
            'produits' => []
        ]);
    }

    public function remplirPanier(Commande $commande) {
        $panier = [
            'total' => [],
            'produits' => []
        ];

        foreach ($commande->getCommandeProduits() as $cp) {
            $id = $cp->getProduit()->getId();
            $panier['produits'][$id] = [
                'produit_id' => $id,
                'quantite' => $cp->getQuantite()
            ];
        }

        $this->mettreAJourLePanier($panier);
    }
}
