<?php include("header.php");?>
	    
	<?php if($session->logged_in){?>
	<!-- Body Content -->
	<div class="wrapper" role="main">
		<div class="row-fluid">
			
			<aside class="span4 toolbar">
				<div class="tabbable tabs-below"> <!-- Only required for left/right tabs -->
					<ul class="nav nav-tabs">
						<li class="active"><a href="#toolbar-products" data-toggle="tab">Products</a></li>
						<li><a href="#toolbar-prices" data-toggle="tab">Prices</a></li>
						<li><a href="#toolbar-shops" data-toggle="tab">Shops</a></li>
					</ul>
				
					<!-- left-aligned toolbar with settings and misc tools -->
					<div class="tab-content">
						<section class="tab-pane active" id="toolbar-products">
							<h2>add new product</h2>
							<form class="toolbar-set toolbar-add-item">
								<label>
									Product Name:
									<input type="text" class="input toolbar-add-item-name">
								</label>
								<div class="control-group toolbar-add-item-brand-group">
									<label class="control-label">
										Brand:
										<input type="text" class="input toolbar-add-item-brand">
									</label>
								</div>
								<div class="well well-small search-results toolbar-add-item-brand-results"></div>
								<label>
									Category:
									<select class="toolbar-add-item-cat">
										<?php 
											//To be filled with ajax (hopefully)
											$product_functions->categoryList();
										?>
									</select>
								</label>
								<label>Volume Type:</label>
									<label class="radio">
										<input type="radio" name="toolbar-add-item-vol" class="toolbar-add-item-vol" id="toolbar-add-item-vol-1" value="1" checked>KG
									</label>
									<label class="radio">
										<input type="radio" name="toolbar-add-item-vol" class="toolbar-add-item-vol" id="toolbar-add-item-vol-2" value="2">Liters
									</label>
								<label class="toolbar-add-item-size-container">
									Size:
									<div class="input-append">
										<input type="number" class="toolbar-add-item-size">
									</div>
								</label>
								<button class="btn btn-block">Add Product</button>
							</form>
							
							<div class="toolbar-product-search toolbar-set">
								<label>Product Search
									<input type="text" class="toolbar-product-search-form" placeholder="Search...">
								</label>
								<div class="well well-small search-results">
								</div>
							</div>
						</section>
						
						<section class="tab-pane toolbar-price-update-container" id="toolbar-prices">
							<h2>Update Prices</h2>
							
							<form class="toolbar-set toolbar-price-update">
								
								
								<div class="toolbar-price-update-product-container">
									<div class="control-group toolbar-price-update-product-group">
										<label class="control-group">
											Product Name:
											<input type="text" class="toolbar-price-update-product-input" placeholder="Product to Edit">
											<input type="hidden" class="toolbar-price-update-product-id" value="-1">
										</label>
									</div>
									<div class="well well-small search-results"></div>
								</div>
								
								<label>
									Price:
									<input type="number" class="input toolbar-price-update-price">
								</label>
								<div class="toolbar-price-update-shop-container">
									<div class="control-group toolbar-price-update-shop-group">
										<label class="control-group">
											Shop:
											<input type="text" class="input toolbar-price-update-shop">
											<input type="hidden" class="toolbar-price-update-shop-id" value="-1">
											
										</label>
									</div>
									<div class="well well-small search-results toolbar-shop-search-results"></div>
								</div>
								<button type="submit" class="btn btn-block">Update Price</button>
							</form>
						</section>
						
						<section class="tab-pane" id="toolbar-shops">
							<h2>Add New Shops</h2>
						</section>
					</div>
				</div><!-- .tabbable -->
			</aside>
			<section class="span8 dashboard">
				<header class="dashboard-context-bar">
					<!-- context-sensitive buttons for the dashboard -->
					<form class="form-horizontal sort-list">
					    <div class="input-append">
					    	<input class="location-input" name="location" placeholder="Your location" required type="text">
					    	<button class="btn btn-primary sort-list-submit" type="submit">Sort List!</button>
					    </div>
					</form>
				</header>
				<div class="dashboard-content">
					<!-- Content for the dasbboard, such as the list etc. -->
					<div class="dashboard-list-container">
						<!-- Container for the list, to apply fixed width to control size -->
						<?php $session->theShoppingList();?>
					</div>
				</div>
			</section>
		</div>
	</div>
	</div> <!--! end of .wrapper -->
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
