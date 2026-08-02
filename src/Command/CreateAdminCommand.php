<?php

namespace App\Command;

use App\Service\FirebaseService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:create-admin', description: 'Create or update a default admin user from environment variables')]
class CreateAdminCommand extends Command
{
    public function __construct(private readonly FirebaseService $firebaseService)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = (string) getenv('ADMIN_EMAIL');
        $password = (string) getenv('ADMIN_PASSWORD');
        $name = (string) getenv('ADMIN_NAME');

        if ($email === '' || $password === '') {
            $output->writeln('<comment>No admin credentials configured. Skipping admin creation.</comment>');
            return Command::SUCCESS;
        }

        $user = $this->firebaseService->getUserByEmail($email);

        if ($user) {
            $this->firebaseService->updateUser($user['key'], [
                'nomComplete' => $name !== '' ? $name : ($user['nomComplete'] ?? 'Administrateur'),
                'email' => $email,
                'type' => 'admin',
                'pwd' => password_hash($password, PASSWORD_DEFAULT),
                'provider' => 'local',
            ]);
            $output->writeln('<info>Admin account updated successfully.</info>');
            return Command::SUCCESS;
        }

        $this->firebaseService->createUser([
            'nomComplete' => $name !== '' ? $name : 'Administrateur',
            'email' => $email,
            'pwd' => password_hash($password, PASSWORD_DEFAULT),
            'type' => 'admin',
            'provider' => 'local',
            'tel' => 0,
        ]);

        $output->writeln('<info>Admin account created successfully.</info>');

        return Command::SUCCESS;
    }
}
