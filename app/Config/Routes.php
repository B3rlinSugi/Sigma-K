<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

/**
 * --------------------------------------------------------------------
 * E-SKLD REST API V1 ROUTES
 * --------------------------------------------------------------------
 */
$routes->group('api/v1', ['namespace' => 'App\Controllers\Api'], static function ($routes) {
    // Health & Readiness Check
    $routes->get('health', 'HealthController::index');

    // Authentication Endpoints
    $routes->group('auth', ['namespace' => 'App\Controllers\Api'], static function ($routes) {
        $routes->post('login', 'AuthController::login');
        $routes->get('me', 'AuthController::me', ['filter' => 'auth']);
        $routes->post('logout', 'AuthController::logout', ['filter' => 'auth']);
        $routes->get('test', 'AuthController::testAuth', ['filter' => 'auth']);
    });

    // Institutions Endpoints (Scope-Aware)
    $routes->group('institutions', ['namespace' => 'App\Controllers\Api', 'filter' => 'auth'], static function ($routes) {
        $routes->get('/', 'InstitutionController::index');
        $routes->get('(:num)', 'InstitutionController::show/$1');
        $routes->get('(:num)/units', 'OrganizationalUnitController::getInstitutionTree/$1');
    });

    // Organizational Units Endpoints
    $routes->group('units', ['namespace' => 'App\Controllers\Api', 'filter' => 'auth'], static function ($routes) {
        $routes->get('(:num)', 'OrganizationalUnitController::show/$1');
        $routes->get('(:num)/positions', 'PositionController::getByUnit/$1');
    });

    // Positions Endpoints
    $routes->group('positions', ['namespace' => 'App\Controllers\Api', 'filter' => 'auth'], static function ($routes) {
        $routes->get('(:num)', 'PositionController::show/$1');
    });

    // User Scope & Grant Inspection Endpoints
    $routes->group('me', ['namespace' => 'App\Controllers\Api', 'filter' => 'auth'], static function ($routes) {
        $routes->get('scopes', 'ScopeController::myScopes');
        $routes->get('access-grants', 'AccessGrantController::myGrants');
    });

    // Access Requests Workflow Endpoints
    $routes->group('access-requests', ['namespace' => 'App\Controllers\Api', 'filter' => 'auth'], static function ($routes) {
        $routes->post('/', 'AccessRequestController::create');
        $routes->get('/', 'AccessRequestController::index');
        $routes->get('(:num)', 'AccessRequestController::show/$1');
        $routes->post('(:num)/approve', 'AccessRequestController::approve/$1');
        $routes->post('(:num)/reject', 'AccessRequestController::reject/$1');
    });

    // Access Grants Management Endpoints
    $routes->group('access-grants', ['namespace' => 'App\Controllers\Api', 'filter' => 'auth'], static function ($routes) {
        $routes->post('(:num)/revoke', 'AccessGrantController::revoke/$1');
    });

    // Admin Review Queue & Workflow Endpoints
    $routes->group('admin', ['namespace' => 'App\Controllers\Api', 'filter' => 'auth'], static function ($routes) {
        $routes->get('submissions/queue', 'AdminWorkflowController::queue');
    });

    // Verifier Queue & Assigned Submissions Endpoints
    $routes->group('verifier', ['namespace' => 'App\Controllers\Api', 'filter' => 'auth'], static function ($routes) {
        $routes->get('submissions/assigned', 'VerifierWorkflowController::assigned');
        $routes->get('submissions/(:num)/review', 'VerifierWorkflowController::reviewDetails/$1');
    });

    // Submissions Lifecycle, Proposed Changes, Versions & Submit Endpoints
    $routes->group('submissions', ['namespace' => 'App\Controllers\Api', 'filter' => 'auth'], static function ($routes) {
        $routes->post('/', 'SubmissionController::create');
        $routes->get('/', 'SubmissionController::index');
        $routes->get('(:num)', 'SubmissionController::show/$1');
        $routes->post('(:num)/submit', 'SubmissionController::submit/$1');
        $routes->post('(:num)/versions', 'SubmissionController::createVersion/$1');

        // Proposed Unit changes
        $routes->post('(:num)/units', 'SubmissionUnitController::create/$1');
        $routes->put('(:num)/units/(:num)', 'SubmissionUnitController::update/$1/$2');
        $routes->delete('(:num)/units/(:num)', 'SubmissionUnitController::delete/$1/$2');

        // Proposed Position changes
        $routes->post('(:num)/positions', 'SubmissionPositionController::create/$1');
        $routes->put('(:num)/positions/(:num)', 'SubmissionPositionController::update/$1/$2');
        $routes->delete('(:num)/positions/(:num)', 'SubmissionPositionController::delete/$1/$2');

        // Gate 1 Admin Review & Verifier Assignment
        $routes->post('(:num)/admin-review/accept', 'AdminWorkflowController::accept/$1');
        $routes->post('(:num)/admin-review/return', 'AdminWorkflowController::returnRevision/$1');
        $routes->post('(:num)/assign-verifier', 'AdminWorkflowController::assignVerifier/$1');

        // Gate 2 Verifier Substantive Review, Revision, Substantive Approval & Recommendation
        $routes->post('(:num)/verifier-review/start', 'VerifierWorkflowController::start/$1');
        $routes->post('(:num)/verifier-review/notes', 'VerifierWorkflowController::addNote/$1');
        $routes->post('(:num)/verifier-review/return', 'VerifierWorkflowController::returnRevision/$1');
        $routes->post('(:num)/verifier-review/approve', 'VerifierWorkflowController::approve/$1');
        $routes->get('(:num)/recommendation', 'VerifierWorkflowController::recommendation/$1');

        // Step 8 Revision Cycle & Resubmission Endpoints
        $routes->get('(:num)/revision', 'RevisionController::show/$1');
        $routes->post('(:num)/revision', 'RevisionController::start/$1');
        $routes->post('(:num)/resubmit', 'RevisionController::resubmit/$1');

        // Step 10 Final Approval & Master Data Promotion Endpoints
        $routes->post('(:num)/approve', 'ApprovalWorkflowController::approve/$1');
        $routes->post('(:num)/promote', 'ApprovalWorkflowController::promote/$1');
        $routes->get('(:num)/approval', 'ApprovalWorkflowController::status/$1');
    });

    // Step 11 Audit Logs Viewing & Export Endpoints
    $routes->group('audit-logs', ['namespace' => 'App\Controllers\Api', 'filter' => 'auth'], static function ($routes) {
        $routes->get('/', 'AuditLogController::index');
        $routes->get('export', 'AuditLogController::export');
        $routes->get('(:num)', 'AuditLogController::show/$1');
    });

    // Step 11 Executive Reporting Dashboard & Export Endpoints
    $routes->group('reports', ['namespace' => 'App\Controllers\Api', 'filter' => 'auth'], static function ($routes) {
        $routes->get('summary', 'ReportController::summary');
        $routes->get('submissions', 'ReportController::submissions');
        $routes->get('institutions', 'ReportController::institutions');
        $routes->get('approvals', 'ReportController::approvals');
        $routes->get('promotions', 'ReportController::promotions');
        $routes->get('export', 'ReportController::export');
    });
});
