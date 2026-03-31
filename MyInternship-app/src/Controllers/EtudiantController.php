<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\EtudiantModel;
use App\Models\WishlistModel;

class EtudiantController extends Controleur {

    private EtudiantModel $etudiantModel;
    private WishlistModel $wishlistModel;

    public function __construct($twig){
        parent::__construct($twig);
        $this->etudiantModel = new etudiantModel();
        $this->wishlistModel = new WishlistModel();
    }

    public function showDashboard(): void
    {
        if (!isset($_SESSION['user'])) {
            $this->redirect('home');
        }

        if ($_SESSION['user']['role'] !== 3) {
            $this->redirect('home');
        }

        $section = $_GET['section'] ?? 'infos';

        $menu = [
            'infos' => 'Informations',
            'candidatures' => 'Liste des candidatures',
            'wishlist' => 'Ma Wishlist'
        ];

        $wishlist = [];

        if ($section === 'wishlist') {
            $wishlist = $this->wishlistModel->getWishlistByUser($_SESSION['user']['id']);
        }



        $this->render('dashboard/MonCompteEtudiant.html.twig', [
            'section' => $section,
            'menu' => $menu,
            'route' => 'etudiant_dashboard',
            'wishlist' => $wishlist
        ]);
    }
}
