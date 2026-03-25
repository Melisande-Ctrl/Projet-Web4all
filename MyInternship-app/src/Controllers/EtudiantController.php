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

    public function showDashboard(): void {

        $this->render('MonCompteEtudiant.html.twig', [

        ]);

    }
}