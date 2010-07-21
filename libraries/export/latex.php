<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Set of functions used to build dumps of tables
 *
 * @package phpMyAdmin-Export-Latex
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/* Messages used in default captions */
$GLOBALS['strLatexContent'] = __('Content of table @TABLE@');
$GLOBALS['strLatexContinued'] = __('(continued)');
$GLOBALS['strLatexStructure'] = __('Structure of table @TABLE@');

/**
 *
 */
if (isset($plugin_list)) {
    $hide_structure = false;
    if ($plugin_param['export_type'] == 'table' && !$plugin_param['single_table']) {
        $hide_structure = true;
    }
    $plugin_list['latex'] = array(
        'text' => __('LaTeX'),
        'extension' => 'tex',
        'mime_type' => 'application/x-tex',
        'options' => array(
            array('type' => 'bool', 'name' => 'caption', 'text' => __('Include table caption')),
            ),
        'options_text' => __('Options'),
        );
    /* Structure options */
    if (!$hide_structure) {
        $plugin_list['latex']['options'][] =
            array('type' => 'bgroup', 'name' => 'structure', 'text' => __('Structure'), 'force' => 'data');
        $plugin_list['latex']['options'][] =
            array('type' => 'text', 'name' => 'structure_caption', 'text' => __('Table caption'));
        $plugin_list['latex']['options'][] =
            array('type' => 'text', 'name' => 'structure_continued_caption', 'text' => __('Continued table caption'));
        $plugin_list['latex']['options'][] =
            array('type' => 'text', 'name' => 'structure_label', 'text' => __('Label key'));
        if (!empty($GLOBALS['cfgRelation']['relation'])) {
            $plugin_list['latex']['options'][] =
                array('type' => 'bool', 'name' => 'relation', 'text' => __('Relations'));
        }
        $plugin_list['latex']['options'][] =
            array('type' => 'bool', 'name' => 'comments', 'text' => __('Comments'));
        if (!empty($GLOBALS['cfgRelation']['mimework'])) {
            $plugin_list['latex']['options'][] =
                array('type' => 'bool', 'name' => 'mime', 'text' => __('MIME type'));
        }
        $plugin_list['latex']['options'][] =
            array('type' => 'egroup');
    }
    /* Data */
    $plugin_list['latex']['options'][] =
        array('type' => 'bgroup', 'name' => 'data', 'text' => __('Data'), 'force' => 'structure');
    $plugin_list['latex']['options'][] =
        array('type' => 'bool', 'name' => 'columns', 'text' => __('Put columns names in the first row'));
    $plugin_list['latex']['options'][] =
        array('type' => 'text', 'name' => 'data_caption', 'text' => __('Table caption'));
    $plugin_list['latex']['options'][] =
        array('type' => 'text', 'name' => 'data_continued_caption', 'text' => __('Continued table caption'));
    $plugin_list['latex']['options'][] =
        array('type' => 'text', 'name' => 'data_label', 'text' => __('Label key'));
    $plugin_list['latex']['options'][] =
        array('type' => 'text', 'name' => 'null', 'text' => __('Replace NULL by'));
    $plugin_list['latex']['options'][] =
        array('type' => 'egroup');
} else {

/**
 * Escapes some special characters for use in TeX/LaTeX
 *
 * @param   string      the string to convert
 *
 * @return  string      the converted string with escape codes
 *
 * @access  private
 */
function PMA_texEscape($string) {
    $escape = array('$', '%', '{', '}',  '&',  '#', '_', '^');
    $cnt_escape = count($escape);
    for ($k=0; $k < $cnt_escape; $k++) {
        $string = str_replace($escape[$k], '\\' . $escape[$k], $string);
    }
    return $string;
}

/**
 * Outputs comment
 *
 * @param   string      Text of comment
 *
 * @return  bool        Whether it suceeded
 */
function PMA_exportComment($text) {
    return PMA_exportOutputHandler('% ' . $text . $GLOBALS['crlf']);
}

/**
 * Outputs export footer
 *
 * @return  bool        Whether it suceeded
 *
 * @access  public
 */
function PMA_exportFooter() {
    return TRUE;
}

/**
 * Outputs export header
 *
 * @return  bool        Whether it suceeded
 *
 * @access  public
 */
function PMA_exportHeader() {
    global $crlf;
    global $cfg;

    $head  =  '% phpMyAdmin LaTeX Dump' . $crlf
           .  '% version ' . PMA_VERSION . $crlf
           .  '% http://www.phpmyadmin.net' . $crlf
           .  '%' . $crlf
           .  '% ' . __('Host') . ': ' . $cfg['Server']['host'];
    if (!empty($cfg['Server']['port'])) {
         $head .= ':' . $cfg['Server']['port'];
    }
    $head .= $crlf
           .  '% ' . __('Generation Time') . ': ' . PMA_localisedDate() . $crlf
           .  '% ' . __('Server version') . ': ' . substr(PMA_MYSQL_INT_VERSION, 0, 1) . '.' . (int) substr(PMA_MYSQL_INT_VERSION, 1, 2) . '.' . (int) substr(PMA_MYSQL_INT_VERSION, 3) . $crlf
           .  '% ' . __('PHP Version') . ': ' . phpversion() . $crlf;
    return PMA_exportOutputHandler($head);
}

/**
 * Outputs database header
 *
 * @param   string      Database name
 *
 * @return  bool        Whether it suceeded
 *
 * @access  public
 */
function PMA_exportDBHeader($db) {
    global $crlf;
    $head = '% ' . $crlf
          . '% ' . __('Database') . ': ' . (isset($GLOBALS['use_backquotes']) ? PMA_backquote($db) : '\'' . $db . '\''). $crlf
          . '% ' . $crlf;
    return PMA_exportOutputHandler($head);
}

/**
 * Outputs database footer
 *
 * @param   string      Database name
 *
 * @return  bool        Whether it suceeded
 *
 * @access  public
 */
function PMA_exportDBFooter($db) {
    return TRUE;
}

/**
 * Outputs create database database
 *
 * @param   string      Database name
 *
 * @return  bool        Whether it suceeded
 *
 * @access  public
 */
function PMA_exportDBCreate($db) {
    return TRUE;
}

/**
 * Outputs the content of a table in LaTeX table/sideways table environment
 *
 * @param   string      the database name
 * @param   string      the table name
 * @param   string      the end of line sequence
 * @param   string      the url to go back in case of error
 * @param   string      SQL query for obtaining data
 *
 * @return  bool        Whether it suceeded
 *
 * @access  public
 */
function PMA_exportData($db, $table, $crlf, $error_url, $sql_query) {
    $result      = PMA_DBI_try_query($sql_query, null, PMA_DBI_QUERY_UNBUFFERED);

    $columns_cnt = PMA_DBI_num_fields($result);
    for ($i = 0; $i < $columns_cnt; $i++) {
        $columns[$i] = PMA_DBI_field_name($result, $i);
    }
    unset($i);

    $buffer      = $crlf . '%' . $crlf . '% ' . __('Data') . ': ' . $table . $crlf . '%' . $crlf
                 . ' \\begin{longtable}{|';

    for ($index=0;$index<$columns_cnt;$index++) {
       $buffer .= 'l|';
    }
    $buffer .= '} ' . $crlf ;

    $buffer .= ' \\hline \\endhead \\hline \\endfoot \\hline ' . $crlf;
    if (isset($GLOBALS['latex_caption'])) {
        $buffer .= ' \\caption{' . PMA_expandUserString($GLOBALS['latex_data_caption'], 'PMA_texEscape')
                   . '} \\label{' . PMA_expandUserString($GLOBALS['latex_data_label']) . '} \\\\';
    }
    if (!PMA_exportOutputHandler($buffer)) {
        return FALSE;
    }

    // show column names
    if (isset($GLOBALS['latex_columns'])) {
        $buffer = '\\hline ';
        for ($i = 0; $i < $columns_cnt; $i++) {
            $buffer .= '\\multicolumn{1}{|c|}{\\textbf{' . PMA_texEscape(stripslashes($columns[$i])) . '}} & ';
          }

        $buffer = substr($buffer, 0, -2) . '\\\\ \\hline \hline ';
        if (!PMA_exportOutputHandler($buffer . ' \\endfirsthead ' . $crlf)) {
            return FALSE;
        }
        if (isset($GLOBALS['latex_caption'])) {
            if (!PMA_exportOutputHandler('\\caption{' . PMA_expandUserString($GLOBALS['latex_data_continued_caption'], 'PMA_texEscape') . '} \\\\ ')) return FALSE;
        }
        if (!PMA_exportOutputHandler($buffer . '\\endhead \\endfoot' . $crlf)) {
            return FALSE;
        }
    } else {
        if (!PMA_exportOutputHandler('\\\\ \hline')) {
            return FALSE;
        }
    }

    // print the whole table
    while ($record = PMA_DBI_fetch_assoc($result)) {

        $buffer = '';
        // print each row
        for ($i = 0; $i < $columns_cnt; $i++) {
            if (isset($record[$columns[$i]])
             && (! function_exists('is_null') || !is_null($record[$columns[$i]]))) {
                $column_value = PMA_texEscape(stripslashes($record[$columns[$i]]));
            } else {
                $column_value = $GLOBALS['latex_null'];
            }

            // last column ... no need for & character
            if ($i == ($columns_cnt - 1)) {
                $buffer .= $column_value;
            } else {
                $buffer .= $column_value . " & ";
            }
        }
        $buffer .= ' \\\\ \\hline ' . $crlf;
        if (!PMA_exportOutputHandler($buffer)) {
            return FALSE;
        }
    }

    $buffer = ' \\end{longtable}' . $crlf;
    if (!PMA_exportOutputHandler($buffer)) {
        return FALSE;
    }

    PMA_DBI_free_result($result);
    return TRUE;

} // end getTableLaTeX

/**
 * Returns $table's structure as LaTeX
 *
 * @param   string   the database name
 * @param   string   the table name
 * @param   string   the end of line sequence
 * @param   string   the url to go back in case of error
 * @param   boolean  whether to include relation comments
 * @param   boolean  whether to include column comments
 * @param   boolean  whether to include mime comments
 * @param   string   future feature: support view dependencies
 *
 * @return  bool     Whether it suceeded
 *
 * @access  public
 */
 // @@@ Table structure
function PMA_exportStructure($db, $table, $crlf, $error_url, $do_relation = false, $do_comments = false, $do_mime = false, $dates = false, $dummy)
{
    global $cfgRelation;

    /**
     * Get the unique keys in the table
     */
    $keys_query     = 'SHOW KEYS FROM ' . PMA_backquote($table) . ' FROM '. PMA_backquote($db);
    $keys_result    = PMA_DBI_query($keys_query);
    $unique_keys    = array();
    while ($key = PMA_DBI_fetch_assoc($keys_result)) {
        if ($key['Non_unique'] == 0) {
            $unique_keys[] = $key['Column_name'];
        }
    }
    PMA_DBI_free_result($keys_result);

    /**
     * Gets fields properties
     */
    PMA_DBI_select_db($db);
    $local_query = 'SHOW FIELDS FROM ' . PMA_backquote($db) . '.' . PMA_backquote($table);
    $result      = PMA_DBI_query($local_query);
    $fields_cnt  = PMA_DBI_num_rows($result);

    // Check if we can use Relations (Mike Beck)
    if ($do_relation && !empty($cfgRelation['relation'])) {
        // Find which tables are related with the current one and write it in
        // an array
        $res_rel = PMA_getForeigners($db, $table);

        if ($res_rel && count($res_rel) > 0) {
            $have_rel = TRUE;
        } else {
            $have_rel = FALSE;
        }
    } else {
           $have_rel = FALSE;
    } // end if

    /**
     * Displays the table structure
     */
    $buffer      = $crlf . '%' . $crlf . '% ' . __('Structure') . ': ' . $table  . $crlf . '%' . $crlf
                 . ' \\begin{longtable}{';
    if (!PMA_exportOutputHandler($buffer)) {
        return FALSE;
    }

    $columns_cnt = 4;
    $alignment = '|l|c|c|c|';
    if ($do_relation && $have_rel) {
        $columns_cnt++;
        $alignment .= 'l|';
    }
    if ($do_comments) {
        $columns_cnt++;
        $alignment .= 'l|';
    }
    if ($do_mime && $cfgRelation['mimework']) {
        $columns_cnt++;
        $alignment .='l|';
    }
    $buffer = $alignment . '} ' . $crlf ;

    $header = ' \\hline ';
    $header .= '\\multicolumn{1}{|c|}{\\textbf{' . __('Column') . '}} & \\multicolumn{1}{|c|}{\\textbf{' . __('Type') . '}} & \\multicolumn{1}{|c|}{\\textbf{' . __('Null') . '}} & \\multicolumn{1}{|c|}{\\textbf{' . __('Default') . '}}';
    if ($do_relation && $have_rel) {
        $header .= ' & \\multicolumn{1}{|c|}{\\textbf{' . __('Links to') . '}}';
    }
    if ($do_comments) {
        $header .= ' & \\multicolumn{1}{|c|}{\\textbf{' . __('Comments') . '}}';
        $comments = PMA_getComments($db, $table);
    }
    if ($do_mime && $cfgRelation['mimework']) {
        $header .= ' & \\multicolumn{1}{|c|}{\\textbf{MIME}}';
        $mime_map = PMA_getMIME($db, $table, true);
    }

    $local_buffer = PMA_texEscape($table);

    // Table caption for first page and label
    if (isset($GLOBALS['latex_caption'])) {
        $buffer .= ' \\caption{'. PMA_expandUserString($GLOBALS['latex_structure_caption'], 'PMA_texEscape')
                   . '} \\label{' . PMA_expandUserString($GLOBALS['latex_structure_label'])
                   . '} \\\\' . $crlf;
    }
    $buffer .= $header . ' \\\\ \\hline \\hline' . $crlf . '\\endfirsthead' . $crlf;
    // Table caption on next pages
    if (isset($GLOBALS['latex_caption'])) {
        $buffer .= ' \\caption{'. PMA_expandUserString($GLOBALS['latex_structure_continued_caption'], 'PMA_texEscape')
                   . '} \\\\ ' . $crlf;
    }
    $buffer .= $header . ' \\\\ \\hline \\hline \\endhead \\endfoot ' . $crlf;

    if (!PMA_exportOutputHandler($buffer)) {
        return FALSE;
    }

    while ($row = PMA_DBI_fetch_assoc($result)) {

        $type             = $row['Type'];
        // reformat mysql query output
        // set or enum types: slashes single quotes inside options
        if (preg_match('/^(set|enum)\((.+)\)$/i', $type, $tmp)) {
            $tmp[2]       = substr(preg_replace('/([^,])\'\'/', '\\1\\\'', ',' . $tmp[2]), 1);
            $type         = $tmp[1] . '(' . str_replace(',', ', ', $tmp[2]) . ')';
            $type_nowrap  = '';

            $binary       = 0;
            $unsigned     = 0;
            $zerofill     = 0;
        } else {
            $type_nowrap  = ' nowrap="nowrap"';
            $type         = preg_replace('/BINARY/i', '', $type);
            $type         = preg_replace('/ZEROFILL/i', '', $type);
            $type         = preg_replace('/UNSIGNED/i', '', $type);
            if (empty($type)) {
                $type     = '&nbsp;';
            }

            $binary       = preg_match('/BINARY/i', $row['Type']);
            $unsigned     = preg_match('/UNSIGNED/i', $row['Type']);
            $zerofill     = preg_match('/ZEROFILL/i', $row['Type']);
        }
        if (!isset($row['Default'])) {
            if ($row['Null'] != 'NO') {
                $row['Default'] = 'NULL';
            }
        } else {
            $row['Default'] = $row['Default'];
        }

        $field_name = $row['Field'];

        $local_buffer = $field_name . "\000" . $type . "\000"
            . (($row['Null'] == '' || $row['Null'] == 'NO') ? __('No') : __('Yes'))
            . "\000" . (isset($row['Default']) ? $row['Default'] : '');

        if ($do_relation && $have_rel) {
            $local_buffer .= "\000";
            if (isset($res_rel[$field_name])) {
                $local_buffer .= $res_rel[$field_name]['foreign_table'] . ' (' . $res_rel[$field_name]['foreign_field'] . ')';
            }
        }
        if ($do_comments && $cfgRelation['commwork']) {
            $local_buffer .= "\000";
            if (isset($comments[$field_name])) {
                $local_buffer .= $comments[$field_name];
            }
        }
        if ($do_mime && $cfgRelation['mimework']) {
            $local_buffer .= "\000";
            if (isset($mime_map[$field_name])) {
                $local_buffer .= str_replace('_', '/', $mime_map[$field_name]['mimetype']);
            }
        }
        $local_buffer = PMA_texEscape($local_buffer);
        if ($row['Key']=='PRI') {
            $pos=strpos($local_buffer, "\000");
            $local_buffer = '\\textit{' . substr($local_buffer, 0, $pos) . '}' . substr($local_buffer, $pos);
        }
        if (in_array($field_name, $unique_keys)) {
            $pos=strpos($local_buffer, "\000");
            $local_buffer = '\\textbf{' . substr($local_buffer, 0, $pos) . '}' . substr($local_buffer, $pos);
        }
        $buffer = str_replace("\000", ' & ', $local_buffer);
        $buffer .= ' \\\\ \\hline ' . $crlf;

        if (!PMA_exportOutputHandler($buffer)) {
            return FALSE;
        }
    } // end while
    PMA_DBI_free_result($result);

    $buffer = ' \\end{longtable}' . $crlf;
    return PMA_exportOutputHandler($buffer);
} // end of the 'PMA_exportStructure' function

} // end else
?>
