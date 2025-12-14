<?php
require_once 'classes/database.php';

$db = new Database();
$db->createTables();

echo "Database setup complete.";
?>
