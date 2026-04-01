<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\OffreStageModel;
use App\Models\PiloteModel;

class PiloteController extends Controleur {

    private PiloteModel $piloteModel;
    private OffreStageModel $offreStageModel;


    public function __construct($twig){
        parent::__construct($twig);
        $this->piloteModel = new piloteModel();
        $this->offreStageModel = new OffreStageModel();
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
        $etudiants = null;

        if ($section === 'etudiants' && $nom) {
            $etudiants = $this->piloteModel->getEtudiantParNom($nom);
        }



        $menu = [
            'Infos' => 'Informations',
            'etudiants' => 'Etudiants',
            'offres' => 'Offres de stage',
            'creer_offre' => 'Offres'
        ];

        $this->render('dashboard/MonComptePilote.html.twig', [
            'section' => $section,
            'menu' => $menu,
            'route' => 'pilote_dashboard',
            'etudiants' => $etudiants,
            'entreprisesOffres' => $this->offreStageModel->getEntreprisesPourSelection(),
            'offre' => [
                'title' => '',
                'entreprise_id' => '',
                'description' => '',
                'salary' => '',
                'duration_weeks' => '',
                'address' => '',
                'location' => '',
                'country' => '',
                'skills_text' => '',
            ],
        ]);
    }
}
