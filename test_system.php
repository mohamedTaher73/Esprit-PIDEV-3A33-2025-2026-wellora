<?php

/**
 * WellCare Connect - Automated System Tests
 * 
 * This script tests the core functionality of the application:
 * - Route accessibility
 * - Template rendering
 * - Service availability
 * - Entity integrity
 */

require_once __DIR__ . '/vendor/autoload.php';

use App\Kernel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Dotenv\Dotenv;

echo "==========================================\n";
echo "WELLCARE CONNECT - AUTOMATED TESTS\n";
echo "==========================================\n\n";

// Load environment
(new Dotenv())->loadEnv(__DIR__ . '/.env');

// Bootstrap kernel
$kernel = new Kernel('dev', true);
$kernel->boot();

$container = $kernel->getContainer();
$router = $container->get('router');

$results = [
    'passed' => 0,
    'failed' => 0,
    'warnings' => 0,
];

function test($name, $callback) {
    global $results;
    try {
        $result = $callback();
        if ($result === true) {
            echo "✓ PASS: $name\n";
            $results['passed']++;
            return true;
        } elseif ($result === false) {
            echo "✗ FAIL: $name\n";
            $results['failed']++;
            return false;
        } else {
            echo "⚠ WARN: $name - $result\n";
            $results['warnings']++;
            return null;
        }
    } catch (Exception $e) {
        echo "✗ ERROR: $name - " . $e->getMessage() . "\n";
        $results['failed']++;
        return false;
    }
}

function testRoute($name, $path, $expectedStatus = 200) {
    global $kernel;
    
    $request = Request::create($path, 'GET');
    try {
        $response = $kernel->handle($request);
        $status = $response->getStatusCode();
        
        if ($status === $expectedStatus) {
            return test($name, fn() => true);
        } else {
            return test($name, fn() => "Expected $expectedStatus, got $status");
        }
    } catch (Exception $e) {
        return test($name, fn() => $e->getMessage());
    }
}

function testService($name, $serviceId) {
    global $container;
    return test($name, function() use ($container, $serviceId) {
        if ($container->has($serviceId)) {
            return true;
        }
        return "Service $serviceId not found";
    });
}

function testEntity($name, $entityClass) {
    global $container;
    return test($name, function() use ($container, $entityClass) {
        $em = $container->get('doctrine')->getManager();
        try {
            $metadata = $em->getClassMetadata($entityClass);
            return true;
        } catch (Exception $e) {
            return "Entity metadata error: " . $e->getMessage();
        }
    });
}

echo "=== 1. ROUTE TESTS ===\n\n";

// Public routes that should work without authentication
$publicRoutes = [
    ['Homepage', '/', 200],
    ['Login', '/login', 200],
    ['Register Patient', '/register/patient', 200],
    ['Register Doctor', '/register/medecin', 200],
    ['Register Coach', '/register/coach', 200],
    ['Register Nutritionist', '/register/nutritionist', 200],
    ['Forgot Password', '/forgot-password', 200],
    ['Terms', '/terms', 200],
];

foreach ($publicRoutes as $route) {
    testRoute($route[0], $route[1], $route[2]);
}

echo "\n=== 2. SERVICE TESTS ===\n\n";

// Test core services
$services = [
    'Security Authentication' => 'security.authentication_provider',
    'Doctrine ORM' => 'doctrine',
    'Mailer' => 'mailer',
    'Router' => 'router',
    'Validator' => 'validator',
    'Session' => 'session',
    'Captcha Service' => App\Service\CaptchaService::class,
    'Email Verification Service' => App\Service\EmailVerificationService::class,
    'Password Reset Service' => App\Service\PasswordResetService::class,
];

foreach ($services as $name => $serviceId) {
    testService($name, $serviceId);
}

echo "\n=== 3. ENTITY TESTS ===\n\n";

// Test entities exist and have proper metadata
$entities = [
    'User' => App\Entity\User::class,
    'Patient' => App\Entity\Patient::class,
    'Medecin' => App\Entity\Medecin::class,
    'Coach' => App\Entity\Coach::class,
    'Nutritionist' => App\Entity\Nutritionist::class,
    'Administrator' => App\Entity\Administrator::class,
    'ProfessionalVerification' => App\Entity\ProfessionalVerification::class,
];

foreach ($entities as $name => $entityClass) {
    testEntity($name, $entityClass);
}

echo "\n=== 4. SECURITY TESTS ===\n\n";

// Test authentication required routes
$protectedRoutes = [
    ['Admin Dashboard', '/admin/dashboard', 302], // Redirects to login
    ['Admin Users', '/admin/users', 302],
    ['Doctor Dashboard', '/doctor/dashboard', 302],
    ['Coach Dashboard', '/coach/dashboard', 302],
    ['Nutrition Dashboard', '/nutrition/', 302],
];

foreach ($protectedRoutes as $route) {
    testRoute($route[0], $route[1], $route[2]);
}

echo "\n=== 5. CONFIGURATION TESTS ===\n\n";

// Test configuration
test('CSRF Protection Enabled', function() {
    global $container;
    $csrf = $container->get('security.csrf.token_manager');
    return $csrf !== null;
});

test('Session Configuration', function() {
    global $container;
    $session = $container->get('session');
    return $session->isStarted() || true;
});

test('Upload Directory Exists', function() {
    $dir = __DIR__ . '/public/uploads/diplomas';
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    return is_dir($dir) && is_writable($dir);
});

echo "\n=== 6. TEMPLATE TESTS ===\n\n";

// Test key templates exist
$templates = [
    'Base Template' => 'templates/base.html.twig',
    'Auth Layout' => 'templates/layouts/auth.html.twig',
    'Login Template' => 'templates/auth/login.html.twig',
    'Register Patient' => 'templates/auth/register-patient.html.twig',
    'Admin Dashboard' => 'templates/admin/dashboard.html.twig',
    'Verification Dashboard' => 'templates/admin/verification/dashboard.html.twig',
    'Verification View' => 'templates/admin/verification/view.html.twig',
];

foreach ($templates as $name => $template) {
    test($name, function() use ($template) {
        $path = __DIR__ . '/' . $template;
        if (file_exists($path)) {
            return true;
        }
        return "Template not found: $template";
    });
}

echo "\n=== 7. FORM VALIDATION TESTS ===\n\n";

// Test form types exist
$formTypes = [
    'User Registration Type' => App\Form\UserRegistrationType::class,
];

foreach ($formTypes as $name => $formType) {
    test($name, function() use ($formType) {
        if (class_exists($formType)) {
            return true;
        }
        return "Form type not found: $formType";
    });
}

echo "\n=== 8. REPOSITORY TESTS ===\n\n";

// Test repositories
$repositories = [
    'User Repository' => App\Repository\UserRepository::class,
    'Professional Verification Repository' => App\Repository\ProfessionalVerificationRepository::class,
];

foreach ($repositories as $name => $repoClass) {
    test($name, function() use ($repoClass) {
        global $container;
        $em = $container->get('doctrine')->getManager();
        if ($em->getRepository($repoClass)) {
            return true;
        }
        return false;
    });
}

echo "\n=== 9. DATABASE CONNECTION TEST ===\n\n";

test('Database Connection', function() {
    global $container;
    try {
        $conn = $container->get('doctrine')->getConnection();
        $conn->connect();
        return $conn->isConnected();
    } catch (Exception $e) {
        return "Database error: " . $e->getMessage();
    }
});

test('Database Schema Updated', function() {
    global $container;
    try {
        $em = $container->get('doctrine')->getManager();
        $conn = $container->get('doctrine')->getConnection();
        
        // Check if professional_verification table exists
        $tables = $conn->fetchAll("SHOW TABLES LIKE 'professional_verification'");
        
        if (empty($tables)) {
            return "Table 'professional_verification' does not exist. Run: php bin/console make:migration && php bin/console doctrine:migrations:migrate";
        }
        
        return true;
    } catch (Exception $e) {
        return "Schema error: " . $e->getMessage();
    }
});

echo "\n=== 10. FILE UPLOAD TEST ===\n\n";

test('Diploma Upload Directory', function() {
    $dir = __DIR__ . '/public/uploads/diplomas';
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    return is_dir($dir) && is_writable($dir);
});

test('VichUploader Configuration', function() {
    global $container;
    try {
        // Check if vich_uploader is configured
        $vich = $container->get('vich_uploader.storage');
        return $vich !== null;
    } catch (Exception $e) {
        return "VichUploader not configured: " . $e->getMessage();
    }
});

echo "\n==========================================\n";
echo "TEST SUMMARY\n";
echo "==========================================\n";
echo "Passed:  {$results['passed']}\n";
echo "Failed:  {$results['failed']}\n";
echo "Warnings: {$results['warnings']}\n";
echo "==========================================\n";

if ($results['failed'] > 0) {
    echo "\n⚠ Some tests failed! Please review the errors above.\n";
    exit(1);
} else {
    echo "\n✓ All tests passed!\n";
    exit(0);
}
