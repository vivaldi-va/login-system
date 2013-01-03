<?php 
/**
 * @name Product Browse
 * Template for results of a detailed product search to show in the dashboard*/

$pageName = "Browse";
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
		<!-- Container for the list, to apply fixed width to control size -->
		<?php 
		if(isset($_GET['searchTerm']))
		{
			$product_functions->browseSearch($_GET['searchTerm']);
		}
		else 
		{
			echo "<div class=\"alert alert-info\">There was no product entered to edit</div>\n";
		}

			
		?>
	</div>
</div>