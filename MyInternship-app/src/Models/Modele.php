<?php

declare(strict_types=1);

namespace App\Models;

use PDO;
use PDOException;
use RuntimeException;

class Modele
{
    protected PDO $connection;

    public function __construct()
    {
        $envPath = __DIR__ . '/../../../.env';
        $env = parse_ini_file($envPath);

        if ($env === false) {
            throw new RuntimeException('Impossible de lire le fichier .env.');
        }

        try {
            $this->connection = new PDO(
                'mysql:host=' . $env['SERVERNAME'] . ';dbname=' . $env['DBNAME'] . ';charset=utf8',
                $env['USERNAME'],
                $env['PASSWORD']
            );
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            throw new RuntimeException(
                'Connexion a la base de donnees impossible : ' . $e->getMessage(),
                previous: $e
            );
        }
    }
}
