<?php

declare(strict_types=1);

namespace App\Controllers;

class CompanyController extends Controller
{
    public function index(): void
    {
        $companies = [
            [
                'id' => 1,
                'name' => 'Tech Atlantique',
                'sector' => 'Developpement web',
                'rating' => '4,5/5',
            ],
            [
                'id' => 2,
                'name' => 'Infra Ouest',
                'sector' => 'Systemes et reseaux',
                'rating' => '4,2/5',
            ],
        ];

        $this->render('entreprises.html.twig', [
            'page_title' => 'Entreprises - MyInternship',
            'companies' => $companies,
        ]);
    }

    public function show(?int $id = null): void
    {
        if ($id === null) {
            http_response_code(404);
            $this->render('ficheEntreprise.html.twig', [
                'page_title' => 'Entreprise introuvable - MyInternship',
                'company' => null,
            ]);
            return;
        }

        $company = [
            'id' => $id,
            'name' => 'Tech Atlantique',
            'description' => 'Entreprise specialisee dans le developpement d applications web et mobiles.',
            'contact_email' => 'contact@techatlantique.fr',
            'contact_phone' => '05 46 00 00 00',
            'rating' => '4,5/5',
            'offers' => [
                ['id' => 1, 'title' => 'Developpeur Web'],
                ['id' => 4, 'title' => 'Assistant QA'],
            ],
        ];

        $this->render('ficheEntreprise.html.twig', [
            'page_title' => 'Fiche entreprise - MyInternship',
            'company' => $company,
        ]);
    }
}
