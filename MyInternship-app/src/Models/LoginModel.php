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
}
