<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\PiloteModel;

class PiloteController extends Controleur {

    private PiloteModel $piloteModel;


    public function __construct($twig){
        parent::__construct($twig);
        $this->piloteModel = new piloteModel();
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
            'etudiants' => $etudiants
        ]);
    }
}
