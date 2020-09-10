<?php

	$to = 'Eldrinc@plai.today';
	
	$errors = array();
	// print_r($_POST);

	// Check if name has been entered
	if (!isset($_POST['name'])) {
		$errors['name'] = 'Please enter your name';
	}
	
	// Check if email has been entered and is valid
	if (!isset($_POST['email']) || !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
		$errors['email'] = 'Please enter a valid email address';
	}
	
	//Check if message has been entered
	if (!isset($_POST['message'])) {
		$errors['message'] = 'Please enter your message';
	}

	$errorOutput = '';

	if(!empty($errors)){

		$errorOutput .= '<div class="alert alert-danger alert-dismissible" role="alert">';
 		$errorOutput .= '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>';

		$errorOutput  .= '<ul>';

		foreach ($errors as $key => $value) {
			$errorOutput .= '<li>'.$value.'</li>';
		}

		$errorOutput .= '</ul>';
		$errorOutput .= '</div>';

		echo $errorOutput;
		die();
	}



	$name = $_POST['name'];
	$email = $_POST['email'];
	$message = $_POST['message'];
	// This must match a verified SendGrid sender identity, as per:
	// https://sendgrid.com/docs/for-developers/sending-email/sender-identity/
	$from = "christianr@plai.today";
	$subject = 'Website Contact Form';
	
	$body_text = "From: $name\n E-Mail: $email\n Message:\n\n$message";
	$body_html = nl2br(htmlentities($body_text));


	//send the email
	$url = 'https://api.sendgrid.com/';
	$user = 'plaitoday';
	$pass = 'podxo1-muwfim-xeqdyM';

	$params = array(
		'api_user' => $user,
		'api_key' => $pass,
		'to' => $to,
		'subject' => $subject,
		'html' => $body_html,
		'text' => $body_text,
		'from' => $from,
	);

	$request = $url.'api/mail.send.json';

	// Generate curl request
	$session = curl_init($request);

	// Tell curl to use HTTP POST
	curl_setopt ($session, CURLOPT_POST, true);

	// Tell curl that this is the body of the POST
	curl_setopt ($session, CURLOPT_POSTFIELDS, $params);

	// Tell curl not to return headers, but do return the response
	curl_setopt($session, CURLOPT_HEADER, false);
	curl_setopt($session, CURLOPT_RETURNTRANSFER, true);

	// obtain response
	$response = curl_exec($session);
	curl_close($session);

	// print everything out
	//print_r($response);

	$result = '';
	if ($response) {
		$result .= '<div class="alert alert-success alert-dismissible" role="alert">';
 		$result .= '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>';
// For debugging:
//		$result .= print_r($response, TRUE);
		$result .= 'Thank You! We will be in touch.';
		$result .= '</div>';

		echo $result;
		die();
	}

	$result = '';
	$result .= '<div class="alert alert-danger alert-dismissible" role="alert">';
	$result .= '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>';
	$result .= 'An error occurred. Please try again later';
	$result .= '</div>';

	echo $result;
	die();

?>
