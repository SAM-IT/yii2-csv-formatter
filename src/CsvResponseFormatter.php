<?php
namespace SamIT\Yii2;

use yii\base\Arrayable;
use yii\base\Component;
use yii\web\Response;
use yii\web\ResponseFormatterInterface;


/**
 * CsvResponseFormatter formats the given data into CSV response content.
 *
 * It is used by [[Response]] to format response data.
 */
class CsvResponseFormatter extends Component implements ResponseFormatterInterface
{
    public const FORMAT_CSV = 'csv';
    /**
     * @var int Maximum number of bytes to use in memory before using a temp file. Defaults to 20MB
     */
    public $maxMemory = 20971520;

    /**
     * @var boolean Whether to include column names as the first line.
     */
    public $includeColumnNames = true;
    /**
     * @var string The delimiter to use (one character only)
     * @see fputcsv
     */
    public $delimiter = ',';
    /**
     * @var string The field enclosure to use (one character only)
     * @see fputcsv
     */
    public $enclosure = '"';
    /**
     * @var string The escape character to use (one character only)
     * @see fputcsv
     */
    public $escape = '\'';
    /**
     * @var bool Whether to check all rows for column names. This means iterating the data twice but it adds support
     * for non-uniform data (ie rows with missing columns).
     */
    public $checkAllRows = false;
    /**
     * @var string The value to use for NULL values.
     */
    public $nullValue = "(null)";
    /**
     * @var string The value to use for missing columns (only applicable if `$checkAllRows` is true)
     */
    public $missingValue = "(missing)";

    /**
     * Formats the specified response.
     * @param Response $response the response to be formatted.
     * @throws \RuntimeException
     * @return void
     */
    public function format($response): void
    {
        $response->getHeaders()->set('Content-Type', 'text/csv; charset=UTF-8');
        $handle = fopen('php://temp/maxmemory:' . intval($this->maxMemory), 'w+');
        $response->stream = $handle;

        if (!is_iterable($response->data)) {
            throw new \InvalidArgumentException('Response data must be iterable');
        }

        if ($this->includeColumnNames) {
            $columns = $this->getColumnNames($response->data, $this->checkAllRows);
            $this->put($handle, $columns);
        }

        $this->writeData($response, $handle);
        rewind($handle);
    }


    private function writeData(Response $response, $handle)
    {
        foreach($response->data as $row) {

            if ($row instanceof Arrayable) {
                $row = $row->toArray();
            }
            $rowData = [];
            if (isset($columns)) {
                // Map columns.
                foreach($columns as $column) {
                    if (array_key_exists($column, $row)) {
                        $rowData[] = $row[$column] ?? $this->nullValue;
                    } else {
                        $rowData[] = $this->missingValue;
                    }
                }
            } else {
                foreach($row as $column => $value) {
                    $rowData[] = $value ?? $this->nullValue;
                }
            }
            $this->put($handle, $rowData);
        }
    }

    /**
     * @param iterable $data The data set
     * @return string[] The column names found in the data
     */
    protected function getColumnNames(iterable $data, bool $checkAllRows): array
    {
        $columns = [];
        // Use foreach to support arrays and traversable objects.
        foreach($data as $row) {
            foreach($row as $column => $value) {
                if (is_int($column)) {
                    throw new \RuntimeException('You cannot use $checkAllRows in combination with non-associative rows.');
                }
                $columns[$column] = true;
            }
            if (!$checkAllRows) break;
        }
        return array_keys($columns);
    }

    /**
     * Writes a line of CSV data using configuration from the formatter.
     * @param resource $handle The file handle write to
     * @param array $data The data to write
     * @throws \RuntimeException In case CSV data fails to write.
     */
    protected function put($handle, array $data): void
    {
        if (fputcsv($handle, $data, $this->delimiter, $this->enclosure, $this->escape) === false) {
            throw new \RuntimeException("Failed to write CSV data");
        }
    }
}