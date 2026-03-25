<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\PiloteModel;

class PiloteController extends Controller {

    private PiloteModel $piloteModel;

    public function __construct($twig){
        parent::__construct($twig);
        $this->piloteModel = new piloteModel();
    }

    public function showDashboard(): void {

        $this->render('MonComptePilote.html.twig', [

        ]);

    }
}