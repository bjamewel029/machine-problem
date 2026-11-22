<?php
declare(strict_types=1);

// Reads a CSV file one row at a time 
function readCsv(string $path): Generator {
    $f = fopen($path, 'r'); // open the file for reading
    fgetcsv($f, 0, ',', '"', '');  // read and throw away the header line
    while (($row = fgetcsv($f, 0, ',', '"', '')) !== false) { // read the next row; stop at end of file
        yield $row;
        // hand this row to the caller, then continue from here next time
    }
    fclose($f);
}

$ds  = $argv[1] ?? 'small';                       // dataset name from the command line, default "small"
$in  = __DIR__ . "/data/$ds";                     // folder with the input CSV files
$out = __DIR__ . "/out/$ds";                      // folder where we write the results
if (!is_dir($out)) mkdir($out, 0777, true);       // create the output folder if it doesn't exist

$events = []; // list that will hold every event
foreach (readCsv("$in/events.csv") as [$ts, $unit, $kind, $type]) { // go through each event row, naming its 4 columns
    $events[] = [strtotime($ts), $unit, $kind, $type]; // store it, with the time converted to a plain number (UTC seconds)
}
usort($events, fn($a, $b) => $a[0] <=> $b[0]);   // sort events by time, oldest first

$open = []; // alarms that have started but not ended: "unit|kind" => start time
$alarms = []; // finished alarms: [unit, kind, start time, end time]

foreach ($events as [$t, $unit, $kind, $type]) { // go through events in time order
    $key = $unit . '|' . $kind; // one label per unit+kind, like "A1|offline"
    
    if ($type === 'raise') {
        $open[$key] = $t; // this writes it start time
    } elseif ($type === 'clear' && isset($open[$key])) { // the problem ended and we saw it start
        $alarms[] = [$unit, $kind, $open[$key], $t];
        // save the finished alarm (start from the whiteboard, end = now)
        unset($open[$key]); // wipe it off the whiteboard
    }
}

// sort the alarms by start time,  then unit, then kind
usort($alarms, fn($a, $b) => [$a[2], $a[0], $a[1]] <=> [$b[2], $b[0], $b[1]]);

$iso = fn(int $t) => gmdate('Y-m-d\TH:i:s\Z', $t); 
// turns a number back into text like 2026-03-01T00xxxx

$fh =fopen("$out/alarms.csv", 'w'); // open the output file for writing 
fwrite($fh, "unit_id,kind,start,end,root_unit\n"); // write the header line
foreach ($alarms as [$unit, $kind, $s, $e]) { // for each alarm
   fwrite($fh, "$unit,$kind,{$iso($s)},{$iso($e)},$unit\n");   // one row, no spaces around the commas
}
fclose($fh); //close the ouput file 