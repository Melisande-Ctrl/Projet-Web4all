<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\AdminModel;

class AdminController extends Controller {

        private AdminModel $adminModel;

    public function __construct($twig){
        parent::__construct($twig);
        $this->adminModel = new AdminModel();
    }

    public function showDashboard(): void
    {
        if (!isset($_SESSION['user'])) {
            $this->redirect('home');
        }

        if ($_SESSION['user']['role'] !== 1) {
            $this->redirect('home');
        }

        $this->render('MonCompteAdmin.html.twig', []);
    }
}