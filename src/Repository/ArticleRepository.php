<?php

namespace App\Repository;

use App\Entity\Article;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Article>
 *
 * @method Article|null find($id, $lockMode = null, $lockVersion = null)
 * @method Article|null findOneBy(array $criteria, array $orderBy = null)
 * @method Article[]    findAll()
 * @method Article[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ArticleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Article::class);
    }

    public function save(Article $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Article $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findBySearch($query)
    {
        return $this->createQueryBuilder('a')
        ->where('a.titre LIKE :query OR a.description LIKE :query')
        ->orderBy('a.datePublication', 'DESC')
        ->setParameter('query', $query)
        ->getQuery()
        ->getResult();
    }

    public function articlePrecedentQuery(Article $article)
    {
        return $this->createQueryBuilder('a')
        ->where('a.datePublication < :datePublication')
        ->orderBy('a.datePublication', 'DESC')
        ->setParameter('datePublication', $article->getDatePublication())
        ->setMaxResults(1)
        ->getQuery()
        ->getOneOrNullResult();
    }

    public function articleSuivantQuery(Article $article)
    {
        return $this->createQueryBuilder('a')
        ->where('a.datePublication > :datePublication')
        ->orderBy('a.datePublication', 'ASC')
        ->setParameter('datePublication', $article->getDatePublication())
        ->setMaxResults(1)
        ->getQuery()
        ->getOneOrNullResult();
    }
}
