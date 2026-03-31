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

    public function verifyPassword(string $plainPassword, string $storedPassword): bool {
        if (password_verify($plainPassword, $storedPassword)) {
            return true;
        }

        return hash_equals($storedPassword, $plainPassword);
    }

    public function passwordNeedsUpgrade(string $storedPassword): bool {
        $info = password_get_info($storedPassword);

        if (($info['algoName'] ?? 'unknown') === 'unknown') {
            return true;
        }

        return password_needs_rehash($storedPassword, PASSWORD_DEFAULT);
    }

    public function updatePassword(int $id, string $newPassword): void {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        $sql = "UPDATE Comptes SET Password = :newPassword WHERE id_Compte = :id";

        $stmt = $this->connection->prepare($sql);

        $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
        $stmt->bindValue(':newPassword', $hashedPassword, \PDO::PARAM_STR);

        $stmt->execute();
    }
}
