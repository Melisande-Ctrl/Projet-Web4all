<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\AdminModel;
use App\Models\OffreStageModel;

class AdminController extends Controleur {

        private AdminModel $adminModel;
        private OffreStageModel $offreStageModel;

    public function __construct($twig){
        parent::__construct($twig);
        $this->adminModel = new AdminModel();
        $this->offreStageModel = new OffreStageModel();
    }

    public function showDashboard(): void
    {
        if (!isset($_SESSION['user'])) {
            $this->redirect('home');
        }

        if ($_SESSION['user']['role'] !== 1) {
            $this->redirect('home');
        }

        $section = $_GET['section'] ?? 'infos';
        $nom = $_GET['nom'] ?? null;
        $etudiants = null;

        if ($section === 'etudiants' && $nom) {
            $etudiants = $this->adminModel->getEtudiantParNom($nom);
        }

        $menu = [
            'infos' => 'Infos',
            'entreprises' => 'Entreprises',
            'etudiants' => 'Etudiants',
            'offres' => 'Offres'
        ];

        $this->render('dashboard/MonCompteAdmin.html.twig', [
            'section' => $section,
            'menu' => $menu,
            'route' => 'admin_dashboard',
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
