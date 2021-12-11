<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserCreateCommand extends Command
{
    /**
     * @var UserPasswordEncoderInterface
     */
    private $passwordEncoder;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var UserRepository
     */
    private $userRepository;

    public function __construct(UserPasswordEncoderInterface $passwordEncoder, EntityManagerInterface $em, UserRepository $userRepository)
    {
        $this->passwordEncoder = $passwordEncoder;
        $this->em = $em;
        $this->userRepository = $userRepository;
        parent::__construct();
    }

    protected static $defaultName = 'app:user:create';

    protected function configure()
    {
        $this
            ->setDescription('Create a user.')
            ->addArgument('firstname', InputArgument::REQUIRED, 'The firstname')
            ->addArgument('lastname', InputArgument::REQUIRED, 'The lastname')
            ->addArgument('email', InputArgument::REQUIRED, 'The email')
            ->addArgument('password', InputArgument::REQUIRED, 'The password')
            ->addOption('super-admin', null, InputOption::VALUE_NONE, 'Set the user as super admin')
            ->addOption('inactive', null, InputOption::VALUE_NONE, 'Set the user as inactive')
            ->setHelp(implode("\n", [
                'The <info>app:user:create</info> command creates a user:',
                '<info>php %command.full_name% Martin GILBERT</info>',
                'This interactive shell will ask you for an email and then a password.',
                'You can alternatively specify the email and password as the second and third arguments:',
                '<info>php %command.full_name% Martin GILBERt martin.gilbert@dev-fusion.com change_this_password</info>',
                'You can create a super admin via the super-admin flag:',
                '<info>php %command.full_name% --super-admin</info>',
                'You can create an inactive user (will not be able to log in):',
                '<info>php %command.full_name% --inactive</info>',
            ]))
        ;
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $questions = [];

        if (!$input->getArgument('firstname')) {
            $question = new Question('Please enter the firstname:');
            $question->setValidator(function ($firstname) {
                if (empty($firstname)) {
                    throw new \Exception('Firstname can not be empty');
                }

                return $firstname;
            });
            $questions['firstname'] = $question;
        }

        if (!$input->getArgument('lastname')) {
            $question = new Question('Please enter the lastname:');
            $question->setValidator(function ($lastname) {
                if (empty($lastname)) {
                    throw new \Exception('Lastname can not be empty');
                }

                return $lastname;
            });
            $questions['lastname'] = $question;
        }

        if (!$input->getArgument('email')) {
            $question = new Question('Please enter an email:');
            $question->setValidator(function ($email) {
                if (empty($email)) {
                    throw new \Exception('Email can not be empty');
                }
                if ($this->userRepository->findOneByEmail($email)) {
                    throw new \Exception('Email is already used');
                }

                return $email;
            });
            $questions['email'] = $question;
        }

        if (!$input->getArgument('password')) {
            $question = new Question('Please choose a password:');
            $question->setValidator(function ($password) {
                if (empty($password)) {
                    throw new \Exception('Password can not be empty');
                }

                return $password;
            });
            $question->setHidden(true);
            $questions['password'] = $question;
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
        $user = new User();
        $user
            ->setFirstname($input->getArgument('firstname'))
            ->setLastname($input->getArgument('lastname'))
            ->setEmail($email);

        $user->setPassword(
            $this->passwordEncoder->encodePassword(
                $user,
                $input->getArgument('password')
            )
        );

        if ($input->getOption('inactive')) {
            $user->setEnabled(false);
        } else {
            $user->setEnabled(true);
        }

        if ($input->getOption('super-admin')) {
            $user->setRoles(['ROLE_SUPER_ADMIN']);
        }

        $this->em->persist($user);
        $this->em->flush();

        $io->success(sprintf('Created user with email %s.', $email));

        return 0;
    }
}
