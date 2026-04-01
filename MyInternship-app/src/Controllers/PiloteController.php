<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\CandidatureModel;
use App\Models\PiloteModel;

class PiloteController extends Controleur {

    private PiloteModel $piloteModel;
    private CandidatureModel $candidatureModel;


    public function __construct($twig){
        parent::__construct($twig);
        $this->piloteModel = new piloteModel();
        $this->candidatureModel = new CandidatureModel();
    }

    public function showDashboard(): void
    {
        if (!isset($_SESSION['user'])) {
            $this->redirect('home');
        }

        if ($_SESSION['user']['role'] !== 2) {
            $this->redirect('home');
        }

        $section = $_GET['section'] ?? 'Infos';
        $nom = $_GET['nom'] ?? null;
        $piloteId = $_SESSION['user']['id'];
        $etudiants = null;
        $id = (int) ($_GET['id'] ?? 0);


        $etudiantDetail = null;
        $candidatures = null;



        if ($section === 'detail_etudiant' && $id > 0) {
            $etudiantDetail = $this->piloteModel->getEtudiantById($id);
            $candidatures = $this->candidatureModel->getCandidaturesByUser($id);
        }

        if ($section === 'etudiants' && $nom) {
            $etudiants = $this->piloteModel->getEtudiantParNom($nom);
        }



        $menu = [
            'Infos' => 'Informations',
            'etudiants' => 'Etudiants',
            'offres' => 'Offres de stage'
        ];

        $this->render('dashboard/MonComptePilote.html.twig', [
            'section' => $section,
            'menu' => $menu,
            'route' => 'pilote_dashboard',
            'etudiants' => $etudiants,
            'etudiantDetail' => $etudiantDetail,
            'candidatures' => $candidatures,
            'user' => $_SESSION['user']
        ]);
    }
}
