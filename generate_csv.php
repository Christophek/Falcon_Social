<?php

if($_GET)
{
	header("Content-Type: text/csv; charset=utf8");
	header("Content-Disposition: attachment; filename=Contact.csv");

	$config['limit'] = array(
			'filter' 	=> FILTER_VALIDATE_INT,
			'options'	=> array('min_range' => 0, 'max_range' => 1),
		);

	$get = filter_input_array(INPUT_GET, $config);
	$get = $get['limit'] === false ? 1 : $get['limit'];

	# prepare data for the csv
	$column = 'Name, Position, Summary';
	$data 	= 'Christophe Kolbeck, Web-Developer & Digital Designer, Always striving to deliver stunning digital experiences. If you would like to work with me or hire then dont hesitate to get in touch. I dont bite... hard...';

	$column = explode(',', $column);
	$data 	= explode(',', $data);

	$output = fopen('php://output', 'w');

	fputcsv($output, $column);
	for($i = 0; $i < $get; $i++) fputcsv($output, $data);
	fclose($output);
}
 
