<?php

namespace App\Document;

use App\Repository\ContactMessageRepository;
use Doctrine\ODM\MongoDB\Mapping\Attribute as MongoDB;

#[MongoDB\Document(collection: 'contact_messages', repositoryClass: ContactMessageRepository::class)]
class ContactMessage
{
    public const STATUS_NEW  = 'new';
    public const STATUS_READ = 'read';

    public const VALID_STATUSES = [
        self::STATUS_NEW,
        self::STATUS_READ,
    ];

    #[MongoDB\Id]
    private ?string $id = null;

    #[MongoDB\Field(type: 'string')]
    private string $name = '';

    #[MongoDB\Field(type: 'string')]
    private string $email = '';

    #[MongoDB\Field(type: 'string')]
    private string $subject = '';

    #[MongoDB\Field(type: 'string')]
    private string $message = '';

    #[MongoDB\Field(type: 'string')]
    private string $status = self::STATUS_NEW;

    #[MongoDB\Field(type: 'date_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): static
    {
        $this->subject = $subject;
        return $this;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): static
    {
        $this->message = $message;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
