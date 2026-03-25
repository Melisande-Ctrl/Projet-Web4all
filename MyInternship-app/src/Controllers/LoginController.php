<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\LoginModel;
use RuntimeException;

class LoginController extends Controller
{
    public function showLoginForm(): void
    {
        $this->render('connexion.html.twig', [
            'page_title' => 'Connexion - MyInternship',
            'error' => $_SESSION['auth_error'] ?? null,
        ]);

        unset($_SESSION['auth_error']);
    }

    public function login(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('connexion');
        }

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';


        if (empty($email) || empty($password)) {
            $_SESSION['auth_error'] = 'Tous les champs sont obligatoires';
            $this->redirect('connexion');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['auth_error'] = 'Email invalide';
            $this->redirect('connexion');
        }

        try {
            $loginModel = new LoginModel();
            $user = $loginModel->getUserByEmail($email);
        } catch (RuntimeException $e) {
            $_SESSION['auth_error'] = 'La connexion au service d authentification est temporairement indisponible.';
            $this->redirect('connexion');
        }

        if (!$user || !password_verify($password, $user['Password'])) {
            $_SESSION['auth_error'] = 'Identifiants incorrects';
            $this->redirect('connexion');
        }


        session_regenerate_id(true);

        $_SESSION['user'] = [
            'id' => $user['Id_Compte'],
            'nom' => $user['Nom'],
            'prenom' => $user['Prenom'],
            'email' => $user['Email'],
            'role' => $user['Id_Status']
        ];

        switch ($user['Id_Status']) {
            case 1:
                $this->redirect('admin_dashboard');
            case 2:
                $this->redirect('pilote_dashboard');
            case 3:
                $this->redirect('etudiant_dashboard');
            default:
                $this->redirect('connexion');
        }
    }

    public function logout(): void
    {
        session_destroy();
        $this->redirect('home');
    }
}
