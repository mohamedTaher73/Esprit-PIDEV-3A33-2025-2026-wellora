<?php

namespace App\Command;

use App\Entity\Administrator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Creates an admin user',
)]
class CreateAdminUserCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $admin = new Administrator();
        $admin->setEmail('admin@wellcare.tn');
        $admin->setFirstName('Admin');
        $admin->setLastName('User');
        $admin->setIsActive(true);
        $admin->setIsEmailVerified(true);

        $hashedPassword = $this->passwordHasher->hashPassword($admin, 'Admin@123');
        $admin->setPassword($hashedPassword);

        $this->entityManager->persist($admin);
        $this->entityManager->flush();

        $output->writeln('Admin user created successfully!');
        $output->writeln('Email: admin@wellcare.tn');
        $output->writeln('Password: Admin@123');
        $output->writeln('Role: ROLE_ADMIN (automatic from Administrator class)');

        return Command::SUCCESS;
    }
}
