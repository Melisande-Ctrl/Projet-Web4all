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
        // Requête pour nbOffres
        $queryNbOffres = $this->connection->prepare("SELECT COUNT(Id_Offre) as nbOffres FROM Offres_Stages WHERE Id_Entreprise = :id");
        $queryNbOffres->bindParam(':id', $id, PDO::PARAM_INT);
        $queryNbOffres->execute();
        $nbOffres = $queryNbOffres->fetch(PDO::FETCH_ASSOC)['nbOffres'];

        // Requête pour les offres
        $queryOffres = $this->connection->prepare("SELECT Id_Offre, Titre FROM Offres_Stages WHERE Id_Entreprise = :id");
        $queryOffres->bindParam(':id', $id, PDO::PARAM_INT);
        $queryOffres->execute();
        $offres = $queryOffres->fetchAll(PDO::FETCH_ASSOC);

        // Requête pour nbNotes     why????????? need the average... but need the nb to do the calculus...
        $queryNbNotes = $this->connection->prepare("SELECT COUNT(Id_Note) as nbNotes FROM Notes WHERE Id_Entreprise = :id");
        $queryNbNotes->bindParam(':id', $id, PDO::PARAM_INT);
        $queryNbNotes->execute();
        $nbNotes = $queryNbNotes->fetch(PDO::FETCH_ASSOC)['nbNotes'];

        return [$entreprise, $nbNotes, $nbOffres, $offres];
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
        $offset = ($numPage - 1) * $perPage + 1;

        //Récupération des entreprises pour la page actuelle
        $queryEntreprises = $this->connection->prepare("SELECT en.*, v.Nom_Ville FROM Entreprises en 
                        JOIN Adresses a ON en.Siege_social = a.Id_Adresse 
                        JOIN Villes v ON a.Id_Ville = v.Id_Ville  LIMIT ? OFFSET ?");
        $queryEntreprises->bindParam(1, $perPage, PDO::PARAM_INT);
        $queryEntreprises->bindParam(2, $offset, PDO::PARAM_INT);
        $queryEntreprises->execute();
        $entreprises = $queryEntreprises->fetchAll(PDO::FETCH_ASSOC);
        if (!$entreprises)
        {
            return null; // Retourne null si les entreprises ne sont pas trouvées
        }
        //var_dump($entreprises);
        //Récupération des listes pour les filtres
        $queryVilles = $this->connection->query("SELECT Nom_Ville FROM Villes");
        $listeVilles = $queryVilles->fetchAll(PDO::FETCH_ASSOC);
        $queryPays = $this->connection->query("SELECT Nom_Pays FROM Pays");
        $listePays = $queryPays->fetchAll(PDO::FETCH_ASSOC);

        $filtres['listeVilles'] = $listeVilles;
        $filtres['listePays'] = $listePays;
        return [$nbPages, $entreprises, $filtres]; // to be modified...
    }
    public function createEntreprise($dataEntreprise) : bool
    {
        //faut passer l'instance de pdo en param ds la creation d'offre car lors de lier les compétences à l'offre, elle n'existe pas donc erreur
        //gotta get the full adresse from the view and add the info progressively through  Pays, Villes, Adresses
        // if it doesn't already exist while keeping the foreign key each time
        $query = $this->connection->prepare("INSERT INTO Entreprises (Nom_Entreprise, Description_Entreprise, Email, Telephone) VALUES (?, ?, ?, ?)");
        $query->bindParam(1, $dataEntreprise['Nom'], PDO::PARAM_STR);
        $query->bindParam(2, $dataEntreprise['Description'], PDO::PARAM_STR);
        $query->bindParam(3, $dataEntreprise['Email'], PDO::PARAM_STR);
        $query->bindParam(4, $dataEntreprise['Telephone'], PDO::PARAM_STR);
        $creation = $query->execute();

        return $creation; // Retourne true si la création a réussi, sinon false
    }
    public function updateEntreprise($id, $dataEntreprise) : bool
    {
        $query = $this->connection->prepare();
        $modification = $query->execute();

        return $modification; // Retourne true si la modification a réussi, sinon false
    }
    public function deleteEntreprise($id) : bool {
        $query = $this->connection->prepare();
        $suppression = $query->execute();
        return $suppression;
    }
}
