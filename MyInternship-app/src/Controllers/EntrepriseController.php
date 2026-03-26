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
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     *
     * Affiche la fiche détaillée d'une entreprise
     */
    public function ficheEntreprise($id) : void //?int $id = null
    {
        [$entreprise, $nbNotes, $nbOffres, $offres] = $this->model->getEntrepriseById($id);
        if (!$entreprise) { // le ! fait une vérification qui renvoie un booléen true si $entreprise est null
            // Gérer le cas où l'entreprise n'est pas trouvée
            echo $this->templateEngine->render('404.html.twig', ['erreur' => "Entreprise non trouvée"]);
            return;
        }
        echo $this->templateEngine->render('ficheEntreprise.html.twig',
            ['entreprise' => $entreprise,
                'nbNotes' => $nbNotes,
                'nbOffres' => $nbOffres,
                'offres' => $offres]);
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     *
     * Affiche la page de recherche/listing des entreprises
     */
    public function pageRechercheEntreprise() : void // ?int $page = null
    {
        $numPage = 2;//$_GET['numNewPage']
        [$nbPages, $entreprises, $listesFiltres] = $this->model->getEntreprises($numPage);
        var_dump($entreprises);
        var_dump($listesFiltres);
        echo $this->templateEngine->render('rechercheEntreprises.html.twig',
            ['nbPages' => $nbPages,
                'entreprises' => $entreprises,
                'listesFiltres' => $listesFiltres]);//'current_page' => $page,
    }

}