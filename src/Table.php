<?php

namespace TextTableFormatter;

use InvalidArgumentException;

class Table
{
    public const LEFT_ALIGN = 'l';
    public const RIGHT_ALIGN = 'r';

    private $table;
    private $align = [];

    private const DEFAULT_ALIGN = self::LEFT_ALIGN;

    /**
     * @param iterable<iterable<mixed>> $table
     * @throws InvalidArgumentException
     */
    public function __construct(iterable $table)
    {
        $validationErrorMessage = 'Argument must be be a two-dimensional iterable';
        $this->table = [];
        foreach ($table as $row) {
            if (!is_iterable($row)) {
                throw new InvalidArgumentException($validationErrorMessage);
            }
            $thisRow = [];
            foreach ($row as $field) {
                $thisRow[] = self::stringify($field);
            }
            $this->table[] = $thisRow;
        }
    }

    /**
     * @param array<string> $align
     * @return Table
     */
    public function setAlignment(array $align = []): Table
    {
        $columns = count($this->table) > 0 ? count(reset($this->table)) : 0;
        while (count($align) < $columns) {
            $align[] = self::DEFAULT_ALIGN;
        }
        $this->align = $align;
        return $this;
    }

    private static function stripAnsiSequences(string $str): string
    {
        return preg_replace('#\\x1b[[][^A-Za-z]*[A-Za-z]#', '', $str) ?? '';
    }

    private static function width(string $str): int
    {
        return strlen(self::stripAnsiSequences($str));
    }

    private static function pad(string $str, int $width, string $align = self::DEFAULT_ALIGN): string
    {
        $strWidth = self::width($str);
        if ($width <= $strWidth) {
            return $str;
        }
        $padding = str_repeat(' ', $width - $strWidth);
        return ($align === self::RIGHT_ALIGN) ? $padding . $str : $str . $padding;
    }

    /**
     * @param array<array<string>> $table
     * @return array<int>
     */
    private static function calculateWidths(array $table): array
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

    /**
     * @param mixed $x
     * @return string
     */
    private static function stringify($x): string
    {
        if (is_null($x)) {
            return '';
        }
        if (is_string($x)) {
            return $x;
        }
        if (is_object($x) && method_exists($x, '__toString')) {
            return $x->__toString();
        }
        if (is_scalar($x)) {
            return strval($x);
        }
        return '[invalid value]';
    }

    public function __toString()
    {
        $widths = self::calculateWidths($this->table);
        $output = '';
        foreach ($this->table as $row) {
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
