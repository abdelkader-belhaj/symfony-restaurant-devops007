<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserRepository::class)]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $type = 'client'; // Par défaut, le type est 'client'

    #[ORM\Column(length: 255)]
    private ?string $nomComplete = null;

    #[ORM\Column(nullable: true)]
    private ?int $tel = null;

    #[ORM\Column(length: 255)]
    private ?string $email = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $pwd = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $googleId = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $provider = null;

    #[ORM\Column(options: ['default' => false])]
    private ?bool $rouletteUsed = false;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $rouletteGift = null;

    #[ORM\Column(options: ['default' => false])]
    private ?bool $discountActive = false;

    #[ORM\Column(options: ['default' => false])]
    private ?bool $discountUsed = false;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $notifications = [];

    #[ORM\Column(options: ['default' => 0])]
    private ?int $points = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNomComplete(): ?string
    {
        return $this->nomComplete;
    }

    public function setNomComplete(string $nomComplete): static
    {
        $this->nomComplete = $nomComplete;

        return $this;
    }

    public function getTel(): ?int
    {
        return $this->tel;
    }

    public function setTel(?int $tel): static
    {
        $this->tel = $tel;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPwd(): ?string
    {
        return $this->pwd;
    }

    public function setPwd(?string $pwd): static
    {
        $this->pwd = $pwd;

        return $this;
    }

    public function getGoogleId(): ?string
    {
        return $this->googleId;
    }

    public function setGoogleId(?string $googleId): static
    {
        $this->googleId = $googleId;

        return $this;
    }

    public function getProvider(): ?string
    {
        return $this->provider;
    }

    public function setProvider(?string $provider): static
    {
        $this->provider = $provider;

        return $this;
    }

    public function isGoogleAccount(): bool
    {
        return $this->provider === 'google';
    }

    public function isRouletteUsed(): ?bool
    {
        return $this->rouletteUsed;
    }

    public function setRouletteUsed(bool $rouletteUsed): static
    {
        $this->rouletteUsed = $rouletteUsed;

        return $this;
    }

    public function getRouletteGift(): ?array
    {
        return $this->rouletteGift;
    }

    public function setRouletteGift(?array $rouletteGift): static
    {
        $this->rouletteGift = $rouletteGift;

        return $this;
    }

    public function isDiscountActive(): ?bool
    {
        return $this->discountActive;
    }

    public function setDiscountActive(bool $discountActive): static
    {
        $this->discountActive = $discountActive;

        return $this;
    }

    public function isDiscountUsed(): ?bool
    {
        return $this->discountUsed;
    }

    public function setDiscountUsed(bool $discountUsed): static
    {
        $this->discountUsed = $discountUsed;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getNotifications(): ?array
    {
        return $this->notifications;
    }

    public function setNotifications(?array $notifications): static
    {
        $this->notifications = $notifications;

        return $this;
    }

    public function addNotification(array $notification): static
    {
        $this->notifications[] = $notification;

        return $this;
    }

    public function getPoints(): ?int
    {
        return $this->points;
    }

    public function setPoints(?int $points): static
    {
        $this->points = $points;

        return $this;
    }

    public function addPoints(int $amount): static
    {
        $this->points = ($this->points ?? 0) + $amount;

        return $this;
    }
}
