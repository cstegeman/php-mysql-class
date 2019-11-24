<?php
include ("database.php");

$conn = new DBconnection();
$conn->Open("sampledatabase");

$query = "SELECT id, name FROM tmp_table ORDER BY name ASC";
$rs = $conn->GetValues($query);
if (!$rs->Eof()) {
    while (!$rs->eof) {
        echo $rs->fields["name"] . "<br>";
        $rs->MoveNext();
    }
}