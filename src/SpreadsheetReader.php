<?php
/*
The MIT License (MIT)

Copyright (c) 2015 PortPHP

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
 */
namespace Port\Spreadsheet;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Port\Reader\CountableReader;

/**
 * Reads spreadsheet files with the help of PHPSpreadsheet
 *
 * PHPSpreadsheet must be installed.
 *
 * @author David de Boer <david@ddeboer.nl>
 *
 * @see https://github.com/PHPOffice/PhpSpreadsheet
 */
class SpreadsheetReader implements CountableReader, \SeekableIterator
{
    /**
     * @var array
     */
    protected $worksheet;

    /**
     * @var int
     */
    protected $headerRowNumber;

    /**
     * @var int
     */
    protected $pointer = 0;

    /**
     * @var array
     */
    protected $columnHeaders;

    /**
     * Total number of rows
     *
     * @var int
     */
    protected $count;

    // phpcs:disable Generic.Files.LineLength.MaxExceeded
    /**
     * @param \SplFileObject $file            Excel file
     * @param int            $headerRowNumber Optional number of header row
     * @param int            $activeSheet     Index of active sheet to read from
     * @param bool           $readOnly        If set to false, the reader take care of the excel formatting (slow)
     * @param int            $maxRows         Maximum number of rows to read
     */
    public function __construct(\SplFileObject $file, $headerRowNumber = null, $activeSheet = null, $readOnly = true, $maxRows = null)
    {
        // phpcs:enable Generic.Files.LineLength.MaxExceeded
        $reader = IOFactory::createReaderForFile($file->getPathName());
        $reader->setReadDataOnly($readOnly);
        /** @var Spreadsheet $excel */
        $excel = $reader->load($file->getPathname());

        if (null !== $activeSheet) {
            $excel->setActiveSheetIndex($activeSheet);
        }
        $sheet = $excel->getActiveSheet();

        if ($maxRows && $maxRows < $sheet->getHighestDataRow()) {
            $maxColumn       = $sheet->getHighestDataColumn();
            $this->worksheet = $sheet->rangeToArray('A1:'.$maxColumn.$maxRows);
        } else {
            $this->worksheet = $excel->getActiveSheet()->toArray();
        }

        if (null !== $headerRowNumber) {
            $this->setHeaderRowNumber($headerRowNumber);
        }
    }

    /**
     * Return the current row as an array
     *
     * If a header row has been set, an associative array will be returned
     *
     * @return array
     */
    public function current()
    {
        $row = $this->worksheet[$this->pointer];

        // If the CSV has column headers, use them to construct an associative
        // array for the columns in this line
        if (!empty($this->columnHeaders)) {
            // Count the number of elements in both: they must be equal.
            // If not, ignore the row
            if (count($this->columnHeaders) === count($row)) {
                return array_combine(array_values($this->columnHeaders), $row);
            }
        } else {
            // Else just return the column values
            return $row;
        }
    }

    /**
     * Get column headers
     *
     * @return array
     */
    public function getColumnHeaders()
    {
        return $this->columnHeaders;
    }

    /**
     * Set column headers
     *
     * @param array $columnHeaders
     */
    public function setColumnHeaders(array $columnHeaders)
    {
        $this->columnHeaders = $columnHeaders;
    }

    /**
     * Rewind the file pointer
     *
     * If a header row has been set, the pointer is set just below the header
     * row. That way, when you iterate over the rows, that header row is
     * skipped.
     */
    public function rewind()
    {
        if (null === $this->headerRowNumber) {
            $this->pointer = 0;
        } else {
            $this->pointer = $this->headerRowNumber + 1;
        }
    }

    /**
     * Set header row number
     *
     * @param int $rowNumber Number of the row that contains column header names
     */
    public function setHeaderRowNumber($rowNumber)
    {
        $this->headerRowNumber = $rowNumber;
        $this->columnHeaders   = $this->worksheet[$rowNumber];
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        $this->pointer++;
    }

    /**
     * {@inheritdoc}
     */
    public function valid()
    {
        return isset($this->worksheet[$this->pointer]);
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        return $this->pointer;
    }

    /**
     * {@inheritdoc}
     */
    public function seek($pointer)
    {
        $this->pointer = $pointer;
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        $count = count($this->worksheet);
        if (null !== $this->headerRowNumber) {
            $count--;
        }

        return $count;
    }

    /**
     * Get a row
     *
     * @param int $number
     *
     * @return array
     */
    public function getRow($number)
    {
        $this->seek($number);

        return $this->current();
    }
}
