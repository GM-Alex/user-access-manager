jQuery(document).ready(function(){
	//Functions for the setting page
	
	if(jQuery(".uam_hide_page:checked").val()  == "true"){
		jQuery("#uam_page_settings").css("display","none");
		jQuery(this).toggleClass("active");
	}

	jQuery(".uam_hide_page").change(function(){
		jQuery("#uam_page_settings").slideToggle("slow");
		jQuery(this).toggleClass("active");
	});
	
	if(jQuery(".uam_hide_post:checked").val()  == "true"){
		jQuery("#uam_post_settings").css("display","none");
		jQuery(this).toggleClass("active");
	}

	jQuery(".uam_hide_post").change(function(){
		jQuery("#uam_post_settings").slideToggle("slow");
		jQuery(this).toggleClass("active");
	});
	
	if(jQuery(".uam_lock_file:checked").val()  == "false"){
		jQuery("#uam_file_settings").css("display","none");
		jQuery(this).toggleClass("active");
	}
	
	jQuery(".uam_lock_file").change(function(){
		jQuery("#uam_file_settings").slideToggle("slow");
		jQuery(this).toggleClass("active");
	});
	
	
	//Functions for the group info
	
	/*jQuery(".uam_group_info").css("display","none");
	
	jQuery(".uam_group_info_link").click(function(){
		jQuery(this).parent().next().slideToggle("slow");
		jQuery(this).parent().next().toggleClass("active");
	});*/
	
	jQuery(".uam_group_info_link").tooltip({
		effect: 'slide', 
		relative: true,
		position: 'center left'
	});
});