<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\EtudiantModel;

class EtudiantController extends Controller {

    private EtudiantModel $etudiantModel;

    public function __construct($twig){
        parent::__construct($twig);
        $this->etudiantModel = new etudiantModel();
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

        $this->render('dashboard/MonCompteEtudiant.html.twig', [
            'section' => $section,
            'menu' => $menu,
            'route' => 'etudiant_dashboard'
        ]);
    }
}