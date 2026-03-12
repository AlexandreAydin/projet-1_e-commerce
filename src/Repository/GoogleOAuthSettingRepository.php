<?php

namespace App\Repository;

use App\Entity\GoogleOAuthSetting;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class GoogleOAuthSettingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GoogleOAuthSetting::class);
    }

    public function findActive(): ?GoogleOAuthSetting
    {
        return $this->findOneBy(['isEnabled' => true]);
    }
}