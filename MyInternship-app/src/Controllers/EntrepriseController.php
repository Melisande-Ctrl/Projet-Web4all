<?php
namespace App\Controllers;

use App\Models\EntrepriseModel;
use Twig\Environment;

class EntrepriseController extends Controleur
{
    /**
     * Constructeur d'EntrepriseController
     * @param $templateEngine
     */
    public function __construct($templateEngine)
    {
        parent::__construct($templateEngine);
        $this->model = new EntrepriseModel();
    }

    /**
     * Affiche la fiche détaillée d'une entreprise à partir des données récupérées dans la fonction du model.
     * @param int|null $id
     * @return void
     */
    public function ficheEntreprise(int|null $id = 1) : void
    {
        [$entreprise, $note, $offres] = $this->model->ficheEntreprise($id);
        $affichageBoutons = false;
        if (isset($_SESSION['user']))
        {
            if ($_SESSION['user']['role'] === 1 or $_SESSION['user']['role'] === 2)
            {
                $affichageBoutons = true;
            }
        }

        if (!$entreprise) // Vérification qui renvoie un booléen true si $entreprise est null
        {
            echo '<h1>Erreur - Entreprise non trouvée</h1>';
        }
        else
        {
            $this->render('ficheEntreprise.html.twig',
                ['entreprise' => $entreprise,
                    'note' => $note,
                    'offres' => $offres,
                    'affichageBoutons' => $affichageBoutons]);
        }
    }

    /**
     * Lance la fonction de récupération des entreprises et calcul de pagination du model
     * et affiche la page de recherche des entreprises.
     * @param int $currentPage
     * @return void
     */
    public function pageRechercheEntreprises(int $currentPage = 1) : void
    {
        $criteresRecherche['Nom'] = $_GET['Nom'] ?? '';
        $criteresRecherche['Ville'] = $_GET['Ville'] ?? '';
        [$nbPages, $nbEntreprises, $entreprises] = $this->model->getEntreprises($currentPage, $criteresRecherche);

        $this->render('rechercheEntreprises.html.twig',
            ['nbPages' => $nbPages,
             'currentPage' => $currentPage,
             'nbEntreprises' => $nbEntreprises,
             'entreprises' => $entreprises,
             'criteria' => $criteresRecherche]);
    }

    /**
     * Récupère les informations de l'entreprise depuis $_POST et les retourne dans un tableau associatif.
     * @return array
     */
    public function getPOSTdataEntreprise() : array
    {
        $dataEntreprise['Nom'] = $_POST['Nom'] ?? '';
        $dataEntreprise['Description'] = $_POST['Description'] ?? '';
        $dataEntreprise['Email'] = $_POST['Email'] ?? '';
        $dataEntreprise['Telephone'] = $_POST['Telephone'] ?? '';
        $dataEntreprise['Adresse'] = $_POST['Adresse'] ?? '';
        $dataEntreprise['Ville'] = $_POST['Ville'] ?? '';
        $dataEntreprise['Pays'] = $_POST['Pays'] ?? '';

        return $dataEntreprise;
    }
    /**
     * Lance la fonction de CR2ATION de l'entreprise du model à partir des informations récupérées.
     * @return void
     */
    public function createEntreprise() : void {
        $dataEntreprise = $this->getPOSTdataEntreprise();

        foreach ($dataEntreprise as $key => $value)
        {
            if (!is_string($key) or !is_string($value))
            {
                echo '<h1>Erreur - Entrée utilisateur</h1>';
            }
        }
        $Id_Entreprise = $this->model->createEntreprise($dataEntreprise);
        if ($Id_Entreprise === false)
        {
            echo '<h1>Erreur - Entreprise non créée</h1>';
        }
        else
        {
            $this->redirect('entreprise_show', ['id' => $Id_Entreprise]);
        }
    }

    /**
     * Renders la template du formulaire de modification d'entreprise.
     * @param $id
     * @return void
     */
    public function formUpdateEntreprise($id) : void
    {
        $entreprise = $this->model->getEntrepriseById($id);
        if ($entreprise)
        {
            $this->render('formUpdateEntreprise.html.twig', ['entreprise' => $entreprise]);
        }
        else {
            echo '<h1>Erreur - Entreprise non trouvée</h1>';
        }
    }
    /**
     * Lance la fonction de modification des données de l'entreprise à partir des informations récupérées.
     * @param $id
     * @return void
     */
    public function updateEntreprise($id) : void
    {
        $dataEntreprise = $this->getPOSTdataEntreprise(); // Récupération des données d'entreprise depuis $_POST

        if (!is_int($id))
        {
            echo '<h1>Erreur - Entrée utilisateur</h1>';
        }
        foreach ($dataEntreprise as $key => $value)
        {
            if (!is_string($key) or !is_string($value))
            {
                echo '<h1>Erreur - Entrée utilisateur</h1>';
            }
        }
        if ($this->model->updateEntreprise($id, $dataEntreprise))
        {
            $this->redirect('entreprise_show', ['id' => $id]);
        }
        else
        {
            echo '<h1>Erreur - Entreprise non modifiée</h1>';
        }
    }

    /**
     * Lance la fonction de suppression de l'entreprise du model.
     * @param $id
     * @return void
     */
    public function deleteEntreprise($id) : void
    {
        if ($this->model->deleteEntreprise($id))
        {
            echo '<h1>Entreprise supprimée</h1>';
            $this->redirect('entreprises');
        }
        else
        {
            echo '<h1>Erreur - Entreprise non supprimée</h1>';
        }
    }

    /**
     * Lance la fonction de notation d'entreprise du model et redirige vers la fiche de l'entreprise.
     * @param $id
     * @return void
     */
    public function noterEntreprise($id) : void {
        $note = $_GET['note'] ?? '';
        $Id_Compte = null;
        if ($_SESSION['user']['role'] === 1 or $_SESSION['user']['role'] === 2)
        {
            $Id_Compte = $_SESSION['user']['id'];
        }
        if ($this->model->noterEntreprise($id, $note, $Id_Compte))
        {
            $this->redirect('entreprise_show', ['id' => $id]);
        }
        else
        {
            echo '<h1>Erreur - Entreprise non notée</h1>';
        }
    }
}
