<?php

declare(strict_types=1);

namespace App\Controllers;

use Twig\Environment;

/**
 * Base technique commune a tous les controleurs.
 */
abstract class Controleur
{
    protected ?object $model = null;
    protected Environment $templateEngine;

    public function __construct(Environment $templateEngine)
    {
        $this->templateEngine = $templateEngine;
    }

    protected function render(string $template, array $data = []): void
    {
        $data['session'] = $_SESSION;

        echo $this->templateEngine->render($template, $data);
    }

    protected function redirect(string $route, array $params = []): never
    {
        $query = http_build_query(array_merge(['route' => $route], $params));

        header('Location: ?' . $query);
        exit;
    }
}
