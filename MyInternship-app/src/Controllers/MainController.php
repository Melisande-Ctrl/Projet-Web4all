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

        if (!$this->connexionModel->verifyPassword($currentPassword, $utilisateur['Password'])) {
            $_SESSION['password_feedback'] = [
                'type' => 'error',
                'message' => 'Le mot de passe actuel est incorrect.',
            ];
            $this->redirectToPasswordSection();
        }

        if ($newPassword !== $confirmPassword) {
            $_SESSION['password_feedback'] = [
                'type' => 'error',
                'message' => 'Les mots de passe ne correspondent pas.',
            ];
            $this->redirectToPasswordSection();
        }

        $this->connexionModel->updatePassword((int) $_SESSION['user']['id'], $newPassword);

        $_SESSION['password_feedback'] = [
            'type' => 'success',
            'message' => 'Le mot de passe a bien été mis à jour.',
        ];

        $this->redirectToPasswordSection();
    }

    public static function consumePasswordFeedback(): ?array
    {
        $feedback = $_SESSION['password_feedback'] ?? null;
        unset($_SESSION['password_feedback']);

        return is_array($feedback) ? $feedback : null;
    }

    private function redirectToPasswordSection(): void
    {
        $role = (int) ($_SESSION['user']['role'] ?? 0);

        if ($role === 1) {
            $this->redirect('admin_dashboard', ['section' => 'password']);
        }

        if ($role === 2) {
            $this->redirect('pilote_dashboard', ['section' => 'password']);
        }

        $this->redirect('etudiant_dashboard', ['section' => 'password']);
    }
}
