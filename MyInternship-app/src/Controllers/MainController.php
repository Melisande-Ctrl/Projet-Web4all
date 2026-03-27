<?php

namespace App\Controllers;
use App\Models\LoginModel;

class MainController extends Controller
{

    private LoginModel $loginModel;

    public function __construct($twig)
    {
        parent::__construct($twig);
        $this->loginModel = new LoginModel();
    }

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

    public function changePassword(): void
    {
        if (!isset($_SESSION['user'])) {
            $this->redirect('home');
        }

        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';


        $user = $this->loginModel->getUserByEmail($_SESSION['user']['email']);

        if ($user['Password'] !== $currentPassword) {
            die("Mot de passe incorrect");
        }

        if ($newPassword !== $confirmPassword) {
            die("Les mots de passe ne correspondent pas");
        }



        $this->loginModel->updatePassword($_SESSION['user']['id'], $newPassword);


        $this->redirectToDashboard();
    }
}
