<?php
namespace App\Controllers;

use App\Models\EntrepriseModel;
use Twig\Environment;

class EntrepriseController extends Controleur
{
    /**
     * @param $templateEngine
     *
     * Constructeur d'EntrepriseConttroller
     */
    public function __construct($templateEngine)
    {
        parent::__construct($templateEngine);
        $this->model = new EntrepriseModel();
    }

    /**
     * @param int|null $id
     * @return void
     *
     * Affiche la fiche détaillée d'une entreprise
     */
    public function ficheEntreprise(int|null $id = 1) : void
    {
        [$entreprise, $note, $offres] = $this->model->ficheEntreprise($id);
        $affichageBoutons = false;
        if (isset($_SESSION['user'])) {
            if ($_SESSION['user']['role'] === 1 or $_SESSION['user']['role'] === 2) {
                $affichageBoutons = true;
            }
        }

        if (!$entreprise) { // le ! fait une vérification qui renvoie un booléen true si $entreprise est null
            $this->render('404.html.twig', ['erreur' => "Entreprise non trouvée"]);
            return;
        }
        $this->render('ficheEntreprise.html.twig',
            ['entreprise' => $entreprise,
                'note' => $note,
                'offres' => $offres,
                'affichageBoutons' => $affichageBoutons]);
    }

    /**
     * Affiche la page de recherche des entreprises
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

    public function getPOSTdataEntreprise() : array {
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
     * @return void
     */
    public function createEntreprise() : void {
        $dataEntreprise = $this->getPOSTdataEntreprise();

        foreach ($dataEntreprise as $key => $value) {
            if (!is_string($key) or !is_string($value)) {
                //echo '<h1>Erreur - Entrée utilisateur</h1>';
                $this->redirect('mon_espace');
            }
        }
        $Id_Entreprise = $this->model->createEntreprise($dataEntreprise);
        if ($Id_Entreprise === false) {
            echo '<h1>Erreur - Entreprise non créée</h1>';
        }
        else {
            echo '<h1>Entreprise créée</h1>';
            $this->redirect('entreprise_show', ['id' => $Id_Entreprise]);
        }
    }

    /**
     * @param $id
     * @return void
     */
    public function formUpdateEntreprise($id) : void {
        $entreprise = $this->model->getEntrepriseById($id);
        if (!$entreprise) {
            $this->render('404.html.twig', ['erreur' => "Entreprise non trouvée"]);
            return;
        }
        $this->render('formUpdateEntreprise.html.twig', ['entreprise' => $entreprise]);
    }
    /**
     * @param $id
     * @return void
     */
    public function updateEntreprise($id) : void
    {
        $dataEntreprise = $this->getPOSTdataEntreprise();

        if (!is_int($id)) {
            //echo '<h1>Erreur - Entrée utilisateur</h1>';
            $this->redirect('entreprise_show', ['id' => $id]);
        }
        foreach ($dataEntreprise as $key => $value) {
            if (!is_string($key) or !is_string($value)) {
                //echo '<h1>Erreur - Entrée utilisateur</h1>';
                $this->redirect('entreprise_show', ['id' => $id]);
            }
        }
        if ($this->model->updateEntreprise($id, $dataEntreprise)) {
            echo '<h1>Entreprise créée</h1>';
            $this->redirect('entreprise_show', ['id' => $id]);
        }
        else {
            echo '<h1>Erreur - Entreprise non modifiée</h1>';
        }
    }

    public function deleteEntreprise($id) : void {
        if ($this->model->deleteEntreprise($id)) {
            echo '<h1>Entreprise supprimée</h1>';
            $this->redirect('entreprises');
        }
        else {
            echo '<h1>Erreur - Entreprise non supprimée</h1>';
        }
    }

    public function noterEntreprise($id) : void {
        $note = $_GET['note'] ?? '';
        $Id_Compte = null;
        if ($_SESSION['user']['role'] === 1 or $_SESSION['user']['role'] === 2) {
            $Id_Compte = $_SESSION['user']['id'];
        }
        if ($this->model->noterEntreprise($id, $note, $Id_Compte)) {
            echo '<h1>Entreprise supprimée</h1>';
            $this->redirect('entreprise_show', ['id' => $id]);
        }
        else {
            echo '<h1>Erreur - Entreprise non notée</h1>';
        }
    }
}
