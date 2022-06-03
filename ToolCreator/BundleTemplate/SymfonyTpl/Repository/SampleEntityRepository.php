<?php

namespace App\Bundle\SymfonyTpl\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use App\Bundle\SymfonyTpl\Entity\SampleEntity;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method SampleEntity|null find($id, $lockMode = null, $lockVersion = null)
 * @method SampleEntity|null findOneBy(array $criteria, array $orderBy = null)
 * @method SampleEntity[]    findAll()
 * @method SampleEntity[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SampleEntityRepository extends ServiceEntityRepository
{
    /**
     * Store total filtered record
     * @var $totalFilteredRecord
     */
    protected $totalFilteredRecord;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SampleEntity::class);
    }

    /**
     * @param string $search
     * @param array $searchableColumns
     * @param null $orderBy
     * @param null $order
     * @param null $limit
     * @param null $offset
     * @return mixed
     */
    public function getSampleEntityData($search = '', $searchableColumns = [], $orderBy = null, $order = null, $limit = null, $offset = null)
    {
        $qb = $this->createQueryBuilder('a');
        //JOIN
        if (!empty($searchableColumns) && !empty($search)) {
            foreach ($searchableColumns as $column) {
                //WHERE
            }
            $qb->setParameter('search', '%'.$search.'%');
        }
        if (!empty($orderBy)) {
            if (in_array($orderBy, $cols)) {                
                $qb->orderBy("b.$orderBy", $order);
            }
            else {
            $qb->orderBy("a.$orderBy", $order);
            }  
        }
        //set total filtered record without limit
        $this->setTotalFilteredRecord($qb->getQuery()->getResult());

        if(!empty($offset))
            $qb->setFirstResult($offset);

        if(!empty($limit))
            $qb->setMaxResults($limit);

        $qb->groupBy('a.sample_primary_id');

        $query = $qb->getQuery();

        return $query->getResult();
    }

    /**
     * @return mixed
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getTotalRecords()
    {
        $qb = $this->createQueryBuilder('a')->select("count(a.sample_primary_id)");
        $query = $qb->getQuery();
        return $query->getSingleScalarResult();
    }

    /**
     * @param $totalFilteredRecord
     */
    public function setTotalFilteredRecord($totalFilteredRecord)
    {
        $this->totalFilteredRecord = $totalFilteredRecord;
    }

    /**
     * @return mixed
     */
    public function getTotalFilteredRecord()
    {
        return $this->totalFilteredRecord;
    }
}
