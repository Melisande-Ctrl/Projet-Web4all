<?php

namespace App\Controllers;

use App\Models\ConnexionModel;

class MainController extends Controleur
{
    private ConnexionModel $connexionModel;

    public function __construct($twig)
    {
        parent::__construct($twig);
        $this->connexionModel = new ConnexionModel();
    }

    public function redirectToDashboard(): void
    {
        if (!isset($_SESSION['user'])) {
            $this->redirect('accueil');
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

    public function changePassword(): void
    {
        if (!isset($_SESSION['user'])) {
            $this->redirect('accueil');
        }

        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        $utilisateur = $this->connexionModel->getUtilisateurParEmail($_SESSION['user']['email']);

        if ($utilisateur['Password'] !== $currentPassword) {
            die("Mot de passe incorrect");
        }

        if ($newPassword !== $confirmPassword) {
            die("Les mots de passe ne correspondent pas");
        }

        $this->connexionModel->updatePassword((int) $_SESSION['user']['id'], $newPassword);

        $this->redirectToDashboard();
    }
}
