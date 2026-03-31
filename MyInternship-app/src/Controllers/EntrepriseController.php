<?php
namespace App\Controllers;

use App\Models\EntrepriseModel;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class EntrepriseController extends Controleur
{
    /**
     * @param $templateEngine
     *
     * Constructeur d'EntrepriseConttroller
     */
    public function __construct($templateEngine)
    {
        parent::__construct($templateEngine);//$this->templateEngine = $templateEngine;
        $this->model = new EntrepriseModel();
    }

    /**
     * @param $id
     * @return void
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     *
     * Affiche la fiche détaillée d'une entreprise
     */
    public function ficheEntreprise($id) : void //?int $id = null
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
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     *
     * Affiche la page de recherche des entreprises
     * @return void
     */
    public function pageRechercheEntreprises() : void // ?int $page = null ??????
    {
        $numPage = 2;//$_GET['numNewPage']
        [$nbPages, $entreprises, $listesFiltres] = $this->model->getEntreprises($numPage);

        $this->render('rechercheEntreprises.html.twig',
            ['nbPages' => $nbPages,
                'entreprises' => $entreprises,
                'listesFiltres' => $listesFiltres]);//'current_page' => $page,

    }

    /**
     * @param $dataEntreprise
     * @return void
     */
    public function createEntreprise() : void {
        $dataEntreprise['Nom'] = $_POST['Nom'] ?? '';
        $dataEntreprise['Description'] = $_POST['Description'] ?? '';
        $dataEntreprise['Email'] = $_POST['Email'] ?? '';
        $dataEntreprise['Telephone'] = $_POST['Telephone'] ?? '';
        $dataEntreprise['Adresse'] = $_POST['Adresse'] ?? '';
        $dataEntreprise['Ville'] = $_POST['Ville'] ?? '';
        $dataEntreprise['Pays'] = $_POST['Pays'] ?? '';

        if (!is_array($dataEntreprise)) {
            $this->redirect('mon_espace');
            //echo '<h1>Erreur - Entrée utilisateur</h1>';
        }
        foreach ($dataEntreprise as $key => $value) {
            if (!is_string($key) or !is_string($value)) {
                echo '<h1>Erreur - Entrée utilisateur</h1>';
            }
        }
        $Id_Entreprise = $this->model->createEntreprise($dataEntreprise);
        if ($Id_Entreprise === false) {
            echo '<h1>Erreur - Entreprise non créée</h1>';
        }
        else {
            echo '<h1>Entreprise créée</h1>';
            $this->redirect('entreprise_show&id='.$Id_Entreprise);
        }
    }

    /**
     * @param $id
     * @return void
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
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
    public function updateEntreprise($id) : void //faire en sorte que l'id ne vienne pas du user (recup when update button clicked for ex)
    {
        $dataEntreprise['Nom'] = $_POST['Nom'] ?? '';
        $dataEntreprise['Description'] = $_POST['Description'] ?? '';
        $dataEntreprise['Email'] = $_POST['Email'] ?? '';
        $dataEntreprise['Telephone'] = $_POST['Telephone'] ?? '';
        $dataEntreprise['Adresse'] = $_POST['Adresse'] ?? '';
        $dataEntreprise['Ville'] = $_POST['Ville'] ?? '';
        $dataEntreprise['Pays'] = $_POST['Pays'] ?? '';

        if (!is_array($dataEntreprise) or !is_int($id)) {
            //echo '<h1>Erreur - Entrée utilisateur</h1>';
            $this->redirect('entreprise_show&id='.$id);
        }
        foreach ($dataEntreprise as $key => $value) {
            if (!is_string($key) or !is_string($value)) {
                //echo '<h1>Erreur - Entrée utilisateur</h1>';
                $this->redirect('entreprise_show&id='.$id);
            }
        }
        if ($this->model->updateEntreprise($id, $dataEntreprise)) {
            echo '<h1>Entreprise créée</h1>';
            $this->redirect('entreprise_show&id='.$id);
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
            echo '<h1>Erreur - Entreprise non supprimée</h1>';//Bam,tap,careers@bam.com,0162325458,96
        }
    }
}