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
            'page_title' => 'Detail de l offre - MyInternship',
            'offre' => $offre,
        ]);
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
}
