<?php

namespace App\Controllers;

class MainController extends Controller
{
    public function redirectToDashboard(): void
    {
        if (!isset($_SESSION['user'])) {
            $this->redirect('home');
        }

        $role = $_SESSION['user']['role'];

        if ($role === 1) {
            $this->redirect('admin_dashboard');
        } elseif ($role === 2) {
            $this->redirect('pilote_dashboard');
        } else {
            $this->redirect('etudiant_dashboard');
        }
    }
}
