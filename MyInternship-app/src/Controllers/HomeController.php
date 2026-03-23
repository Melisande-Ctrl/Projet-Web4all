<?php

declare(strict_types=1);

namespace App\Controllers;

class HomeController extends Controller
{
    public function index(): void
    {
        $featuredInternships = [
            [
                'id' => 1,
                'title' => 'Developpeur Web',
                'location' => 'La Rochelle',
                'contract' => 'Stage',
                'duration' => '2 mois',
            ],
            [
                'id' => 2,
                'title' => 'Administrateur Reseau',
                'location' => 'Nantes',
                'contract' => 'Stage',
                'duration' => '6 mois',
            ],
            [
                'id' => 3,
                'title' => 'Technicien Support',
                'location' => 'Bordeaux',
                'contract' => 'Stage',
                'duration' => '3 mois',
            ],
        ];

        $this->render('home.html.twig', [
            'page_title' => 'Accueil - MyInternship',
            'featuredInternships' => $featuredInternships,
        ]);
    }

    public function legalNotices(): void
    {
        $this->render('mentions-legales.html.twig', [
            'page_title' => 'Mentions legales - MyInternship',
        ]);
    }
}
