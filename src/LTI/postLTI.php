<?php

// print_r($_POST['data']);
print_r(gettype($_POST['data']));
// exit;

$POST = json_decode($_POST['data'], true);

$ref=$POST['lis_outcome_service_url'];
if (!strpos($ref,"mycourses")){


#$key="2VPGBSOmF11Nx89jOJEz3AqntJ8veXD9+Gps9zOhyvUjBtNXryPzgNBiQ";
#$secret="NAFnkD0bJlP6M18Jx3yeDok/7NYVPqTfnnmmRZPfCMlFtS";

	$key="anythingKey";
	$secret="anythingSecret";
}
else {
	$key="key_NSlctxPORqrIGspICqtDA8UqFvTHcqrxo96XLGgSMdmmnnVBfPXElvFy6";
	$secret="secret_AAAAB3NzaC1yc2EAAAABJQAAAQEAkett8rI9w9NufPDOkB";

#	$secret="NSlctxPORqrIGspICqtDA8UqFvTHcqrxo96XLGgSMdmmnnVBfPXElvFy6";
#	$key="AAAAB3NzaC1yc2EAAAABJQAAAQEAkett8rI9w9NufPDOk";
}
if (!array_key_exists('lis_result_sourcedid', $POST)) {print 'In lti\test\index.php : No ID<br>';
	print_r($POST);
exit;

}


	else{		
		$ses = array('fname' => $POST['lis_person_name_given'], 'lname' => $POST['lis_person_name_family'], 'id' => $POST['lis_result_sourcedid'], 'url' => $POST['lis_outcome_service_url']);

#

		include 'php/message.php';
		include 'php/OAuthBody.php';
		$id    = $ses['id'];
		$url   = $ses['url'];
		$grade = $POST["grade"];
		if (is_null($grade))
		{
		$grade=0;
		}
		$result = sendOAuthBodyPOST("POST", $url, $key, $secret, "application/xml", message($id, $grade));
		$result = preg_replace("/\r|\n/", "", $result);
		if(stristr($result, 'success') === FALSE) 
		{
			$status= "failure";
			print $result;
			// file_put_contents("/home/tltsecure/apache2/htdocs/bookMaker/Users/xml.txt","$result", FILE_APPEND);
			// file_put_contents("/home/tltsecure/apache2/htdocs/bookMaker/Users/last.xml","$key $ref");
		}
		else 
		{

			$status= "success";
			$status.= "\n$result";


		}
		print $status;
		$time=date('Y-m-d H:i:s');
		//file_put_contents("/home/tltsecure/apache2/htdocs/bookMaker/Users/$status.csv","$status,${_GET['name']},$ref,$grade,\"${_SERVER['HTTP_USER_AGENT']}\",$time\n", FILE_APPEND);
	}
