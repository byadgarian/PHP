<?php
########## Handle Errors ##########--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
#BXP: No need for error handlers (already included in "create_envelope.php")

########## Include Classes ##########--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
require_once($_SERVER['DOCUMENT_ROOT'] . "/docusign/time_sheet_report.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/docusign/create_envelope.php");

########## Main ##########--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
$pdf = new PDF('L', 'mm', 'Letter');
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Times','',12);
$pdf->Body();
$pdf->Output('F', $root . "/files/time_keeping/" . $pdf->get_document_name() . ".pdf"); //BXP: add permissions to save in time_keeping/docusign_time_sheet_reports - use general name to overwrite the same file

$envelope = new CreateEnvelope; //BXP: Alternatively use header('location: docusign_time_sheet_create.php');
$envelope->public_create_envelope($pdf->get_document_name(), $pdf->get_employee_name());
?>