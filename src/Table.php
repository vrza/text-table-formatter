<?php

namespace TextTableFormatter;

class Table
{
    private $table;
    private $align;
    private const DEFAULT_ALIGN = 'l';

    public function __construct(iterable $table)
    {
        $this->table = $table ?? [];
    }

    public function setAlignment(iterable $align = []): Table
    {
        $columns = count($this->table) > 0 ? count($this->table[0]) : 0;
        while (count($align) < $columns) {
            $align[] = self::DEFAULT_ALIGN;
        }
        $this->align = $align;
        return $this;
    }

    private static function stripAnsiSequences(string $str)
    {
        return ($str === null)
            ? null
            : preg_replace('#\\x1b[[][^A-Za-z]*[A-Za-z]#', '', $str);
    }

    private static function width(string $str): int
    {
        return strlen(self::stripAnsiSequences($str));
    }

    private static function pad(string $str, int $width, $align = self::DEFAULT_ALIGN): string
    {
        $strWidth = self::width($str);
        if ($width <= $strWidth) {
            return $str;
        }
        $padding = str_repeat(' ', $width - $strWidth);
        if ($align === 'r') {
            return $padding . $str;
        } else {
            return $str . $padding;
        }
    }

    private static function calculateWidths(iterable $table): array
    {
        $widths = [];
        foreach ($table as $row) {
            for ($cellIndex = 0; $cellIndex < count($row); $cellIndex++) {
                $cell = $row[$cellIndex];
                $width = self::width($cell);
                if (!isset($widths[$cellIndex]) || $widths[$cellIndex] < $width) {
                    $widths[$cellIndex] = $width;
                }
            }
        }
        return $widths;
    }

    public function __toString()
    {
        $widths = self::calculateWidths($this->table);
        $output = '';
        for ($rowIndex = 0; $rowIndex < count($this->table); $rowIndex++) {
            $row = $this->table[$rowIndex];
            $separator = '';
            for ($cellIndex = 0; $cellIndex < count($row); $cellIndex++) {
                $cell = $row[$cellIndex];
                $width = $widths[$cellIndex];
                $align = $this->align[$cellIndex];
                $output .= $separator . self::pad($cell, $width, $align);
                $separator = '   ';
            }
            $output .= PHP_EOL;
        }
        return $output;
    }
}
