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
        $this->templateEngine = $templateEngine;
        $this->model = new EntrepriseModel();
    }
    /**p
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function ficheEntreprise($id) : void
    {
        $entreprise = $this->model->getEntrepriseById($id);
        if (!$entreprise) { // le ! fait une vérification qui renvoie un booléen true si $entreprise est null
            // Gérer le cas où l'entreprise n'est pas trouvée
            echo $this->templateEngine->render('404.html.twig', ['erreur' => "Entreprise non trouvée"]);
        }
        echo $this->templateEngine->render('ficheEntreprise.html.twig', ['entreprise' => $entreprise]);
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function pageRechercheEntreprise() : void
    {
        $numPage = $_POST['numNewPage'];
        [$entreprises, $listesFiltres] = $this->model->getEntreprises($numPage);

        echo $this->templateEngine->render('rechercheEntreprises.html.twig',
            ['entreprises' => $entreprises, 'listesFiltres' => $listesFiltres]);
    }
}
