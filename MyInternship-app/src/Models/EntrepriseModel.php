<?php
namespace App\Models;

use PDO;

class EntrepriseModel extends Modele
{
    /**
     * Récupère le nombre total d'entreprises dans la base de données.
     * @return int
     */
    public function getNbEntreprises() : int
    {
        $query = $this->connection->query("SELECT COUNT(DISTINCT(Id_Entreprise)) FROM Entreprises");
        $nb = $query->fetch(PDO::FETCH_ASSOC);
        return $nb['COUNT(DISTINCT(Id_Entreprise))'];
    }

    /**
     * Récupère une entreprise par son identifiant unique avec toutes ses données (y compris l'adresse complète).
     * @param $idEntreprise
     * @return array|null
     */
    public function getEntrepriseById($idEntreprise) : array | null
    {
        $queryEntreprise = $this->connection->prepare(
            "SELECT en.*, a.Nom_Adresse, a.Id_Ville, v.Nom_Ville, v.Id_Pays, Pays.Nom_Pays FROM Entreprises en 
                        JOIN Adresses a ON en.Siege_social = a.Id_Adresse 
                        JOIN Villes v ON a.Id_Ville = v.Id_Ville 
                        JOIN Pays ON v.Id_Pays = Pays.Id_Pays WHERE Id_Entreprise = :id");
        $queryEntreprise->bindParam(':id',$idEntreprise, PDO::PARAM_INT);
        $queryEntreprise->execute();
        $entreprise = $queryEntreprise->fetch(PDO::FETCH_ASSOC);

        if ($entreprise === false)
        {
            return null; // Retourne null si l'entreprise n'est pas trouvée
        }
        return $entreprise;
    }

    /**
     * Récupération des informations de l'entreprise dans la base de données pour sa fiche.
     * @param $idEntreprise
     * @return array
     */
    public function ficheEntreprise($idEntreprise) : array
    {
        // Récupération des données d'une entreprise avec son adresse complète :
        $entreprise = $this->getEntrepriseById($idEntreprise);

        // Requête pour les offres de l'entreprise :
        $queryOffres = $this->connection->prepare("SELECT Id_Offre, Titre FROM Offres_Stages WHERE Id_Entreprise = :id");
        $queryOffres->bindParam(':id', $idEntreprise, PDO::PARAM_INT);
        $queryOffres->execute();
        $offres = $queryOffres->fetchAll(PDO::FETCH_ASSOC);

        // Requête pour la moyenne des notes de l'entreprise :
        $queryNote = $this->connection->prepare("SELECT AVG(Valeur_Note) as note FROM Notes WHERE Id_Entreprise = :id");
        $queryNote->bindParam(':id', $idEntreprise, PDO::PARAM_INT);
        $queryNote->execute();
        $note = round((int)$queryNote->fetch(PDO::FETCH_ASSOC)['note'], 1);

        return [$entreprise, $note, $offres];
    }

    /**
     * Récupère les entreprises avec pagination en appliquant les filtres utilisateur.
     * @param $numPage
     * @param $criteresRecherche
     * @return array|null
     */
    public function getEntreprises($numPage, $criteresRecherche) : array | null
    {
        // Construction de la partie WHERE pour ajouter les filtres de l'utilisateur
        $conditions = [];
        $criteriaValues = [];

        $nomFilter = trim((string)($criteresRecherche['Nom'] ?? ''));
        if ($nomFilter !== '')
        {
            $conditions[] = 'en.Nom_Entreprise LIKE :nomFilter';
            $criteriaValues[':nomFilter'] = '%' . $nomFilter . '%';
        }
        $villeFilter = trim((string)($criteresRecherche['Ville'] ?? ''));
        if ($villeFilter !== '')
        {
            $conditions[] = 'v.Nom_Ville LIKE :villeFilter';
            $criteriaValues[':villeFilter'] = '%' . $villeFilter . '%';
        }
        if ($conditions === [])
        {
            $completeWHERE = '';
        }
        else
        {
            $completeWHERE = ' WHERE ' . implode(' AND ', $conditions);
        }
        // Trouver le nombre total d'entreprises nécessaire pour calculer le nombre de pages dans la pagination
        if ($criteriaValues === []) // S'il n'y a pas de filtres à appliquer, on prend le nombre d'entreprises enregistré
        {
            $nbEntreprises = $this->getNbEntreprises(); // Nombre d'entreprises dans la base de données
        }
        else
        {
            $queryCountEntreprises = $this->connection->prepare("SELECT COUNT(DISTINCT(en.Id_Entreprise)) as NbEntreprises
                        FROM Entreprises en 
                        LEFT JOIN Adresses a ON en.Siege_social = a.Id_Adresse 
                        LEFT JOIN Villes v ON a.Id_Ville = v.Id_Ville 
                        LEFT JOIN Offres_Stages os ON en.Id_Entreprise = os.Id_Entreprise
                        $completeWHERE
                        ORDER BY en.Nom_Entreprise");
            foreach ($criteriaValues as $name => $value)
            {
                $queryCountEntreprises->bindParam($name, $value, PDO::PARAM_STR);
            }
            $queryCountEntreprises->execute();
            $nbEntreprises = (int)$queryCountEntreprises->fetch(PDO::FETCH_ASSOC)['NbEntreprises'];
        }

        $perPage = 10; // Nombre d'entreprises par page
        $nbPages = (int)ceil($nbEntreprises / $perPage); // Nombre total de pages (arrondi au supérieur)
        // Calcul du décalage à appliquer lors de la récupération des entreprises dans la base de données :
        $offset = ($numPage - 1) * $perPage;

        // Récupération des entreprises pour la page actuelle
        $queryEntreprises = $this->connection->prepare("SELECT en.*, v.Nom_Ville, 
                        COALESCE(COUNT(os.Id_Offre), 0) AS nbOffres FROM Entreprises en 
                        LEFT JOIN Adresses a ON en.Siege_social = a.Id_Adresse 
                        LEFT JOIN Villes v ON a.Id_Ville = v.Id_Ville 
                        LEFT JOIN Offres_Stages os ON en.Id_Entreprise = os.Id_Entreprise
                        $completeWHERE
                        GROUP BY en.Id_Entreprise, en.Nom_Entreprise ORDER BY en.Nom_Entreprise LIMIT :limit OFFSET :offset");
        foreach ($criteriaValues as $name => $value) // Insertion des filtres de l'utilisateur dans la requête
        {
            $queryEntreprises->bindParam($name, $value, PDO::PARAM_STR);
        }
        $queryEntreprises->bindParam(':limit', $perPage, PDO::PARAM_INT);
        $queryEntreprises->bindParam(':offset', $offset, PDO::PARAM_INT);
        $queryEntreprises->execute();
        $entreprises = $queryEntreprises->fetchAll(PDO::FETCH_ASSOC);
        if (!$entreprises)
        {
            return null; // Retourne null si les entreprises ne sont pas trouvées
        }

        return [$nbPages, $nbEntreprises, $entreprises];
    }

    /**
     * Création de l'entreprise dans la base de données y compris son adresse, sa ville et son pays (sinon récupération de l'identifiant).
     * @param $dataEntreprise
     * @return int|bool
     */
    public function createEntreprise($dataEntreprise) : int | bool
    {
        //Vérification de la présence du pays dans la table, récupération de l'Id_Pays si oui, sinon insertion
        $queryVerifyPays = $this->connection->prepare("SELECT Id_Pays FROM Pays WHERE Nom_Pays = ?");
        $queryVerifyPays->bindParam(1, $dataEntreprise['Pays'], PDO::PARAM_STR);
        $queryVerifyPays->execute();
        $existsPays = $queryVerifyPays->fetch(PDO::FETCH_ASSOC)['Id_Pays'];
        if ($existsPays)
        {
            $Id_Pays = (int)$existsPays;
        }
        else
        {
            $queryCreatePays = $this->connection->prepare("INSERT INTO Pays (Nom_Pays) VALUES (?)");
            $queryCreatePays->bindParam(1, $dataEntreprise['Pays'], PDO::PARAM_STR);
            $queryCreatePays->execute();
            $Id_Pays = (int)$this->connection->lastInsertId();
        }

        //Vérification de la présence de la ville dans la table, récupération de l'Id_Ville si oui, sinon insertion
        $queryVerifyVille = $this->connection->prepare("SELECT Id_Ville FROM Villes WHERE Nom_Ville = ? AND Id_Pays = ?");
        $queryVerifyVille->bindParam(1, $dataEntreprise['Ville'], PDO::PARAM_STR);
        $queryVerifyVille->bindParam(2, $Id_Pays, PDO::PARAM_INT);
        $queryVerifyVille->execute();
        $existsVille = $queryVerifyVille->fetch(PDO::FETCH_ASSOC)['Id_Ville'];
        if ($existsVille)
        {
            $Id_Ville = (int)$existsVille;
        }
        else
        {
            $queryCreateVille = $this->connection->prepare("INSERT INTO Villes(Nom_Ville, Id_Pays) VALUES (?, ?)");
            $queryCreateVille->bindParam(1, $dataEntreprise['Ville'], PDO::PARAM_STR);
            $queryCreateVille->bindParam(2, $Id_Pays, PDO::PARAM_INT);
            $queryCreateVille->execute();
            $Id_Ville = (int)$this->connection->lastInsertId();
        }

        //Vérification de la présence de l'adresse dans la table, récupération de l'Id_Adresse si oui, sinon insertion
        $queryVerifyAdresse = $this->connection->prepare("SELECT Id_Adresse FROM Adresses WHERE Nom_Adresse = ? AND Id_Ville = ?");
        $queryVerifyAdresse->bindParam(1, $dataEntreprise['Adresse'], PDO::PARAM_STR);
        $queryVerifyAdresse->bindParam(2, $Id_Ville, PDO::PARAM_INT);
        $queryVerifyAdresse->execute();
        $existsAdresse = $queryVerifyAdresse->fetch(PDO::FETCH_ASSOC)['Id_Adresse'];
        if ($existsAdresse)
        {
            $Id_Adresse = (int)$existsAdresse;
        }
        else
        {
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

        if ($queryCreateEntreprise->execute()) // Retourne true si la création a réussi, sinon false
        {
            return (int)$this->connection->lastInsertId(); // Renvoie l'id pour rediriger vers la fiche de l'entreprise
        }
        return false;
    }

    /**
     * Modification des données de l'entreprise dans la base de données
     * si l'utilisateur les a modifiées dans le formulaire pré-rempli,
     * y compris son adresse, sa ville et son pays si aucune autre entrée n'en dépend (sinon création).
     * @param $Id_Entreprise
     * @param $dataEntreprise
     * @return bool
     */
    public function updateEntreprise($Id_Entreprise, $dataEntreprise) : bool
    {
        // Récupération des données de l'entreprise avec son adresse complète
        $databaseDataEntreprise = $this->getEntrepriseById($Id_Entreprise);
        if ($databaseDataEntreprise === null) {
            return false; // l'entreprise n'existe pas dans la base de données
        }

        if ($databaseDataEntreprise['Nom'] !== $dataEntreprise['Nom'])
        {
            $queryUpdateNomEntreprise = $this->connection->prepare("UPDATE Entreprises SET Nom_Entreprise = ? WHERE Id_Entreprise = ?");
            $queryUpdateNomEntreprise->bindParam(1, $dataEntreprise['Nom'], PDO::PARAM_STR);
            $queryUpdateNomEntreprise->bindParam(2,$Id_Entreprise, PDO::PARAM_INT);
            $queryUpdateNomEntreprise->execute();
        }
        if ($databaseDataEntreprise['Description'] !== $dataEntreprise['Description'])
        {
            $queryUpdateDescriptionEntreprise = $this->connection->prepare("UPDATE Entreprises SET Description_Entreprise = ? WHERE Id_Entreprise = ?");
            $queryUpdateDescriptionEntreprise->bindParam(1, $dataEntreprise['Description'], PDO::PARAM_STR);
            $queryUpdateDescriptionEntreprise->bindParam(2,$Id_Entreprise, PDO::PARAM_INT);
            $queryUpdateDescriptionEntreprise->execute();
        }
        if ($databaseDataEntreprise['Email'] !== $dataEntreprise['Email'])
        {
            $queryUpdateEmailEntreprise = $this->connection->prepare("UPDATE Entreprises SET Email = ? WHERE Id_Entreprise = ?");
            $queryUpdateEmailEntreprise->bindParam(1, $dataEntreprise['Email'], PDO::PARAM_STR);
            $queryUpdateEmailEntreprise->bindParam(2,$Id_Entreprise, PDO::PARAM_INT);
            $queryUpdateEmailEntreprise->execute();
        }
        if ($databaseDataEntreprise['Telephone'] !== $dataEntreprise['Telephone'])
        {
            $queryUpdateTelephoneEntreprise = $this->connection->prepare("UPDATE Entreprises SET Telephone = ? WHERE Id_Entreprise = ?");
            $queryUpdateTelephoneEntreprise->bindParam(1, $dataEntreprise['Telephone'], PDO::PARAM_STR);
            $queryUpdateTelephoneEntreprise->bindParam(2,$Id_Entreprise, PDO::PARAM_INT);
            $queryUpdateTelephoneEntreprise->execute();
        }

        if ($databaseDataEntreprise['Nom_Adresse'] !== $dataEntreprise['Adresse'])
        {
            $queryCount = $this->connection->prepare("SELECT
                (SELECT COUNT(*) FROM Entreprises WHERE Siege_social = :Id_Adresse) AS nbEntreprises,
                (SELECT COUNT(*) FROM Offres_Stages WHERE Id_Adresse = :Id_Adresse) AS nbOffres;");
            $queryCount->bindParam(':Id_Adresse',$databaseDataEntreprise['Siege_social'], PDO::PARAM_INT);
            $queryCount->execute();
            $nbEntreprisesEtOffres = $queryCount->fetch(PDO::FETCH_ASSOC);
            $nbUsingAdresse = (int)$nbEntreprisesEtOffres['nbEntreprises'] + (int)$nbEntreprisesEtOffres['nbOffres'];
            if ($nbUsingAdresse < 2) // Aucune autre entrée ne dépend de cette adresse
            {
                $queryUpdateNomAdresseEntreprise = $this->connection->prepare("UPDATE Adresses SET Nom_Adresse = ? WHERE Id_Adresse = ?");
                $queryUpdateNomAdresseEntreprise->bindParam(1, $dataEntreprise['Adresse'], PDO::PARAM_STR);
                $queryUpdateNomAdresseEntreprise->bindParam(2, $databaseDataEntreprise['Siege_social'], PDO::PARAM_INT);
                $queryUpdateNomAdresseEntreprise->execute();
            }
            else
            {
                // Insertion de l'adresse :
                $queryCreateAdresse = $this->connection->prepare("INSERT INTO Adresses(Nom_Adresse, Id_Ville) VALUES (?, ?)");
                $queryCreateAdresse->bindParam(1, $dataEntreprise['Adresse'], PDO::PARAM_STR);
                $queryCreateAdresse->bindParam(2, $databaseDataEntreprise['Id_Ville'], PDO::PARAM_INT);
                $queryCreateAdresse->execute();
                $Id_Adresse = (int)$this->connection->lastInsertId();
                $databaseDataEntreprise['Siege_social'] = $Id_Adresse;
                // Update l'Id_Ville de l'entreprise :
                $queryUpdateIdAdresseEntreprise = $this->connection->prepare("UPDATE Entreprises SET Siege_social = ? WHERE Id_Entreprise = ?");
                $queryUpdateIdAdresseEntreprise->bindParam(1, $databaseDataEntreprise['Siege_social'], PDO::PARAM_INT);
                $queryUpdateIdAdresseEntreprise->bindParam(2,$Id_Entreprise, PDO::PARAM_INT);
                $queryUpdateIdAdresseEntreprise->execute();
            }
        }

        if ($databaseDataEntreprise['Nom_Ville'] !== $dataEntreprise['Ville'])
        {
            $queryCountAdresses = $this->connection->prepare("SELECT COUNT(Id_Adresse) FROM Adresses WHERE Id_Ville = :Id_Ville");
            $queryCountAdresses->bindParam(':Id_Ville',$databaseDataEntreprise['Id_Ville'], PDO::PARAM_INT);
            $queryCountAdresses->execute();
            $nbAdressesAtVille = (int)$queryCountAdresses->fetch(PDO::FETCH_ASSOC)['COUNT(Id_Adresse)'];
            if ($nbAdressesAtVille < 2) // Aucune autre adresse ne dépend de cette ville
            {
                $queryUpdateNomVilleEntreprise = $this->connection->prepare("UPDATE Villes SET Nom_Ville = ? WHERE Id_Ville = ?");
                $queryUpdateNomVilleEntreprise->bindParam(1, $dataEntreprise['Ville'], PDO::PARAM_STR);
                $queryUpdateNomVilleEntreprise->bindParam(2, $databaseDataEntreprise['Id_Ville'], PDO::PARAM_INT);
                $queryUpdateNomVilleEntreprise->execute();
            }
            else
            {
                // Insertion de la ville :
                $queryCreateVille = $this->connection->prepare("INSERT INTO Villes(Nom_Ville, Id_Pays) VALUES (?, ?)");
                $queryCreateVille->bindParam(1, $dataEntreprise['Ville'], PDO::PARAM_STR);
                $queryCreateVille->bindParam(2, $databaseDataEntreprise['Id_Pays'], PDO::PARAM_INT);
                $queryCreateVille->execute();
                $Id_Ville = (int)$this->connection->lastInsertId();
                $databaseDataEntreprise['Id_Ville'] = $Id_Ville;
                // Update l'Id_Ville de l'entreprise :
                $queryUpdateIdVilleEntreprise = $this->connection->prepare("UPDATE Adresses SET Id_Ville = ? WHERE Id_Adresse = ?");
                $queryUpdateIdVilleEntreprise->bindParam(1, $databaseDataEntreprise['Id_Ville'], PDO::PARAM_INT);
                $queryUpdateIdVilleEntreprise->bindParam(2,$databaseDataEntreprise['Siege_social'], PDO::PARAM_INT);
                $queryUpdateIdVilleEntreprise->execute();
            }
        }

        if ($databaseDataEntreprise['Nom_Pays'] !== $dataEntreprise['Pays'])
        {
            $queryCountVilles = $this->connection->prepare("SELECT COUNT(Id_Ville) FROM Villes WHERE Id_Pays = :Id_Pays");
            $queryCountVilles->bindParam(':Id_Pays',$databaseDataEntreprise['Id_Pays'], PDO::PARAM_INT);
            $queryCountVilles->execute();
            $nbVillesAtPays = (int)$queryCountVilles->fetch(PDO::FETCH_ASSOC)['COUNT(Id_Ville)'];
            if ($nbVillesAtPays < 2) // Aucune autre ville ne dépend de ce pays
            {
                $queryUpdateNomPaysEntreprise = $this->connection->prepare("UPDATE Pays SET Nom_Pays = ? WHERE Id_Pays = ?");
                $queryUpdateNomPaysEntreprise->bindParam(1, $dataEntreprise['Pays'], PDO::PARAM_STR);
                $queryUpdateNomPaysEntreprise->bindParam(2, $databaseDataEntreprise['Id_Pays'], PDO::PARAM_INT);
                $queryUpdateNomPaysEntreprise->execute();
            }
            else
            {
                // Insertion du pays :
                $queryCreatePays = $this->connection->prepare("INSERT INTO Pays(Nom_Pays) VALUES (?)");
                $queryCreatePays->bindParam(1, $dataEntreprise['Pays'], PDO::PARAM_STR);
                $queryCreatePays->execute();
                $Id_Pays = (int)$this->connection->lastInsertId();
                $databaseDataEntreprise['Id_Pays'] = $Id_Pays;
                // Update l'Id_Pays de l'entreprise :
                $queryUpdateIdPaysEntreprise = $this->connection->prepare("UPDATE Villes SET Id_Pays = ? WHERE Id_Ville = ?");
                $queryUpdateIdPaysEntreprise->bindParam(1, $databaseDataEntreprise['Id_Pays'], PDO::PARAM_INT);
                $queryUpdateIdPaysEntreprise->bindParam(2,$databaseDataEntreprise['Id_Ville'], PDO::PARAM_INT);
                $queryUpdateIdPaysEntreprise->execute();
            }
        }
        return true;
    }

    /**
     * Suppression d'une entreprise, son adresse, sa ville et son pays si aucune autre entrée n'en dépend.
     * @param $id
     * @return bool
     */
    public function deleteEntreprise($id) : bool {
        $queryNbOffres = $this->connection->prepare("SELECT COALESCE(COUNT(Id_Offre), 0) AS nbOffres 
                        FROM Offres_Stages WHERE Id_Entreprise = :id GROUP BY Id_Entreprise");
        $queryNbOffres->bindParam(':id',$id, PDO::PARAM_INT);
        $queryNbOffres->execute();
        $nbOffres = (int)$queryNbOffres->fetch(PDO::FETCH_ASSOC)['nbOffres'];
        if ($nbOffres > 0) {
            return false; // Retourne false si l'entreprise a des offres de stage associées
        }
        //Récupération de l'identifiant unique de l'adresse de l'entreprise
        $queryId_Adresse = $this->connection->prepare("SELECT Siege_social FROM Entreprises WHERE Id_Entreprise = :id");
        $queryId_Adresse->bindParam(':id',$id, PDO::PARAM_INT);
        $queryId_Adresse->execute();
        $Id_Adresse = (int)$queryId_Adresse->fetch(PDO::FETCH_ASSOC)['Siege_social'];

        //Suppression de l'entreprise
        $queryDeleteEntreprise = $this->connection->prepare("DELETE FROM Entreprises WHERE Id_Entreprise = :id");
        $queryDeleteEntreprise->bindParam(':id',$id, PDO::PARAM_INT);
        $suppression = $queryDeleteEntreprise->execute();

        $queryCount = $this->connection->prepare("SELECT
                (SELECT COUNT(*) FROM Entreprises WHERE Siege_social = :Id_Adresse) AS nbEntreprises,
                (SELECT COUNT(*) FROM Offres_Stages WHERE Id_Adresse = :Id_Adresse) AS nbOffres;");
        $queryCount->bindParam(':Id_Adresse',$Id_Adresse, PDO::PARAM_INT);
        $queryCount->execute();
        $nbEntreprisesEtOffres = $queryCount->fetch(PDO::FETCH_ASSOC);
        $nbUsingAdresse = (int)$nbEntreprisesEtOffres['nbEntreprises'] + (int)$nbEntreprisesEtOffres['nbOffres'];
        if ($nbUsingAdresse < 1) {
            //Récupération de l'identifiant unique de la ville de l'adresse
            $queryId_Ville = $this->connection->prepare("SELECT Id_Ville FROM Adresses WHERE Id_Adresse = :Id_Adresse");
            $queryId_Ville->bindParam(':Id_Adresse',$Id_Adresse, PDO::PARAM_INT);
            $queryId_Ville->execute();
            $Id_Ville = (int)$queryId_Ville->fetch(PDO::FETCH_ASSOC)['Id_Ville'];

            //Suppression de l'adresse
            $queryDeleteAdresse = $this->connection->prepare("DELETE FROM Adresses WHERE Id_Adresse = :Id_Adresse");
            $queryDeleteAdresse->bindParam(':Id_Adresse',$Id_Adresse, PDO::PARAM_INT);
            $suppression = $suppression && $queryDeleteAdresse->execute();

            $queryCountAdresses = $this->connection->prepare("SELECT COUNT(Id_Adresse) FROM Adresses WHERE Id_Ville = :Id_Ville");
            $queryCountAdresses->bindParam(':Id_Ville',$Id_Ville, PDO::PARAM_INT);
            $queryCountAdresses->execute();
            $nbAdressesAtVille = (int)$queryCountAdresses->fetch(PDO::FETCH_ASSOC)['COUNT(Id_Adresse)'];

            if ($nbAdressesAtVille < 1) {
                //Récupération de l'identifiant unique du pays de la ville
                $queryId_Pays = $this->connection->prepare("SELECT Id_Pays FROM Villes WHERE Id_Ville = :Id_Ville");
                $queryId_Pays->bindParam(':Id_Ville',$Id_Ville, PDO::PARAM_INT);
                $queryId_Pays->execute();
                $Id_Pays = (int)$queryId_Pays->fetch(PDO::FETCH_ASSOC)['Id_Pays'];

                //Suppression de la ville
                $queryDeleteVille = $this->connection->prepare("DELETE FROM Villes WHERE Id_Ville = :Id_Ville");
                $queryDeleteVille->bindParam(':Id_Ville',$Id_Ville, PDO::PARAM_INT);
                $suppression = $suppression && $queryDeleteVille->execute();

                $queryCountVilles = $this->connection->prepare("SELECT COUNT(Id_Ville) FROM Villes WHERE Id_Pays = :Id_Pays");
                $queryCountVilles->bindParam(':Id_Pays',$Id_Pays, PDO::PARAM_INT);
                $queryCountVilles->execute();
                $nbVillesAtPays = (int)$queryCountVilles->fetch(PDO::FETCH_ASSOC)['COUNT(Id_Pays)'];
                if ($nbVillesAtPays < 1) {
                    //Suppression du pays
                    $queryDeletePays = $this->connection->prepare("DELETE FROM Pays WHERE Id_Pays = :Id_Pays");
                    $queryDeletePays->bindParam(':Id_Pays',$Id_Pays, PDO::PARAM_INT);
                    $suppression = $suppression && $queryDeletePays->execute();
                }
            }
        }
        return $suppression;
    }

    /**
     * Insertion de la note dans la base de données
     * ou modification si déjà existante pour le couple (Id_Entreprise, Id_Compte). 
     * @param $Id_Entreprise
     * @param $note
     * @param $Id_Compte
     * @return bool
     */
    public function noterEntreprise($Id_Entreprise, $note, $Id_Compte) : bool
    {
        if ($Id_Compte === null)
        {
            return false;
        }
        $queryNote = $this->connection->prepare("INSERT INTO Notes (Valeur_Note, Id_Entreprise, Id_Compte) VALUES (:note, :Id_Entreprise, :Id_Compte)
                        ON DUPLICATE KEY UPDATE Valeur_Note = :note");
        $queryNote->bindParam(':note', $note, PDO::PARAM_INT);
        $queryNote->bindParam(':Id_Entreprise', $Id_Entreprise, PDO::PARAM_INT);
        $queryNote->bindParam(':Id_Compte', $Id_Compte, PDO::PARAM_INT);
        return $queryNote->execute();
    }
}