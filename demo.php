<?php

// Pretty json
function format_json($json) {

    $result      = '';
    $pos         = 0;
    $strLen      = strlen($json);
    $indentStr   = '  ';
    $newLine     = "\n";
    $prevChar    = '';
    $outOfQuotes = true;

    for ($i=0; $i<=$strLen; $i++) {

        // Grab the next character in the string.
        $char = substr($json, $i, 1);

        // Are we inside a quoted string?
        if ($char == '"' && $prevChar != '\\') {
            $outOfQuotes = !$outOfQuotes;

        // If this character is the end of an element, 
        // output a new line and indent the next line.
        } else if(($char == '}' || $char == ']') && $outOfQuotes) {
            $result .= $newLine;
            $pos --;
            for ($j=0; $j<$pos; $j++) {
                $result .= $indentStr;
            }
        }

        // Add the character to the result string.
        $result .= $char;

        // If the last character was the beginning of an element, 
        // output a new line and indent the next line.
        if (($char == ',' || $char == '{' || $char == '[') && $outOfQuotes) {
            $result .= $newLine;
            if ($char == '{' || $char == '[') {
                $pos ++;
            }

            for ($j = 0; $j < $pos; $j++) {
                $result .= $indentStr;
            }
        }

        $prevChar = $char;
    }

    return $result;
}

// First create your application in the GoCardless sandbox:
// https://sandbox.gocardless.com
// Then grab your application identifier and secret...

// Include library
include_once 'gocardless.php';

// ...and paste them in here

// Config vars
$gocardless_config = array(
	'merchant_id'		=> '258584',
	'app_id'			=> 'eCxrcWDxjYsQ55zhsDTgs6VeKf6YWZP7be/9rY0PGFbeyqmLJV6k84SUQdISLUhf',
	'app_secret'		=> '2utXOc65Hy9dolp3urYBMoIN0DM11Q9uuoboFDkHY3nzsugqcuzD1FuJYA7X9TP+',
	'access_token'		=> '+vJh7dkHLr5rbdqBLlRk3dPALyn0uvAKTMvRnfWOAKcQ6WRCx/QGsdOefGqEs6h6',
	'environment'		=> 'sandbox',
	'redirect_uri'		=> 'http://localhost:8888/demo.php',
	'response_format'	=> 'application/json'
);

// Initialize GoCardless
$gocardless = new GoCardless($gocardless_config);

if (isset($_GET['resource_id']) && isset($_GET['resource_type'])) {
	// Can haz get vars so time to confirm payment
	
	$confirm_params = array(
		'resource_id'	=> $_GET['resource_id'],
		'resource_type'	=> $_GET['resource_type']
	);
	
	$confirm = $gocardless->confirm_resource($confirm_params);
	
	$confirm_decoded = json_decode($confirm, true);
	
	if ($confirm_decoded['success'] == TRUE) {
		
		echo '<p>Payment confirmed!</p>';
		
	} else {
		
		echo 'Payment not confirmed, following message was returned:';
		echo '<pre>';
		var_dump($confirm);
		echo '</pre>';
		
	}
	
}

echo '<h2>New payment URLs</h2>';

// New subscription

$payment_details = array(
	'amount'			=> '10.00',
	'interval_length'	=> 1,
	'interval_unit'		=> 'month'
);

$subscription_url = $gocardless->new_subscription_url($payment_details);

echo '<p><a href="'.$subscription_url.'">New subscription</a>';

// New pre-authorization

$payment_details = array(
	'max_amount'		=> '20.00',
	'interval_length'	=> 1,
	'interval_unit'		=> 'month'
);

$pre_auth_url = $gocardless->new_pre_authorization_url($payment_details);

echo ' &middot; <a href="'.$pre_auth_url.'">New pre-authorized payment</a>';

// New bill

$payment_details = array(
	'amount'		=> '20.00',
	'user'				=> array(
		'first_name'	=> 'Tom',
		'last_name'		=> 'Blomfield',
		'email'			=> 'tom@gocardless.com'
		)
);

$bill_url = $gocardless->new_bill_url($payment_details);

echo ' &middot; <a href="'.$bill_url.'">New bill</a></p>';

echo 'NB. The \'new bill\' link is also a demo of pre-populated user data';

echo '<h2>API calls</h2>';

echo '$gocardless->merchant->get(\'258584\')';
echo '<blockquote><pre>';
$merchant = $gocardless->merchant->get(258584);
print_r($merchant);
echo '</pre></blockquote>';

echo '$gocardless->merchant->bills(\'258584\')';
echo '<blockquote><pre>';
$bills = $gocardless->merchant->bills(258584);
print_r($bills);
echo '</pre></blockquote>';

echo 'validate webhook:';
echo '<blockquote><pre>';
$webhook_json = '{"payload":{"bills":[{"id":"880807"},{"status":"pending"},{"source_type":"subscription"},{"source_id":"21"},{"uri":"https:\/\/sandbox.gocardless.com\/api\/v1\/bills\/880807"}],"action":"created","resource_type":"bill","signature":"f25a611fb9afbc272ab369ead52109edd8a88cbb29a3a00903ffbce0ec6be5cb"}}';
$webhook = json_decode($webhook_json, true);
var_dump($gocardless->validate_webhook($webhook));
echo '</pre></blockquote>';

?>