<?php

namespace App\Repository;

use App\Entity\WeeklyCommission;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WeeklyCommission>
 */
class WeeklyCommissionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WeeklyCommission::class);
    }

    public function findByEmployeeAndWeek(Employee $employee, \DateTime $weekStart, \DateTime $weekEnd): ?WeeklyCommission
    {
        return $this->createQueryBuilder('wc')
            ->where('wc.employee = :employee')
            ->andWhere('wc.weekStart = :weekStart')
            ->andWhere('wc.weekEnd = :weekEnd')
            ->setParameter('employee', $employee)
            ->setParameter('weekStart', $weekStart)
            ->setParameter('weekEnd', $weekEnd)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findUnvalidatedCommissions(): array
    {
        return $this->createQueryBuilder('wc')
            ->where('wc.validated = false')
            ->orderBy('wc.weekEnd', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findValidatedCommissions(): array
    {
        return $this->createQueryBuilder('wc')
            ->where('wc.validated = true')
            ->andWhere('wc.paid = false')
            ->orderBy('wc.weekEnd', 'ASC')
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return WeeklyCommission[] Returns an array of WeeklyCommission objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //     *     return $this->createQueryBuilder('w')
    //     *         ->andWhere('w.exampleField = :val')
    //     *         ->setParameter('val', $value)
    //     *         ->orderBy('w.id', 'ASC')
    //     *         ->setMaxResults(10)
    //     *         ->getQuery()
    //     *         ->getResult()
    //     *     ;
    //    }

    //    public function findOneBySomeField($value): ?WeeklyCommission
    //    {
    //        return $this->createQueryBuilder('w')
    //            ->andWhere('w.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
