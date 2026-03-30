<?php

namespace App\Models;

use PDO;

class WishlistModel extends Modele
{

    public function getWishlistByUser(int $compteId): array
    {
        $sql = "SELECT o.* FROM Wishlist w JOIN Offres o ON w.Id_Offre = o.Id_Offre WHERE w.Id_Compte = :compte_id;";

        $stmt = $this->connection->prepare($sql);

        $stmt->bindValue(':compte_id', $compteId, PDO::PARAM_INT);

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
