<?php

namespace Odb\SmartExportBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Odb\SmartExportBundle\Enum\FilterWidget;
use Odb\SmartExportBundle\Repository\SmartExportColumnRepository;

#[ORM\Entity(repositoryClass: SmartExportColumnRepository::class)]
#[ORM\Table(name: 'smart_export_column')]
#[ORM\HasLifecycleCallbacks]
class SmartExportColumn
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column]
    private ?int $id = null;


    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private ?DateTime $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: 'datetime')]
    private ?DateTime $updatedAt = null;


    #[ORM\Column(options: ['default' => 1])]
    private bool $enabled = true;

    #[ORM\Column(name: 'choice_position', nullable: true)]
    private ?int $choicePosition = null;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'choice_label', type: 'string', length: 128, nullable: true)]
    private $choiceLabel;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'header_label', type: 'string', length: 128, nullable: true)]
    private $headerLabel;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'class_property', type: 'string', length: 128, nullable: true)]
    private $classProperty;

    /**
     * @var string|null
     */
    #[ORM\Column(type: 'string', length: 16, nullable: true)]
    private $interpreter;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'column_group_index', type: 'string', length: 32, nullable: true)]
    private $columnGroupIndex;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'cell_group_index', type: 'string', length: 32, nullable: true)]
    private $cellGroupIndex;

    /**
     * @var bool
     */
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private $filterable = false;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'filter_default_value', type: 'string', length: 255, nullable: true)]
    private $filterDefaultValue;

    /**
     * Which form widget the demo popup uses for this filter — see FilterWidget.
     */
    #[ORM\Column(name: 'filter_widget', type: 'string', length: 16, nullable: true, enumType: FilterWidget::class)]
    private ?FilterWidget $filterWidget = FilterWidget::Auto;

    /**
     * @var SmartExportEngine
     */
    #[ORM\ManyToOne(targetEntity: SmartExportEngine::class, inversedBy: 'columns')]
    private $engine;


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

    public function getId(): ?int
    {
        return $this->id;
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

    public function getChoicePosition(): ?int
    {
        return $this->choicePosition;
    }

    public function setChoicePosition(?int $choicePosition): self
    {
        $this->choicePosition = $choicePosition;

        return $this;
    }

    public function getChoiceLabel(): ?string
    {
        return $this->choiceLabel;
    }

    public function setChoiceLabel(?string $choiceLabel): self
    {
        $this->choiceLabel = $choiceLabel;

        return $this;
    }

    public function getHeaderLabel(): ?string
    {
        return $this->headerLabel;
    }

    public function setHeaderLabel(?string $headerLabel): self
    {
        $this->headerLabel = $headerLabel;

        return $this;
    }

    public function getInterpreter(): ?string
    {
        return $this->interpreter;
    }

    public function setInterpreter(?string $interpreter): self
    {
        $this->interpreter = $interpreter;

        return $this;
    }

    public function getColumnGroupIndex(): ?string
    {
        return $this->columnGroupIndex;
    }

    public function setColumnGroupIndex(?string $columnGroupIndex): self
    {
        $this->columnGroupIndex = $columnGroupIndex;

        return $this;
    }

    public function getCellGroupIndex(): ?string
    {
        return $this->cellGroupIndex;
    }

    public function setCellGroupIndex(?string $cellGroupIndex): self
    {
        $this->cellGroupIndex = $cellGroupIndex;

        return $this;
    }

    public function getClassProperty(): ?string
    {
        return $this->classProperty;
    }

    public function setClassProperty(?string $classProperty): self
    {
        $this->classProperty = $classProperty;

        return $this;
    }

    public function isFilterable(): ?bool
    {
        return $this->filterable;
    }

    public function setFilterable(bool $filterable): self
    {
        $this->filterable = $filterable;

        return $this;
    }

    public function getFilterDefaultValue(): ?string
    {
        return $this->filterDefaultValue;
    }

    public function setFilterDefaultValue(?string $filterDefaultValue): self
    {
        $this->filterDefaultValue = $filterDefaultValue;

        return $this;
    }

    public function getFilterWidget(): FilterWidget
    {
        return $this->filterWidget ?? FilterWidget::Auto;
    }

    public function setFilterWidget(?FilterWidget $filterWidget): self
    {
        $this->filterWidget = $filterWidget ?? FilterWidget::Auto;

        return $this;
    }

    public function getEngine(): ?SmartExportEngine
    {
        return $this->engine;
    }

    public function setEngine(?SmartExportEngine $engine): self
    {
        $this->engine = $engine;

        return $this;
    }
}
