<?php

namespace App\Models;

class PiloteModel extends Modele {

    public function getEtudiantParNom(string $nom): ?array {
        $sql = "SELECT * FROM Comptes WHERE Nom LIKE :nom AND ID_Status = 3";

        $stmt = $this->connection->prepare($sql);

        $stmt->bindValue(':nom', '%' . $nom . '%', \PDO::PARAM_STR);

        $stmt->execute();

        $etudiants = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return $etudiants ?: null;
    }

}
