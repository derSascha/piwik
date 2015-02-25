<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Archive;

class DataTableRecordConfiguration {
    public $recordName;
    public $maximumRowsInDataTableLevelZero;
    public $maximumRowsInSubDataTable;
    public $columnToSortByBeforeTruncation;
    public $recursiveLabelSeparator;

    public function __construct($recordName)
    {
        $this->recordName = $recordName;
    }
}