
jQuery(document).ready(function(){
	

	/*var dtToday = new Date();
	
	var wwsd_month = dtToday.getMonth() + 1;
	var wwsd_day = dtToday.getDate();
	var wwsd_year = dtToday.getFullYear();
	if(wwsd_month < 10)
		wwsd_month = '0' + wwsd_month.toString();
	if(wwsd_day < 10)
		wwsd_day = '0' + wwsd_day.toString();
	
	var wwsdMaxDate = wwsd_year + '-' + wwsd_month + '-' + wwsd_day;
	jQuery('.wwsd_from_date').attr('min', wwsdMaxDate);
	jQuery('.wwsd_to_date').attr('min', wwsdMaxDate);*/

	
	
	var slab_row = jQuery('#wwsd-wholesale-append-js-row').html();
	
	jQuery('#wwsd-add-discount-slab').click(function(){ 
		jQuery('#wwsd-wholesale-field-settings').append(slab_row);
	});	
	
	jQuery('#wwsd_all_time_discount').on('change',function(){
		if (jQuery(this).is(':checked')) {
			jQuery('#wwsd_discount_date_range').hide();
		}else{
			jQuery('#wwsd_discount_date_range').show();
		}
	});
	
	
	
	jQuery( document ).on( "click", ".wwsd-add-discount-slab", function() {
	
		//jQuery('#wwsd-wholesale-field-settings').append(slab_row);
		var slab_row = jQuery('#wwsd-wholesale-append-js-row-hide .wwsd-wholesale-append-js-row');
		var $newInnerPanel = slab_row.clone();
		var hash_id = jQuery(this).parent().find('.wwsd-wholesale-append-js-row-cont').attr("rel");
		
		//jQuery('#wwsd-wholesale-append-js-row-hide input').val("");
		
		$newInnerPanel.find('.wwsd-cat-min-dis').attr('name', 'wwsd_minimum_discount_qrt'+hash_id+'[]').attr('name', 'wwsd_minimum_discount_qrt'+hash_id+'[]');
		$newInnerPanel.find('.wwsd-cat-flat-dis').attr('name', 'wwsd_flat_discount_rate'+hash_id+'[]').attr('name', 'wwsd_flat_discount_rate'+hash_id+'[]');
		$newInnerPanel.find('.wwsd-cat-percent-dis').attr('name', 'wwsd_percent_discount'+hash_id+'[]').attr('name', 'wwsd_percent_discount'+hash_id+'[]');
		
		jQuery(this).parent().find('.wwsd-wholesale-append-js-row-cont').append($newInnerPanel.fadeIn());
		
	});	
	
	
	jQuery( document ).on( "click", ".wwsd_all_time_discount", function() {
	
		if (jQuery(this).is(':checked')) {
			jQuery('#wwsd_wholesale_discount_tab .wwsd_discount_date_range').hide();
		}else{
			jQuery('#wwsd_wholesale_discount_tab .wwsd_discount_date_range').show();
		}
	});
	

	/** classic mode of multiselection dropdown **/
	//jQuery('.wwsd_discount_categories').select();	
	var chooseCat = new Choices('.wwsd_discount_categories', {removeItemButton:true,});


	
	/** Dynamic Accordian for Category discount **/

	var $template = jQuery(".new-category-block-clone");
	var hash = 0;
	
	jQuery(".add-new-cat-discount").on("click", function () {
		
		var slab_count = parseInt(jQuery('#hdnTotalSlabs').val());

		var slab_count_counter = jQuery("#hdnTotalSlabsCounter").val(); 
		slab_count_counter_arr = slab_count_counter.split(',');
		var lastval = parseInt(slab_count_counter_arr[slab_count_counter_arr.length-1]);
		if(isNaN(lastval)) lastval = 0;
		slab_count = lastval + 1;
		hash = slab_count;
		
		var $newPanel = $template.clone();

		$newPanel.find(".collapse").removeClass("in");
		$newPanel.find(".accordion-toggle").attr("href",  "#" + (hash)).text("Category Discount Rule #" + slab_count);
		$newPanel.find(".cat-track").addClass("wwsd_discount_categories_clone"+hash);
		$newPanel.find(".panel-collapse").attr("id", hash).addClass("collapse").removeClass("in");
		jQuery("#accordion-cat-discount").append($newPanel.fadeIn());
		
		new Choices('.wwsd_discount_categories_clone'+hash, {removeItemButton:true,});
		//jQuery('.wwsd_discount_categories_clone'+hash+' .wwsd-cat-min-dis').attr('name', jQuery(this).attr('name') + hash);

		//alert(jQuery('#'+hash+' .wwsd-cat-min-dis').val());
		$newPanel.find('.wwsd-wholesale-append-js-row-cont').attr("rel", hash);
		
		jQuery('#'+hash+' .wwsd-cat-min-dis').attr('name', 'wwsd_minimum_discount_qrt'+hash+'[]').attr('name', 'wwsd_minimum_discount_qrt'+hash+'[]');
		jQuery('#'+hash+' .wwsd-cat-flat-dis').attr('name', 'wwsd_flat_discount_rate'+hash+'[]').attr('name', 'wwsd_flat_discount_rate'+hash+'[]');
		jQuery('#'+hash+' .wwsd-cat-percent-dis').attr('name', 'wwsd_percent_discount'+hash+'[]').attr('name', 'wwsd_percent_discount'+hash+'[]');
		jQuery('#'+hash+' .cat-track').attr('name', 'wwsd_discount_categories'+hash+'[]').attr('name', 'wwsd_discount_categories'+hash+'[]');
		jQuery('#'+hash+' .enable_wwsd_discount').attr('name', 'enable_wwsd_discount'+hash).attr('name', 'enable_wwsd_discount'+hash);
		jQuery('#'+hash+' .wwsd_all_time_discount').attr('name', 'wwsd_all_time_discount'+hash).attr('name', 'wwsd_all_time_discount'+hash);
		jQuery('#'+hash+' .wwsd_from_date').attr('name', 'wwsd_from_date'+hash).attr('name', 'wwsd_from_date'+hash);
		jQuery('#'+hash+' .wwsd_to_date').attr('name', 'wwsd_to_date'+hash).attr('name', 'wwsd_to_date'+hash);
		//jQuery('#wwsd-wholesale-append-js-row .wwsd-cat-min-dis').attr('name', 'wwsd_minimum_discount_qrt'+hash+'[]').attr('name', 'wwsd_minimum_discount_qrt'+hash+'[]');
		
		//--> Count Total Slabs
		jQuery('#hdnTotalSlabsCounter').val(function(i,val) { 
			 return val + (!val ? '' : ',') + slab_count;
		});
		
		jQuery('#hdnTotalSlabs').val( function(i, old_val) {
			return ++old_val;
		});
	});

});


/** Remove Discount Slab row **/
jQuery(document).on('click', '.remove-discount-slab' ,function(){ 
	jQuery(this).parent('div').remove();
});

/** Remove Discount Rule **/
jQuery(document).on('click', '.wwsd-remove-discount-rule' ,function(){ 
	event.preventDefault();
	
	var slab_count = parseInt(jQuery('#hdnTotalSlabs').val()); 
	slab_count = slab_count - 1;
	
	//--> Remove comma seperated value
	var slab_count_counter = jQuery("#hdnTotalSlabsCounter").val(); 
	var id_to_delete = jQuery(this).parent().find('.wwsd-wholesale-append-js-row-cont').attr("rel");
	var slab_count_counter_arr = slab_count_counter.split(',');
	
	Array.prototype.remove = function() {
		var what, a = arguments, L = a.length, ax;
		while (L && this.length) {
			what = a[--L];
			while ((ax = this.indexOf(what)) !== -1) {
				this.splice(ax, 1);
			}
		}
		return this;
	};
	
	var ary = ['three', 'seven', 'eleven'];
	
	slab_count_counter_arr_new = slab_count_counter_arr.remove(id_to_delete);
	jQuery("#hdnTotalSlabsCounter").val(slab_count_counter_arr_new.join());
	
	jQuery('#hdnTotalSlabs').val(slab_count);
	jQuery(this).parent().parent().parent().remove();
});