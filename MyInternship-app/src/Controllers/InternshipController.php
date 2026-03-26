<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Internship;

class InternshipController extends Controller
{
    public function __construct($templateEngine)
    {
        $this->templateEngine = $templateEngine;
        $this->model = new Internship();
    }

    public function index(): void
    {
        $filters = [
            'keyword' => trim((string) ($_GET['keyword'] ?? '')),
            'location' => trim((string) ($_GET['location'] ?? '')),
        ];

        $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);
        $page = $page !== false && $page !== null ? max(1, $page) : 1;

        $results = $this->model->searchInternships($filters, $page);
        $pagination = $this->buildPagination(
            $results['current_page'],
            $results['total_pages'],
            $filters
        );

        $this->render('trouverUnStage.html.twig', [
            'page_title' => 'Offres de stage - MyInternship',
            'internships' => $results['items'],
            'filters' => $filters,
            'total_results' => $results['total'],
            'pagination' => $pagination,
        ]);
    }

    public function show(?int $id = null): void
    {
        if ($id === null) {
            http_response_code(404);
            $this->render('offreDeStage.html.twig', [
                'page_title' => 'Offre introuvable - MyInternship',
                'offer' => null,
            ]);
            return;
        }

        $offer = $this->model->getInternshipById($id);

        $this->render('offreDeStage.html.twig', [
            'page_title' => 'Detail de l offre - MyInternship',
            'offer' => $offer,
        ]);
    }

    private function buildPagination(int $currentPage, int $totalPages, array $filters): array
    {
        $links = [];

        for ($page = 1; $page <= $totalPages; $page++) {
            $links[] = [
                'page' => $page,
                'url' => $this->buildInternshipUrl($page, $filters),
                'is_current' => $page === $currentPage,
            ];
        }

        return [
            'current_page' => $currentPage,
            'total_pages' => $totalPages,
            'links' => $links,
            'previous_url' => $currentPage > 1 ? $this->buildInternshipUrl($currentPage - 1, $filters) : null,
            'next_url' => $currentPage < $totalPages ? $this->buildInternshipUrl($currentPage + 1, $filters) : null,
        ];
    }

    private function buildInternshipUrl(int $page, array $filters): string
    {
        $query = [
            'route' => 'internships',
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
