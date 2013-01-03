<?php 
require ('./include/constants.php');
include("header.php");?>
	    
	<?php if($session->logged_in){?>
	<!-- Body Content -->
	<?php 
	
		
		if(isset($_GET['page']))
		{
			$_SESSION['dashcontent'] = $_GET['page'];
			if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>get var set @ index.php</p>\n";}
		}
		else
		{
			if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>get not set, unsetting the session var @ index.php</p>\n";} 
			unset($_SESSION['dashcontent']);
		}
		
		include 'views/dashboard.php';
	?>
	
	
	<?php }else{?>
	<div class="wrapper" role="main">
		<section class="intro-text">
			<p>
				Sed eu dui ut sapien luctus dictum. Vivamus ac imperdiet ligula. 
				Fusce mattis auctor ante, sit amet convallis mi consectetur nec.
			</p>
		</section>
		<section class="row-fluid hero-unit landing-showcase">
			<article class="span6">
				Eos et illum sonet errem, per at esse vitae, an doming eripuit fääcilisi qui. 
				Mel at liber malörum ancillääe, ea tale persequeris mei, suas option feugait cu vix. 
				Assum quidam phaedrum vix te, suavitäte cönceptam vim at, 
				sed äd nöbis viderer omittantur. Cu novum epicurei sit, tötää vidisse cu est.
				<div class="clearfix showcase-buttons">	
					<button class="btn btn-large">Try It For Free</button>
					<button class="btn btn-inverse btn-large">Join Now</button>
				</div>
			</article>
			<aside class="span6">
				<img src="http://placehold.it/320x240" alt="showcase image placeholder">
			</aside>
		</section>
		
		<section class="row-fluid">
			<article class="span3">
				<h2>Lorum Ipsum</h2>
				<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. 
				Sed eu dui ut sapien luctus dictum. Vivamus ac imperdiet ligula. 
				Fusce mattis auctor ante, sit amet convallis mi consectetur nec.</p>
			</article>
			<article class="span3">
				<h2>Lorum Ipsum</h2>
				<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. 
				Sed eu dui ut sapien luctus dictum. Vivamus ac imperdiet ligula. 
				Fusce mattis auctor ante, sit amet convallis mi consectetur nec.</p>
			</article>
			<article class="span3">
				<h2>Lorum Ipsum</h2>
				<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. 
				Sed eu dui ut sapien luctus dictum. Vivamus ac imperdiet ligula. 
				Fusce mattis auctor ante, sit amet convallis mi consectetur nec.</p>
			</article>
			<article class="span3">
				<h2>Lorum Ipsum</h2>
				<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. 
				Sed eu dui ut sapien luctus dictum. Vivamus ac imperdiet ligula. 
				Fusce mattis auctor ante, sit amet convallis mi consectetur nec.</p>
			</article>
			
			
		</section>
	</div>
	<?php }?>
    
    <?php include("footer.php");?>
