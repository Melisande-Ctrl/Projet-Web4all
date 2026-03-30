<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

class OffreStage extends Modele
{
    private const HOME_FEATURED_LIMIT = 3;
    private const LIST_PER_PAGE = 6;

    public function getNombreTotalOffresStage(array $filters = []): int
    {
        [$whereSql, $parameters] = $this->buildFilters($filters);

        $query = $this->connection->prepare(
            'SELECT COUNT(DISTINCT o.Id_Offre)
             FROM Offres_Stages o
             INNER JOIN Entreprises e ON e.Id_Entreprise = o.Id_Entreprise
             INNER JOIN Adresses a ON a.Id_Adresse = o.Id_Adresse
             INNER JOIN Villes v ON v.Id_Ville = a.Id_Ville
             LEFT JOIN Offres_Competences_Liaison ocl ON ocl.Id_Offre = o.Id_Offre
             LEFT JOIN Competences c ON c.Id_Competence = ocl.Id_Competence'
            . $whereSql
        );

        foreach ($parameters as $name => $value) {
            $query->bindValue($name, $value, PDO::PARAM_STR);
        }

        $query->execute();

        return (int) $query->fetchColumn();
    }

    public function getOffresMisesEnAvant(int $limit = self::HOME_FEATURED_LIMIT): array
    {
        $query = $this->connection->prepare(
            'SELECT
                o.Id_Offre AS id,
                o.Titre AS title,
                e.Nom_Entreprise AS company,
                v.Nom_Ville AS location,
                o.Base_Remuneration AS salary,
                o.Duree_Semaines AS duration_weeks,
                o.Date_Creation AS created_at
            FROM Offres_Stages o
            INNER JOIN Entreprises e ON e.Id_Entreprise = o.Id_Entreprise
            INNER JOIN Adresses a ON a.Id_Adresse = o.Id_Adresse
            INNER JOIN Villes v ON v.Id_Ville = a.Id_Ville
            ORDER BY o.Date_Creation DESC, o.Id_Offre DESC
            LIMIT :limit'
        );
        $query->bindValue(':limit', $limit, PDO::PARAM_INT);
        $query->execute();

        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function rechercherOffresStage(array $filters, int $page, int $perPage = self::LIST_PER_PAGE): array
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        [$whereSql, $parameters] = $this->buildFilters($filters);

        $total = $this->getNombreTotalOffresStage($filters);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $currentPage = min($page, $totalPages);
        $offset = ($currentPage - 1) * $perPage;

        $query = $this->connection->prepare(
            'SELECT
                o.Id_Offre AS id,
                o.Titre AS title,
                e.Nom_Entreprise AS company,
                v.Nom_Ville AS location,
                o.Description AS description,
                o.Base_Remuneration AS salary,
                o.Duree_Semaines AS duration_weeks,
                o.Date_Creation AS created_at,
                GROUP_CONCAT(DISTINCT c.Nom_Competence ORDER BY c.Nom_Competence SEPARATOR "||") AS skills
            FROM Offres_Stages o
            INNER JOIN Entreprises e ON e.Id_Entreprise = o.Id_Entreprise
            INNER JOIN Adresses a ON a.Id_Adresse = o.Id_Adresse
            INNER JOIN Villes v ON v.Id_Ville = a.Id_Ville
            LEFT JOIN Offres_Competences_Liaison ocl ON ocl.Id_Offre = o.Id_Offre
            LEFT JOIN Competences c ON c.Id_Competence = ocl.Id_Competence'
            . $whereSql .
            ' GROUP BY
                o.Id_Offre,
                o.Titre,
                e.Nom_Entreprise,
                v.Nom_Ville,
                o.Description,
                o.Base_Remuneration,
                o.Duree_Semaines,
                o.Date_Creation
            ORDER BY o.Date_Creation DESC, o.Id_Offre DESC
            LIMIT :limit OFFSET :offset'
        );

        foreach ($parameters as $name => $value) {
            $query->bindValue($name, $value, PDO::PARAM_STR);
        }

        $query->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $query->bindValue(':offset', $offset, PDO::PARAM_INT);
        $query->execute();

        $offresStage = $query->fetchAll(PDO::FETCH_ASSOC);

        foreach ($offresStage as &$offreStage) {
            $offreStage['skills'] = $this->hydrateSkills($offreStage['skills'] ?? null);
        }
        unset($offreStage);

        return [
            'items' => $offresStage,
            'total' => $total,
            'current_page' => $currentPage,
            'per_page' => $perPage,
            'total_pages' => $totalPages,
        ];
    }

    public function getOffreStageById(int $id): ?array
    {
        $query = $this->connection->prepare(
            'SELECT
                o.Id_Offre AS id,
                o.Titre AS title,
                o.Description AS description,
                o.Base_Remuneration AS salary,
                o.Duree_Semaines AS duration_weeks,
                o.Date_Creation AS created_at,
                e.Nom_Entreprise AS company,
                a.Nom_Adresse AS address,
                v.Nom_Ville AS location,
                p.Nom_Pays AS country,
                GROUP_CONCAT(DISTINCT c.Nom_Competence ORDER BY c.Nom_Competence SEPARATOR "||") AS skills
            FROM Offres_Stages o
            INNER JOIN Entreprises e ON e.Id_Entreprise = o.Id_Entreprise
            INNER JOIN Adresses a ON a.Id_Adresse = o.Id_Adresse
            INNER JOIN Villes v ON v.Id_Ville = a.Id_Ville
            INNER JOIN Pays p ON p.Id_Pays = v.Id_Pays
            LEFT JOIN Offres_Competences_Liaison ocl ON ocl.Id_Offre = o.Id_Offre
            LEFT JOIN Competences c ON c.Id_Competence = ocl.Id_Competence
            WHERE o.Id_Offre = :id
            GROUP BY
                o.Id_Offre,
                o.Titre,
                o.Description,
                o.Base_Remuneration,
                o.Duree_Semaines,
                o.Date_Creation,
                e.Nom_Entreprise,
                a.Nom_Adresse,
                v.Nom_Ville,
                p.Nom_Pays'
        );
        $query->bindValue(':id', $id, PDO::PARAM_INT);
        $query->execute();

        $offre = $query->fetch(PDO::FETCH_ASSOC);

        if ($offre === false) {
            return null;
        }

        $offre['skills'] = $this->hydrateSkills($offre['skills'] ?? null);

        return $offre;
    }

    private function buildFilters(array $filters): array
    {
        $conditions = [];
        $parameters = [];

        $keyword = trim((string) ($filters['keyword'] ?? ''));
        if ($keyword !== '') {
            $conditions[] = '(o.Titre LIKE :keyword OR o.Description LIKE :keyword OR e.Nom_Entreprise LIKE :keyword OR c.Nom_Competence LIKE :keyword)';
            $parameters[':keyword'] = '%' . $keyword . '%';
        }

        $location = trim((string) ($filters['location'] ?? ''));
        if ($location !== '') {
            $conditions[] = 'v.Nom_Ville LIKE :location';
            $parameters[':location'] = '%' . $location . '%';
        }

        if ($conditions === []) {
            return ['', []];
        }

        return [' WHERE ' . implode(' AND ', $conditions), $parameters];
    }

    private function hydrateSkills(?string $skills): array
    {
        if ($skills === null || $skills === '') {
            return [];
        }

        return array_values(array_filter(explode('||', $skills)));
    }
}
