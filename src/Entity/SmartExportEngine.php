<?php

namespace Odb\SmartExportBundle\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;
use Odb\SmartExportBundle\Repository\SmartExportEngineRepository;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\UuidV7;

#[ORM\Entity(repositoryClass: SmartExportEngineRepository::class)]
#[ORM\Table(name: 'smart_export_engine')]
#[ORM\HasLifecycleCallbacks]
class SmartExportEngine
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private UuidV7 $uuid;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE)]
    private DateTime $createdAt;


    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_MUTABLE)]
    private DateTime $updatedAt;

    #[ORM\Column(options: ['default' => true])]
    private bool $enabled = true;

    #[ORM\Column(length: 16, nullable: true)]
    private ?string $code = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $name = null;

    #[ORM\Column(name: 'class_name', length: 128)]
    private string $className;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $description = null;

    /**
     * @var Collection
     */
    #[ORM\OneToMany(mappedBy: 'engine', targetEntity: SmartExportColumn::class, cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['choicePosition' => 'ASC'])]
    private $columns;

    /** ------------------------------------------------------------------------------------------------------------- */
    /**                                        OWN LOGIC                                                              */
    /** ------------------------------------------------------------------------------------------------------------- */

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function updateDate(): void
    {
        $now = new \DateTime('now');
        $this->setUpdatedAt($now);

        if ($this->getCreatedAt() === null) {
            $this->setCreatedAt($now);
        }
    }


    /** ------------------------------------------------------------------------------------------------------------- */
    /**                                      END OWN LOGIC                                                            */
    /** ------------------------------------------------------------------------------------------------------------- */

    public function __construct()
    {
        $this->columns = new ArrayCollection();
        $this->uuid = new UuidV7();
        // $createdAt/$updatedAt are non-nullable typed properties with no default:
        // left uninitialized, updateDate()'s own getCreatedAt() === null check
        // (meant to only set createdAt once, on the FIRST persist) throws
        // "must not be accessed before initialization" instead of returning null.
        $this->createdAt = new DateTime();
        $this->updatedAt = new DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUuid(): UuidV7
    {
        return $this->uuid;
    }

    /**
     * Not used by normal engine creation (the constructor already generates a
     * fresh uuid) — exists for SmartExportEngineTransfer's import, so a config
     * promoted from one environment to another (e.g. dev -> recette) can keep
     * the SAME uuid a host app's `smart_export_popup($uuid)` calls already
     * reference, instead of silently breaking every one of them.
     */
    public function setUuid(UuidV7 $uuid): self
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTime $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function isEnabled(): ?bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): self
    {
        $this->code = $code;

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

    public function getClassName(): ?string
    {
        return $this->className;
    }

    public function setClassName(string $className): self
    {
        $this->className = $className;

        return $this;
    }


    public function getDescription(): ?string
    {
        return $this->description;
    }


    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }



    /**
     * @return Collection|SmartExportColumn[]
     */
    public function getColumns(): Collection
    {
        return $this->columns;
    }

    public function addColumn(SmartExportColumn $column): self
    {
        if (!$this->columns->contains($column)) {
            $this->columns[] = $column;
            $column->setEngine($this);
        }

        return $this;
    }

    public function removeColumn(SmartExportColumn $column): self
    {
        if ($this->columns->removeElement($column)) {
            // set the owning side to null (unless already changed)
            if ($column->getEngine() === $this) {
                $column->setEngine(null);
            }
        }

        return $this;
    }
}
