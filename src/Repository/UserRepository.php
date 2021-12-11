<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * @return [] Returns an array of User objects
     */
    public function search(Request $request, Session $session, array $data, string &$page)
    {
        if ((int) $page < 1) {
            throw new \InvalidArgumentException(sprintf('The page argument can not be less than 1 (value : %s)', $page));
        }
        $firstResult = ($page - 1) * $data['number_by_page'];
        $query = $this->getSearchQuery($data);
        $query->setFirstResult($firstResult)->setMaxResults($data['number_by_page'])->addOrderBy('u.updatedAt', 'DESC');
        $paginator = new Paginator($query);
        if ($paginator->count() <= $firstResult && 1 != $page) {
            if (!$request->get('page')) {
                $session->set('back_user_page', --$page);

                return $this->search($request, $session, $data, $page);
            } else {
                throw new NotFoundHttpException();
            }
        }

        return $paginator;
    }

    /**
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    public function getSearchQuery(array $data)
    {
        $query = $this->createQueryBuilder('u');
        if (null !== ($data['search'] ?? null)) {
            $exprOrX = $query->expr()->orX();
            $exprOrX
                ->add($query->expr()->like('u.firstname', ':search'))
                ->add($query->expr()->like('u.lastname', ':search'))
                ->add($query->expr()->like('u.email', ':search'));
            $query->where($exprOrX)->setParameter('search', '%'.$data['search'].'%');
        }
        if (null !== ($data['role'] ?? null)) {
            $query
                ->andWhere('u.roles LIKE :role')
                ->setParameter('role', '%"'.$data['role'].'"%');
        }

        return $query;
    }

    // /**
    //  * @return User[] Returns an array of User objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('u.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
