<?php
/**
 * This is the router, the main entry point of the application.
 * It handles the routing and dispatches requests to the appropriate controller methods.
 */
declare(strict_types=1);

/**
*Configuration des erreurs (développement)
*/
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

/**
*Démarrage de la session
*/
session_start();

/**
*Chargement automatique via Composer
*/
require_once __DIR__ . '/../vendor/autoload.php';

use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\Extension\DebugExtension;
use App\Controllers\AccueilController;
use App\Controllers\ConnexionController;
use App\Controllers\OffreStageController;
use App\Controllers\EntrepriseController;
use App\Controllers\AdminController;
use App\Controllers\PiloteController;
use App\Controllers\EtudiantController;
use App\Controllers\MainController;

/**
*Initialisation de Twig
*/
$loader = new FilesystemLoader(__DIR__ . '/../MyInternship-app/src/Views');

$twig = new Environment($loader, [
    'cache' => false,
    'debug' => true,
]);

$twig->addExtension(new DebugExtension());

/**
*Lecture de la requête
*/
$route = $_GET['route'] ?? 'accueil';
$httpMethod = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? (int) $_GET['id'] : null;

/**
*Définition des routes
*/
$routes = [
    'GET' => [
        'accueil' => [AccueilController::class, 'index'],
        'connexion' => [ConnexionController::class, 'afficherFormulaireConnexion'],
        'offres_stage' => [OffreStageController::class, 'index'],
        'offre_stage' => [OffreStageController::class, 'show'],
        'offre_stage_candidature' => [OffreStageController::class, 'showCandidatureForm'],
        'entreprises' => [EntrepriseController::class, 'pageRechercheEntreprises'],
        'entreprise_show' => [EntrepriseController::class, 'ficheEntreprise'],
        'entreprise_edit' => [EntrepriseController::class, 'formUpdateEntreprise'],
        'entreprise_delete' => [EntrepriseController::class, 'deleteEntreprise'],
        'mentions_legales' => [AccueilController::class, 'mentionsLegales'],
        'admin_dashboard' => [AdminController::class, 'showDashboard'],
        'pilote_dashboard' => [PiloteController::class, 'showDashboard'],
        'etudiant_dashboard' => [EtudiantController::class, 'showDashboard'],
        'mon_espace' => [MainController::class, 'redirectToDashboard'],
        ],
    'POST' => [
        'traitement_connexion' => [ConnexionController::class, 'connecter'],
        'deconnexion' => [ConnexionController::class, 'deconnecter'],
        'login' => [ConnexionController::class, 'connecter'],
        'logout' => [ConnexionController::class, 'deconnecter'],
        'change_password' => [MainController::class, 'changePassword'],
        'wishlist_ajouter' => [OffreStageController::class, 'ajouterWishlist'],
        'new_entreprise' => [EntrepriseController::class, 'createEntreprise'],
        'candidature_ajouter' => [OffreStageController::class, 'ajouterCandidature'],
        'remove_wishlist' => [EtudiantController::class, 'removeFromWishlist'],
        'updateEntreprise' => [EntrepriseController::class, 'updateEntreprise'],
    ],
];

/**
*Dispatch de la requête
*/
try {
    if (!isset($routes[$httpMethod][$route])) {
        http_response_code(404);
        echo '<h1>404 - Page non trouvée</h1>';
        exit;
    }

    [$controllerClass, $method] = $routes[$httpMethod][$route];

    $controller = new $controllerClass($twig);

    if (!method_exists($controller, $method)) {
        throw new Exception("La méthode {$method} n'existe pas dans le contrôleur {$controllerClass}.");
    }

    if ($route === 'offres_stage') {
        $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);
        $page = $page !== false && $page !== null ? max(1, $page) : 1;
        $controller->$method($page);
    } elseif ($id !== null) {
        $controller->$method($id);
    } else {
        $controller->$method();
    }

} catch (Throwable $e) {
    http_response_code(500);
    echo '<h1>500 - Erreur interne du serveur</h1>';
    echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
}
