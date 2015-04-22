<?php/* ------------------------------------------------------------------------------------- *  Improve debugging * *  Turn on all debugging for developing purpose * ------------------------------------------------------------------------------------- */error_reporting(E_ALL);ini_set('display_errors', 1);/* ------------------------------------------------------------------------------------- *  Allow huge memory * *  Since a lot of data is handled, the allowed memory in increased significantly * ------------------------------------------------------------------------------------- */ini_set('memory_limit', '512M');set_time_limit(300);/* ------------------------------------------------------------------------------------- *  Config * ------------------------------------------------------------------------------------- */include 'successvariables.php';/* -------------------------------------------------------------------------------------*  Import the data**  Import the data from Zephyr and make it readable in the language currently used* ------------------------------------------------------------------------------------- */descriptive('Import the data');// Import Excel data (csv)$code = file_get_contents('Zephyr_Export_20april.csv');// Convert csv to arrays$rows = explode('', $code);// $rows = array_slice($rows,0, 500);foreach ($rows as &$row) {    $row = explode(';', $row);}// Remove the table headingunset($rows[0]);/* ------------------------------------------------------------------------------------- *  Restructure the data into funding rounds * ------------------------------------------------------------------------------------- */descriptive('Restructure the into rounds');$rounds = [];foreach ($rows as $key => &$row) {    if (!empty($row[2]))        $rounds[$row[1]]['name'] = $row[2];    $rounds[$row[1]]['type'] = $row[3];    if (!empty($row[4]))        $rounds[$row[1]]['acquirers'][] = $row[4];    if (!empty($row[5]))        $rounds[$row[1]]['vendors'][] = $row[5];}info('<b>Rounds in the rawdata:</b> ' . count($rounds));/* ------------------------------------------------------------------------------------ *  Restructure the data into rounds * ------------------------------------------------------------------------------------- */descriptive('Restructure the rounds into target companies');$targets = [];foreach ($rounds as $key => $round) {    $targetKey = minimizeCompanyName($round['name']);    unset($round['name']);    $round['acquirers'] = (isset($round['acquirers'])) ? $round['acquirers'] : [];    $round['vendors'] = (isset($round['vendors'])) ? $round['vendors'] : [];    $targets[$targetKey]['rounds'][$key] = $round;}info('<b>Target companies in the raw data:</b> ' . count($targets));/* ------------------------------------------------------------------------------------- *  Divide rounds into developing capital or exit rounds * ------------------------------------------------------------------------------------- */descriptive('Divide rounds into developing capital or exit rounds');$exitRoundTypes = [];$excludes = ['Minority', 'buyback', 'Capital Increase'];foreach ($targets as $targetKey => $target) {    foreach ($target['rounds'] as $roundKey => $round) {        if (count($round['vendors'])) {            $include = true;            foreach ($excludes as $exclude) {                if (stripos($round['type'], $exclude) !== false) {                    $include = false;                }            }            if($include){                $exitRoundTypes[] = $round['type'];                $targets[$targetKey]['exitRounds'][] = $round;            }        } else            $targets[$targetKey]['fundingRounds'][] = $round;    }    unset($targets[$targetKey]['rounds']);}/* ------------------------------------------------------------------------------------- *  Remove all target companies with too few rounds * ------------------------------------------------------------------------------------- */filter('Remove all target companies with too few rounds');foreach ($targets as $key => $target) {    if (!isset($target['fundingRounds'])) {        unset($targets[$key]);        continue;    }    if (count($target['fundingRounds']) < 3) {        unset($targets[$key]);    }}info('<b>Target companies left after filtering:</b> ' . count($targets));/* ------------------------------------------------------------------------------------- *  Remove all target companies with too few rounds * ------------------------------------------------------------------------------------- */filter('Remove all target companies missing meta data in one or more rounds');foreach ($targets as $targetKey => $target) {    foreach ($target['fundingRounds'] as $round) {        if (count($round['acquirers']) == 0 && count($round['vendors']) == 0) {            unset($targets[$targetKey]);        }    }}info('<b>Target companies left after filtering:</b> ' . count($targets));/* ------------------------------------------------------------------------------------- *  Degree of reinvestment * ------------------------------------------------------------------------------------- */descriptive('Define each target companies\' degree of reinvestments');foreach ($targets as $targetKey => &$target) {    $aquirersWithInsight = [];    $reinvestments = 0;    $reinvestmentsOpportunities = 0;    foreach ($target['fundingRounds'] as $round) {        $reinvestmentsOpportunities = $reinvestmentsOpportunities + count($aquirersWithInsight);        foreach ($round['acquirers'] as $aquirer) {            if (in_array($aquirer, $aquirersWithInsight)) {                $reinvestments++;            } else {                $aquirersWithInsight[] = $aquirer;            }        }    }    $target['reinvestments'] = $reinvestments;    $target['reinvestmentsOpportunities'] = $reinvestmentsOpportunities;    if ($reinvestmentsOpportunities > 0)        $target['reinvestmentsDegree'] = round($reinvestments / $reinvestmentsOpportunities, 3);    else        unset($targets[$targetKey]);}/* ------------------------------------------------------------------------------------- *  Success * ------------------------------------------------------------------------------------- */descriptive('Define each target companies\' success or no sucess');foreach ($targets as &$target) {    $success = 0;    if (isset($target['exitRounds'])) {        if (count($target['exitRounds'])) {            $success = 1;        }    } else {        $target['exitRounds'] = [];    }    $target['success'] = $success;}// -------------------------------------------------------------------------------------//     PRINT THE RESULT// -------------------------------------------------------------------------------------echo '<table border="1">';echo '<thead>';echo '<tr>';echo '<th>name</th>';echo '<th>reinvestmentdegree</th>';echo '<th>success</th>';echo '<th>fundingRounds</th>';echo '<th>exitRounds</th>';echo '</tr>';echo '</thead>';foreach ($targets as $name => $target) {    echo '<tr>';    echo '<td>' . $name . '</td>';    echo '<td>' . $target['reinvestmentsDegree'] . '</td>';    echo '<td>' . $target['success'] . '</td>';    echo '<td>' . count($target['fundingRounds']) . '</td>';    echo '<td>' . count($target['exitRounds']) . '</td>';    echo '</tr>';}echo '</table>';function minimizeCompanyName($name){    return strtolower(preg_replace("/[^a-zA-Z]+/", "", $name));}function info($str){    echo '<div style="padding:20px; background: #b0d7fc; border: solid 2px #7aacdb; margin-bottom: 10px;">' . $str . '</div>';}function descriptive($str){    echo '<div style="padding:10px 20px; background: #f1f1f1; border: solid 2px #cccccc; margin-bottom: 10px;">' . $str . '</div>';}function filter($str){    echo '<div style="padding:10px 20px; background: #fcb0b2; border: solid 2px #ff5256; margin-bottom: 10px;">' . $str . '</div>';}