<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\CandidatureModel;
use App\Models\OffreStageModel;
use RuntimeException;
use Twig\Environment;

class OffreStageController extends Controleur
{
    public function __construct(Environment $templateEngine)
    {
        parent::__construct($templateEngine);
        $this->model = new OffreStageModel();
    }

    public function index(int $page = 1): void
    {
        $filters = [
            'keyword' => trim((string) ($_GET['keyword'] ?? '')),
            'location' => trim((string) ($_GET['location'] ?? '')),
        ];

        $resultats = $this->model->rechercherOffresStage($filters, $page);
        $pagination = $this->buildPagination(
            $resultats['current_page'],
            $resultats['total_pages'],
            $filters
        );

        $this->render('trouverUnStage.html.twig', [
            'page_title' => 'Offres de stage - MyInternship',
            'offres_stage' => $resultats['items'],
            'filters' => $filters,
            'total_results' => $resultats['total'],
            'pagination' => $pagination,
            'current_list_url' => $this->buildOffreStageUrl($resultats['current_page'], $filters),
            'wishlist_feedback' => $this->consumeWishlistFeedback(),
            'offre_management_feedback' => $this->consumeOffreManagementFeedback(),
        ]);
    }

    public function show(?int $id = null): void
    {
        if ($id === null) {
            http_response_code(404);
            $this->render('offreDeStage.html.twig', [
                'page_title' => 'Offre introuvable - MyInternship',
                'offre' => null,
            ]);
            return;
        }

        $offre = $this->model->getOffreStageById($id);

        $this->render('offreDeStage.html.twig', [
            'page_title' => 'Détail de l’offre - MyInternship',
            'offre' => $offre,
            'wishlist_feedback' => $this->consumeWishlistFeedback(),
            'candidature_feedback' => $this->consumeCandidatureFeedback(),
            'offre_management_feedback' => $this->consumeOffreManagementFeedback(),
        ]);
    }

    public function showStats(): void
    {
        $this->render('statsOffres.html.twig', [
            'page_title' => 'Statistiques des offres - MyInternship',
            'stats' => $this->model->getOffreStageStatistics(),
        ]);
    }

    public function showCreateForm(): void
    {
        $this->requireOffreManagementAccess();

        $this->renderOffreStageForm(
            [
                'title' => '',
                'entreprise_id' => '',
                'description' => '',
                'salary' => '',
                'duration_weeks' => '',
                'address' => '',
                'location' => '',
                'country' => '',
                'skills_text' => '',
            ],
            'create'
        );
    }

    public function create(): void
    {
        $this->requireOffreManagementAccess();

        $data = $this->getOffreStageFormDataFromRequest();
        $error = $this->validateOffreStageFormData($data);

        if ($error !== null) {
            $this->renderOffreStageForm($data, 'create', null, $error);
            return;
        }

        $offreId = $this->model->createOffreStage($data);

        if ($offreId === false) {
            $this->renderOffreStageForm(
                $data,
                'create',
                null,
                'Impossible de créer cette offre pour le moment.'
            );
            return;
        }

        $_SESSION['offre_management_feedback'] = [
            'type' => 'success',
            'message' => 'L’offre a bien été créée.',
        ];

        $this->redirect('offre_stage', ['id' => $offreId]);
    }

    public function showEditForm(?int $id = null): void
    {
        $this->requireOffreManagementAccess();

        if ($id === null) {
            http_response_code(404);
            $this->render('404.html.twig', ['erreur' => 'Offre non trouvée']);
            return;
        }

        $offre = $this->model->getOffreStageForFormById($id);

        if ($offre === null) {
            http_response_code(404);
            $this->render('404.html.twig', ['erreur' => 'Offre non trouvée']);
            return;
        }

        $this->renderOffreStageForm($offre, 'edit', $id);
    }

    public function update(?int $id = null): void
    {
        $this->requireOffreManagementAccess();

        if ($id === null) {
            http_response_code(404);
            $this->render('404.html.twig', ['erreur' => 'Offre non trouvée']);
            return;
        }

        if ($this->model->getOffreStageForFormById($id) === null) {
            http_response_code(404);
            $this->render('404.html.twig', ['erreur' => 'Offre non trouvée']);
            return;
        }

        $data = $this->getOffreStageFormDataFromRequest();
        $error = $this->validateOffreStageFormData($data);

        if ($error !== null) {
            $this->renderOffreStageForm($data, 'edit', $id, $error);
            return;
        }

        if (!$this->model->updateOffreStage($id, $data)) {
            $this->renderOffreStageForm(
                $data,
                'edit',
                $id,
                'Impossible de modifier cette offre pour le moment.'
            );
            return;
        }

        $_SESSION['offre_management_feedback'] = [
            'type' => 'success',
            'message' => 'L’offre a bien été modifiée.',
        ];

        $this->redirect('offre_stage', ['id' => $id]);
    }

    public function delete(?int $id = null): void
    {
        $this->requireOffreManagementAccess();

        if ($id === null) {
            http_response_code(404);
            $this->render('404.html.twig', ['erreur' => 'Offre non trouvée']);
            return;
        }

        if ($this->model->getOffreStageForFormById($id) === null) {
            http_response_code(404);
            $this->render('404.html.twig', ['erreur' => 'Offre non trouvée']);
            return;
        }

        if (!$this->model->deleteOffreStage($id)) {
            $_SESSION['offre_management_feedback'] = [
                'type' => 'error',
                'message' => 'Impossible de supprimer cette offre pour le moment.',
            ];
            $this->redirect('offre_stage', ['id' => $id]);
        }

        $_SESSION['offre_management_feedback'] = [
            'type' => 'success',
            'message' => 'L’offre a bien été supprimée.',
        ];

        $this->redirect('offres_stage');
    }

    public function showCandidatureForm(?int $id = null): void
    {
        if (!isset($_SESSION['user']) || (int) ($_SESSION['user']['role'] ?? 0) !== 3) {
            $_SESSION['candidature_feedback'] = [
                'type' => 'error',
                'message' => 'Tu dois être connecté comme étudiant pour postuler à une offre.',
            ];
            $this->redirect('connexion');
        }

        if ($id === null) {
            http_response_code(404);
            $this->render('candidatureOffre.html.twig', [
                'page_title' => 'Candidature introuvable - MyInternship',
                'offre' => null,
            ]);
            return;
        }

        $offre = $this->model->getOffreStageById($id);

        if ($offre === null) {
            http_response_code(404);
        }

        $this->render('candidatureOffre.html.twig', [
            'page_title' => 'Postuler à une offre - MyInternship',
            'offre' => $offre,
            'candidature_feedback' => $this->consumeCandidatureFeedback(),
        ]);
    }

    public function ajouterWishlist(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('offres_stage');
        }

        if (!isset($_SESSION['user']) || (int) ($_SESSION['user']['role'] ?? 0) !== 3) {
            $_SESSION['wishlist_feedback'] = [
                'type' => 'error',
                'message' => 'Tu dois être connecté comme étudiant pour ajouter une offre à ta wishlist.',
            ];
            $this->redirectToReturnUrl();
        }

        $offreId = filter_input(INPUT_POST, 'offre_id', FILTER_VALIDATE_INT);
        if ($offreId === false || $offreId === null || $offreId < 1) {
            $_SESSION['wishlist_feedback'] = [
                'type' => 'error',
                'message' => 'Impossible d’ajouter cette offre à la wishlist.',
            ];
            $this->redirectToReturnUrl();
        }

        $compteId = (int) $_SESSION['user']['id'];
        $offreAjoutee = $this->model->ajouterOffreDansWishlist($compteId, (int) $offreId);

        $_SESSION['wishlist_feedback'] = [
            'type' => $offreAjoutee ? 'success' : 'info',
            'message' => $offreAjoutee
                ? 'Offre ajoutée à la wishlist.'
                : 'Cette offre est déjà dans la wishlist.',
        ];

        $this->redirectToReturnUrl();
    }

    public function ajouterCandidature(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('offres_stage');
        }

        if (!isset($_SESSION['user']) || (int) ($_SESSION['user']['role'] ?? 0) !== 3) {
            $_SESSION['candidature_feedback'] = [
                'type' => 'error',
                'message' => 'Tu dois être connecté comme étudiant pour postuler à une offre.',
            ];
            $this->redirectToReturnUrl();
        }

        $offreId = filter_input(INPUT_POST, 'offre_id', FILTER_VALIDATE_INT);
        if ($offreId === false || $offreId === null || $offreId < 1) {
            $_SESSION['candidature_feedback'] = [
                'type' => 'error',
                'message' => 'Impossible d’enregistrer cette candidature.',
            ];
            $this->redirectToReturnUrl();
        }

        $candidatureModel = new CandidatureModel();
        $compteId = (int) $_SESSION['user']['id'];

        if ($candidatureModel->etudiantAPostule($compteId, (int) $offreId)) {
            $_SESSION['candidature_feedback'] = [
                'type' => 'info',
                'message' => 'Tu as déjà postulé à cette offre.',
            ];
            $this->redirectToReturnUrl();
        }

        try {
            $cvPath = $this->uploadCandidatureFile('cv', 'cv');
            $lmPath = $this->uploadCandidatureFile('lettre_motivation', 'lm');
            $candidatureModel->enregistrerCandidature($compteId, (int) $offreId, $cvPath, $lmPath);
        } catch (RuntimeException $e) {
            $_SESSION['candidature_feedback'] = [
                'type' => 'error',
                'message' => $e->getMessage(),
            ];
            $this->redirectToReturnUrl();
        }

        $_SESSION['candidature_feedback'] = [
            'type' => 'success',
            'message' => 'Ta candidature a bien été envoyée.',
        ];

        $this->redirectToReturnUrl();
    }

    private function buildPagination(int $currentPage, int $totalPages, array $filters): array
    {
        $links = [];

        for ($page = 1; $page <= $totalPages; $page++) {
            $links[] = [
                'page' => $page,
                'url' => $this->buildOffreStageUrl($page, $filters),
                'is_current' => $page === $currentPage,
            ];
        }

        return [
            'current_page' => $currentPage,
            'total_pages' => $totalPages,
            'links' => $links,
            'previous_url' => $currentPage > 1 ? $this->buildOffreStageUrl($currentPage - 1, $filters) : null,
            'next_url' => $currentPage < $totalPages ? $this->buildOffreStageUrl($currentPage + 1, $filters) : null,
        ];
    }

    private function buildOffreStageUrl(int $page, array $filters): string
    {
        $query = [
            'route' => 'offres_stage',
            'page' => $page,
        ];

        if ($filters['keyword'] !== '') {
            $query['keyword'] = $filters['keyword'];
        }

        if ($filters['location'] !== '') {
            $query['location'] = $filters['location'];
        }

        return '?' . http_build_query($query);
    }

    private function consumeWishlistFeedback(): ?array
    {
        $feedback = $_SESSION['wishlist_feedback'] ?? null;
        unset($_SESSION['wishlist_feedback']);

        return is_array($feedback) ? $feedback : null;
    }

    private function consumeCandidatureFeedback(): ?array
    {
        $feedback = $_SESSION['candidature_feedback'] ?? null;
        unset($_SESSION['candidature_feedback']);

        return is_array($feedback) ? $feedback : null;
    }

    private function consumeOffreManagementFeedback(): ?array
    {
        $feedback = $_SESSION['offre_management_feedback'] ?? null;
        unset($_SESSION['offre_management_feedback']);

        return is_array($feedback) ? $feedback : null;
    }

    private function requireOffreManagementAccess(): void
    {
        if (!$this->canManageOffres()) {
            $_SESSION['offre_management_feedback'] = [
                'type' => 'error',
                'message' => 'Tu dois être connecté comme admin ou pilote pour gérer les offres.',
            ];
            $this->redirect('mon_espace');
        }
    }

    private function canManageOffres(): bool
    {
        $role = (int) ($_SESSION['user']['role'] ?? 0);

        return $role === 1 || $role === 2;
    }

    private function getOffreStageFormDataFromRequest(): array
    {
        return [
            'title' => trim((string) ($_POST['Titre'] ?? '')),
            'entreprise_id' => filter_input(INPUT_POST, 'Id_Entreprise', FILTER_VALIDATE_INT) ?: 0,
            'description' => trim((string) ($_POST['Description'] ?? '')),
            'salary' => trim((string) ($_POST['Base_Remuneration'] ?? '')),
            'duration_weeks' => filter_input(INPUT_POST, 'Duree_Semaines', FILTER_VALIDATE_INT) ?: 0,
            'address' => trim((string) ($_POST['Adresse'] ?? '')),
            'location' => trim((string) ($_POST['Ville'] ?? '')),
            'country' => trim((string) ($_POST['Pays'] ?? '')),
            'skills_text' => trim((string) ($_POST['Competences'] ?? '')),
        ];
    }

    private function validateOffreStageFormData(array $data): ?string
    {
        if ($data['title'] === '' || mb_strlen($data['title']) > 80) {
            return 'Le titre de l’offre est obligatoire et doit rester court.';
        }

        if ($data['entreprise_id'] < 1) {
            return 'Tu dois sélectionner une entreprise.';
        }

        $entrepriseIds = array_map(
            static fn (array $entreprise): int => (int) $entreprise['id'],
            $this->model->getEntreprisesPourSelection()
        );
        if (!in_array((int) $data['entreprise_id'], $entrepriseIds, true)) {
            return 'L’entreprise sélectionnée est invalide.';
        }

        if ($data['description'] === '') {
            return 'La description de l’offre est obligatoire.';
        }

        if ($data['duration_weeks'] < 1) {
            return 'La durée doit être supérieure à zéro.';
        }

        if ($data['address'] === '' || $data['location'] === '' || $data['country'] === '') {
            return 'L’adresse, la ville et le pays sont obligatoires.';
        }

        if ($data['salary'] !== '' && mb_strlen($data['salary']) > 20) {
            return 'La rémunération est trop longue.';
        }

        return null;
    }

    private function renderOffreStageForm(array $data, string $mode, ?int $id = null, ?string $error = null): void
    {
        $this->render('formOffreStage.html.twig', [
            'page_title' => $mode === 'create'
                ? 'Créer une offre - MyInternship'
                : 'Modifier une offre - MyInternship',
            'mode' => $mode,
            'offre' => $data,
            'offre_id' => $id,
            'entreprises' => $this->model->getEntreprisesPourSelection(),
            'form_error' => $error,
        ]);
    }

    private function uploadCandidatureFile(string $fieldName, string $prefix): string
    {

        if (!isset($_FILES[$fieldName]) || !is_array($_FILES[$fieldName])) {
            throw new RuntimeException('Les fichiers demandés sont obligatoires.');
        }

        $file = $_FILES[$fieldName];

        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Le téléversement des fichiers a échoué.');
        }

        $originalName = (string) ($file['name'] ?? '');
        $extension = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION));
        if ($extension !== 'pdf') {
            throw new RuntimeException('Seuls les fichiers PDF sont autorisés pour les candidatures.');
        }

        $uploadDirectory = __DIR__ . '/../../../public/uploads/candidatures';
        if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0775, true) && !is_dir($uploadDirectory)) {
            throw new RuntimeException('Impossible de préparer le dossier des candidatures.');
        }



        $filename = sprintf(
            '%s_%d_%s.pdf',
            $prefix,
            (int) ($_SESSION['user']['id'] ?? 0),
            bin2hex(random_bytes(8))
        );


        $destination = $uploadDirectory . '/' . $filename;
        if (!move_uploaded_file((string) $file['tmp_name'], $destination)) {
            throw new RuntimeException('Impossible d’enregistrer les fichiers envoyés.');
        }

        return 'uploads/candidatures/' . $filename;
    }

    private function redirectToReturnUrl(): never
    {
        $returnUrl = $_POST['return_url'] ?? '?route=offres_stage';

        if (!is_string($returnUrl) || $returnUrl === '' || $returnUrl[0] !== '?') {
            $returnUrl = '?route=offres_stage';
        }

        header('Location: ' . $returnUrl);
        exit;
    }
}
