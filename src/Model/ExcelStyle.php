<?php

namespace Odb\SmartExportBundle\Model;

use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ExcelStyle
{
    /**
     * @var string
     */
    private $fontFamily = 'Calibri';

    /**
     * @var int
     */
    private $fontSizeSmall = 9;

    /**
     * @var int
     */
    private $fontSizeMedium = 11;

    /**
     * @var int
     */
    private $fontSizeLarge = 14;

    /**
     * @var int
     */
    private $fontSizeExtraLarge = 18;

    /**
     * @var string
     */
    private $numerFormat5 = '#,#####0.00000';

    /**
     * @var string
     */
    private $colorCellRed = 'D50000';

    /**
     * @var string
     */
    private $colorCellGreen = '2E7D32';

    /**
     * @var string
     */
    private $colorCellBlue = '0277BD';

    /**
     * @var string
     */
    private $colorCellOrange = 'FFA000';

    /**
     * @var string
     */
    private $colorCellYellow = 'FFD600';

    /**
     * @var string
     */
    private $colorTextRed = 'C62828';

    /**
     * @var string
     */
    private $colorTextGreen = '1B5E20';

    /**
     * @var string
     */
    private $colorTextBlue = '0277BD';

    /**
     * @var array
     */
    private $main_style;

    /**
     * @var array
     */
    private $main_style_left;

    /**
     * @var array
     */
    private $main_style_center;

    /**
     * @var array
     */
    private $main_style_right;

    /**
     * @var array
     */
    private $head_style;

    /**
     * @var array[]
     */
    private $title_1;

    /**
     * @var array[]
     */
    private $title_2;

    /**
     * @var array[]
     */
    private $title_3;

    /**
     * @return string
     */
    public function getFontFamily(): string
    {
        return $this->fontFamily;
    }

    /**
     * @param string $fontFamily
     */
    public function setFontFamily(string $fontFamily): void
    {
        $this->fontFamily = $fontFamily;
    }

    /**
     * @return int
     */
    public function getFontSizeSmall(): int
    {
        return $this->fontSizeSmall;
    }

    /**
     * @param int $fontSizeSmall
     */
    public function setFontSizeSmall(int $fontSizeSmall): void
    {
        $this->fontSizeSmall = $fontSizeSmall;
    }

    /**
     * @return int
     */
    public function getFontSizeMedium(): int
    {
        return $this->fontSizeMedium;
    }

    /**
     * @param int $fontSizeMedium
     */
    public function setFontSizeMedium(int $fontSizeMedium): void
    {
        $this->fontSizeMedium = $fontSizeMedium;
    }

    /**
     * @return int
     */
    public function getFontSizeLarge(): int
    {
        return $this->fontSizeLarge;
    }

    /**
     * @param int $fontSizeLarge
     */
    public function setFontSizeLarge(int $fontSizeLarge): void
    {
        $this->fontSizeLarge = $fontSizeLarge;
    }

    /**
     * @return int
     */
    public function getFontSizeExtraLarge(): int
    {
        return $this->fontSizeExtraLarge;
    }

    /**
     * @param int $fontSizeExtraLarge
     */
    public function setFontSizeExtraLarge(int $fontSizeExtraLarge): void
    {
        $this->fontSizeExtraLarge = $fontSizeExtraLarge;
    }

    /**
     * @return string
     */
    public function getNumerFormat5(): string
    {
        return $this->numerFormat5;
    }

    /**
     * @param string $numerFormat5
     */
    public function setNumerFormat5(string $numerFormat5): void
    {
        $this->numerFormat5 = $numerFormat5;
    }

    /**
     * @return string
     */
    public function getColorCellRed(): string
    {
        return $this->colorCellRed;
    }

    /**
     * @param string $colorCellRed
     */
    public function setColorCellRed(string $colorCellRed): void
    {
        $this->colorCellRed = $colorCellRed;
    }

    /**
     * @return string
     */
    public function getColorCellGreen(): string
    {
        return $this->colorCellGreen;
    }

    /**
     * @param string $colorCellGreen
     */
    public function setColorCellGreen(string $colorCellGreen): void
    {
        $this->colorCellGreen = $colorCellGreen;
    }

    /**
     * @return string
     */
    public function getColorCellBlue(): string
    {
        return $this->colorCellBlue;
    }

    /**
     * @param string $colorCellBlue
     */
    public function setColorCellBlue(string $colorCellBlue): void
    {
        $this->colorCellBlue = $colorCellBlue;
    }

    /**
     * @return string
     */
    public function getColorCellOrange(): string
    {
        return $this->colorCellOrange;
    }

    /**
     * @param string $colorCellOrange
     */
    public function setColorCellOrange(string $colorCellOrange): void
    {
        $this->colorCellOrange = $colorCellOrange;
    }

    /**
     * @return string
     */
    public function getColorCellYellow(): string
    {
        return $this->colorCellYellow;
    }

    /**
     * @param string $colorCellYellow
     */
    public function setColorCellYellow(string $colorCellYellow): void
    {
        $this->colorCellYellow = $colorCellYellow;
    }

    /**
     * @return string
     */
    public function getColorTextRed(): string
    {
        return $this->colorTextRed;
    }

    /**
     * @param string $colorTextRed
     */
    public function setColorTextRed(string $colorTextRed): void
    {
        $this->colorTextRed = $colorTextRed;
    }

    /**
     * @return string
     */
    public function getColorTextGreen(): string
    {
        return $this->colorTextGreen;
    }

    /**
     * @param string $colorTextGreen
     */
    public function setColorTextGreen(string $colorTextGreen): void
    {
        $this->colorTextGreen = $colorTextGreen;
    }

    /**
     * @return string
     */
    public function getColorTextBlue(): string
    {
        return $this->colorTextBlue;
    }

    /**
     * @param string $colorTextBlue
     */
    public function setColorTextBlue(string $colorTextBlue): void
    {
        $this->colorTextBlue = $colorTextBlue;
    }

    /**
     * @return array
     */
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
    public function setMainStyle(array $main_style): void
    {
        $this->main_style = $main_style;
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
    public function setMainStyleLeft(array $main_style_left): void
    {
        $this->main_style_left = $main_style_left;
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
    public function setMainStyleCenter(array $main_style_center): void
    {
        $this->main_style_center = $main_style_center;
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
    public function setMainStyleRight(array $main_style_right): void
    {
        $this->main_style_right = $main_style_right;
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
    public function setHeadStyle(array $head_style): void
    {
        $this->head_style = $head_style;
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
    public function setTitle1(array $title_1): void
    {
        $this->title_1 = $title_1;
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
    public function setTitle2(array $title_2): void
    {
        $this->title_2 = $title_2;
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
    public function setTitle3(array $title_3): void
    {
        $this->title_3 = $title_3;
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