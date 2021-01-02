<?php
$amso_konekcija = pg_connect("host=10.101.50.12 dbname=a user=z");

if (!$amso_konekcija) {
    exit('Greška otvaranja konekcije prema SQL serveru.');
}
?>
