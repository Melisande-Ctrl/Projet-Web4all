<?php
namespace App\Controllers;

use App\Models\EntrepriseModel;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class EntrepriseController extends Controller
{
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
        if (!$entreprise) { // le ! fait une vérification qui renvoie un booléen true si $entreprise est null
            echo $this->templateEngine->render('404.html.twig', ['erreur' => "Entreprise non trouvée"]);
            return;
        }
        echo $this->templateEngine->render('ficheEntreprise.html.twig',
            ['entreprise' => $entreprise,
                'note' => $note,
                'offres' => $offres]);
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     *
     * Affiche la page de recherche/listing des entreprises
     * @return void
     */
    public function pageRechercheEntreprise() : void // ?int $page = null
    {
        $numPage = 2;//$_GET['numNewPage']
        [$nbPages, $entreprises, $nbOffres, $listesFiltres] = $this->model->getEntreprises($numPage);
        var_dump($entreprises);
        var_dump($listesFiltres);
        echo $this->templateEngine->render('rechercheEntreprises.html.twig',
            ['nbPages' => $nbPages,
                'entreprises' => $entreprises,
                'nbOffres' => $nbOffres,
                'listesFiltres' => $listesFiltres]);//'current_page' => $page,
    }

    /**
     * @param $dataEntreprise
     * @return void
     */
    public function createEntreprise($dataEntreprise) : void {
        if (!is_array($dataEntreprise)) {
            echo '<h1>Erreur - Entrée utilisateur</h1>';
        }
        foreach ($dataEntreprise as $key => $value) {
            if (!is_string($key) or !is_string($value)) {
                echo '<h1>Erreur - Entrée utilisateur</h1>';
            }
        }
        if ($this->model->createEntreprise($dataEntreprise)) {
            echo '<h1>Entreprise créée</h1>';
        }
        else {
            echo '<h1>Erreur - Entreprise non créée</h1>';
        }
    }

    /**
     * @param $dataEntreprise
     * @return void
     */
    public function updateEntreprise($id, $dataEntreprise) : void //faire en sorte que l'id ne vienne pas du user (recup when update button clicked for ex)
    {
        if (!is_array($dataEntreprise) or !is_int($id)) {
            echo '<h1>Erreur - Entrée utilisateur</h1>';
        }
        foreach ($dataEntreprise as $key => $value) {
            if (!is_string($key) or !is_string($value)) {
                echo '<h1>Erreur - Entrée utilisateur</h1>';
            }
        }
        if ($this->model->updateEntreprise($id, $dataEntreprise)) {
            echo '<h1>Entreprise créée</h1>';
        }
        else {
            echo '<h1>Erreur - Entreprise non créée</h1>';
        }
    }

    public function deleteEntreprise($id) : void {
        if ($this->model->deleteEntreprise($id)) {
            echo '<h1>Entreprise supprimée</h1>';
        }
    }
}