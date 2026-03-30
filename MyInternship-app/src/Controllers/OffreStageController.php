<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\OffreStage;
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
