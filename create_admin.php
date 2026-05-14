<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Entity\Administrator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Dotenv\Dotenv;

(new Dotenv())->loadEnv(__DIR__ . '/.env');

$kernel = new \App\Kernel('dev', true);
$kernel->boot();

$container = $kernel->getContainer();
$em = $container->get('doctrine')->getManager();

// Check if admin exists
$existingAdmin = $em->getRepository(Administrator::class)->findOneBy(['email' => 'admin@wellcare.tn']);

if ($existingAdmin) {
    echo "Admin with email admin@wellcare.tn already exists!\n";
} else {
    // Create new admin
    $admin = new Administrator();
    $admin->setEmail('admin@wellcare.tn');
    $admin->setFirstName('Admin');
    $admin->setLastName('WellCare');
    $admin->setIsActive(true);
    $admin->setIsEmailVerified(true);

    // Hash password
    $passwordHasher = $container->get('security.password_hasher');
    $hashedPassword = $passwordHasher->hashPassword($admin, 'Admin@123');
    $admin->setPassword($hashedPassword);

    $em->persist($admin);
    $em->flush();

    echo "Admin user created successfully!\n";
    echo "Email: admin@wellcare.tn\n";
    echo "Password: Admin@123\n";
}
