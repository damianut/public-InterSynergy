<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 * @ORM\Table(name="user")
 */
class User implements UserInterface
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     * @Assert\NotBlank(message="E-mail jest wymagany")
     * @Assert\Email
     */
    private $email;

    /**
     * @ORM\Column(type="json")
     */
    private $roles = [];

    /**
     * @var string The hashed password
     * @ORM\Column(type="string")
     * @Assert\NotBlank(message="Hasło nie może być puste")
     */
    private $password;
    
    /**
     * @ORM\Column(type="boolean")
     */
    private $enabled;
    
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $failedLogin;
    
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\Length(max=255, maxMessage="Imię może składać się maksymalnie z 255 znaków.")
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\Length(max=255, maxMessage="Nazwisko może składać się maksymalnie z 255 znaków.")
     */
    private $surname;

    /**
     * @ORM\Column(type="string", length=15, nullable=true)
     * @Assert\Length(max=15, maxMessage="PESEL może składać się maksymalnie z 15 znaków.")
     */
    private $PESEL;

    /**
     * @ORM\Column(type="string", length=25, nullable=true)
     * @Assert\Length(max=25, maxMessage="NIP może składać się maksymalnie z 25 znaków.")
     */
    private $NIP;

    /**
     * @ORM\Column(type="string", length=400, nullable=true)
     * @Assert\Length(max=400, maxMessage="Adres może składać się maksymalnie z 400 znaków.")
     */
    private $address;

    /**
     * @ORM\Column(type="string", length=3000, nullable=true)
     * @Assert\Length(max=3000, maxMessage="Opis osoby może składać się maksymalnie z 3000 znaków.")
     */
    private $personDescription;

    /**
     * @ORM\Column(type="string", length=1000, nullable=true)
     * @Assert\Length(max=1000, maxMessage="Opis zainteresowań może składać się maksymalnie z 1000 znaków.")
     */
    private $interests;

    /**
     * @ORM\Column(type="string", length=1000, nullable=true)
     * @Assert\Length(max=1000, maxMessage="Opis umiejętności może składać się maksymalnie z 1000 znaków.")
     */
    private $skills;

    /**
     * @ORM\Column(type="string", length=5000, nullable=true)
     * @Assert\Length(max=5000, maxMessage="Opis doświadczenia może składać sie maksymalnie z 5000 znaków.")
     */
    private $experience;
    
    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $birthDate;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $rating;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $CVFilename;
    
    /**
     * @ORM\Column(type="datetime")
     */
    private $registrationDate;

    /**
     * @ORM\Column(type="datetime")
     */
    private $entryUpdatingDate;
    
    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $loginDate;
    
    /**
     * A token sent to the user whose account is not enabled.
     *
     * @ORM\Column(type="string", nullable=true, unique=true)
     */
    private $blockedConfirmationToken;
    
    /**
     * A token sent to the user who want to reset password
     *
     * @ORM\Column(type="string", nullable=true, unique=true)
     */
    private $resetToken;
    
    /**
     * A token which indicate that user is logged in
     *
     * @ORM\Column(type="string", nullable=true, unique=true)
     */
    private $loggedToken;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUsername(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getPassword(): string
    {
        return (string) $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getSalt()
    {
        // not needed when using the "bcrypt" algorithm in security.yaml
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }
    
    public function getEnabled(): ?bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function getFailedLogin(): ?int
    {
        return $this->failedLogin;
    }

    public function setFailedLogin(?int $failedLogin): self
    {
        $this->failedLogin = $failedLogin;

        return $this;
    }
    
    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getSurname(): ?string
    {
        return $this->surname;
    }

    public function setSurname(?string $surname): self
    {
        $this->surname = $surname;

        return $this;
    }

    public function getPESEL(): ?string
    {
        return $this->PESEL;
    }

    public function setPESEL(?string $PESEL): self
    {
        $this->PESEL = $PESEL;

        return $this;
    }

    public function getNIP(): ?string
    {
        return $this->NIP;
    }

    public function setNIP(?string $NIP): self
    {
        $this->NIP = $NIP;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): self
    {
        $this->address = $address;

        return $this;
    }

    public function getPersonDescription(): ?string
    {
        return $this->personDescription;
    }

    public function setPersonDescription(?string $personDescription): self
    {
        $this->personDescription = $personDescription;

        return $this;
    }

    public function getInterests(): ?string
    {
        return $this->interests;
    }

    public function setInterests(?string $interests): self
    {
        $this->interests = $interests;

        return $this;
    }

    public function getSkills(): ?string
    {
        return $this->skills;
    }

    public function setSkills(?string $skills): self
    {
        $this->skills = $skills;

        return $this;
    }

    public function getExperience(): ?string
    {
        return $this->experience;
    }

    public function setExperience(?string $experience): self
    {
        $this->experience = $experience;

        return $this;
    }

    public function getBirthDate(): ?\DateTimeInterface
    {
        return $this->birthDate;
    }

    public function setBirthDate(?\DateTimeInterface $birthDate): self
    {
        $this->birthDate = $birthDate;

        return $this;
    }

    public function getRating(): ?int
    {
        return $this->rating;
    }

    public function setRating(?int $rating): self
    {
        $this->rating = $rating;

        return $this;
    }

    public function getCVFilename(): ?string
    {
        return $this->CVFilename;
    }

    public function setCVFilename(?string $CVFilename): self
    {
        $this->CVFilename = $CVFilename;

        return $this;
    }

    public function getRegistrationDate(): ?\DateTimeInterface
    {
        return $this->registrationDate;
    }

    public function setRegistrationDate(\DateTimeInterface $registrationDate): self
    {
        $this->registrationDate = $registrationDate;

        return $this;
    }
    
    public function getLoginDate(): ?\DateTimeInterface
    {
        return $this->loginDate;
    }

    public function setLoginDate(\DateTimeInterface $loginDate): self
    {
        $this->loginDate = $loginDate;

        return $this;
    }

    public function getEntryUpdatingDate(): ?\DateTimeInterface
    {
        return $this->entryUpdatingDate;
    }

    public function setEntryUpdatingDate(\DateTimeInterface $entryUpdatingDate): self
    {
        $this->entryUpdatingDate = $entryUpdatingDate;

        return $this;
    }

    public function getResetToken(): ?string
    {
        return $this->resetToken;
    }

    public function setResetToken(?string $resetToken): self
    {
        $this->resetToken = $resetToken;

        return $this;
    }
    
    public function getBlockedConfirmationToken(): ?string
    {
        return $this->blockedConfirmationToken;
    }

    public function setBlockedConfirmationToken(?string $blockedConfirmationToken): self
    {
        $this->blockedConfirmationToken = $blockedConfirmationToken;

        return $this;
    }
    
    public function getLoggedToken(): ?string
    {
        return $this->loggedToken;
    }

    public function setLoggedToken(?string $loggedToken): self
    {
        $this->loggedToken = $loggedToken;

        return $this;
    }
}
