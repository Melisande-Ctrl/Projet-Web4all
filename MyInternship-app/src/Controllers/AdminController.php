<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\AdminModel;

class AdminController extends Controleur {

        private AdminModel $adminModel;

    public function __construct($twig){
        parent::__construct($twig);
        $this->adminModel = new AdminModel();
    }

    public function showDashboard(): void {

        $this->render('MonCompteAdmin.html.twig', [

        ]);

    }
}
