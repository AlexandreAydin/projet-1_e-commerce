<?php

namespace App\Entity;

use App\Repository\GoogleOAuthSettingRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GoogleOAuthSettingRepository::class)]
class GoogleOAuthSetting
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = 'Google OAuth';

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imageUrl = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $clientId = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $clientSecret = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $redirectUri = null;

    #[ORM\Column]
    private bool $isEnabled = false;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function getId(): ?int { return $this->id; }

    public function getName(): ?string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }

    public function getImageUrl(): ?string { return $this->imageUrl; }
    public function setImageUrl(?string $imageUrl): static { $this->imageUrl = $imageUrl; return $this; }

    public function getClientId(): ?string { return $this->clientId; }
    public function setClientId(?string $clientId): static { $this->clientId = $clientId; return $this; }

    public function getClientSecret(): ?string { return $this->clientSecret; }
    public function setClientSecret(?string $clientSecret): static { $this->clientSecret = $clientSecret; return $this; }

    public function getRedirectUri(): ?string { return $this->redirectUri; }
    public function setRedirectUri(?string $redirectUri): static { $this->redirectUri = $redirectUri; return $this; }

    public function isEnabled(): bool { return $this->isEnabled; }
    public function setIsEnabled(bool $isEnabled): static { $this->isEnabled = $isEnabled; return $this; }

    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }
    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static { $this->updatedAt = $updatedAt; return $this; }
}