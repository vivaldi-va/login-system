<aside class="toolbar">
		<div class="toolbar-section">
			<a href="#" class="btn toolbar-button toolbar-detailed-search-link">Add Product</a>
			<section class="toolbar-window" id="toolbar-products">
				<h2>search and browse</h2>
				<form class="toolbar-detailed-search">
					<div class="input-append">
						<input type="text" class="toolbar-detailed-search-input" placeholder="Product Name or Brand">
						<button class="btn" type="submit">Search</button>
					</div>
				</form>
			</section>
		</div>
		<div class="toolbar-section">
			<a href="#" class="btn toolbar-button toolbar-add-product-link">Add Product</a>
			<section class="toolbar-window" id="toolbar-products">
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
						<input type="number" class="toolbar-add-item-size">
					</label>
					<label>
						Barcode:
						<input type="number" class="toolbar-add-item-barcode">
					</label>
					<button class="btn btn-block">Add Product</button>
				</form>
			</section>
		</div>
		
		<div class="toolbar-section">
			<a href="#" class="btn toolbar-button toolbar-add-price-link">Add a Price</a>
			<section class="toolbar-window toolbar-price-update-container" id="toolbar-prices">
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
		</div>
	</aside>