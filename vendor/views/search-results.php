<section class="search-results">
<?php
$searchResult = $product_functions->search($_GET['searchterm'], true);
if($searchResult)
{
	foreach($searchResult AS $prodArr)
	{
	?>
	<article class="search-result">
		<div class="search-result-price">
			
			<?php 
			if($price = $product_functions->getProductPrice($prodArr['id'], $vendor->shop))
			{
				echo $price;?>&euro;
			<?php 
			}
			else
			{
			?>
				N/A
			<?php 
			}
			?>
		</div>
		<div class="search-result-title">
			<span class="search-result-brand"><?php echo $prodArr['brand'];?></span>
			<span class="search-result-name"><?php echo $prodArr['name'];?></span>
		</div>
	</article>
	<?php
	}
}
else
{
?>
	<article class="search-result">
		<div class="search-result-price">
		</div>
		<div class="search-result-title">
			<span class="search-result-name">no products found</span>
		</div>
	</article>
<?php 
}
?>
</section>
