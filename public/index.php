<?php
/**
 * This is the router, the main entry point of the application.
 * It handles the routing and dispatches requests to the appropriate controller methods.
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require "../vendor/autoload.php";

/*use App\Controllers\TaskController...;*/

$loader = new \Twig\Loader\FilesystemLoader('../MyInternship-app/src/Views');
$twig = new \Twig\Environment($loader, [
    'debug' => true
]);

?>
