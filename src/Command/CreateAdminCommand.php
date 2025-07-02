<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\User;
use App\Service\AuthService;
use Doctrine\ORM\EntityManagerInterface;
use Override;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Throwable;

#[AsCommand(name: 'app:create:admin', description: 'Creates a new admin user',)]
class CreateAdminCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $passwordHasher,
        private AuthService $authService,
    ) {
        parent::__construct();
    }

    #[Override]
    protected function configure(): void
    {
        $this
            ->setDescription('Creates a new admin user')
            ->addArgument('phoneNumber', InputArgument::REQUIRED, 'Phone number of the admin user')
            ->addArgument('password', InputArgument::REQUIRED, 'Password for the admin user');
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $phoneNumber = $input->getArgument('phoneNumber');
        $password = $input->getArgument('password');
        $user = new User();
        $user->setPhoneNumber($phoneNumber);
        $user->setRoles(['ROLE_ADMIN']);
        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);
        $this->em->persist($user);
        $this->em->flush();
        try {
            $this->authService->createUser([
                'phoneNumber' => $phoneNumber,
                'password' => $password,
                'roles' => ['ROLE_ADMIN'],
            ]);
            $output->writeln("<info>Admin user with phone {$phoneNumber} created successfully.</info>");
            return Command::SUCCESS;
        } catch (Throwable $e) {
            $output->writeln('<error>Failed to create admin user: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }
}
