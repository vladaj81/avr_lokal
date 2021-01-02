<?php
//$date = date_create('2020-12-31');

//$datum = 
/*
$current_quarter = ceil(date('n') / 3);
$first_day_of_this_quarter = date('m/d/Y', strtotime(date('Y').'-'.(($current_quarter*3)-2).'-1'));
$last_day_of_this_quarter = date('m/d/Y', strtotime(date('Y').'-'.($current_quarter*3).'-'.(date("t",date('Y').'-'.($current_quarter*3).'-1'))));

echo $current_quarter;
echo $last_day_of_this_quarter;
*/

$QuarterMonth = ["1" => ["Y-01-01","Y-03-31"],"2"=>["Y-04-01","Y-06-30"],"3"=>["Y-07-01","Y-09-30"],"4"=>["Y-10-01","Y-12-31"]];
$curMonth = 10;

$qtr_month_from = date($QuarterMonth[ceil($curMonth/3)][0]);
$qtr_month_to = date($QuarterMonth[ceil($curMonth/3)][1]);

echo $curMonth.'<br>';
echo $qtr_month_from.'<br>';

echo $qtr_month_to;
