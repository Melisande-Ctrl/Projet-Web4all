<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\CandidatureModel;
use App\Models\EtudiantModel;
use App\Models\WishlistModel;

class EtudiantController extends Controleur {

    private EtudiantModel $etudiantModel;
    private WishlistModel $wishlistModel;
    private CandidatureModel $candidature;

    public function __construct($twig){
        parent::__construct($twig);
        $this->etudiantModel = new etudiantModel();
        $this->wishlistModel = new WishlistModel();
        $this->candidature = new CandidatureModel();
    }

    public function showDashboard(): void
    {
        if (!isset($_SESSION['user'])) {
            $this->redirect('accueil');
        }

        if ($_SESSION['user']['role'] !== 3) {
            $this->redirect('accueil');
        }

        $section = $_GET['section'] ?? 'infos';

        $menu = [
            'infos' => 'Informations',
            'candidatures' => 'Mes Candidatures',
            'wishlist' => 'Ma Wishlist'
        ];

        $candidatures = [];
        $wishlist = [];


        if ($section === 'candidatures') {
            $candidatures = $this->candidature->getCandidaturesByUser($_SESSION['user']['id']);
        }

        if ($section === 'wishlist') {
            $wishlist = $this->wishlistModel->getWishlistByUser($_SESSION['user']['id']);
        }

        $this->render('dashboard/MonCompteEtudiant.html.twig', [
            'section' => $section,
            'menu' => $menu,
            'route' => 'etudiant_dashboard',
            'candidatures' => $candidatures,
            'wishlist' => $wishlist,
            'password_feedback' => MainController::consumePasswordFeedback()
        ]);
    }

    public function removeFromWishlist(): void
    {
        if (!isset($_SESSION['user'])) {
            $this->redirect('accueil');
        }

        $offreId = (int) ($_POST['offre_id'] ?? 0);
        $userId = $_SESSION['user']['id'];
        $section = (string) ($_POST['section'] ?? 'wishlist');
        $allowedSections = ['infos', 'candidatures', 'wishlist', 'password'];

        if (!in_array($section, $allowedSections, true)) {
            $section = 'wishlist';
        }

        if ($offreId) {
            $this->wishlistModel->removeFromWishlist($userId, $offreId);
        }

        $this->redirect('etudiant_dashboard', ['section' => $section]);
    }

    public function searchEtudiant() : void {

        $nom = $_GET['nom'] ?? null;
        $etudiants = null;

        if ($nom) {
            $etudiants = $this->etudiantModel->getEtudiantParNom($nom);
        }

        $this->render('dashboard/MonComptePilote.html.twig', [
            'etudiants' => $etudiants
        ]);

    }
}
