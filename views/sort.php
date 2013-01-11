<?php 
/**
 * @name Product Browse
 * Template for results of a detailed product search to show in the dashboard*/

$pageName = "Sort";
?>

<header class="dashboard-context-bar">
	<!-- context-sensitive buttons for the dashboard -->
	
	<select>
		<option>Filter options
	</select>
</header>
<div class="dashboard-content">
	<!-- Content for the dasbboard, such as the list etc. -->
	<div class="dashboard-list-container">
		<?php
		if(isset($_GET['location']))
		{
			$sortedList = $product_functions->sortList($_GET['location']);
			foreach($sortedList['shops'] AS $shopID => $shopArray)
			{
			?>
			
			<article class="sorted-list <?php if($shopID == 3){echo "prisma";}elseif($shopID == 70){echo "kmarket";}?>">
				<h2><?php echo $shopArray['attributes']['chainName']?></h2>
				
				<?php 
				if(isset($shopArray['listItems']))
				{
					
					foreach($shopArray['listItems'] AS $listItemID => $listItemArray)
					{
							
					?>
					
					<div class="sorted-list-item">
						<?php echo $listItemArray['quantity']?>&times; 
						<span class="list-brand"><?php echo $listItemArray['brand']?></span> 
						<?php echo $listItemArray['name']?>
						<div class="price-info">
							<?php echo $product_functions->formatPriceValue($listItemArray['price'])?>&euro; 
							<small>Saved: <?php echo $product_functions->formatPriceValue($listItemArray['saved'])?>&euro;</small>
						</div>
						<div class="checkout-button">checkout this item</div>
					</div>
					
					<?php
					}
				
				?>
				<footer>
					shop total: <?php echo $product_functions->formatPriceValue($shopArray['attributes']['total'])?>&euro;
				</footer>
				<?php 
				}
				else
				{
					
				?>
				No products for this shop
			<?php
				}
				?>
				
			</article>
				<?php 
				
			}
			?>
			<div class="totals">
				<span class="sum-total">sum total: <?php echo $product_functions->formatPriceValue($sortedList['attributes']['listtotal'])?>&euro;</span>
				<span class="total-saved">total saved: <?php echo $product_functions->formatPriceValue($sortedList['attributes']['totalsaved'])?>&euro;</span>
			</div>
			<?php
		}
		elseif(empty($_GET['location']) || $_GET['location'] == "")
		{
		?>
		
			<div class="alert alert-info">No location to sort with</div>
		
		<?php
		}
		?>
	</div>
</div>