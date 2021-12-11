<?php

namespace App\Command;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

class UserDemoteCommand extends Command
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var UserRepository
     */
    private $userRepository;

    public function __construct(EntityManagerInterface $em, UserRepository $userRepository)
    {
        $this->em = $em;
        $this->userRepository = $userRepository;
        parent::__construct();
    }

    protected static $defaultName = 'app:user:demote';

    protected function configure()
    {
        $this
            ->setDescription('Demotes a user by removing a role')
            ->addArgument('email', InputArgument::REQUIRED, 'The email')
            ->addArgument('role', InputArgument::REQUIRED, 'The role to remove')
            ->setHelp(implode("\n", [
                'The <info>app:user:demote</info> command remove role to a user:',
                '<info>php %command.full_name% martin.gilbert@dev-fusion.com</info>',
                'This interactive shell will first ask you for a role.',
                'You can alternatively specify the role as a second argument:',
                '<info>php %command.full_name% martin.gilbert@dev-fusion.com ROLE_ADMIN</info>',
            ]))
        ;
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $questions = [];

        if (!$input->getArgument('email')) {
            $question = new Question('Please give the email:');
            $question->setValidator(function ($email) {
                if (empty($email)) {
                    throw new \Exception('email can not be empty');
                }

                if (!$this->userRepository->findOneByEmail($email)) {
                    throw new \Exception('No user found with this email');
                }

                return $email;
            });
            $questions['email'] = $question;
        }

        if (!$input->getArgument('role')) {
            $question = new Question('Please enter the role to remove:');
            $question->setValidator(function ($role) {
                if (empty($role)) {
                    throw new \Exception('role can not be empty');
                }

                return $role;
            });
            $questions['role'] = $question;
        }

        foreach ($questions as $name => $question) {
            $answer = $this->getHelper('question')->ask($input, $output, $question);
            $input->setArgument($name, $answer);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = $input->getArgument('email');
        $role = $input->getArgument('role');
        $user = $this->userRepository->findOneByEmail($email);

        $roles = $user->getRoles();

        if (!in_array($role, $roles)) {
            $io->error(sprintf('The user %s has not role %s.', $email, $role));

            return 1;
        } else {
            array_splice($roles, array_search($role, $roles), 1);
            $user->setRoles($roles);
            $this->em->flush();
            $io->success(sprintf('The role %s has been removed to user %s.', $role, $email));

            return 0;
        }
    }
}
