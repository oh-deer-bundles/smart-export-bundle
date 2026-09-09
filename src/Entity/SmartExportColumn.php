<?php

namespace Odb\SmartExportBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;
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

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE)]
    private ?DateTime $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_MUTABLE)]
    private ?DateTime $updatedAt = null;

    #[ORM\Column(options: ['default' => 1])]
    private bool $enabled = true;

    #[ORM\Column(name: 'choice_position', nullable: true)]
    private ?int $choicePosition = null;

    #[ORM\Column(length: 128, nullable: true)]
    private ?string $label = null;

    #[ORM\Column(name: 'class_property', length: 128, nullable: true)]
    private ?string $classProperty = null;

    /**
     * Whether this column is offered at all as a selectable export field in the
     * Colonnes panel. Independent of $filterable: a column can be filterable
     * without being exportable, and vice versa. Default true preserves the
     * pre-existing behavior (every enabled column is offered).
     */
    #[ORM\Column(name: 'column_display', options: ['default' => 1])]
    private bool $columnDisplay = true;

    /**
     * When columnDisplay is true, whether this column starts pre-checked in the
     * Colonnes panel. Default false preserves the pre-existing behavior (nothing
     * pre-checked).
     */
    #[ORM\Column(name: 'selected_by_default', options: ['default' => 0])]
    private bool $selectedByDefault = false;

    #[ORM\Column(length: 16, nullable: true)]
    private ?string $interpreter = null;

    #[ORM\Column(name: 'cell_group_index', length: 32, nullable: true)]
    private ?string $cellGroupIndex = null;

    #[ORM\Column(options: ['default' => 0])]
    private bool $filterable = false;

    /**
     * When filterable is true, whether the filter widget is rendered in the
     * Filtres panel at all, vs. applied silently server-side with
     * filterDefaultValue and no visible UI. Default true preserves the
     * pre-existing behavior (every filterable column's widget shows).
     */
    #[ORM\Column(name: 'filter_display', options: ['default' => 1])]
    private bool $filterDisplay = true;

    #[ORM\Column(name: 'filter_default_value', length: 255, nullable: true)]
    private ?string $filterDefaultValue = null;

    /**
     * Which form widget the demo popup uses for this filter — see FilterWidget.
     */
    #[ORM\Column(name: 'filter_widget', length: 16, nullable: true, enumType: FilterWidget::class)]
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

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(?string $label): self
    {
        $this->label = $label;

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

    public function isColumnDisplay(): bool
    {
        return $this->columnDisplay;
    }

    public function setColumnDisplay(bool $columnDisplay): self
    {
        $this->columnDisplay = $columnDisplay;

        return $this;
    }

    public function isSelectedByDefault(): bool
    {
        return $this->selectedByDefault;
    }

    public function setSelectedByDefault(bool $selectedByDefault): self
    {
        $this->selectedByDefault = $selectedByDefault;

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

    public function isFilterDisplay(): bool
    {
        return $this->filterDisplay;
    }

    public function setFilterDisplay(bool $filterDisplay): self
    {
        $this->filterDisplay = $filterDisplay;

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
