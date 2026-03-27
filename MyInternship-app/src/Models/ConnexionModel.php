<?php

namespace App\Models;

class ConnexionModel extends Modele {
    public function getUtilisateurParEmail(string $email): ?array {
        $sql = "SELECT * FROM Comptes WHERE Email = :email";

        $stmt = $this->connection->prepare($sql);

        $stmt->bindValue(':email', $email, \PDO::PARAM_STR);

        $stmt->execute();

        $utilisateur = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $utilisateur ?: null;
    }
}
