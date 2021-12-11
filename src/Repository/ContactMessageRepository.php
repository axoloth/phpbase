<?php

namespace App\Repository;

use App\Entity\ContactMessage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use \Doctrine\ORM\Tools\Pagination\Paginator;
use \Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\HttpFoundation\Session\Session;
use \Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @method ContactMessage|null find($id, $lockMode = null, $lockVersion = null)
 * @method ContactMessage|null findOneBy(array $criteria, array $orderBy = null)
 * @method ContactMessage[]    findAll()
 * @method ContactMessage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ContactMessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ContactMessage::class);
    }

    /**
     * @return [] Returns an array of ContactMessage objects
     */
    public function searchBack(Request $request, Session $session, array $data, string &$page)
    {
        if ((int) $page < 1) {
            throw new \InvalidArgumentException(sprintf("The page argument can not be less than 1 (value : %s)", $page));
        }
        $firstResult = ($page - 1) * $data['number_by_page'];
        $query = $this->getBackQuery($data);
        $query->setFirstResult($firstResult)->setMaxResults($data['number_by_page'])->addOrderBy('c.id', 'DESC');
        $paginator = new Paginator($query);
        if ($paginator->count() <= $firstResult && $page != 1) {
            if (!$request->get('page')) {
                $session->set('back_contact_message_page', --$page);
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
    public function getBackQuery(array $data)
    {
        $query = $this->createQueryBuilder('c');
        if (null !== ($data['search'] ?? null)) {
            $exprOrX = $query->expr()->orX();
            $exprOrX->add($query->expr()->like('c.firstname', ':search'))->add($query->expr()->like('c.lastname', ':search'))->add($query->expr()->like('c.email', ':search'))->add($query->expr()->like('c.subject', ':search'));
            $query->where($exprOrX)->setParameter('search', '%' . $data['search'] . '%');
        }
        return $query;
    }
}
