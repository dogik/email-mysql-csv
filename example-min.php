<?php
# minimum code use example
# see example.php for more options that you can use, and for trouble shooting tips
require 'class.email-query-results-as-csv-file.php';

$emailCSV = new EmailQueryResultsAsCsv('localhost','databae','user','password');

$emailCSV->setSmtpHost('');
$emailCSV->setSmtpPort('465');
$emailCSV->setSmtpUsername('');
$emailCSV->setSmtpPassword('');
$emailCSV->setSmtpSecurity('ssl');

$emailCSV->setQuery("SELECT * FROM table");
$emailCSV->sendEmail("sender@example.com","recipient@example.com","MySQL Query Results as CSV Attachment", 'Email body', 'file.csv');
?>
