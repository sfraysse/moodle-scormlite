<?php

/* * *************************************************************
 *  This script has been developed for Moodle - http://moodle.org/
 *
 *  You can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
  *
 * ************************************************************* */

// NNX2016 - Added to ensure compatibility since these functions are not in the original file anymore (Moodle 3.1)

/**
 * @package   moodlecore
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class table_spreadsheet_export_format_parent extends core_table\base_export_format {
    var $currentrow;
    var $workbook;
    var $worksheet;
    /**
     * @var object format object - format for normal table cells
     */
    var $formatnormal;
    /**
     * @var object format object - format for header table cells
     */
    var $formatheaders;

    /**
     * should be overriden in child class.
     */
    var $fileextension;

    /**
     * This method will be overridden in the child class.
     */
    function define_workbook() {
    }

    function start_document($filename) {
        $filename = $filename.'.'.$this->fileextension;
        $this->define_workbook();
        // format types
        $this->formatnormal = $this->workbook->add_format();
        $this->formatnormal->set_bold(0);
        $this->formatheaders = $this->workbook->add_format();
        $this->formatheaders->set_bold(1);
        $this->formatheaders->set_align('center');
        // Sending HTTP headers
        $this->workbook->send($filename);
        $this->documentstarted = true;
    }

    function start_table($sheettitle) {
        $this->worksheet = $this->workbook->add_worksheet($sheettitle);
        $this->currentrow=0;
    }

    function output_headers($headers) {
        $colnum = 0;
        foreach ($headers as $item) {
            $this->worksheet->write($this->currentrow,$colnum,$item,$this->formatheaders);
            $colnum++;
        }
        $this->currentrow++;
    }

    function add_data($row) {
        $colnum = 0;
        foreach ($row as $item) {
            $this->worksheet->write($this->currentrow,$colnum,$item,$this->formatnormal);
            $colnum++;
        }
        $this->currentrow++;
        return true;
    }

    function add_seperator() {
        $this->currentrow++;
        return true;
    }

    function finish_table() {
    }

    function finish_document() {
        $this->workbook->close();
        exit;
    }
}


/**
 * @package   moodlecore
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class table_excel_export_format extends table_spreadsheet_export_format_parent {
    var $fileextension = 'xls';

    function define_workbook() {
        global $CFG;
        require_once("$CFG->libdir/excellib.class.php");
        // Creating a workbook
        $this->workbook = new MoodleExcelWorkbook("-");
    }

}


/**
 * @package   moodlecore
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class table_ods_export_format extends table_spreadsheet_export_format_parent {
    var $fileextension = 'ods';
    function define_workbook() {
        global $CFG;
        require_once("$CFG->libdir/odslib.class.php");
        // Creating a workbook
        $this->workbook = new MoodleODSWorkbook("-");
    }
}


/**
 * @package   moodlecore
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class table_text_export_format_parent extends core_table\base_export_format {
    protected $seperator = "tab";
    protected $mimetype = 'text/tab-separated-values';
    protected $ext = '.txt';
    protected $myexporter;
    protected $filename;

    public function __construct() {
        $this->myexporter = new csv_export_writer($this->seperator, '"', $this->mimetype);
    }

    public function start_document($filename) {
        $this->filename = $filename;
        $this->documentstarted = true;
        $this->myexporter->set_filename($filename, $this->ext);
    }

    public function start_table($sheettitle) {
        //nothing to do here
    }

    public function output_headers($headers) {
        $this->myexporter->add_data($headers);
    }

    public function add_data($row) {
        $this->myexporter->add_data($row);
        return true;
    }

    public function finish_table() {
        //nothing to do here
    }

    public function finish_document() {
        $this->myexporter->download_file();
        exit;
    }

    /**
     * Format a row of data.
     * @param array $data
     */
    protected function format_row($data) {
        $escapeddata = array();
        foreach ($data as $value) {
            $escapeddata[] = '"' . str_replace('"', '""', $value) . '"';
        }
        return implode($this->seperator, $escapeddata) . "\n";
    }
}


/**
 * @package   moodlecore
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class table_tsv_export_format extends table_text_export_format_parent {
    protected $seperator = "tab";
    protected $mimetype = 'text/tab-separated-values';
    protected $ext = '.txt';
}

require_once($CFG->libdir . '/csvlib.class.php');
/**
 * @package   moodlecore
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class table_csv_export_format extends table_text_export_format_parent {
    protected $seperator = "comma";
    protected $mimetype = 'text/csv';
    protected $ext = '.csv';
}

/**
 * @package   moodlecore
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class table_xhtml_export_format extends core_table\base_export_format {
    function start_document($filename) {
        header("Content-Type: application/download\n");
        header("Content-Disposition: attachment; filename=\"$filename.html\"");
        header("Expires: 0");
        header("Cache-Control: must-revalidate,post-check=0,pre-check=0");
        header("Pragma: public");
        //html headers
        echo <<<EOF
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html
  PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml"
  xml:lang="en" lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<style type="text/css">/*<![CDATA[*/

.flexible th {
white-space:normal;
}
th.header, td.header, div.header {
border-color:#DDDDDD;
background-color:lightGrey;
}
.flexible th {
white-space:nowrap;
}
th {
font-weight:bold;
}

.generaltable {
border-style:solid;
}
.generalbox {
border-style:solid;
}
body, table, td, th {
font-family:Arial,Verdana,Helvetica,sans-serif;
font-size:100%;
}
td {
    border-style:solid;
    border-width:1pt;
}
table {
    border-collapse:collapse;
    border-spacing:0pt;
    width:80%;
    margin:auto;
}

h1, h2 {
    text-align:center;
}
.bold {
font-weight:bold;
}
.mdl-align {
    text-align:center;
}
/*]]>*/</style>
<title>$filename</title>
</head>
<body>
EOF;
        $this->documentstarted = true;
    }

    function start_table($sheettitle) {
        $this->table->sortable(false);
        $this->table->collapsible(false);
        echo "<h2>{$sheettitle}</h2>";
        $this->table->start_html();
    }

    function output_headers($headers) {
        $this->table->print_headers();
        echo html_writer::start_tag('tbody');
    }

    function add_data($row) {
        $this->table->print_row($row);
        return true;
    }

    function add_seperator() {
        $this->table->print_row(NULL);
        return true;
    }

    function finish_table() {
        $this->table->finish_html();
    }

    function finish_document() {
        echo "</body>\n</html>";
        exit;
    }

    function format_text($text, $format=FORMAT_MOODLE, $options=NULL, $courseid=NULL) {
        if (is_null($options)) {
            $options = new stdClass;
        }
        //some sensible defaults
        if (!isset($options->para)) {
            $options->para = false;
        }
        if (!isset($options->newlines)) {
            $options->newlines = false;
        }
        if (!isset($options->smiley)) {
            $options->smiley = false;
        }
        if (!isset($options->filter)) {
            $options->filter = false;
        }
        return format_text($text, $format, $options);
    }
}






