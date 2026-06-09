<?php
/*
 * $Horde: horde/lib/Data.php,v 1.10.2.16 2004/02/10 20:51:00 jan Exp $
 *
 * Copyright 1999-2003 Jan Schneider <jan@horde.org>
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 */

// Import actions

/** @constant IMPORT_MAPPED Import already mapped csv data. */
define('IMPORT_MAPPED', 1);

/** @constant IMPORT_DATETIME Map date and time entries of csv data. */
define('IMPORT_DATETIME', 2);

/** @constant IMPORT_CSV Import generic csv data. */
define('IMPORT_CSV', 3);

/** @constant IMPORT_OUTLOOK Import MS Outlook data. */
define('IMPORT_OUTLOOK', 4);

/** @constant IMPORT_ICALENDAR Import vCalendar/iCalendar data. */
define('IMPORT_ICALENDAR', 5);

/** @constant IMPORT_VCARD Import vCards. */
define('IMPORT_VCARD', 6);

/** @constant IMPORT_TSV Import generic tsv data. */
define('IMPORT_TSV', 7);

/** @constant IMPORT_MULBERRY Import Mulberry address book data */
define('IMPORT_MULBERRY', 8);

/** @constant IMPORT_PINE Import Pine address book data. */
define('IMPORT_PINE', 9);

define('EXPORT_CSV', 100);
define('EXPORT_ICALENDAR', 101);
define('EXPORT_VCARD', 102);
define('EXPORT_TSV', 103);

/**
 * Abstract class to allow data exchange between the Horde applications and
 * external sources
 *
 * @author  Jan Schneider <jan@horde.org>
 * @version $Revision: 1.1.1.1 $
 * @since   Horde 1.3
 * @package horde.data
 */
class Data {

    function import()
    {
    }

    function export()
    {
    }

    function importFile()
    {
    }

    function exportFile()
    {
    }

    function mapDate()
    {
    }

}

class Data_csv extends Data {

    function importFile($filename, $header=false, $delimiter=',')
    {
        $fp = fopen($filename, 'r');
        if (!$fp) return false;

        $data = array();

        if ($header) $head = fgetcsv($fp, 1024, $delimiter);

        while ($line = fgetcsv($fp, 1024, $delimiter)) {
            if (!isset($head)) $data[] = $line;
            else {
                $newline = array();
                for ($i=0; $i<count($head); $i++) {
                    //if (!empty($line[$i]))
                    $newline[$head[$i]] = empty($line[$i]) ? '' : $line[$i];
                }
                $data[] = $newline;
            }
        }

        fclose($fp);
        return $data;
    }

    function exportFile($filename, $data, $header=false, $delimiter=',')
    {
        if (!is_array($data) || count($data) == 0) {
            return;
        }

        include_once HORDE_BASE . '/lib/Browser.php';
        $browser = new Browser();

        $export = '';

        if ($header) {
            $head = current($data);
            while (list($key,) = each($head)) {
                if (!empty($key)) {
                    $export .= '"' . addslashes($key) . '"';
                }
                $export .= ',';
            }
            $export = substr($export, 0, -1) . "\n";
        }

        foreach ($data as $row) {
            foreach ($row as $cell) {
                if (!empty($cell) || $cell === 0) {
                    $export .= '"' . addslashes($cell) . '"';
                }
                $export .= ',';
            }
            $export = substr($export, 0, -1) . "\n";
        }

        header('Content-Type: application/csv');
        if ($browser->getBrowser() == 'opera') {
            $filename = strtr($filename, ' ', '_');
        }
        if ($browser->hasQuirk('break_disposition_header')) {
            header('Content-Disposition: filename=' . $filename);
        } else {
            header('Content-Disposition: attachment; filename=' . $filename);
        }
        if ($browser->hasQuirk('cache_ssl_downloads')) {
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
        }
        header('Content-Length: ' . strlen($export));

        echo $export;
    }

    function mapDate($date, $type, $delimiter, $format)
    {
        switch ($type) {

        case 'date':
            $dates = explode($delimiter, $date);
            $index = array_flip(explode('/', $format));
            $date_arr = array('mday' => $dates[$index['mday']],
                              'month' => $dates[$index['month']],
                              'year' => $dates[$index['year']]);
            break;

        case 'time':
            $dates = explode($delimiter, $date);
            if ($format == 'ampm') {
                if (strpos(strtolower($dates[count($dates)-1]), 'pm') !== false) {
                    if ($dates[0] !== '12') {
                        $dates[0] += 12;
                    }
                } elseif ($dates[0] == '12') {
                    $dates[0] = '0';
                }
                $dates[count($dates)-1] = (int) $dates[count($dates)-1];
            }
            $date_arr = array('hour' => $dates[0],
                              'min' => $dates[1],
                              'sec' => $dates[2]);
            break;

        case 'datetime':
            break;

        }

        return $date_arr;
    }

}

class Data_rfc2425 extends Data {

    var $cards = array();
    var $version;

    function importFile($filename)
    {
        $lines = file($filename);
        return $this->import(implode("\n", $lines));
    }

    function import($text)
    {
        $lines = explode("\n", $text);
        $data = array();
        // unfolding
        foreach ($lines as $line) {
            if (preg_match('/^[ \t]/', $line) && count($data) > 1) {
                $data[count($data)-1] .= substr($line, 1);
            } elseif (trim($line) != '') {
                $data[] = $line;
            }
            $data[count($data)-1] = trim($data[count($data)-1]);
        }
        $lines = $data;
        $data = array();
        foreach ($lines as $line) {
            $line = preg_replace('/"([^":]*):([^":]*)"/', "\"\\1\x00\\2\"", $line);
            list($name, $value) = explode(':', $line, 2);
            $name = preg_replace('/\0/', ':', $name);
            $value = preg_replace('/\0/', ':', $value);
            $name = explode(';', $name);
            $params = array();
            if (isset($name[1])) {
                for ($i = 1; $i < count($name); $i++) {
                    $name_value = explode('=', $name[$i]);
                    $paramname = $name_value[0];
                    $paramvalue = isset($name_value[1]) ? $name_value[1] : null;
                    if (isset($paramvalue)) {
                        preg_match_all('/("((\\\\"|[^"])*)"|[^,]*)(,|$)/', $paramvalue, $split);
                        for ($j = 0; $j < count($split[1]) - 1; $j++) {
                            $params[$paramname][] = stripslashes($split[1][$j]);
                        }
                    } else {
                        $params[$paramname] = true;
                    }
                }
            }

            // Store unsplitted value for vCard 2.1
            $value21 = $value;

            $value = preg_replace('/\\\\,/', "\x00", $value);
            $values = explode(',', $value);
            for ($i = 0; $i < count($values); $i++) {
                $values[$i] = preg_replace('/\0/', ',', $values[$i]);
                $values[$i] = preg_replace('/\\\\n/', "\n", $values[$i]);
                $values[$i] = preg_replace('/\\\\,/', ',', $values[$i]);
                $values[$i] = preg_replace('/\\\\\\\\/', '\\', $values[$i]);
            }

            $data[] = array('name' => strtoupper($name[0]),
                            'params' => $params,
                            'values' => $values,
                            'value21' => $value21);
        }
        $start = 0;
        $this->cards = $this->_build($data, $start);

        return $this->cards;
    }

    function _build($data, &$i)
    {
        $objects = array();

        while (isset($data[$i])) {
            if (strtoupper($data[$i]['name']) != 'BEGIN') {
                Horde::raiseMessage(_("Import Error: ") . sprintf(_("Expected \"BEGIN\" in the line %d."), $i), HORDE_ERROR);
                return;
            }
            $type = $data[$i]['values'][0];
            $object = array('type' => $type);
            $object['objects'] = array();
            $object['params'] = array();
            $i++;
            while (isset($data[$i]) && strtoupper($data[$i]['name']) != 'END') {
                if ($data[$i]['name'] == 'BEGIN') {
                    $object['objects'][] = $this->_build($data, $i);
                } else {
                    $object['params'][] = $data[$i];
                    if (strtoupper($type) == 'VCARD' && strtoupper($data[$i]['name']) == 'VERSION') {
                        $object['version'] = $data[$i]['values'][0];
                    }
                }
                $i++;
            }
            if (!isset($data[$i])) {
                Horde::raiseMessage(_("Import Error: ") . _("Unexpected end of file."), HORDE_ERROR);
                return;
            }
            if ($data[$i]['values'][0] != $type) {
                Horde::raiseMessage(_("Import Error: ") . _("Type mismatch.") . sprintf(_("Expected \"END:%s\" in line %d."), $type, $i), HORDE_ERROR);
                return;
            }
            $objects[] = $object;
            $i++;
        }

        return $objects;
    }

    function read($attribute, $index = 0)
    {
        if ($index == 0 && $this->version < 3.0) {
            $value = $attribute['value21'];
        } else {
            $value = $attribute['values'][$index];
        }

        if (isset($attribute['params']['ENCODING'])) {
            switch ($attribute['params']['ENCODING'][0]) {

            case 'QUOTED-PRINTABLE':
                $value = quoted_printable_decode($value);
                break;

            }
        }

        return $value;
    }

    function getValues($attribute, $card = 0)
    {
        $values = array();
        $attribute = strtoupper($attribute);
        $this->version = isset($this->cards[$card]['version']) ? $this->cards[$card]['version'] : null;

        for ($i = 0; $i < count($this->cards[$card]['params']); $i++) {
            $param = $this->cards[$card]['params'][$i];
            if ($param['name'] == $attribute) {
                for ($j = 0; $j < count($param['values']); $j++) {
                    $values[] = array('value' => $this->read($param, $j), 'params' => $param['params']);
                }
            }
        }

        return $values;
    }

    function mapDate($datestring)
    {
        @list($date, $time) = explode('T', $datestring);

        if (strlen($date) == 10) {
            $dates = explode('-', $date);
        } else {
            $dates = array();
            $dates[] = substr($date, 0, 4);
            $dates[] = substr($date, 4, 2);
            $dates[] = substr($date, 6, 2);
        }

        $date_arr = array('mday' => $dates[2],
                          'month' => $dates[1],
                          'year' => $dates[0]);

        if (isset($time)) {
            @list($time, $zone) = explode('Z', $time);
            if (strstr($time, ':') !== false) {
                $times = explode(':', $time);
            } else {
                $times = array();
                $times[] = substr($time, 0, 2);
                $times[] = substr($time, 2, 2);
                $times[] = substr($time, 4);
            }

            $date_arr['hour'] = $times[0];
            $date_arr['min'] = $times[1];
            $date_arr['sec'] = $times[2];
        }

        return $date_arr;
    }
}

class Data_tsv extends Data {

    function importFile($filename, $header=false, $delimiter = "\t")
    {
        $fp = fopen($filename, 'r');
        if (!$fp) {
            return false;
        }

        $contents = preg_split( "/(\r\n|\n|\r)/", rtrim(fread($fp, filesize($filename))));

        fclose($fp);

        $data = array();

        if ($header) {
            $head = explode($delimiter, array_shift($contents));
        }

        foreach ($contents as $line) {
            $line = explode($delimiter, $line);
            if (!isset($head)) {
                $data[] = $line;
            } else {
                $newline = array();
                for ($i = 0; $i < count($head); $i++) {
                    //if (!empty($line[$i]))
                    $newline[$head[$i]] = empty($line[$i]) ? '' : $line[$i];
                }
                $data[] = $newline;
            }
        }

        return $data;
    }

    function import()
    {
    }

    function export()
    {
    }

    function exportFile($filename, $data, $header=false, $delimiter = "\t")
    {
        if (!is_array($data) || count($data) == 0) {
            return;
        }

        include_once HORDE_BASE . '/lib/Browser.php';
        $browser = new Browser();

        $export = '';

        if ($header) {
            $head = current($data);
            while (list($key,) = each($head)) {
                if (!empty($key)) {
                    $export .= '"' . addslashes($key) . '"';
                }
                $export .= "\t";
            }
            $export = substr($export, 0, -1) . "\n";
        }

        foreach ($data as $row) {
            foreach ($row as $cell) {
                if (!empty($cell) || $cell === 0) {
                    $export .= '"' . addslashes($cell) . '"';
                }
                $export .= "\t";
            }
            $export = substr($export, 0, -1) . "\n";
        }

        header('Content-Type: application/csv');
        if ($browser->getBrowser() == 'opera') {
            $filename = strtr($filename, ' ', '_');
        }
        if ($browser->hasQuirk('break_disposition_header')) {
            header('Content-Disposition: filename=' . $filename);
        } else {
            header('Content-Disposition: attachment; filename=' . $filename);
        }
        if ($browser->hasQuirk('cache_ssl_downloads')) {
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
        }
        header('Content-Length: ' . strlen($export));

        echo $export;
    }

    function mapDate()
    {
    }

}
