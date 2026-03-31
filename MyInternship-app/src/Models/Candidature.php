<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

class Candidature extends Modele
{
    public function etudiantAPostule(int $compteId, int $offreId): bool
    {
        $query = $this->connection->prepare(
            'SELECT 1
             FROM Candidatures
             WHERE Id_Compte = :compte_id AND Id_Offre = :offre_id
             LIMIT 1'
        );

        $query->bindValue(':compte_id', $compteId, PDO::PARAM_INT);
        $query->bindValue(':offre_id', $offreId, PDO::PARAM_INT);
        $query->execute();

        return $query->fetchColumn() !== false;
    }

    public function enregistrerCandidature(int $compteId, int $offreId, string $cvPath, string $lmPath): void
    {
        $query = $this->connection->prepare(
            'INSERT INTO Candidatures (Id_Compte, Id_Offre, Date_Candidature, CV, LM)
             VALUES (:compte_id, :offre_id, :date_candidature, :cv, :lm)'
        );

        $query->bindValue(':compte_id', $compteId, PDO::PARAM_INT);
        $query->bindValue(':offre_id', $offreId, PDO::PARAM_INT);
        $query->bindValue(':date_candidature', date('Y-m-d'), PDO::PARAM_STR);
        $query->bindValue(':cv', $cvPath, PDO::PARAM_STR);
        $query->bindValue(':lm', $lmPath, PDO::PARAM_STR);
        $query->execute();
    }

    public function getCandidaturesByUser(int $compteId): array
    {
        $sql = "
        SELECT 
            c.*,
            o.Titre,
            o.Description,
            o.Duree_Semaines
        FROM Candidatures c
        JOIN Offres_Stages o ON c.Id_Offre = o.Id_Offre
        WHERE c.Id_Compte = :compte_id
        ORDER BY c.Date_Candidature DESC
    ";

        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue(':compte_id', $compteId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


}
