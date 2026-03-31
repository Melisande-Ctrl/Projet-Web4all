<?php

declare(strict_types=1);

namespace App\Models;

use PDO;
use Throwable;

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

    public function ajouterOffreDansWishlist(int $compteId, int $offreId): bool
    {
        $query = $this->connection->prepare(
            'INSERT IGNORE INTO Wishlist (Id_Compte, Id_Offre)
             VALUES (:compte_id, :offre_id)'
        );

        $query->bindValue(':compte_id', $compteId, PDO::PARAM_INT);
        $query->bindValue(':offre_id', $offreId, PDO::PARAM_INT);
        $query->execute();

        return $query->rowCount() > 0;
    }

    public function getEntreprisesPourSelection(): array
    {
        $query = $this->connection->query(
            'SELECT Id_Entreprise AS id, Nom_Entreprise AS name
             FROM Entreprises
             ORDER BY Nom_Entreprise'
        );

        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getOffreStageForFormById(int $id): ?array
    {
        $query = $this->connection->prepare(
            'SELECT
                o.Id_Offre AS id,
                o.Titre AS title,
                o.Id_Entreprise AS entreprise_id,
                o.Description AS description,
                o.Base_Remuneration AS salary,
                o.Duree_Semaines AS duration_weeks,
                a.Nom_Adresse AS address,
                v.Nom_Ville AS location,
                p.Nom_Pays AS country,
                GROUP_CONCAT(DISTINCT c.Nom_Competence ORDER BY c.Nom_Competence SEPARATOR ", ") AS skills_text
            FROM Offres_Stages o
            INNER JOIN Adresses a ON a.Id_Adresse = o.Id_Adresse
            INNER JOIN Villes v ON v.Id_Ville = a.Id_Ville
            INNER JOIN Pays p ON p.Id_Pays = v.Id_Pays
            LEFT JOIN Offres_Competences_Liaison ocl ON ocl.Id_Offre = o.Id_Offre
            LEFT JOIN Competences c ON c.Id_Competence = ocl.Id_Competence
            WHERE o.Id_Offre = :id
            GROUP BY
                o.Id_Offre,
                o.Titre,
                o.Id_Entreprise,
                o.Description,
                o.Base_Remuneration,
                o.Duree_Semaines,
                a.Nom_Adresse,
                v.Nom_Ville,
                p.Nom_Pays'
        );
        $query->bindValue(':id', $id, PDO::PARAM_INT);
        $query->execute();

        $offre = $query->fetch(PDO::FETCH_ASSOC);

        return $offre === false ? null : $offre;
    }

    public function createOffreStage(array $data): int|false
    {
        try {
            $this->connection->beginTransaction();

            $adresseId = $this->getOrCreateAdresseId(
                $data['address'],
                $data['location'],
                $data['country']
            );

            $query = $this->connection->prepare(
                'INSERT INTO Offres_Stages (
                    Titre,
                    Id_Adresse,
                    Id_Entreprise,
                    Description,
                    Date_Creation,
                    Base_Remuneration,
                    Duree_Semaines
                ) VALUES (
                    :title,
                    :address_id,
                    :entreprise_id,
                    :description,
                    :created_at,
                    :salary,
                    :duration_weeks
                )'
            );

            $query->bindValue(':title', $data['title'], PDO::PARAM_STR);
            $query->bindValue(':address_id', $adresseId, PDO::PARAM_INT);
            $query->bindValue(':entreprise_id', $data['entreprise_id'], PDO::PARAM_INT);
            $query->bindValue(':description', $data['description'], PDO::PARAM_STR);
            $query->bindValue(':created_at', date('Y-m-d'), PDO::PARAM_STR);
            $query->bindValue(':salary', $data['salary'] !== '' ? $data['salary'] : null, $data['salary'] !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $query->bindValue(':duration_weeks', $data['duration_weeks'], PDO::PARAM_INT);
            $query->execute();

            $offreId = (int) $this->connection->lastInsertId();
            $this->syncSkills($offreId, $data['skills_text'] ?? '');

            $this->connection->commit();

            return $offreId;
        } catch (Throwable) {
            if ($this->connection->inTransaction()) {
                $this->connection->rollBack();
            }

            return false;
        }
    }

    public function updateOffreStage(int $id, array $data): bool
    {
        try {
            $this->connection->beginTransaction();

            $adresseId = $this->getOrCreateAdresseId(
                $data['address'],
                $data['location'],
                $data['country']
            );

            $query = $this->connection->prepare(
                'UPDATE Offres_Stages
                 SET Titre = :title,
                     Id_Adresse = :address_id,
                     Id_Entreprise = :entreprise_id,
                     Description = :description,
                     Base_Remuneration = :salary,
                     Duree_Semaines = :duration_weeks
                 WHERE Id_Offre = :id'
            );

            $query->bindValue(':id', $id, PDO::PARAM_INT);
            $query->bindValue(':title', $data['title'], PDO::PARAM_STR);
            $query->bindValue(':address_id', $adresseId, PDO::PARAM_INT);
            $query->bindValue(':entreprise_id', $data['entreprise_id'], PDO::PARAM_INT);
            $query->bindValue(':description', $data['description'], PDO::PARAM_STR);
            $query->bindValue(':salary', $data['salary'] !== '' ? $data['salary'] : null, $data['salary'] !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $query->bindValue(':duration_weeks', $data['duration_weeks'], PDO::PARAM_INT);
            $query->execute();

            $this->syncSkills($id, $data['skills_text'] ?? '');

            $this->connection->commit();

            return true;
        } catch (Throwable) {
            if ($this->connection->inTransaction()) {
                $this->connection->rollBack();
            }

            return false;
        }
    }

    public function deleteOffreStage(int $id): bool
    {
        try {
            $this->connection->beginTransaction();

            $deletedRows = 0;

            $queries = [
                'DELETE FROM Wishlist WHERE Id_Offre = :id',
                'DELETE FROM Candidatures WHERE Id_Offre = :id',
                'DELETE FROM Offres_Competences_Liaison WHERE Id_Offre = :id',
                'DELETE FROM Offres_Stages WHERE Id_Offre = :id',
            ];

            foreach ($queries as $sql) {
                $query = $this->connection->prepare($sql);
                $query->bindValue(':id', $id, PDO::PARAM_INT);
                $query->execute();

                if (str_contains($sql, 'DELETE FROM Offres_Stages')) {
                    $deletedRows = $query->rowCount();
                }
            }

            $this->connection->commit();

            return $deletedRows > 0;
        } catch (Throwable) {
            if ($this->connection->inTransaction()) {
                $this->connection->rollBack();
            }

            return false;
        }
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

    private function getOrCreateAdresseId(string $address, string $city, string $country): int
    {
        $countryId = $this->getOrCreateCountryId($country);
        $cityId = $this->getOrCreateCityId($city, $countryId);

        $query = $this->connection->prepare(
            'SELECT Id_Adresse
             FROM Adresses
             WHERE Nom_Adresse = :address AND Id_Ville = :city_id'
        );
        $query->bindValue(':address', $address, PDO::PARAM_STR);
        $query->bindValue(':city_id', $cityId, PDO::PARAM_INT);
        $query->execute();

        $adresseId = $query->fetchColumn();
        if ($adresseId !== false) {
            return (int) $adresseId;
        }

        $query = $this->connection->prepare(
            'INSERT INTO Adresses (Nom_Adresse, Id_Ville)
             VALUES (:address, :city_id)'
        );
        $query->bindValue(':address', $address, PDO::PARAM_STR);
        $query->bindValue(':city_id', $cityId, PDO::PARAM_INT);
        $query->execute();

        return (int) $this->connection->lastInsertId();
    }

    private function getOrCreateCountryId(string $country): int
    {
        $query = $this->connection->prepare(
            'SELECT Id_Pays
             FROM Pays
             WHERE Nom_Pays = :country'
        );
        $query->bindValue(':country', $country, PDO::PARAM_STR);
        $query->execute();

        $countryId = $query->fetchColumn();
        if ($countryId !== false) {
            return (int) $countryId;
        }

        $query = $this->connection->prepare(
            'INSERT INTO Pays (Nom_Pays)
             VALUES (:country)'
        );
        $query->bindValue(':country', $country, PDO::PARAM_STR);
        $query->execute();

        return (int) $this->connection->lastInsertId();
    }

    private function getOrCreateCityId(string $city, int $countryId): int
    {
        $query = $this->connection->prepare(
            'SELECT Id_Ville
             FROM Villes
             WHERE Nom_Ville = :city AND Id_Pays = :country_id'
        );
        $query->bindValue(':city', $city, PDO::PARAM_STR);
        $query->bindValue(':country_id', $countryId, PDO::PARAM_INT);
        $query->execute();

        $cityId = $query->fetchColumn();
        if ($cityId !== false) {
            return (int) $cityId;
        }

        $query = $this->connection->prepare(
            'INSERT INTO Villes (Nom_Ville, Id_Pays)
             VALUES (:city, :country_id)'
        );
        $query->bindValue(':city', $city, PDO::PARAM_STR);
        $query->bindValue(':country_id', $countryId, PDO::PARAM_INT);
        $query->execute();

        return (int) $this->connection->lastInsertId();
    }

    private function syncSkills(int $offreId, string $skillsText): void
    {
        $deleteQuery = $this->connection->prepare(
            'DELETE FROM Offres_Competences_Liaison WHERE Id_Offre = :id'
        );
        $deleteQuery->bindValue(':id', $offreId, PDO::PARAM_INT);
        $deleteQuery->execute();

        $skills = $this->normalizeSkillsText($skillsText);
        if ($skills === []) {
            return;
        }

        $insertQuery = $this->connection->prepare(
            'INSERT INTO Offres_Competences_Liaison (Id_Offre, Id_Competence)
             VALUES (:offre_id, :competence_id)'
        );

        foreach ($skills as $skill) {
            $competenceId = $this->getOrCreateCompetenceId($skill);
            $insertQuery->bindValue(':offre_id', $offreId, PDO::PARAM_INT);
            $insertQuery->bindValue(':competence_id', $competenceId, PDO::PARAM_INT);
            $insertQuery->execute();
        }
    }

    private function getOrCreateCompetenceId(string $skill): int
    {
        $query = $this->connection->prepare(
            'SELECT Id_Competence
             FROM Competences
             WHERE Nom_Competence = :skill'
        );
        $query->bindValue(':skill', $skill, PDO::PARAM_STR);
        $query->execute();

        $competenceId = $query->fetchColumn();
        if ($competenceId !== false) {
            return (int) $competenceId;
        }

        $query = $this->connection->prepare(
            'INSERT INTO Competences (Nom_Competence)
             VALUES (:skill)'
        );
        $query->bindValue(':skill', $skill, PDO::PARAM_STR);
        $query->execute();

        return (int) $this->connection->lastInsertId();
    }

    private function normalizeSkillsText(string $skillsText): array
    {
        $skills = array_map('trim', explode(',', $skillsText));
        $skills = array_filter($skills, static fn (string $skill): bool => $skill !== '');

        $uniqueSkills = [];
        foreach ($skills as $skill) {
            $uniqueSkills[mb_strtolower($skill)] = $skill;
        }

        return array_values($uniqueSkills);
    }
}
