<?php

namespace App\DataFixtures;

use App\Entity\Article;
use App\Entity\ArticleCategory;
use App\Entity\ArticlePositionCategory;
use App\Entity\Config;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class AppFixtures extends Fixture
{
    private $passwordEncoder;

    private $faker;

    public function __construct(UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->passwordEncoder = $passwordEncoder;
        $this->faker = Faker\Factory::create('fr_FR');
    }

    public function load(ObjectManager $manager)
    {
        $config = new Config();
        $value = '{"name": "DevFusion", "admin_email": "martin.gilbert@dev-fusion.com", "developer_email": "martin3129@gmail.com"}';
        $value = json_decode($value, true);
        $config->setName('app')
            ->setValue([
                'type' => 'json',
                'value' => $value,
            ]);
        $manager->persist($config);
        for ($i = 0; $i < 1; ++$i) {
            $user = (new User())
                ->setEmail('martin3129@gmail.com')
                ->setFirstname('Martin')
                ->setLastname('GILBERT')
                ->setEnabled(true)
                ->setRoles(['ROLE_SUPER_ADMIN']);
            $user->setPassword(
                $this->passwordEncoder->encodePassword(
                    $user,
                    '12345'
                )
            );
            $manager->persist($user);
        }

        $categories = [];
        for ($i = 0; $i < 4; ++$i) {
            $categoryI = new ArticleCategory();
            $categoryI
                ->setDisplayedHome(true)
                ->setDisplayedMenu(true)
                ->setPosition($i)
                ->setName(sprintf('Cat-%s', $i));
            $categories[] = $categoryI;
            $manager->persist($categoryI);
            for ($j = 0; $j < 4; ++$j) {
                $categoryJ = new ArticleCategory();
                $categoryJ
                    ->setDisplayedHome(true)
                    ->setDisplayedMenu(true)
                    ->setPosition($j)
                    ->setName(sprintf('Cat-%s-%s', $i, $j))
                    ->setParentCategory($categoryI);
                $categories[] = $categoryJ;
                $manager->persist($categoryJ);
                for ($k = 0; $k < 4; ++$k) {
                    $categoryK = new ArticleCategory();
                    $categoryK
                        ->setDisplayedHome(true)
                        ->setDisplayedMenu(true)
                        ->setPosition($k)
                        ->setName(sprintf('Cat-%s-%s-%s', $i, $j, $k))
                        ->setParentCategory($categoryJ);
                    $categories[] = $categoryK;
                    $manager->persist($categoryK);
                }
            }
        }
        $manager->flush();
        foreach ($categories as $key => $category) {
            for ($i = 0; $i < 4; ++$i) {
                $description = $this->faker->realText(300);
                $content = '';
                for ($j = 0; $j < 5; ++$j) {
                    $content .= '<p>';
                    $content .= $this->faker->realText(1024);
                    $content .= '</p>';
                }
                $createdAt = $this->faker->dateTimeThisYear();
                $article = (new Article())
                ->setAuthor($user)
                ->setTitle("Article $key $i")
                ->setDescription($description)
                ->setContent($content)
                ->setCreatedAt($createdAt)
                ->setUpdatedAt($createdAt);
                $articlePositionCategory = new ArticlePositionCategory();
                $article->addPositionCategory($articlePositionCategory);
                $category->addPositionArticle($articlePositionCategory);
                $manager->persist($article);
            }
        }

        $manager->flush();
    }
}
