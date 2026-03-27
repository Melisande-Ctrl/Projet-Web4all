<?php

namespace App\Models;

class LoginModel extends Model {
    public function getUserByEmail(string $email): ?array {
        $sql = "SELECT * FROM Comptes WHERE Email = :email";

        $stmt = $this->connection->prepare($sql);

        $stmt->bindValue(':email', $email, \PDO::PARAM_STR);

        $stmt->execute();

        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    public function updatePassword(int $id, string $newPassword): void {

        $sql = "UPDATE Comptes SET Password = :newPassword WHERE id_Compte = :id";

        $stmt = $this->connection->prepare($sql);

        $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
        $stmt->bindValue(':newPassword', $newPassword, \PDO::PARAM_STR);

        $stmt->execute();
    }
}
