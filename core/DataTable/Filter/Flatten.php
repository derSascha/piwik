<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\DataTable\Filter;

use Piwik\Common;
use Piwik\DataTable\BaseFilter;
use Piwik\DataTable\Row;
use Piwik\DataTable;

/**
 * Adds processed metrics columns to a {@link DataTable} using metrics that already exist.
 *
 * Columns added are:
 *
 * - **conversion_rate**: percent value of `nb_visits_converted / nb_visits
 * - **nb_actions_per_visit**: `nb_actions / nb_visits`
 * - **avg_time_on_site**: in number of seconds, `round(visit_length / nb_visits)`. Not
 *                         pretty formatted.
 * - **bounce_rate**: percent value of `bounce_count / nb_visits`
 *
 * Adding the **filter_add_columns_when_show_all_columns** query parameter to
 * an API request will trigger the execution of this Filter.
 *
 * _Note: This filter must be called before {@link ReplaceColumnNames} is called._
 *
 * **Basic usage example**
 *
 *     $dataTable->filter('AddColumnsProcessedMetrics');
 *
 * @api
 */
class Flatten extends BaseFilter
{

    /**
     * Separator for building recursive labels (or paths)
     * @var string
     */
    public $recursiveLabelSeparator = ' - ';

    private $subTableFilter;

    private $rows = array();

    public function __construct($table, $recursiveLabelSeparator, $subTableFilter = null)
    {
        $this->recursiveLabelSeparator = $recursiveLabelSeparator;

        if (!is_array($subTableFilter)) {
            $subTableFilter = array($subTableFilter, array());
        }

        $this->subTableFilter = $subTableFilter;
        parent::__construct($table);
    }

    /**
     * Adds the processed metrics. See {@link AddColumnsProcessedMetrics} for
     * more information.
     *
     * @param DataTable $table
     */
    public function filter($table)
    {
        $table->applyQueuedFilters();

        $rows = $table->getRowsWithoutSummaryRow();

        foreach ($rows as $id => $row) {
            $this->flattenRow($row, $id);
        }

        $table->setRows($this->rows);
    }

    /**
     * @param Row $row
     * @param DataTable $dataTable
     * @param mixed $rowId
     * @param string $labelPrefix
     * @param bool $parentLogo
     */
    private function flattenRow(Row $row, $rowId, $labelPrefix = '', $parentLogo = false)
    {
        $label = $row->getColumn('label');

        if ($label !== false) {
            $label = trim($label);

            if (substr($label, 0, 1) == '/' && $this->recursiveLabelSeparator == '/') {
                $label = substr($label, 1);
            } elseif ($rowId === DataTable::ID_SUMMARY_ROW && $label !== DataTable::LABEL_SUMMARY_ROW) {
                $label = ' - ' . $label;
            }

            $label = $labelPrefix . $label;
            $row->setColumn('label', $label);
        }

        $logo = $row->getMetadata('logo');
        if ($logo === false && $parentLogo !== false) {
            $logo = $parentLogo;
            $row->setMetadata('logo', $logo);
        }

        /** @var DataTable $subTable */
        $subTable = $row->getSubtable();
        $row->removeSubtable();

        if (empty($subTable)) {
            $this->rows[] = $row;
        } else {
            if ($this->subTableFilter[0]) {
                $subTable->filter($this->subTableFilter[0], $this->subTableFilter[1]);
            }

            $subTable->applyQueuedFilters();

            $prefix = $label . $this->recursiveLabelSeparator;
            foreach ($subTable->getRows() as $rowId => $row) {
                $this->flattenRow($row, $rowId, $prefix, $logo);
            }
        }
    }
}