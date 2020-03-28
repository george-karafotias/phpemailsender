<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'mail/Exception.php';
require 'mail/PHPMailer.php';
require 'mail/SMTP.php';

function sendEmail($toEmails, $subject, $body, $emailAttachments) {
	try {		
		if (empty($toEmails)) {
			return false;
		}
		if (trim($body)=="" && empty($emailAttachments)) {
			return false;
		}

		$fromEmailAddress = 'test@gmail.com';
		$mail = new PHPMailer;
		$mail->isSMTP(); 
		$mail->SMTPDebug = 0;
		$mail->CharSet = 'UTF-8';
		$mail->Host = "smtp.gmail.com";
		$mail->SMTPSecure = 'tls';
		$mail->SMTPAuth = true;
		$mail->Username = $fromEmailAddress;
		$mail->Password = 'test';
		$mail->setFrom($fromEmailAddress, '');
		$mail->addAddress($fromEmailAddress, '');
		foreach($toEmails  as $toEmail) {
			$mail->AddBCC($toEmail);  
        }
		$mail->Subject = $subject;
		$mail->IsHTML(true);
		$mail->Body = html_entity_decode($body);
		foreach($emailAttachments as $emailAttachment) {
			$mail->AddAttachment($emailAttachment['tmp_name'], $emailAttachment['name']);
		}

		if (!$mail->send()) {
			return false;
		} else {
			return true;
		}
	} catch (Exception $e) {
		return false;
	}
}

function sanitizeInput($input) {
	$output = '';
	if (!empty($input)) {
		$output = trim(htmlspecialchars($input));
	}
	return $output;
}

$emailRecipientsStr = '';
$emails = '';
$emailSent = false;
$errorMsg = '';

$email = array();
$email['subject'] = "";
$email['body'] = "";
$email['to'] = "";

if (empty($_POST)) {
	if (isset($_GET['members'])) {
		$emailRecipientsStr = $_GET['members'];
	}
	if (isset($_GET['emailSent'])) {
		$emailSent = true;
	}
} else {	
	$email['subject'] = sanitizeInput($_POST['emailSubject']);
	$email['body'] = sanitizeInput($_POST['emailBody']);
	$email['to'] = sanitizeInput($_POST['emailTo']);
	$emails = explode(";", $email['to']);
	if (empty($emails)) {
		$errorMsg = "Please fill in the recipients.";
	} else {
		$emailAttachments = [];
		for ($i = 1; $i <= 3; $i++) {
			$oneFile = $_FILES['emailAttachment'.$i]; 
			if (isset($oneFile) && $oneFile['error'] == UPLOAD_ERR_OK) {
				$emailAttachment =  array('tmp_name'=>$oneFile['tmp_name'], 'name'=>$oneFile['name']);
				array_push($emailAttachments, $emailAttachment);
			}
		}
		
		if (trim($email['body'])=="" && empty($emailAttachments)) {
			$errorMsg = "Please fill in the message body or the email attachments.";
		}

		$emailSent = sendEmail($emails, $email['subject'], $email['body'], $emailAttachments);
		if ($emailSent) {
			header('location: ' . $_SERVER['REQUEST_URI']."?emailSent=1");
		} else {
			$errorMsg = "Cannot send email.";
		}
	}
}
?>
<!DOCTYPE html>
<html>
	<head>
		<title>Send Email</title>
		<?php include('bootstrap.html'); ?>
		<script type="text/javascript" src="js/tinymce.min.js"></script>
		<script>
		tinymce.init({
			selector: 'textarea#editor'
		});
		</script>
		<style>
		.tox-notifications-container {display: none !important;}
		#editor { height:300px; }
		</style>
	</head>
	<body>
		<h1>Send Email</h1>
		
		<?php if ($emailSent) { ?>
		<div class="successfulEntry">Email was sent successfully.</div>
		<?php } ?>
		
		<?php if (!empty($errorMsg)) { ?>
		<div class="errorEntry"><?php echo $errorMsg; ?></div>
		<?php } ?>
		
		<form class="imdbForm" method="post" enctype="multipart/form-data" action="">
		
			<div class="form-group">
				<label for="emailTo">To: <span class="required">*</span></label><input type="text" class="form-control" id="emailTo" name="emailTo" autocomplete="off" autofocus value="<?php echo $email['to']; ?>">
			</div>

			<div class="form-group">
				<label for="emailSubject">Subject:</label><input type="text" class="form-control" id="emailSubject" name="emailSubject" autocomplete="off" autofocus value="<?php echo $email['subject']; ?>">
			</div>
			
			<div class="form-group">
				<label for="emailBody">Message:</label><textarea class="form-control" rows="5" id="editor" name="editor" autocomplete="off"><?php echo $email['body']; ?></textarea>
			</div>

			<input type="file" name="emailAttachment1">

			<input type="hidden" id="emailBody" name="emailBody" />

			<button onClick="saveText(); this.form.submit(); this.disabled=true;" class="btn btn-primary submitBtn">Send</button>
		</form>
		
		<script>	
			$(document).ready(function(){
				document.getElementById('sendEmailsTabButton').className += " active";
			});

			function saveText() {
				tinyMCE.triggerSave();
				$("#emailBody").val($("textarea#editor").val());
			}
		</script>
	</body>
</html>