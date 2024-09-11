<?php

namespace Odb\SmartExportBundle\Entity;

use DateTime;


class SmartExportColumn
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var DateTime
     */
    private $createdAt;

    /**
     * @var DateTime
     */
    private $updatedAt;

    /**
     * @var bool
     */
    private $enabled = 1;

    /**
     * @var int
     */
    private $choicePosition;

    /**
     * @var string|null
     */
    private $choiceLabel;

    /**
     * @var string|null
     */
    private $headerLabel;

    /**
     * @var string|null
     */
    private $classProperty;

    /**
     * @var string|null
     */
    private $interpreter;

    /**
     * @var string|null
     */
    private $columnGroupIndex;

    /**
     * @var string|null
     */
    private $cellGroupIndex;

    /**
     * @var SmartExportEngine
     */
    private $engine;


    /** ------------------------------------------------------------------------------------------------------------- */
    /**                                        OWN LOGIC                                                              */
    /** ------------------------------------------------------------------------------------------------------------- */


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
