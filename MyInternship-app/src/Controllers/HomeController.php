<?php

declare(strict_types=1);

namespace App\Controllers;

class HomeController extends Controller
{
    public function index(): void
    {
        $siteStats = [
            ['value' => '128', 'label' => 'offres actives'],
            ['value' => '46', 'label' => 'entreprises partenaires'],
            ['value' => '312', 'label' => 'candidatures suivies'],
            ['value' => '3', 'label' => 'roles securises'],
        ];

        $profiles = [
            [
                'title' => 'Etudiant',
                'description' => 'Recherche une offre, gere sa wish-list et suit ses candidatures depuis un espace personnel.',
                'cta_route' => 'connexion',
                'cta_label' => 'Acceder au compte etudiant',
            ],
            [
                'title' => 'Pilote',
                'description' => 'Consulte l avancement des etudiants, les candidatures envoyees et les entreprises sollicitees.',
                'cta_route' => 'connexion',
                'cta_label' => 'Acceder au compte pilote',
            ],
            [
                'title' => 'Administrateur',
                'description' => 'Administre les comptes, les offres, les entreprises et les permissions de la plateforme.',
                'cta_route' => 'connexion',
                'cta_label' => 'Acceder a l administration',
            ],
        ];

        $featuredInternships = [
            [
                'id' => 1,
                'title' => 'Developpeur Web',
                'company' => 'Tech Atlantique',
                'location' => 'La Rochelle',
                'contract' => 'Stage',
                'duration' => '2 mois',
                'salary' => '4,35 EUR / heure',
            ],
            [
                'id' => 2,
                'title' => 'Administrateur Reseau',
                'company' => 'Infra Ouest',
                'location' => 'Nantes',
                'contract' => 'Stage',
                'duration' => '6 mois',
                'salary' => '4,35 EUR / heure',
            ],
            [
                'id' => 3,
                'title' => 'Technicien Support',
                'company' => 'HelpDesk 17',
                'location' => 'Bordeaux',
                'contract' => 'Stage',
                'duration' => '3 mois',
                'salary' => '4,35 EUR / heure',
            ],
        ];

        $featuredCompanies = [
            [
                'id' => 1,
                'name' => 'Tech Atlantique',
                'sector' => 'Developpement web',
                'rating' => '4,5/5',
                'summary' => 'Entreprise locale qui accueille regulierement des profils developpement et QA.',
            ],
            [
                'id' => 2,
                'name' => 'Infra Ouest',
                'sector' => 'Systemes et reseaux',
                'rating' => '4,2/5',
                'summary' => 'Structure orientee infrastructures, administration reseau et support de proximite.',
            ],
        ];

        $processSteps = [
            'Consulter les offres et filtrer selon son profil.',
            'Ajouter les opportunites interessantes en wish-list.',
            'Envoyer une candidature avec CV et lettre de motivation.',
            'Suivre l avancement depuis son espace personnel.',
        ];

        $this->render('home.html.twig', [
            'page_title' => 'Accueil - MyInternship',
            'siteStats' => $siteStats,
            'profiles' => $profiles,
            'featuredInternships' => $featuredInternships,
            'featuredCompanies' => $featuredCompanies,
            'processSteps' => $processSteps,
        ]);
    }

    public function legalNotices(): void
    {
        $this->render('mentions-legales.html.twig', [
            'page_title' => 'Mentions legales - MyInternship',
        ]);
    }
}
