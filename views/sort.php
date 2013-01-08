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
			
				/*if($shopID === "noprice")
				{
				?>
							
					<article class="sorted-list">
						<h2>products with no price</h2>
					<?php 
						foreach($sortedList['noprice'] AS $listItemID => $listItemArray)
						{
						?>
							<div class="sorted-list-item">
								<span class="list-brand"><?php echo $listItemArray['brand']?></span> 
								<?php echo $listItemArray['name']?>
							</div>
						<?php
						}
					?>
					</article>
					
				<?php
				}
				*/
			?>
			
			<article class="sorted-list">
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
						<?php echo $listItemArray['name']?> &mdash; 
						<?php echo $product_functions->formatPriceValue($listItemArray['price'])?>&euro; 
						<strong>Saved: <?php echo $product_functions->formatPriceValue($listItemArray['saved'])?>&euro;</strong>
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
			</article>
			<?php
				}
			}
			?>
			sum total: <?php echo $product_functions->formatPriceValue($sortedList['attributes']['listtotal'])?>&euro;<br>
			<em>total saved: <?php echo $product_functions->formatPriceValue($sortedList['attributes']['totalsaved'])?>&euro;</em>
			
			<?php
		}
		else
		{
		?>
		
			<div class="alert alert-info">No location to sort with</div>
		
		<?php
		}
		?>
	</div>
</div>