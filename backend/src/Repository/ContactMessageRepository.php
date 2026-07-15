<?php

namespace App\Repository;

use App\Document\ContactMessage;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\Bundle\MongoDBBundle\Repository\ServiceDocumentRepository;

/** @extends ServiceDocumentRepository<ContactMessage> */
class ContactMessageRepository extends ServiceDocumentRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ContactMessage::class);
    }
}
