/*
function populateList()
{
	$.get("process.php", {subdisplaylist: 1},
		function(data)
		{
			$(".shopping-list").html(data);
		}
	);
}
*/

function loadingAnimation(stop=false)
{
	if(!stop)
	{
		$("body").append("<div class=\"loading-animation\"><img src=\"./img/loading.png\" alt=\"loading animation\"></div>")
	}
	else
	{
		$(".loading-animation").fadeOut("fast");
	}
}


$(document).ready(function(){
	
	
	$.fn.preload = function() {
	    this.each(function(){
	        $('<img/>')[0].src = this;
	    });
	}

	// Usage:

	$(['./img/loading.png']).preload();

	

	/* Dashboard fullscreen */
	$(".dashboard").height($(window).height()-42);
	$(".dashboard-content").height($(window).height()-82);
	$(".toolbar").height($(window).height()-42);



	/* Login form dropdown */
	$(".login-form .dropdown-toggle").click(function(){
		$(".login-dropdown-form").toggle("fast");
	});
	
	/*
	 * Login Form Submit
	 */
	$(".login-form").submit(function(e){
		e.preventDefault();
		var email = $(".login-form .login-email").val();
		var pass = $(".login-form .login-pass").val();
		var remember = false;
		if($(".login-form .login-remember:checked").length == 1)
		{
			var remember = true;
		}
		var token = $(".login-form .form-token").val();
		
		$.post("process.php", {sublogin: 1, email: email, pass: pass, remember: remember, token: token},
		function(){
			window.location.replace("index.php");
		});
		
	});
	
	

	
	
	/*
	 * If the category dropdown list select element is presnet on the page, 
	 * populate it with the info returned form the php function in process.php
	 */
	/*if($(".cat-dropdown").length > 0)
	{
		console.log("category selector found");
		$.post("process.php", {subcatlist: 1},
		function(data)
		{
			console.log("populating dropdown");
			$(".cat-dropdown").html(data);
		});
	}*/
	
	
	/**
	 * Function to reset the session var controling the 
	 */
	/*$("#home-link").click(function(e){
		e.preventDefault();
		console.log("index link clicked");
		$.post("process.php", {subindex: 1}, 
		function()
		{
			window.location = "index.php";
		});
		
	});*/
	
	$(".sort-list").submit(function(event){
		event.preventDefault();
		var location = $("#primary-location").val();
		console.log("Sort list activated at location: " + location);
		$.post("process.php", {sortlocation: location, subsortlist: 1},
			function(data)
			{
				$(".dashboard-list-container").html(data);
				console.log("something has returned!");
			}
		);
	});
	
	$(".dashboard-list-container").on("click", ".return-to-list", function(e){
		e.preventDefault();
		console.log("List return link clicked");
		$.post("process.php", {subdisplaylist: 1},
		function(data)
		{
			$(".dashboard-list-container").html(data).fadeIn(200);
			console.log("something has returned!");
		});
	});
	
	
	
	
	
	$(".toolbar-button").click(function(e){
		e.preventDefault();
		
		if($(".toolbar-button").hasClass("active"))
		{
			$(".toolbar-button").removeClass("active");
		}
		else
		{
			$(this).addClass("active");
		}
		
		/*
		 * Hide all open windows
		 */
		if($(".toolbar-window").hasClass("visible"))
		{
			$(".toolbar-window").removeClass("visible");
		}
		else
		{
			/*
			 * Show the window attached to the button you clicked
			 * (it's the element following the button
			 */
			$(this).next(".toolbar-window").addClass("visible");
		}
	});
	
//	$("html").click(function(){
//		$(".toolbar-window").hide();
//	});
	
	
	/*
	 * Product search auto-complete function
	 * 	
	 */
	
	
	$(".toolbar-product-search-form").keyup(function(){
		if($(".toolbar-product-search-form").val().length > 2 && $(".toolbar-product-search-form").is(":focus"))
		{
			
			$(".toolbar-product-search .search-results").show('fast');
			//Loading animation image
			$(".toolbar-product-search .search-results").html("<img src=\"./img/loading.png\" alt=\"loading animation\" style=\"position: relative; left: 45%; padding: 1em 0;\">");
			
			//$(".toolbar-product-search .search-results img").show();
			console.log('search bar changed');
			var term = $(this).val();
			
			$.post("process.php", {searchTerm: term, subsearch: 1},
			function(data)
			{
				$(".toolbar-product-search .search-results img").hide();
				if(data != "")
				{
					console.log('something has returned');
					$(".toolbar-product-search .search-results").html(data);
				}
				else
				{
					$(".toolbar-product-search .search-results").hide();
				}
			});
			
			/*
			$.ajax({
				  url:"process.php",
				  type:"POST",
				  data:{searchTerm: term, subsearch: 1},
				  contentType: "application/x-www-form-urlencoded; charset=ISO-8859-1",
				  dataType:"text",
				  success: function(data){
					  $(".toolbar-product-search .search-results img").hide();
						if(data != "")
						{
							console.log('something has returned');
							$(".toolbar-product-search .search-results").html(data);
						}
						else
						{
							$(".toolbar-product-search .search-results").hide();
						}
				  }
				});*/
			
		}
		else
		{
			
			$(".toolbar-product-search .search-results").hide('fast');
		}
	});
	
	/*
	 * Add product to the shopping list
	 */
	$(".toolbar-product-search").on("click", ".add-item", function(e){
		console.log("search item clicked");
		var prodId = $(this).attr("id");
		var quantity = parseInt($(this).prev().val());
		console.log("product id: " + prodId);
		console.log("quantity: (" + typeof quantity + ") " + quantity);
		$.post("process.php", {addProdID: prodId, addProdQuantity: quantity, subaddtolist: 1},
		function(data)
		{
			$(".dashboard-list-container").fadeIn(300).html(data);
		});
		$(this).removeClass("icon-plus");
		$(this).addClass("icon-ok");
		$(this).parent().css("background-color", "#DFF0D8");
		$(this).parent().css("color", "#468847");
		//$(this).parent().animate({color: '#468847'}, 300);
		$(this).parent().delay(600).fadeOut("fast");
		

		$(".toolbar-product-search .search-results").delay(1500).hide('fast');
	});
	
	$(".dashboard").on("click", ".list-add", function(e){
		e.preventDefault();
		console.log("search item clicked");
		var prodId = $(this).attr("id");
		var quantity = parseInt($(this).prev().val());
		console.log("product id: " + prodId);
		console.log("quantity: (" + typeof quantity + ") " + quantity);
		console.log(typeof($(this).parent().parent()));
		$.post("process.php", {addProdID: prodId, addProdQuantity: 1, subaddtolist: 1},
		function()
		{
			console.log("Something has returned!");
			//$(".dashboard-list-container").fadeIn(300).html(data);
			$(this).parent().parent().css("background-color", "#DFF0D8");
			$(this).parent().parent().css("color", "#468847");
		});
	});
	
	
	
	
	/*
	 * Call to sort the list by price and chain
	 
	$(".sort-list").click(function(){
		 $.post("process.php", {subsortlist: 1});
	});
	*/
	
	$(".toolbar-detailed-search").submit(function(e)
	{
		e.preventDefault();
		console.log("search initialized");
		var searchTerm = $(".toolbar-detailed-search-input").val();
		console.log("Sending: " + searchTerm);
		$(".toolbar-detailed-search").append("<img src=\"./img/loading.png\" alt=\"loading animation\" style=\"position: relative; left: 45%; padding: 1em 0;\">")
		/*$.post("process.php", {browseTerm: searchTerm, subbrowse: 1},
		function(){
			console.log("Something has returned!");
			//window.location = "index.php"
		});*/
		//$.get("index.php", {page: "browse", searchTerm: searchTerm});
		window.location = "index.php?page=browse&searchTerm=" + searchTerm;
	});
	
	
	
	
	
	$(".dashboard-list-container").on("change", ".list-item .quantity-input", function()
	{
		var listItemID = $(this).attr("id");
		var quantity = $(this).val();
		if(quantity == null)
		{
			quantity  = 1;
		}
		console.log("list item id: " + listItemID);
		$.post("process.php", {updateQuantListID: listItemID, updateQuantQuantity: quantity, subquantity: 1},
		function()
		{
			$(this).css("background-color", "#DFF0D8");
			$(this).delay(1000).css("background-color", "#fff");
		});
	});
	
	$(".dashboard-list-container").on("click", ".list-dropdown", function(e)
	{
		e.preventDefault();
		if($(this).parent().parent().hasClass("expanded"))
		{
			$(this).parent().parent().removeClass("expanded");
		}
		else
		{
			$(this).parent().parent().addClass("expanded");
		}
	});
	
	/**
	 * Add Product Functions
	 */
	
	
	$(".toolbar-add-item").submit(function(e){
		e.preventDefault();
		console.log("form submitted!");
		var productName = $(".toolbar-add-item .toolbar-add-item-name").val();
		var catID = $(".toolbar-add-item .toolbar-add-item-cat").val();
		var brandName = $(".toolbar-add-item .toolbar-add-item-brand").val();
		var volID = $(".toolbar-add-item .toolbar-add-item-vol:checked").val();
		var size = $(".toolbar-add-item .toolbar-add-item-size").val();
		var barcode = $(".toolbar-add-item .toolbar-add-item-barcode").val();
		console.log("volID = "+volID);	
		
		$.post("process.php", {subnewproduct: 1, productName: productName, catID: catID, brandName: brandName, volID: volID, size: size, barcode: barcode},
		function(data){
			$(".toolbar-add-item").append(data);
			$(".toolbar-add-item .alert").delay(2000).fadeOut(300);
			console.log("something has returned!");
		});
	});
	
	/*
	 * Brand name auto-complete
	 */
	$(".toolbar-add-item-brand").keyup(function(){
		if($(".toolbar-add-item-brand").val().length > 2)
		{
			$(".toolbar-add-item-brand-results").show('fast');
			var term = $(this).val();
			loadingAnimation();
			$.get("process.php", {subbrandsearch: 1, brandString: term}, 
			function(data)
			{
				$(".toolbar-add-item-brand-results").html(data);
				loadingAnimation(true);
			});
		}
	});
	
	$(".toolbar-add-item-brand-results").on("click", ".toolbar-add-item-brand-results .search-result a", function(){
		console.log("brand name result clicked");
		var brandName = $(this).attr("title");
		$(".toolbar-add-item-brand").val(brandName);
		$(".toolbar-add-item-brand-group").addClass("success");
		$(this).removeClass("icon-plus");
		$(this).addClass("icon-ok");
		$(this).parent().css("background-color", "#DFF0D8");
		$(this).parent().css("color", "#468847");
		$(".toolbar-add-item-brand-results").delay(300).fadeOut("fast");
		
	});
	
	$(".edit-product-brand").keyup(function(){
		if($(this).val().length > 2)
		{
			$(".edit-product-brand-results").show('fast');
			var term = $(this).val();
			loadingAnimation(true);
			$.get("process.php", {subbrandsearch: 1, brandString: term}, 
			function(data)
			{
				$(".edit-product-brand-results").html(data);
				loadingAnimation(true);
			});
		}
	});
	
	$(".edit-product-brand-results").on("click", ".search-result a", function(){
		console.log("brand name result clicked");
		var brandName = $(this).attr("title");
		$(".edit-product-brand").val(brandName);
		//$(".toolbar-add-item-brand-group").addClass("success");
		$(this).removeClass("icon-plus");
		$(this).addClass("icon-ok");
		$(this).parent().css("background-color", "#DFF0D8");
		$(this).parent().css("color", "#468847");
		$(".edit-product-brand-results").delay(300).fadeOut("fast");
		
	});
	
	
	$(".add-product-form").on("click", ".search-result a", function(){
		var brandName = $(this).attr("title");
		var brandID = $(this).attr("id");
		$(".brand-name-input").val(brandName);
		$(".brand-id").val(brandID);
	});
	
	
	sizeUnit = "kg";
	$(".toolbar-add-item-size-container .input-append").html("<input type=\"number\" class=\"toolbar-add-item-size\"><div class=\"add-on\">"+sizeUnit+"</div>");
	$(".toolbar-add-item-vol").change(function(){
		
		if($("#toolbar-add-item-vol-1").attr("checked"))
		{
			var sizeUnit = "kg";
		}
		else
		{
			var sizeUnit = "l";
		}
		
		$(".toolbar-add-item-size-container .input-append").html("<input type=\"number\" class=\"toolbar-add-item-size\"><div class=\"add-on\">"+sizeUnit+"</div>");
	});
	
	
	/**
	 * Add Price Functions
	 * ***************************************************************************************||
	 */
	
	
	/*
	 * Product Name Autocomplete
	 */
	$(".toolbar-price-update-product-input").keyup(function(){
		if($(".toolbar-price-update-product-input").val().length > 2)
		{
			
			$(".toolbar-price-update-product-container .search-results").show('fast').css("position", "relative");
			//Loading animation image
			$(".toolbar-price-update-product-container .search-results").html("<img src=\"./img/loading.png\" alt=\"loading animation\" style=\"position: relative; left: 45%; padding: 1em 0;\">");
			
			//$(".toolbar-product-search .search-results img").show();
			console.log('search bar changed');
			var term = $(this).val();
			
			/*$.post("process.php", {searchTerm: term, subsearch: 1},
			function(data)
			{
				$(".toolbar-product-search .search-results img").hide();
				if(data != "")
				{
					console.log('something has returned');
					$(".toolbar-product-search .search-results").html(data);
				}
				else
				{
					$(".toolbar-product-search .search-results").hide();
				}
			});*/
			
			
			$.ajax({
				  url:"process.php",
				  type:"POST",
				  data:{searchTerm: term, subsearch: 1},
				  contentType: "application/x-www-form-urlencoded; charset=UTF-8",
				  dataType:"text",
				  success: function(data){
					  $(".toolbar-price-update-product-container .search-results img").hide();
						if(data != "")
						{
							console.log('something has returned');
							$(".toolbar-price-update-product-container .search-results").html(data);
						}
						else
						{
							$(".toolbar-price-update-product-container .search-results").hide();
						}
				  }
				});
		}
		else
		{
			
			$(".toolbar-product-search .search-results").hide('fast');
		}
	});
	
	/* Add search result to the form */
	$(".toolbar-price-update-product-container .search-results").on("click", ".search-result a", function(){
		console.log("Product search result clicked");
		
		var productID = $(this).attr("id");
		$(".toolbar-price-update-product-id").val(productID);
		
		var productName = $(this).attr("title");
		
		$(".toolbar-price-update-product-input").val(productName);
		$(".toolbar-price-update-product-group").addClass("success");
		$(this).removeClass("icon-plus");
		$(this).addClass("icon-ok");
		$(this).parent().css("background-color", "#DFF0D8");
		$(this).parent().css("color", "#468847");
		$(".toolbar-price-update-product-container .search-results").delay(400).fadeOut(200);
		console.log($(".shop-id-value").val());
	});
	
	
	/*
	 * Shop location search auto-complete
	 */
	$(".toolbar-price-update").on("keyup", ".toolbar-price-update-shop", function(){
		if($(".toolbar-price-update-shop").val().length > 2)
		{
			$(".toolbar-price-update-shop-container .search-results").show('fast').css("position", "relative");
			//Loading animation image
			$(".toolbar-price-update-shop-container .search-results").html("<img src=\"./img/loading.png\" alt=\"loading animation\" style=\"position: relative; left: 45%; padding: 1em 0;\">");
			$(".toolbar-price-update-shop-group").removeClass("success");
			
			var term = $(this).val();
			console.log('shop search: '+term );
			$.post("process.php", {shopSearchString: term, subshopsearch: 1},
			function(data)
			{
				if(data != "")
				{
					console.log('something has returned');
					$(".toolbar-price-update-shop-container .search-results").html(data);
				}
				else
				{
					$(".toolbar-price-update-shop-container .search-results").hide('fast');
				}
			});
			
			/*$.ajax({
				  url:"process.php",
				  type:"POST",
				  data:{shopSearchString: term, subshopsearch: 1},
				  contentType: "application/x-www-form-urlencoded; charset=UTF-8",
				  dataType:"text",
				  success: function(data){
					  $(".toolbar-price-update-shop-container .search-results img").hide();
					  if(data != "")
						{
							console.log('something has returned');
							$(".toolbar-price-update-shop-container .search-results").html(data);
						}
						else
						{
							$(".toolbar-price-update-shop-container .search-results").hide('fast');
						}
				  }
				});*/
			
		}
		else
		{
			$(".toolbar-price-update-shop-container .search-results").hide('fast');
		}
	});
	
	

	/* Add search result to the form */
	$(".toolbar-price-update-shop-container .search-results").on("click", ".search-result a", function(){
		console.log("Store search result clicked");
		var storeID = $(this).attr("id");
		var storeName = $(this).attr("title");
		$(".toolbar-price-update-shop").val(storeName);
		$(".toolbar-price-update-shop-id").val(storeID);
		
		
		$(".toolbar-price-update-shop-group").addClass("success");
		$(this).removeClass("icon-plus");
		$(this).addClass("icon-ok");
		$(this).parent().css("background-color", "#DFF0D8");
		$(this).parent().css("color", "#468847");
		$(".toolbar-price-update-shop-container .search-results").delay(400).fadeOut(200);;
		console.log($(".shop-id-value").val());
	});
	
	
	
	/*
	 * Submit the price change form
	 */
	$(".toolbar-price-update").submit(function(e){
		e.preventDefault();
		console.log("price change form submitted");
		var productID = $(".toolbar-price-update-product-id").val();
		var price = $(".toolbar-price-update-price").val();
		var shopID = $(".toolbar-price-update-shop-id").val();
		$.post("process.php", {productID: productID, price: price, shopID: shopID, subupdateprice: 1},
		function(data)
		{
			$(".toolbar-price-update").append(data);
			$(".toolbar-price-update .alert").delay(400).fadeOut(200);
		});
	});
	
	
	/*
	 * Add brand form
	 */
	
	$(".admin-add-brand").submit(function(e){
		e.preventDefault();
		console.log("add brand formsubmitted");
		var brandName = $(".admin-add-brand .admin-brandname-input").val();
		$.post("process.php", {adminBrandName: brandName, subaddbrand: 1}, 
		function(data){
			console.log("something has returned");
			$(".admin-add-brand .admin-brandname-input").after(data);
			$(".admin-add-brand .alert").delay(2000);
			$(".admin-add-brand .alert").fadeOut(200);
		});
	});
	
	$(".admin-add-brand button").click(function(){
		console.log("add brand button clicked");
	});
	
	
	$(".dashboard-list-container").on("click", ".list-remove", function(e)
	{
		e.preventDefault();
		var listItemID = $(this).attr("id");
		//alert(productName);
		
		if(listItemID.length >= 0)
		{
			//$(".dashboard-loading").show("fast");
			loadingAnimation();
			$.post("process.php", {remProdID: listItemID, subremovefromlist: 1},
			function(data)
			{
				console.log("list item #"+listItemID+" removed from list");
				$(".dashboard-list-container").fadeIn(300).html(data);
				//$(".dashboard-loading").hide("fast");
				loadingAnimation(true);
			});
			
		}
		
	});
	
	
	$(".edit-product-form").submit(function(e){
		e.preventDefault();
		var productID = $(".edit-product-id").val();
		var productName = $(".edit-product-name").val();
		var brandName = $(".edit-product-brand").val();
		var category = $(".edit-product-cat").val();
		var barcode = $(".edit-product-barcode").val();
		var description = $(".edit-product-description").val();
		
		loadingAnimation();
		$.post("process.php", {productID: productID, productName: productName, brandName: brandName, category: category, barcode: barcode, description: description, subupdateproduct: 1},
		function(data)
		{
			$(".edit-product-form button").after(data);
			loadingAnimation(true);
			$(".edit-product-form .alert").delay(2000).fadeOut("fast");
		});
	});
	
	
	
});//document.ready

/*
function addClicked(searchItem)
{
	productID = searchItem.getAttribute("id");
	//alert(productName);
	
	if(productID.length >= 0)
	{
		$.post("process.php", {addProdID: productID, subaddtolist: 1});
		console.log("product #"+productID+" added to list");
	}
	
}

function removeClicked(searchItem)
{
	var listItemID = searchItem.getAttribute("id");
	//alert(productName);
	
	if(listItemID.length >= 0)
	{
		$(".dashboard-loading").show(fast);
		$.post("process.php", {remProdID: listItemID, subremovefromlist: 1},
		function(data)
		{
			console.log("list item #"+listItemID+" removed from list");
			$(".dashboard-list-container").fadeIn(300).html(data);
			$(".dashboard-loading").hide(fast);
		});
		
	}
	
} */

/*
 * When you click an edit-product button, send the product id to be stored as a session var
 * then redirect to the product edit page.
 
$(".shopping-list").on("click", ".product-edit", function(){
	var productID = $(".product-edit").attr("id");
	console.log("Editing product " + productID);
	$.post("process.php", {subprodedit: 1, prodEditProductID: productID},
	function(){
		window.location.replace("priceUpdate.php");
	});
})*/

function editClicked(searchItem)
{
	var productID = searchItem.getAttribute("id");
	console.log("Editing product " + productID);
	$.post("process.php", {subprodedit: 1, prodEditProductID: productID},
	function()
	{
		window.location.replace("priceUpdate.php");
	});
}