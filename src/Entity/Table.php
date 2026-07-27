<?php

namespace App\Entity;

use App\Repository\TableRepository;
use Doctrine\DBAL\Types\Types;

#[\Doctrine\ORM\Mapping\Entity(repositoryClass: TableRepository::class)]
#[\Doctrine\ORM\Mapping\Table(name: '`table`')]
class Table
{
    #[\Doctrine\ORM\Mapping\Id]
    #[\Doctrine\ORM\Mapping\GeneratedValue]
    #[\Doctrine\ORM\Mapping\Column]
    private ?int $id = null;

    #[\Doctrine\ORM\Mapping\Column(length: 255)]
    private ?string $name = null;

    #[\Doctrine\ORM\Mapping\Column(length: 255)]
    private ?string $email = null;

    #[\Doctrine\ORM\Mapping\Column]
    private ?int $tel = null;

    #[\Doctrine\ORM\Mapping\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $date = null;

    #[\Doctrine\ORM\Mapping\Column(type: Types::TIME_MUTABLE)]
    private ?\DateTime $time = null;

    #[\Doctrine\ORM\Mapping\Column]
    private ?int $numberPeople = null;

    #[\Doctrine\ORM\Mapping\Column(type: Types::TEXT)]
    private ?string $message = null;

    #[\Doctrine\ORM\Mapping\Column(length: 50)]
    private ?string $status = 'available';

    #[\Doctrine\ORM\Mapping\Column(length: 20, nullable: true, options: ['default' => 'pending'])]
    private ?string $reservationStatus = 'pending';

    #[\Doctrine\ORM\Mapping\Column(nullable: true)]
    private ?int $capacity = 2;

    #[\Doctrine\ORM\Mapping\Column(type: Types::JSON, nullable: true)]
    private ?array $reservation = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

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

    public function getTel(): ?int
    {
        return $this->tel;
    }

    public function setTel(int $tel): static
    {
        $this->tel = $tel;

        return $this;
    }

    public function getDate(): ?\DateTime
    {
        return $this->date;
    }

    public function setDate(\DateTime $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getTime(): ?\DateTime
    {
        return $this->time;
    }

    public function setTime(\DateTime $time): static
    {
        $this->time = $time;

        return $this;
    }

    public function getNumberPeople(): ?int
    {
        return $this->numberPeople;
    }

    public function setNumberPeople(int $numberPeople): static
    {
        $this->numberPeople = $numberPeople;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): static
    {
        $this->message = $message;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getReservationStatus(): ?string
    {
        return $this->reservationStatus;
    }

    public function setReservationStatus(?string $reservationStatus): static
    {
        $this->reservationStatus = $reservationStatus;

        return $this;
    }

    public function getCapacity(): ?int
    {
        return $this->capacity;
    }

    public function setCapacity(?int $capacity): static
    {
        $this->capacity = $capacity;

        return $this;
    }

    public function getReservation(): ?array
    {
        return $this->reservation;
    }

    public function setReservation(?array $reservation): static
    {
        $this->reservation = $reservation;

        return $this;
    }
}
