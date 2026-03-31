<?php

namespace App\Models;

use PDO;

class WishlistModel extends Modele
{

    /*public function getWishlistByUser(int $compteId): array
    {
        $sql = "SELECT o.* FROM Wishlist w JOIN Offres_Stages o ON w.Id_Offre = o.Id_Offre WHERE w.Id_Compte = :compte_id;";

        $stmt = $this->connection->prepare($sql);

        $stmt->bindValue(':compte_id', $compteId, PDO::PARAM_INT);

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
*/
    public function getWishlistByUser(int $compteId): array
    {
        $sql = "
        SELECT 
            o.*,
            c.Nom_Competence AS competence
        FROM Wishlist w
        JOIN Offres_Stages o ON w.Id_Offre = o.Id_Offre
        LEFT JOIN Offres_Competences_Liaison l ON o.Id_Offre = l.Id_Offre
        LEFT JOIN Competences c ON l.Id_Competence = c.Id_Competence
        WHERE w.Id_Compte = :compte_id
    ";

        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue(':compte_id', $compteId, PDO::PARAM_INT);
        $stmt->execute();

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $wishlist = [];

        foreach ($results as $row) {
            $id = $row['Id_Offre'];

            if (!isset($wishlist[$id])) {
                $wishlist[$id] = $row;
                $wishlist[$id]['competences'] = [];
            }

            if ($row['competence']) {
                $wishlist[$id]['competences'][] = $row['competence'];
            }
        }

        return array_values($wishlist);
    }


}
