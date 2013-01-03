<?php 
if(isset($_GET))
{
$productID = $_GET['productid'];

?>

<section class="product-info">
	<article class="info-block base-price">
	<?php echo $product_functions->getProductPrice($productID, $vendor->shop)?>
	</article>
	<article class="info-block calculated-price">
	
	</article>
	<article class="info-block set-discount-price">
	
	</article>
	<article class="info-block discount-percent">
	
	</article>
	<article class="info-block valid-time">
	
	</article>
	<article class="info-block batch-size">
	
	</article>
</section>
<?php 
}
else
{
?>

<section class="product-info">
	<h2>error, no product info</h2>
</section>

<?php 
}
?>