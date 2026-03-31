<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Candidature;
use App\Models\OffreStage;
use RuntimeException;
use Twig\Environment;

class OffreStageController extends Controleur
{
    public function __construct(Environment $templateEngine)
    {
        parent::__construct($templateEngine);
        $this->model = new OffreStage();
    }

    public function index(int $page = 1): void
    {
        $filters = [
            'keyword' => trim((string) ($_GET['keyword'] ?? '')),
            'location' => trim((string) ($_GET['location'] ?? '')),
        ];

        $resultats = $this->model->rechercherOffresStage($filters, $page);
        $pagination = $this->buildPagination(
            $resultats['current_page'],
            $resultats['total_pages'],
            $filters
        );

        $this->render('trouverUnStage.html.twig', [
            'page_title' => 'Offres de stage - MyInternship',
            'offres_stage' => $resultats['items'],
            'filters' => $filters,
            'total_results' => $resultats['total'],
            'pagination' => $pagination,
            'current_list_url' => $this->buildOffreStageUrl($resultats['current_page'], $filters),
            'wishlist_feedback' => $this->consumeWishlistFeedback(),
        ]);
    }

    public function show(?int $id = null): void
    {
        if ($id === null) {
            http_response_code(404);
            $this->render('offreDeStage.html.twig', [
                'page_title' => 'Offre introuvable - MyInternship',
                'offre' => null,
            ]);
            return;
        }

        $offre = $this->model->getOffreStageById($id);

        $this->render('offreDeStage.html.twig', [
            'page_title' => 'Détail de l’offre - MyInternship',
            'offre' => $offre,
            'wishlist_feedback' => $this->consumeWishlistFeedback(),
            'candidature_feedback' => $this->consumeCandidatureFeedback(),
        ]);
    }

    public function showCandidatureForm(?int $id = null): void
    {
        if (!isset($_SESSION['user']) || (int) ($_SESSION['user']['role'] ?? 0) !== 3) {
            $_SESSION['candidature_feedback'] = [
                'type' => 'error',
                'message' => 'Tu dois être connecté comme étudiant pour postuler à une offre.',
            ];
            $this->redirect('connexion');
        }

        if ($id === null) {
            http_response_code(404);
            $this->render('candidatureOffre.html.twig', [
                'page_title' => 'Candidature introuvable - MyInternship',
                'offre' => null,
            ]);
            return;
        }

        $offre = $this->model->getOffreStageById($id);

        if ($offre === null) {
            http_response_code(404);
        }

        $this->render('candidatureOffre.html.twig', [
            'page_title' => 'Postuler à une offre - MyInternship',
            'offre' => $offre,
            'candidature_feedback' => $this->consumeCandidatureFeedback(),
        ]);
    }

    public function ajouterWishlist(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('offres_stage');
        }

        if (!isset($_SESSION['user']) || (int) ($_SESSION['user']['role'] ?? 0) !== 3) {
            $_SESSION['wishlist_feedback'] = [
                'type' => 'error',
                'message' => 'Tu dois être connecté comme étudiant pour ajouter une offre à ta wishlist.',
            ];
            $this->redirectToReturnUrl();
        }

        $offreId = filter_input(INPUT_POST, 'offre_id', FILTER_VALIDATE_INT);
        if ($offreId === false || $offreId === null || $offreId < 1) {
            $_SESSION['wishlist_feedback'] = [
                'type' => 'error',
                'message' => 'Impossible d’ajouter cette offre à la wishlist.',
            ];
            $this->redirectToReturnUrl();
        }

        $compteId = (int) $_SESSION['user']['id'];
        $offreAjoutee = $this->model->ajouterOffreDansWishlist($compteId, (int) $offreId);

        $_SESSION['wishlist_feedback'] = [
            'type' => $offreAjoutee ? 'success' : 'info',
            'message' => $offreAjoutee
                ? 'Offre ajoutée à la wishlist.'
                : 'Cette offre est déjà dans la wishlist.',
        ];

        $this->redirectToReturnUrl();
    }

    public function ajouterCandidature(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('offres_stage');
        }

        if (!isset($_SESSION['user']) || (int) ($_SESSION['user']['role'] ?? 0) !== 3) {
            $_SESSION['candidature_feedback'] = [
                'type' => 'error',
                'message' => 'Tu dois être connecté comme étudiant pour postuler à une offre.',
            ];
            $this->redirectToReturnUrl();
        }

        $offreId = filter_input(INPUT_POST, 'offre_id', FILTER_VALIDATE_INT);
        if ($offreId === false || $offreId === null || $offreId < 1) {
            $_SESSION['candidature_feedback'] = [
                'type' => 'error',
                'message' => 'Impossible d’enregistrer cette candidature.',
            ];
            $this->redirectToReturnUrl();
        }

        $candidatureModel = new Candidature();
        $compteId = (int) $_SESSION['user']['id'];

        if ($candidatureModel->etudiantAPostule($compteId, (int) $offreId)) {
            $_SESSION['candidature_feedback'] = [
                'type' => 'info',
                'message' => 'Tu as déjà postulé à cette offre.',
            ];
            $this->redirectToReturnUrl();
        }

        try {
            $cvPath = $this->uploadCandidatureFile('cv', 'cv');
            $lmPath = $this->uploadCandidatureFile('lettre_motivation', 'lm');
            $candidatureModel->enregistrerCandidature($compteId, (int) $offreId, $cvPath, $lmPath);
        } catch (RuntimeException $e) {
            $_SESSION['candidature_feedback'] = [
                'type' => 'error',
                'message' => $e->getMessage(),
            ];
            $this->redirectToReturnUrl();
        }

        $_SESSION['candidature_feedback'] = [
            'type' => 'success',
            'message' => 'Ta candidature a bien été envoyée.',
        ];

        $this->redirectToReturnUrl();
    }

    private function buildPagination(int $currentPage, int $totalPages, array $filters): array
    {
        $links = [];

        for ($page = 1; $page <= $totalPages; $page++) {
            $links[] = [
                'page' => $page,
                'url' => $this->buildOffreStageUrl($page, $filters),
                'is_current' => $page === $currentPage,
            ];
        }

        return [
            'current_page' => $currentPage,
            'total_pages' => $totalPages,
            'links' => $links,
            'previous_url' => $currentPage > 1 ? $this->buildOffreStageUrl($currentPage - 1, $filters) : null,
            'next_url' => $currentPage < $totalPages ? $this->buildOffreStageUrl($currentPage + 1, $filters) : null,
        ];
    }

    private function buildOffreStageUrl(int $page, array $filters): string
    {
        $query = [
            'route' => 'offres_stage',
            'page' => $page,
        ];

        if ($filters['keyword'] !== '') {
            $query['keyword'] = $filters['keyword'];
        }

        if ($filters['location'] !== '') {
            $query['location'] = $filters['location'];
        }

        return '?' . http_build_query($query);
    }

    private function consumeWishlistFeedback(): ?array
    {
        $feedback = $_SESSION['wishlist_feedback'] ?? null;
        unset($_SESSION['wishlist_feedback']);

        return is_array($feedback) ? $feedback : null;
    }

    private function consumeCandidatureFeedback(): ?array
    {
        $feedback = $_SESSION['candidature_feedback'] ?? null;
        unset($_SESSION['candidature_feedback']);

        return is_array($feedback) ? $feedback : null;
    }

    private function uploadCandidatureFile(string $fieldName, string $prefix): string
    {

        if (!isset($_FILES[$fieldName]) || !is_array($_FILES[$fieldName])) {
            throw new RuntimeException('Les fichiers demandés sont obligatoires.');
        }

        $file = $_FILES[$fieldName];

        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Le téléversement des fichiers a échoué.');
        }

        $originalName = (string) ($file['name'] ?? '');
        $extension = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION));
        if ($extension !== 'pdf') {
            throw new RuntimeException('Seuls les fichiers PDF sont autorisés pour les candidatures.');
        }

        $uploadDirectory = __DIR__ . '/../../../public/uploads/candidatures';
        if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0775, true) && !is_dir($uploadDirectory)) {
            throw new RuntimeException('Impossible de préparer le dossier des candidatures.');
        }



        $filename = sprintf(
            '%s_%d_%s.pdf',
            $prefix,
            (int) ($_SESSION['user']['id'] ?? 0),
            bin2hex(random_bytes(8))
        );


        $destination = $uploadDirectory . '/' . $filename;
        if (!move_uploaded_file((string) $file['tmp_name'], $destination)) {
            throw new RuntimeException('Impossible d’enregistrer les fichiers envoyés.');
        }

        return 'uploads/candidatures/' . $filename;
    }

    private function redirectToReturnUrl(): never
    {
        $returnUrl = $_POST['return_url'] ?? '?route=offres_stage';

        if (!is_string($returnUrl) || $returnUrl === '' || $returnUrl[0] !== '?') {
            $returnUrl = '?route=offres_stage';
        }

        header('Location: ' . $returnUrl);
        exit;
    }
}
