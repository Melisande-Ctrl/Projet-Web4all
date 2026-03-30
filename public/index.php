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
use App\Controllers\HomeController;
use App\Controllers\LoginController;
use App\Controllers\InternshipController;
use App\Controllers\EntrepriseController;
use App\Controllers\AdminController;
use App\Controllers\PiloteController;
use App\Controllers\EtudiantController;

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
$route = $_GET['route'] ?? 'home';
$httpMethod = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;

/**
*Définition des routes
*/
$routes = [
    'GET' => [
        'home' => [HomeController::class, 'index'],
        'connexion' => [LoginController::class, 'showLoginForm'],
        'internships' => [InternshipController::class, 'index'],
        'internship_show' => [InternshipController::class, 'show'],
        'entreprises' => [EntrepriseController::class, 'pageRechercheEntreprises'],
        'entreprise_show' => [EntrepriseController::class, 'ficheEntreprise'],
        'entreprise_edit' => [EntrepriseController::class, 'formUpdateEntreprise'],
        'updateEntreprise' => [EntrepriseController::class, 'updateEntreprise'],
        'entreprise_delete' => [EntrepriseController::class, 'deleteEntreprise'],
        'mentions_legales' => [HomeController::class, 'legalNotices'],
        'admin_dashboard' => [AdminController::class, 'showDashboard'],
        'pilote_dashboard' => [PiloteController::class, 'showDashboard'],
        'etudiant_dashboard' => [EtudiantController::class, 'showDashboard'],
    ],
    'POST' => [
        'login' => [LoginController::class, 'login'],
        'logout' => [LoginController::class, 'logout'],
    ],
];

/**
*Dispatch de la requête
*/
try {
    if (!isset($routes[$httpMethod][$route])) {
        http_response_code(404);
        echo '<h1>404 - Page non trouvée</h1>';
//        echo $twig->render('404.html.twig', [
//            'page_title' => 'Erreur 404',
//            'message' => 'La page demandée n\'existe pas.',
//        ]);
        exit;
    }

    [$controllerClass, $method] = $routes[$httpMethod][$route];

    $controller = new $controllerClass($twig);

    if (!method_exists($controller, $method)) {
        throw new Exception("La méthode {$method} n'existe pas dans le contrôleur {$controllerClass}.");
    }

    if ($id !== null) {
        $controller->$method($id);
    } else {
        $controller->$method();
    }
//    if (!method_exists($controller, $method)) {
//        throw new Exception("La méthode {$method} n'existe pas dans {$controllerClass}.");
//    }
//
//    // Appel de la méthode avec les paramètres appropriés
//    call_user_func_array([$controller, $method], array_filter([$id, $page], fn($v) => $v !== null));
} catch (Throwable $e) {
    http_response_code(500);
    echo '<h1>500 - Erreur interne du serveur</h1>';
    echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
//    echo $twig->render('500.html.twig', [
//        'page_title' => 'Erreur serveur',
//        'message' => $e->getMessage(),
//    ]);
//    error_log($e->getMessage());
}
