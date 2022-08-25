
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

	<title><?php echo $title;?></title>
	<meta name="description" content="Kurma Media Whatsapp Gateway login">
	<meta name=”robots” content="index, follow">

	<link href="<?php echo base_url();?>assets/bower_components/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet" type="text/css">
	<link href="<?php echo base_url();?>assets/css/styles.css" rel="stylesheet">  	

	<!-- favicon -->
	<link rel="apple-touch-icon" sizes="57x57" href="<?php echo base_url();?>assets/images/favicon/apple-icon-57x57.png">
	<link rel="apple-touch-icon" sizes="60x60" href="<?php echo base_url();?>assets/images/favicon/apple-icon-60x60.png">
	<link rel="apple-touch-icon" sizes="72x72" href="<?php echo base_url();?>assets/images/favicon/apple-icon-72x72.png">
	<link rel="apple-touch-icon" sizes="76x76" href="<?php echo base_url();?>assets/images/favicon/apple-icon-76x76.png">
	<link rel="apple-touch-icon" sizes="114x114" href="<?php echo base_url();?>assets/images/favicon/apple-icon-114x114.png">
	<link rel="apple-touch-icon" sizes="120x120" href="<?php echo base_url();?>assets/images/favicon/apple-icon-120x120.png">
	<link rel="apple-touch-icon" sizes="144x144" href="<?php echo base_url();?>assets/images/favicon/apple-icon-144x144.png">
	<link rel="apple-touch-icon" sizes="152x152" href="<?php echo base_url();?>assets/images/favicon/apple-icon-152x152.png">
	<link rel="apple-touch-icon" sizes="180x180" href="<?php echo base_url();?>assets/images/favicon/apple-icon-180x180.png">
	<link rel="icon" type="image/png" sizes="192x192"  href="<?php echo base_url();?>assets/images/favicon/android-icon-192x192.png">
	<link rel="icon" type="image/png" sizes="32x32" href="<?php echo base_url();?>assets/images/favicon/favicon-32x32.png">
	<link rel="icon" type="image/png" sizes="96x96" href="<?php echo base_url();?>assets/images/favicon/favicon-96x96.png">
	<link rel="icon" type="image/png" sizes="16x16" href="<?php echo base_url();?>assets/images/favicon/favicon-16x16.png">
	<link rel="manifest" href="<?php echo base_url();?>assets/images/favicon/manifest.json">
	<meta name="msapplication-TileColor" content="#ffffff">
	<meta name="msapplication-TileImage" content="<?php echo base_url();?>assets/images/favicon/ms-icon-144x144.png">
	<meta name="theme-color" content="#ffffff">		

	<?php
	/*
	<script src="https://www.google.com/recaptcha/api.js" async defer></script>
	<script>
		function onSubmit(token) {
			let identify = document.getElementById('identify').value;
			let password = document.getElementById('password').value;

			if(identify == '')
			{
				alert('Username and Password cannot be empty');
			}
			else if(document.getElementById('identify').value == '' || document.getElementById('password').value == '')
			{
				alert('Username or Password cannot be empty');
			}
			else
			{
				document.getElementById("kumis-form-login").submit();
			}
			
			grecaptcha.reset();
		}
     </script>
	 */
	 ?>
</head>

<body style="background-color:#f2f6fc">

<div class="container">
	<div class="row justify-content-center">
		<div class="col-xl-5 col-lg-6 col-md-9">
			<div class="card o-hidden border-1 shadow-sm my-5">
				<div class="card-body p-0">
					<div class="row">
						<div class="col-lg-12">
							<div class="p-5">
								<div class="text-center">
									<h3><?php echo APP_NAME; ?></h3>
								</div>

								<form action="<?php echo site_url();?>/login/process_login" method="post" id="kumis-form-login">								
								<?php 
								echo validation_errors();
								echo $this->session->flashdata('action_status');
								?>
									<div class="form-group">
										<div class="input url">
											<label for="email">E-mail</label>
											<input name="identity" type="text" id="identify" class="form-control" autofocus="autofocus"/>
										</div>
									</div>
									<div class="form-group">
										<div class="input number">
											<label for="password">Password</label>
											<input name="password" class="form-control" type="password" id="password"/>
										</div>
									</div>
									<div class="form-group">
										<div class="submit">
											<?php
											/*
											<button type="submit" id="btn-login" class="g-recaptcha btn btn-success btn-lg btn-user btn-block" data-sitekey="6LdSKYwcAAAAAKjCpRV2vUq2eXR9_bl1G5yxikyc" data-callback="onSubmit">Login</button>
											*/
											?>
											<button type="submit" id="btn-login" class="btn btn-success btn-lg btn-user btn-block" >Login</button>
										</div>  
										<?php
										/*
										<hr>
										<div class="text-center">
											<a class="small" href="<?php echo base_url();?>assets/register">Create an Account!</a>
										</div>
										*/
										?>
									</div>
								<?php echo form_close();?>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="text-center">
				<a href="https://kurmamedia.com" target="_blank"><img src="<?php echo base_url();?>assets/images/from-kurmamedia.png"/></a>
			</div>
		</div>
	</div>
</div>

</body>
</html>
