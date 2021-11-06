<?php

namespace App\Repository;

use App\Entity\Conference;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\ORM\QueryBuilder;
use App\Entity\Comment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Comment|null find($id, $lockMode = null, $lockVersion = null)
 * @method Comment|null findOneBy(array $criteria, array $orderBy = null)
 * @method Comment[]    findAll()
 * @method Comment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CommentRepository extends ServiceEntityRepository
{
    /**
     * Count of days before rejected will be removed
     */
    private const DAYS_BEFORE_REJECTED_REMOVAL = 7;
    
    /**
     *  Max count of articles per one page
     */
    public const PAGINATOR_PER_PAGE = 2;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Comment::class);
    }

    /**
     * Undocumented function
     *
     * @return integer
     */
    public function countOldRejected(): int
    {
        return $this->getOldRejectedQueryBuilder()->select('COUNT(c.id)')->getQuery()->getSingleScalarResult();
    }

    /**
     * Undocumented function
     *
     * @return integer
     */
    public function deleteOldRejected(): int
    {
        return $this->getOldRejectedQueryBuilder()->delete()->getQuery()->execute();
    }

    /**
     * Undocumented function
     *
     * @return QueryBuilder
     */
    private function getOldRejectedQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.state = :state_rejected or c.state = :state_spam')
            ->andWhere('c.createdAt < :date')
            ->setParameters([
                'state_rejected' => 'rejected',
                'state_spam' => 'spam',
                'date' => new \DateTime(-self::DAYS_BEFORE_REJECTED_REMOVAL.' days'),
            ])
        ;
    }

    /**
     * Undocumented function
     *
     * @param Conference $conference
     * @param integer $offset
     * @return Paginator
     */
    public function getCommentPaginator(Conference $conference, int $offset): Paginator
    {
        $query = $this->createQueryBuilder('c')
            ->andWhere('c.conference = :conference')
            ->addWhere('c.state  = :state')
            ->setParameter('conference', $conference)
            ->setParameter('state', 'published')
            ->orderBy('c.createdAt', 'DESC')
            ->setMaxResults(self::PAGINATOR_PER_PAGE)
            ->setFirstResult($offset)
            ->getQuery();

        return new Paginator($query);
    }

    // /**
    //  * @return Comment[] Returns an array of Comment objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Comment
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
