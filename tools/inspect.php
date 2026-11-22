<?php 
declare(strict_types=1);

function readCsv(string $path): Generator {
    $f = fopen($path, 'r'); // open the file for reading
    fgetcsv($f, 0, ',', '"', '');  // read and throw away the header line
    while (($row = fgetcsv($f, 0, ',', '"', '')) !== false) { // read the next row; stop at end of file
        yield $row;
        // hand this row to the caller, then continue from here next time
    }
    fclose($f);
}

$in = __DIR__ . '/../data/' . ($argv[1] ?? 'large'); // dataset folder, defautl "large"

$units = []; // unit id => parent id from topology.csv
$topoRows = 0; // how many topology rows we read 
$topoBad = 0; // topology w/o exactly 3 cols
$topoDup = 0; // unit ids that appear more than once

foreach (readCsv("$in/topology.csv") as $r) { //each topology row
    $topoRows++; // count it
    if (count($r) !== 3) { $topoBad++; continue; } // wrong cols count: count it and skip the row
    if (isset($units[$r[0]])) $topoDup++; // we have seen this unit before: duplicate
    $units[$r[0]] = $r[2]; // remeber unit => parent 
}

$rows = 0; // how many event rows we read
$badCols = 0; // event rows w/o exactly 4 cols (includes blank lines)
$badTs = 0; // timestamps PHP cannot read 
$unknown = 0; //events for units that are not in topology.csv
$kinds = []; // how many times it appears
$types = []; // how many times appears
$shapes = []; // timestamp "shape" -> how many times it appears 

foreach (readCsv("$in/events.csv") as $r) { //each event row
    $rows++; // count it 

    if (count($r) !== 4) { $badCols++; continue; } // wrong col cont: count it and skip the row
    [$ts, $unit, $kind, $type]= $r; // name he 4 cols
    $kinds[$kind] = ($kinds[$kind] ?? 0) + 1; // count this kind
     $types[$type] = ($types[$type] ?? 0) + 1; // count this type
    $shape = preg_replace('/\d/', '9', $ts); // replace every digit with 9, so formats are easy to compare
    $shapes[$shape] = ($shapes[$shape] ?? 0) + 1; // count this timestamp shape

    if (strtotime($ts) === false) $badTs++; // php could not read this timestamp
    if(!isset($units[$unit])) $unknown++; // this unit not in the topology
}

echo "topology rows: $topoRows, bad: $topoBad, duplicate units: $topoDup\n"; // topology summary
echo "event rows: $rows, bad column count: $badCols, bad timestamps: $badTs, unknows units: $unknown\n"; // events summary
echo "kinds: \n"; print_r($kinds); // every kind and its count
echo "types:\n"; print_r($types); // type and its count
echo "timestamp shapes:\n"; print_r($shapes); // timestamp format and its count