<?php

namespace Odb\SmartExportBundle\Model;

use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ExcelStyle
{

    private string $fontFamily = 'Calibri';

    private int $fontSizeSmall = 9;

    private int $fontSizeMedium = 11;

    private int $fontSizeLarge = 14;

    private int $fontSizeExtraLarge = 18;

    private string $numberFormat5 = '#,#####0.00000';

    private string $colorCellRed = 'D50000';

    private string $colorCellGreen = '2E7D32';

    private string $colorCellBlue = '0277BD';

    private string $colorCellOrange = 'FFA000';

    private string $colorCellYellow = 'FFD600';

    private string $colorTextRed = 'C62828';

    private string $colorTextGreen = '1B5E20';

    private string $colorTextBlue = '0277BD';

    private array $main_style;

    private array $main_style_left;

    private array $main_style_center;

    private array $main_style_right;

    private array $head_style;

    private array $title_1;

    private array $title_2;

    private array $title_3;

    public function getFontFamily(): string
    {
        return $this->fontFamily;
    }

    public function setFontFamily(string $fontFamily): static
    {
        $this->fontFamily = $fontFamily;
        return $this;
    }

    public function getFontSizeSmall(): int
    {
        return $this->fontSizeSmall;
    }

    public function setFontSizeSmall(int $fontSizeSmall): static
    {
        $this->fontSizeSmall = $fontSizeSmall;
        return $this;
    }

    public function getFontSizeMedium(): int
    {
        return $this->fontSizeMedium;
    }

    public function setFontSizeMedium(int $fontSizeMedium): static
    {
        $this->fontSizeMedium = $fontSizeMedium;
        return $this;
    }

    public function getFontSizeLarge(): int
    {
        return $this->fontSizeLarge;
    }

    public function setFontSizeLarge(int $fontSizeLarge): static
    {
        $this->fontSizeLarge = $fontSizeLarge;
        return $this;
    }

    public function getFontSizeExtraLarge(): int
    {
        return $this->fontSizeExtraLarge;
    }

    public function setFontSizeExtraLarge(int $fontSizeExtraLarge): static
    {
        $this->fontSizeExtraLarge = $fontSizeExtraLarge;
        return $this;
    }

    public function getNumberFormat5(): string
    {
        return $this->numberFormat5;
    }

    public function setNumberFormat5(string $numberFormat5): static
    {
        $this->numberFormat5 = $numberFormat5;
        return $this;
    }

    public function getColorCellRed(): string
    {
        return $this->colorCellRed;
    }

    public function setColorCellRed(string $colorCellRed): static
    {
        $this->colorCellRed = $colorCellRed;
        return $this;
    }

    public function getColorCellGreen(): string
    {
        return $this->colorCellGreen;
    }

    public function setColorCellGreen(string $colorCellGreen): static
    {
        $this->colorCellGreen = $colorCellGreen;
        return $this;
    }

    public function getColorCellBlue(): string
    {
        return $this->colorCellBlue;
    }

    public function setColorCellBlue(string $colorCellBlue): static
    {
        $this->colorCellBlue = $colorCellBlue;
        return $this;
    }

    public function getColorCellOrange(): string
    {
        return $this->colorCellOrange;
    }

    public function setColorCellOrange(string $colorCellOrange): static
    {
        $this->colorCellOrange = $colorCellOrange;
        return $this;
    }

    public function getColorCellYellow(): string
    {
        return $this->colorCellYellow;
    }

    public function setColorCellYellow(string $colorCellYellow): static
    {
        $this->colorCellYellow = $colorCellYellow;
        return $this;
    }

    public function getColorTextRed(): string
    {
        return $this->colorTextRed;
    }

    public function setColorTextRed(string $colorTextRed): static
    {
        $this->colorTextRed = $colorTextRed;
        return $this;
    }

    public function getColorTextGreen(): string
    {
        return $this->colorTextGreen;
    }

    public function setColorTextGreen(string $colorTextGreen): static
    {
        $this->colorTextGreen = $colorTextGreen;
        return $this;
    }

    public function getColorTextBlue(): string
    {
        return $this->colorTextBlue;
    }

    public function setColorTextBlue(string $colorTextBlue): static
    {
        $this->colorTextBlue = $colorTextBlue;
        return $this;
    }

    public function getMainStyle(): array
    {
        return $this->main_style ?? [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                    ],
                    'outline' => [
                        'borderStyle' => Border::BORDER_THIN,
                    ],
                ],
                'font' => [
                    'size' => $this->fontSizeMedium,
                ],
            ];
    }

    /**
     * @param array $main_style
     */
    public function setMainStyle(array $main_style): static
    {
        $this->main_style = $main_style;
        return $this;
    }

    /**
     * @return array
     */
    public function getMainStyleLeft(): array
    {
        return $this->main_style_left ?? [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                    ],
                    'outline' => [
                        'borderStyle' => Border::BORDER_THIN,
                    ],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT,
                ],
                'font' => [
                    'size' => $this->fontSizeMedium,
                ],
            ];
    }

    /**
     * @param array $main_style_left
     */
    public function setMainStyleLeft(array $main_style_left): static
    {
        $this->main_style_left = $main_style_left;
        return $this;
    }

    /**
     * @return array
     */
    public function getMainStyleCenter(): array
    {
        return $this->main_style_center ??
            [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                    ],
                    'outline' => [
                        'borderStyle' => Border::BORDER_THIN,
                    ],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
                'font' => [
                    'size' => $this->fontSizeMedium,
                ],
            ];
    }

    /**
     * @param array $main_style_center
     */
    public function setMainStyleCenter(array $main_style_center): static
    {
        $this->main_style_center = $main_style_center;
        return $this;
    }

    /**
     * @return array
     */
    public function getMainStyleRight(): array
    {
        return $this->main_style_right ??
            [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                    ],
                    'outline' => [
                        'borderStyle' => Border::BORDER_THIN,
                    ],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_RIGHT,
                ],
                'font' => [
                    'size' => $this->fontSizeMedium,
                ],
            ];
    }

    /**
     * @param array $main_style_right
     */
    public function setMainStyleRight(array $main_style_right): static
    {
        $this->main_style_right = $main_style_right;
        return $this;
    }

    /**
     * @return array
     */
    public function getHeadStyle(): array
    {
        return $this->head_style ?? [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => $this->fontSizeMedium,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'outline' => [
                        'borderStyle' => Border::BORDER_MEDIUM,
                    ],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER_CONTINUOUS,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'color' => ['rgb' => $this->colorTextGreen],
                ],
            ];
    }

    /**
     * @param array $head_style
     */
    public function setHeadStyle(array $head_style): static
    {
        $this->head_style = $head_style;
        return $this;
    }

    /**
     * @return array[]
     */
    public function getTitle1(): array
    {
        return $this->title_1 ?? [
                'font' => [
                    'bold' => true,
                    'size' => $this->fontSizeExtraLarge,
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ];
    }

    /**
     * @param array[] $title_1
     */
    public function setTitle1(array $title_1): static
    {
        $this->title_1 = $title_1;
        return $this;
    }

    /**
     * @return array[]
     */
    public function getTitle2(): array
    {
        return $this->title_2 ?? [
                'font' => [
                    'bold' => true,
                    'size' => $this->fontSizeLarge,
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ];
    }

    /**
     * @param array[] $title_2
     */
    public function setTitle2(array $title_2): static
    {
        $this->title_2 = $title_2;
        return $this;
    }

    /**
     * @return array[]
     */
    public function getTitle3(): array
    {
        return $this->title_3 ?? [
                'font' => [
                    'bold' => true,
                    'size' => $this->fontSizeMedium,
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ];
    }

    /**
     * @param array[] $title_3
     */
    public function setTitle3(array $title_3): static
    {
        $this->title_3 = $title_3;
        return $this;
    }

    /**
     * @return \bool[][]
     */
    public function getTextBold(): array
    {
        return [
            'font' => [
                'bold' => true,
            ],
        ];
    }

    /**
     * @return \string[][][]
     */
    public function getTextRed(): array
    {
        return [
            'font' => [
                'color' => ['rgb' => $this->colorTextRed],
            ],
        ];
    }

    /**
     * @return \string[][][]
     */
    public function getTextGreen(): array
    {
        return [
            'font' => [
                'color' => ['rgb' => $this->colorTextGreen],
            ],
        ];
    }

    /**
     * @return array[]
     */
    public function getCellCenter(): array
    {
        return [
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ];
    }

    /**
     * @return array[]
     */
    public function getCellRight(): array
    {
        return [
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_RIGHT,
            ],
        ];
    }

    /**
     * @return array
     */
    public function getCellRed(): array
    {
        return [
            'font' => [
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'color' => ['rgb' => $this->colorCellRed],
            ],
        ];
    }

    /**
     * @return array[]
     */
    public function getCellYellow(): array
    {
        return [
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'color' => ['rgb' => $this->colorCellYellow],
            ],
        ];
    }

    /**
     * @return array
     */
    public function getCellOrange(): array
    {
        return [
            'font' => [
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'color' => ['rgb' => $this->colorCellOrange],
            ],
        ];
    }

    /**
     * @return array
     */
    public function getCellBlue(): array
    {
        return [
            'font' => [
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'color' => ['rgb' => $this->colorCellBlue],
            ],
        ];
    }

    /**
     * @return array
     */
    public function getCellGreen(): array
    {
        return [
            'font' => [
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'color' => ['rgb' => $this->colorCellGreen],
            ],
        ];
    }

    /**
     * @param int $number
     * @return string
     */
    public static function getColLetter(int $number): string
    {
        $col = '';

        while ($number > 0) {
            $rest = ($number - 1) % 26;
            $letter = chr($rest + 65);
            $col = $letter . $col;
            $number = (int)(($number - ($rest + 1)) / 26);
        }

        return $col;
    }
}