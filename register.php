<?php
/**
 * Register.php
 * 
 * Displays the registration form if the user needs to sign-up,
 * or lets the user know, if he's already logged in, that he
 * can't register another name.
 *
 * Written by: Jpmaster77 a.k.a. The Grandmaster of C++ (GMC)
 * Last Updated: August 19, 2004
 */
include("include/session.php");
include 'header.php';
?>


<div class="wrapper">
<section>
<?php

if(!$session->logged_in)
{
?>
	<h1>create new account</h1>
	<form method="post" action="process.php">
		<label for="reg-email">email address</label>
		<input type="email" id="reg-email" name="reg-email" class="input">
		
		<label for="reg-name">name</label>
		<input type="text" id="reg-name" name="reg-name" class="input">
		
		<label for="reg-password1">password</label>
		<input type="password" id="reg-password1" name="reg-password1" class="input">
		
		<label for="reg-password2">repeat password</label>
		<input type="password" id="reg-password2" name="reg-password2" class="input">
		
		<input type="hidden" value="1" name="subjoin">
		<button type="submit" class="btn btn-primary">create account</button>
	</form>
<?php 	
}
else
{
?>
	<h1>you are already registered</h1>
	<a href="index.php" class="btn">Go to dashboard</a>
<?php
}
?>
</section>
</div>
<?php include 'footer.php';?>
