<?php
########## Handle Errors ##########--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
#BXP: No need for error handlers (already included in "retrieve_envelope.php")

########## Include Classes ##########--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
require_once($_SERVER['DOCUMENT_ROOT'] . "/backend/crud_Time_Keeping_test.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/docusign/retrieve_envelope.php");

########## Download ##########--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
class Download{

    function __construct(){} #BXP: Construct object

    function __destruct(){} #BXP: Destruct object
    
    public function public_download(){ #BXP: Public access to private method with no arguments

        if(isset($_SESSION['select_pay_period'])){
            $pay_period = $_SESSION['select_pay_period'];
        } else {
            ?><script> window.location = "time_keeping.php?notification=test";</script><?php
        }

        if(isset($_SESSION['select_employee_time_sheet'])){
            $employee_code = $_SESSION['select_employee_time_sheet'];
        } else {
            ?><script> window.location = "time_keeping.php?notification=mepr";</script><?php
        }

        $this->download($pay_period, $employee_code);
    }

    private function download($pay_period, $employee_code){ #BXP: Private access (class members not accessible outside the scope)

        $crudTime = new crudTimeKeeping();
        $envelope = new RetrieveEnvelope();

        $crudTime->reportTimeSheet($pay_period, $employee_code);
        $ts_pay_period = $crudTime->tsPayPeriodEnding();
        $ts_employee_code = $crudTime->tsEmployeeCode();
        $employee_name = $crudTime->employeeName();

        $day = date('d', strtotime($ts_pay_period));
        $adjusted_date = new DateTime($ts_pay_period);
        $adjusted_date->sub(new DateInterval('P13D'));
        $adjusted_date = $adjusted_date->format('m/d/Y');
        $month = date('m', strtotime($ts_pay_period));
        $year = date('Y', strtotime($ts_pay_period));

        if((int)$month < 07 && (int)$day < 01 && (int)$year < 2017){
            if($day > 15) {
                $pay_period_start = $month . "/16" . "/" . $year;
                $pay_period_end = $month . "/" . $day . "/" . $year;
            } else {
                $pay_period_start = $month . "/01" . "/" . $year;
                $pay_period_end = $month . "/" . $day . "/" . $year;
            }
        } else if((int)$month == 07 && (int)$day == 01 && (int)$year == 2017){
            $pay_period_start = "07/01/2017";
            $pay_period_end = "07/01/2017";
        } else {
            $pay_period_start = $adjusted_date;
            $pay_period_end = $month . "/" . $day . "/" . $year;
        }

        $document_name = date('Ymd', strtotime($pay_period_start)) . "-" . date('Ymd', strtotime($pay_period_end)) . " " . $ts_employee_code . " Time Sheet" . ".pdf"; #BXP: No employee code?

        $envelope->public_retrieve_envelope($document_name, $employee_name);
    }
}

########## Main ##########--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
$download = new Download;
$download->public_download();

########## Temp ##########--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
// if(isset($_POST['retrieve_envelope'])){
//     $envelope = new RetrieveEnvelope;
//     $envelop->public_retrieve_envelope($document_name, $employee_name);
// }
?>