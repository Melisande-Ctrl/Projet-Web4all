<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Candidature;
use App\Models\EtudiantModel;
use App\Models\WishlistModel;

class EtudiantController extends Controleur {

    private EtudiantModel $etudiantModel;
    private WishlistModel $wishlistModel;
    private Candidature $candidature;

    public function __construct($twig){
        parent::__construct($twig);
        $this->etudiantModel = new etudiantModel();
        $this->wishlistModel = new WishlistModel();
        $this->candidature = new Candidature();
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

        $candidatures = [];

        if ($section === 'candidatures') {
            $candidatures = $this->candidature->getCandidaturesByUser($_SESSION['user']['id']);
        }

        $this->render('dashboard/MonCompteEtudiant.html.twig', [
            'section' => $section,
            'menu' => $menu,
            'route' => 'etudiant_dashboard',
            'candidatures' => $candidatures
        ]);
    }

    public function removeFromWishlist(): void
    {
        if (!isset($_SESSION['user'])) {
            $this->redirect('home');
        }

        $offreId = (int) ($_POST['offre_id'] ?? 0);
        $userId = $_SESSION['user']['id'];
        $section = $_POST['section'] ?? 'wishlist';

        if ($offreId) {
            $this->wishlistModel->removeFromWishlist($userId, $offreId);
        }

        header('Location: ?route=etudiant_dashboard&section=' . $section);
        exit;
    }
}
