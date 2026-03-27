<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\OffreStage;

class AccueilController extends Controleur
{
    public function index(): void
    {
        $modeleOffreStage = new OffreStage();
        $offresMisesEnAvant = $modeleOffreStage->getOffresMisesEnAvant();
        $totalOffresStage = $modeleOffreStage->getNombreTotalOffresStage();

        $siteStats = [
            ['value' => (string) $totalOffresStage, 'label' => 'offres actives'],
            ['value' => '46', 'label' => 'entreprises partenaires'],
            ['value' => '312', 'label' => 'candidatures suivies'],
            ['value' => '3', 'label' => 'roles securises'],
        ];

        $popularSearches = [
            'Developpement web',
            'Systemes et reseaux',
            'Cybersecurite',
            'Data',
        ];

        $benefits = [
            [
                'title' => 'Suivez chaque candidature',
                'description' => 'Les etudiants visualisent les offres deja ciblees, la wish-list et l historique des candidatures.',
            ],
            [
                'title' => 'Des entreprises plus lisibles',
                'description' => 'Les fiches entreprise centralisent les contacts, les offres et les retours d experience utiles.',
            ],
            [
                'title' => 'Un suivi pour les pilotes',
                'description' => 'Les pilotes disposent d une vision claire sur l avancement des recherches de stage de leur promotion.',
            ],
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

        $this->render('accueil.html.twig', [
            'page_title' => 'Accueil - MyInternship',
            'siteStats' => $siteStats,
            'popularSearches' => $popularSearches,
            'benefits' => $benefits,
            'profiles' => $profiles,
            'offresMisesEnAvant' => $offresMisesEnAvant,
            'featuredCompanies' => $featuredCompanies,
            'processSteps' => $processSteps,
        ]);
    }

    public function mentionsLegales(): void
    {
        $this->render('mentions-legales.html.twig', [
            'page_title' => 'Mentions legales - MyInternship',
        ]);
    }
}
