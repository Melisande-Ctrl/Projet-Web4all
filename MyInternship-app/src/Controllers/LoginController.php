<?php

declare(strict_types=1);

namespace App\Controllers;

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
        $_SESSION['auth_error'] = 'Authentification non implementee pour le moment.';
        $this->redirect('connexion');
    }

    public function logout(): void
    {
        unset($_SESSION['user']);
        $this->redirect('home');
    }
}
