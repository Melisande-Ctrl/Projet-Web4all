<?php
namespace App\Models;

use PDO;

class EntrepriseModel extends Model {

    /**
     * Récupère le nombre total d'entreprises
     */
    public function getNbEntreprises() : int
    {
        $query = $this->connection->query("SELECT COUNT(Id_Entreprise) FROM Entreprises");
        $nb = $query->fetch(PDO::FETCH_ASSOC);
        return $nb['COUNT(Id_Entreprise)'];
    }

    /**
     * Récupère une entreprise par son ID avec toutes ses données
     */
    public function getEntrepriseById($id) : array | null
    {
        $queryEntreprise = $this->connection->prepare(
            "SELECT en.*, a.Nom_Adresse, v.Nom_Ville, Pays.Nom_Pays FROM Entreprises en 
                        JOIN Adresses a ON en.Siege_social = a.Id_Adresse 
                        JOIN Villes v ON a.Id_Ville = v.Id_Ville 
                        JOIN Pays ON v.Id_Pays = Pays.Id_Pays WHERE Id_Entreprise = :id");
        $queryEntreprise->bindParam(':id',$id, PDO::PARAM_INT);
        $queryEntreprise->execute();
        $entreprise = $queryEntreprise->fetch(PDO::FETCH_ASSOC);

        if ($entreprise === false)
        {
            return null; // Retourne null si l'entreprise n'est pas trouvée
        }

        // Requête pour les offres
        $queryOffres = $this->connection->prepare("SELECT Id_Offre, Titre FROM Offres_Stages WHERE Id_Entreprise = :id");
        $queryOffres->bindParam(':id', $id, PDO::PARAM_INT);
        $queryOffres->execute();
        $offres = $queryOffres->fetchAll(PDO::FETCH_ASSOC);

        // Requête pour la note de l'entreprise
        $queryNote = $this->connection->prepare("SELECT AVG(Valeur_Note) as note FROM Notes WHERE Id_Entreprise = :id");
        $queryNote->bindParam(':id', $id, PDO::PARAM_INT);
        $queryNote->execute();
        $note = round($queryNote->fetch(PDO::FETCH_ASSOC)['note'], 1);

        return [$entreprise, $note, $offres];
    }

    /**
     * Récupère les entreprises avec pagination
     */
    public function getEntreprises($numPage) : array | null
    {
        $nbEntreprises = $this->getNbEntreprises(); // Nombre d'entreprises dans la base de données
        $perPage = 10; // Nombre d'entreprises par page
        $nbPages = (int)ceil($nbEntreprises / $perPage); // Nombre total de pages (arrondi au supérieur)
        // Calcul du décalage à appliquer lors de la récupération des entreprises dans la base de données
        $offset = ($numPage - 1) * $perPage;
        echo $offset;
        //Récupération des entreprises pour la page actuelle
        $queryEntreprises = $this->connection->prepare("SELECT en.*, v.Nom_Ville FROM Entreprises en 
                        JOIN Adresses a ON en.Siege_social = a.Id_Adresse 
                        JOIN Villes v ON a.Id_Ville = v.Id_Ville LIMIT ? OFFSET ?");
        $queryEntreprises->bindParam(1, $perPage, PDO::PARAM_INT);
        $queryEntreprises->bindParam(2, $offset, PDO::PARAM_INT);
        $queryEntreprises->execute();
        $entreprises = $queryEntreprises->fetchAll(PDO::FETCH_ASSOC);
        if (!$entreprises)
        {
            return null; // Retourne null si les entreprises ne sont pas trouvées
        }
        // Requête pour nbOffres
        $maxId_Entreprise = $offset+$perPage; // Id_Entreprise max à récupérer pour la page actuelle
        $queryNbOffres = $this->connection->prepare("SELECT COUNT(Id_Offre) as nbOffres FROM Offres_Stages WHERE Id_Entreprise > ? AND Id_Entreprise <= ? GROUP BY Id_Entreprise");
        $queryNbOffres->bindParam(1, $offset, PDO::PARAM_INT);
        $queryNbOffres->bindParam(2, $maxId_Entreprise, PDO::PARAM_INT);
        $queryNbOffres->execute();
        $nbOffres = $queryNbOffres->fetchAll(PDO::FETCH_ASSOC);

        var_dump($nbOffres);
        //Récupération des listes pour les filtres
        $queryVilles = $this->connection->query("SELECT Nom_Ville FROM Villes");
        $listeVilles = $queryVilles->fetchAll(PDO::FETCH_ASSOC);
        $queryPays = $this->connection->query("SELECT Nom_Pays FROM Pays");
        $listePays = $queryPays->fetchAll(PDO::FETCH_ASSOC);

        $filtres['listeVilles'] = $listeVilles;
        $filtres['listePays'] = $listePays;
        return [$nbPages, $entreprises, $nbOffres, $filtres];
    }
    public function createEntreprise($dataEntreprise) : bool
    {
        //Vérification de la présence du pays dans la table, récupération si oui, sinon insertion
        $queryVerifyPays = $this->connection->prepare("SELECT Id_Pays FROM Pays WHERE Nom_Pays = ?");
        $queryVerifyPays->bindParam(1, $dataEntreprise['Pays'], PDO::PARAM_STR);
        $existsPays = $queryVerifyPays->execute();
        if ($existsPays) {
            $Id_Pays = $queryVerifyPays->fetch(PDO::FETCH_ASSOC)['Id_Pays'];
        }
        else {
            $queryCreatePays = $this->connection->prepare("INSERT INTO Pays (Nom_Pays) VALUES (?)");
            $queryCreatePays->bindParam(1, $dataEntreprise['Pays'], PDO::PARAM_STR);
            $queryCreatePays->execute();
            $Id_Pays = (int)$this->connection->lastInsertId();
        }

        $queryVerifyVille = $this->connection->prepare("SELECT Id_Ville FROM Villes WHERE Nom_Ville = ? AND Id_Pays = ?");
        $queryVerifyVille->bindParam(1, $dataEntreprise['Ville'], PDO::PARAM_STR);
        $queryVerifyVille->bindParam(2, $Id_Pays, PDO::PARAM_INT);
        $existsVille = $queryVerifyVille->execute();
        if ($existsVille) {
            $Id_Ville = $queryVerifyVille->fetch(PDO::FETCH_ASSOC)['Id_Ville'];
        }
        else {
            $queryCreateVille = $this->connection->prepare("INSERT INTO Villes(Nom_Ville, Id_Pays) VALUES (?, ?)");
            $queryCreateVille->bindParam(1, $dataEntreprise['Ville'], PDO::PARAM_STR);
            $queryCreateVille->bindParam(2, $Id_Pays, PDO::PARAM_INT);
            $queryCreateVille->execute();
            $Id_Ville = (int)$this->connection->lastInsertId();
        }

        $queryVerifyAdresse = $this->connection->prepare("SELECT Id_Adresse FROM Adresses WHERE Nom_Adresse = ? AND Id_Ville = ?");
        $queryVerifyAdresse->bindParam(1, $dataEntreprise['Adresse'], PDO::PARAM_STR);
        $queryVerifyAdresse->bindParam(2, $Id_Ville, PDO::PARAM_INT);
        $existsAdresse = $queryVerifyAdresse->execute();
        if ($existsAdresse) {
            $Id_Adresse = $queryVerifyAdresse->fetch(PDO::FETCH_ASSOC)['Id_Adresse'];
        }
        else {
            $queryCreateAdresse = $this->connection->prepare("INSERT INTO Adresses(Nom_Adresse, Id_Ville) VALUES (?, ?)");
            $queryCreateAdresse->bindParam(1, $dataEntreprise['Adresse'], PDO::PARAM_STR);
            $queryCreateAdresse->bindParam(2, $Id_Ville, PDO::PARAM_INT);
            $queryCreateAdresse->execute();
            $Id_Adresse = (int)$this->connection->lastInsertId();
        }
        // Ajout de l'entreprise dans la base de données :
        $queryCreateEntreprise = $this->connection->prepare("INSERT INTO Entreprises (Nom_Entreprise, Description_Entreprise, Email, Telephone, Siege_Social) VALUES (?, ?, ?, ?, ?)");
        $queryCreateEntreprise->bindParam(1, $dataEntreprise['Nom'], PDO::PARAM_STR);
        $queryCreateEntreprise->bindParam(2, $dataEntreprise['Description'], PDO::PARAM_STR);
        $queryCreateEntreprise->bindParam(3, $dataEntreprise['Email'], PDO::PARAM_STR);
        $queryCreateEntreprise->bindParam(4, $dataEntreprise['Telephone'], PDO::PARAM_STR);
        $queryCreateEntreprise->bindParam(5, $Id_Adresse, PDO::PARAM_INT);

        return $queryCreateEntreprise->execute(); // Retourne true si la création a réussi, sinon false
    }
    public function updateEntreprise($id, $dataEntreprise) : bool
    {
        $query = $this->connection->prepare();
        $modification = $query->execute();

        return $modification; // Retourne true si la modification a réussi, sinon false
    }
    public function deleteEntreprise($id) : bool {
        //Récupération de l'identifiant unique de l'adresse de l'entreprise
        $queryId_Adresse = $this->connection->prepare("SELECT Siege_social FROM Entreprise WHERE Id_Entreprise = :id");
        $queryId_Adresse->bindParam(':id',$id, PDO::PARAM_INT);
        $queryId_Adresse->execute();
        $Id_Adresse = $queryId_Adresse->fetch(PDO::FETCH_ASSOC)['Siege_social'];

        //Suppression de l'entreprise
        $queryDeleteEntreprise = $this->connection->prepare("DELETE FROM Entreprise WHERE Id_Entreprise = :id");
        $queryDeleteEntreprise->bindParam(':id',$id, PDO::PARAM_INT);
        $suppression = $queryDeleteEntreprise->execute();

        $queryCountEntreprises = $this->connection->prepare("SELECT COUNT(Id_Entreprise) FROM Entreprise WHERE Siege_social = :Id_Adresse");
        $queryCountEntreprises->bindParam(':Id_Adresse',$Id_Adresse, PDO::PARAM_INT);
        $queryCountEntreprises->execute();
        $nbEntreprisesAtAdresse = $queryCountEntreprises->fetch(PDO::FETCH_ASSOC)['COUNT(Id_Entreprise)'];
        if ($nbEntreprisesAtAdresse < 2) {
            //Récupération de l'identifiant unique de la ville de l'adresse
            $queryId_Ville = $this->connection->prepare("SELECT Id_Ville FROM Adresses WHERE Id_Adresse = :Id_Adresse");
            $queryId_Ville->bindParam(':Id_Adresse',$Id_Adresse, PDO::PARAM_INT);
            $queryId_Ville->execute();
            $Id_Ville = $queryId_Ville->fetch(PDO::FETCH_ASSOC)['Id_Ville'];

            //Suppression de l'adresse
            $queryDeleteAdresse = $this->connection->prepare("DELETE FROM Adresses WHERE Id_Adresse = :Id_Adresse");
            $queryDeleteAdresse->bindParam(':Id_Adresse',$Id_Adresse, PDO::PARAM_INT);
            $suppression2 = $queryDeleteAdresse->execute();

            $queryCountAdresses = $this->connection->prepare("SELECT COUNT(Id_Adresse) FROM Adresses WHERE Id_Ville = :Id_Ville");
            $queryCountAdresses->bindParam(':Id_Ville',$Id_Ville, PDO::PARAM_INT);
            $queryCountAdresses->execute();
            $nbAdressesAtVille = $queryCountAdresses->fetch(PDO::FETCH_ASSOC)['COUNT(Id_Adresse)'];

            if ($nbAdressesAtVille < 2) {
                //Récupération de l'identifiant unique du pays de la ville
                $queryId_Pays = $this->connection->prepare("SELECT Id_Pays FROM Villes WHERE Id_Ville = :Id_Ville");
                $queryId_Pays->bindParam(':Id_Ville',$Id_Ville, PDO::PARAM_INT);
                $queryId_Pays->execute();
                $Id_Pays = $queryId_Pays->fetch(PDO::FETCH_ASSOC)['Id_Pays'];

                //Suppression de la ville
                $queryDeleteVille = $this->connection->prepare("DELETE FROM Villes WHERE Id_Ville = :Id_Ville");
                $queryDeleteVille->bindParam(':Id_Ville',$Id_Ville, PDO::PARAM_INT);
                $suppression3 = $queryDeleteVille->execute();

                $queryCountVilles = $this->connection->prepare("SELECT COUNT(Id_Ville) FROM Villes WHERE Id_Pays = :Id_Pays");
                $queryCountVilles->bindParam(':Id_Pays',$Id_Pays, PDO::PARAM_INT);
                $queryCountVilles->execute();
                $nbVillesAtPays = $queryCountVilles->fetch(PDO::FETCH_ASSOC)['COUNT(Id_Pays)'];

                if ($nbVillesAtPays < 2) {
                    //Suppression du pays
                    $queryDeletePays = $this->connection->prepare("DELETE FROM Pays WHERE Id_Pays = :Id_Pays");
                    $queryDeletePays->bindParam(':Id_Pays',$Id_Pays, PDO::PARAM_INT);
                    $suppression4 = $queryDeletePays->execute();
                }
            }
        }
        return $suppression;
    }
}
