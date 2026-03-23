<?php

declare(strict_types=1);

namespace App\Controllers;

class InternshipController extends Controller
{
    public function index(): void
    {
        $internships = [
            [
                'id' => 1,
                'title' => 'Developpeur Web',
                'company' => 'Tech Atlantique',
                'location' => 'La Rochelle',
                'salary' => '4,35 EUR / heure',
            ],
            [
                'id' => 2,
                'title' => 'Administrateur Reseau',
                'company' => 'Infra Ouest',
                'location' => 'Nantes',
                'salary' => '4,35 EUR / heure',
            ],
        ];

        $this->render('trouverUnStage.html.twig', [
            'page_title' => 'Offres de stage - MyInternship',
            'internships' => $internships,
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

        $offer = [
            'id' => $id,
            'title' => 'Developpeur Web',
            'company' => 'Tech Atlantique',
            'location' => 'La Rochelle',
            'salary' => '4,35 EUR / heure',
            'description' => 'Participation au developpement et a la maintenance d\'applications web internes.',
            'skills' => ['PHP', 'SQL', 'HTML/CSS', 'JavaScript'],
        ];

        $this->render('offreDeStage.html.twig', [
            'page_title' => 'Detail de l offre - MyInternship',
            'offer' => $offer,
        ]);
    }
}
