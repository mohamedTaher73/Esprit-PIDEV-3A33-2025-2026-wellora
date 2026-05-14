<?php

/**
 * WellCare Connect - Simple Verification Test
 * 
 * This script performs basic checks without HTTP requests
 * to avoid session conflicts.
 */

echo "==========================================\n";
echo "WELLCARE CONNECT - BASIC VERIFICATION\n";
echo "==========================================\n\n";

$baseDir = __DIR__;
$passed = 0;
$failed = 0;

function check($name, $condition, $message = '') {
    global $passed, $failed;
    if ($condition) {
        echo "✓ PASS: $name\n";
        $passed++;
    } else {
        echo "✗ FAIL: $name" . ($message ? " - $message" : "") . "\n";
        $failed++;
    }
}

echo "=== 1. DIRECTORY STRUCTURE ===\n\n";

// Check key directories
check('src/Controller exists', is_dir("$baseDir/src/Controller"));
check('src/Entity exists', is_dir("$baseDir/src/Entity"));
check('src/Service exists', is_dir("$baseDir/src/Service"));
check('templates exists', is_dir("$baseDir/templates"));
check('public/uploads exists', is_dir("$baseDir/public/uploads"));
check('uploads/diplomas exists', is_dir("$baseDir/public/uploads/diplomas"));

echo "\n=== 2. KEY FILES ===\n\n";

// Check key files
$keyFiles = [
    'composer.json' => 'Composer configuration',
    'config/packages/security.yaml' => 'Security configuration',
    'config/packages/doctrine.yaml' => 'Doctrine configuration',
    'config/packages/vich_uploader.yaml' => 'VichUploader configuration',
    'config/packages/scheb_2fa.yaml' => '2FA configuration',
    'src/Entity/User.php' => 'User entity',
    'src/Entity/ProfessionalVerification.php' => 'Verification entity',
    'src/Service/DiplomaVerificationService.php' => 'Diploma verification service',
    'src/Controller/AuthController.php' => 'Auth controller',
    'src/Controller/Admin/ProfessionalVerificationController.php' => 'Verification controller',
    'templates/auth/login.html.twig' => 'Login template',
    'templates/admin/verification/dashboard.html.twig' => 'Verification dashboard',
];

foreach ($keyFiles as $file => $description) {
    check("$description exists", file_exists("$baseDir/$file"), "File not found: $file");
}

echo "\n=== 3. CONFIGURATION CHECKS ===\n\n";

// Check composer.json for required packages
$composer = json_decode(file_get_contents("$baseDir/composer.json"), true);
$requiredPackages = ['symfony/security-bundle', 'doctrine/doctrine-bundle', 'vich/uploader-bundle', 'scheb/2fa-bundle', 'smalot/pdfparser'];
$forbiddenPackages = ['friendsofsymfony/user-bundle'];

foreach ($requiredPackages as $package) {
    $found = isset($composer['require'][$package]) || isset($composer['require-dev'][$package]);
    check("Package $package installed", $found);
}

foreach ($forbiddenPackages as $package) {
    $forbidden = isset($composer['require'][$package]) || isset($composer['require-dev'][$package]);
    check("Package $package NOT installed", !$forbidden, "Forbidden package found!");
}

echo "\n=== 4. SECURITY CONFIGURATION ===\n\n";

// Check security.yaml
$security = file_get_contents("$baseDir/config/packages/security.yaml");
check('Security config has firewall', strpos($security, 'firewalls:') !== false);
check('Security config has password_hashers', strpos($security, 'password_hashers:') !== false);
check('Security config has access_control', strpos($security, 'access_control:') !== false);

echo "\n=== 5. ENTITY CHECKS ===\n\n";

// Check User entity has required fields
$userEntity = file_get_contents("$baseDir/src/Entity/User.php");
check('User entity has email field', strpos($userEntity, 'email') !== false);
check('User entity has password field', strpos($userEntity, 'password') !== false);
check('User entity uses DiscriminatorColumn for roles', strpos($userEntity, 'DiscriminatorColumn') !== false || strpos($userEntity, 'DiscriminatorMap') !== false);
check('User entity has UUID', strpos($userEntity, 'uuid') !== false || strpos($userEntity, 'Uuid') !== false);

// Check ProfessionalVerification entity
$verificationEntity = file_get_contents("$baseDir/src/Entity/ProfessionalVerification.php");
check('Verification entity has status', strpos($verificationEntity, 'status') !== false);
check('Verification entity has confidenceScore', strpos($verificationEntity, 'confidenceScore') !== false);
check('Verification entity has diplomaPath', strpos($verificationEntity, 'diplomaPath') !== false);
check('Verification entity has specialty', strpos($verificationEntity, 'specialty') !== false);

echo "\n=== 6. CONTROLLER CHECKS ===\n\n";

// Check controllers have required routes
$authController = file_get_contents("$baseDir/src/Controller/AuthController.php");
check('AuthController has login route', strpos($authController, 'app_login') !== false);
check('AuthController has register routes', strpos($authController, 'register') !== false);

$verificationController = file_get_contents("$baseDir/src/Controller/Admin/ProfessionalVerificationController.php");
check('VerificationController has dashboard', strpos($verificationController, 'dashboard') !== false);
check('VerificationController has approve', strpos($verificationController, 'approve') !== false);
check('VerificationController has reject', strpos($verificationController, 'reject') !== false);

echo "\n=== 7. SERVICE CHECKS ===\n\n";

// Check DiplomaVerificationService
$diplomaService = file_get_contents("$baseDir/src/Service/DiplomaVerificationService.php");
check('DiplomaVerificationService has OCR', strpos($diplomaService, 'extractText') !== false || strpos($diplomaService, 'pdfparser') !== false);
check('DiplomaVerificationService has scoring', strpos($diplomaService, 'confidence') !== false || strpos($diplomaService, 'score') !== false);

echo "\n=== 8. TEMPLATE CHECKS ===\n\n";

// Check key templates
$templates = [
    'templates/base.html.twig',
    'templates/layouts/auth.html.twig',
    'templates/auth/login.html.twig',
    'templates/auth/register-patient.html.twig',
    'templates/auth/register-professional.html.twig',
    'templates/admin/dashboard.html.twig',
    'templates/admin/verification/dashboard.html.twig',
    'templates/admin/verification/view.html.twig',
];

foreach ($templates as $template) {
    check("$template exists", file_exists("$baseDir/$template"));
}

echo "\n=== 9. UPLOAD DIRECTORY ===\n\n";

// Check upload directory is writable
$uploadDir = "$baseDir/public/uploads/diplomas";
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}
check('Diploma upload directory writable', is_writable($uploadDir));

echo "\n=== 10. ROUTES ===\n\n";

// Check routes.yaml
$routes = file_get_contents("$baseDir/config/routes.yaml");
check('Routes configured', strpos($routes, 'controllers:') !== false);

echo "\n==========================================\n";
echo "SUMMARY\n";
echo "==========================================\n";
echo "Passed:  $passed\n";
echo "Failed:  $failed\n";
echo "==========================================\n";

if ($failed > 0) {
    echo "\n⚠ Some checks failed. Please review.\n";
    exit(1);
} else {
    echo "\n✓ All basic checks passed!\n";
    echo "The system is properly configured.\n";
    echo "\nNext steps:\n";
    echo "1. Run: php bin/console doctrine:migrations:migrate\n";
    echo "2. Start server: php bin/console server:run\n";
    echo "3. Test manually using MANUAL_TESTS.md\n";
    exit(0);
}
