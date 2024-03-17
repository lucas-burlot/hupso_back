<?php

namespace App\Repository;

use App\Entity\Book;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Book>
 *
 * @method Book|null find($id, $lockMode = null, $lockVersion = null)
 * @method Book|null findOneBy(array $criteria, array $orderBy = null)
 * @method Book[]    findAll()
 * @method Book[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BookRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Book::class);
    }


    public function findByFilters(?string $title, ?string $category, ?string $publicationYear): array
    {
        $qb = $this->createQueryBuilder('b');

        if($title) {
            $qb->andWhere('b.title LIKE :title')
                ->setParameter('title', '%'.$title.'%');
        }

        if($category) {
            $qb->andWhere('b.category = :category')
                ->setParameter('category', $category);
        }
        
        if ($publicationYear) {
            // Convertir l'année en dates de début et de fin de l'année
            $startOfYear = new \DateTime($publicationYear . '-01-01');
            $endOfYear = new \DateTime($publicationYear . '-12-31');

            $qb->andWhere('b.publishedAt BETWEEN :startOfYear AND :endOfYear')
                ->setParameter('startOfYear', $startOfYear)
                ->setParameter('endOfYear', $endOfYear);
        }
        return $qb->getQuery()->getResult();
    }

    //    /**
    //     * @return Book[] Returns an array of Book objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('b')
    //            ->andWhere('b.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('b.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Book
    //    {
    //        return $this->createQueryBuilder('b')
    //            ->andWhere('b.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
