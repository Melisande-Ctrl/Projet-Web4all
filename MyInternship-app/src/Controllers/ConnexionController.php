<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\ConnexionModel;
use RuntimeException;

class ConnexionController extends Controleur
{
    public function afficherFormulaireConnexion(): void
    {
        $this->render('connexion.html.twig', [
            'page_title' => 'Connexion - MyInternship',
            'error' => $_SESSION['auth_error'] ?? null,
        ]);

        unset($_SESSION['auth_error']);
    }

    public function connecter(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('connexion');
        }

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($email === '' || $password === '') {
            $_SESSION['auth_error'] = 'Tous les champs sont obligatoires';
            $this->redirect('connexion');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['auth_error'] = 'Email invalide';
            $this->redirect('connexion');
        }

        try {
            $modeleConnexion = new ConnexionModel();
            $utilisateur = $modeleConnexion->getUtilisateurParEmail($email);
        } catch (RuntimeException $e) {
            $_SESSION['auth_error'] = 'La connexion au service d’authentification est temporairement indisponible.';
            $this->redirect('connexion');
        }

        if (!$utilisateur || !$modeleConnexion->verifyPassword($password, $utilisateur['Password'])) {
            $_SESSION['auth_error'] = 'Identifiants incorrects';
            $this->redirect('connexion');
        }

        if ($modeleConnexion->passwordNeedsUpgrade($utilisateur['Password'])) {
            $modeleConnexion->updatePassword((int) $utilisateur['Id_Compte'], $password);
        }

        session_regenerate_id(true);

        $_SESSION['user'] = [
            'id' => $utilisateur['Id_Compte'],
            'nom' => $utilisateur['Nom'],
            'prenom' => $utilisateur['Prenom'],
            'email' => $utilisateur['Email'],
            'role' => $utilisateur['Id_Status'],
        ];

        switch ($utilisateur['Id_Status']) {
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

    public function deconnecter(): void
    {
        $_SESSION = [];
        session_destroy();
        $this->redirect('accueil');
    }
}
