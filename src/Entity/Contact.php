<?php

namespace App\Entity;

use App\Repository\ContactRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;


#[UniqueEntity('phone', message: 'Ce numéro de téléphone est déjà utilisé.')]
#[UniqueEntity('email', message: 'Cet e-mail est déjà utilisé.')]
#[ORM\Entity(repositoryClass: ContactRepository::class)]
class Contact
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'Le prénom est obligatoire.')]
    #[Assert\Length(
        max: 50,
        maxMessage: 'Le prénom ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $firstName = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'Le nom est obligatoire.')]
    #[Assert\Length(
        max: 50,
        maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $lastName = null;

    #[ORM\Column(length: 10, unique: true)]
    #[Assert\NotBlank(message: 'Le numéro de téléphone est obligatoire.')]
    #[Assert\Regex(
        pattern: '/^\d{10}$/',
        message: 'Le numéro de téléphone doit contenir exactement 10 chiffres.'
    )]
    private ?string $phone = null;

    #[ORM\Column(length: 100, nullable: true, unique: true)]
    #[Assert\Email(message: 'Veuillez fournir une adresse email valide.')]
    #[Assert\Length(
        max: 100,
        maxMessage: 'L\'email ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $email = null;

    private ?File $photoFile = null;

    #[ORM\Column(type: "string", nullable: true)]
    private ?string $photo = null;

    #[ORM\OneToMany(mappedBy: 'contact', targetEntity: ContactField::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $customFields;

    #[ORM\ManyToMany(targetEntity: Group::class, inversedBy: 'contacts', cascade: ['persist'])]
    #[ORM\JoinTable(name: 'contact_group')]
    private Collection $groups;

    public function __construct()
    {
        $this->customFields = new ArrayCollection();
        $this->groups = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPhotoFile(): ?File
    {
        return $this->photoFile;
    }

    public function setPhotoFile(?File $photoFile): self
    {
        $this->photoFile = $photoFile;

        return $this;
    }

    public function getPhoto(): ?string
    {
        return $this->photo;
    }

    public function setPhoto($photo): static
    {
        $this->photo = $photo;

        return $this;
    }

    public function getGroups(): Collection
    {
        return $this->groups;
    }

    public function addGroup(Group $group): self
    {
        if(!$this->groups->contains($group)) {
            $this->groups->add($group);
            $group->addContact($this);
        }

        return $this;
    }

    public function removeGroup(Group $group): self
    {
        if($this->groups->removeElement($group)) {
            $group->removeContact($this);
        }

        return $this;
    }

    public function getCustomFields(): Collection
    {
        return $this->customFields;
    }

    public function setCustomFields(Collection $customFields): self
    {
        $this->customFields = $customFields;
        return $this;
    }

    public function addCustomField(ContactField $customField): self
    {
        if (!$this->customFields->contains($customField)) {
            $this->customFields[] = $customField;
            $customField->setContact($this);
        }

        return $this;
    }

    public function removeCustomField(ContactField $customField): self
    {
        if ($this->customFields->removeElement($customField)) {
            if ($customField->getContact() === $this) {
                $customField->setContact(null);
            }
        }

        return $this;
    }
}
