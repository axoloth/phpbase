<?php

namespace App\Repository;

use App\Entity\ArticleCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * @method ArticleCategory|null find($id, $lockMode = null, $lockVersion = null)
 * @method ArticleCategory|null findOneBy(array $criteria, array $orderBy = null)
 * @method ArticleCategory[]    findAll()
 * @method ArticleCategory[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ArticleCategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ArticleCategory::class);
    }

    /**
     * @return [] Returns an array of ArticleCategory objects
     */
    public function searchBack(Request $request, Session $session, array $data)
    {
        return $this->getBackQuery($data)->getQuery()->getResult();
    }

    /**
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    public function getBackQuery(array $data)
    {
        $query = $this->createQueryBuilder('a');

        if (null !== ($data['search'] ?? null)) {
            $exprOrX = $query->expr()->orX();
            $exprOrX->add($query->expr()->like('a.slug', ':search'));
            $query->where($exprOrX)->setParameter('search', '%'.$data['search'].'%');
        }

        return $query;
    }

    public function findMenuRoots()
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.subcategories', 's')
            ->leftJoin('c.parentCategory', 'p')
            ->where('c.displayedMenu = 1')
            ->andWhere('p IS NULL')
            ->orderBy('c.position', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findRoots()
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.subcategories', 's')
            ->leftJoin('c.parentCategory', 'p')
            ->where('p IS NULL')
            ->orderBy('c.position', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
