jQuery(document).ready(function(){
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
	
	jQuery(".uam_info_content").css("display","none");
	
	jQuery("a.uam_info_link").click(function(){
		jQuery(this).next().slideToggle("slow");
		jQuery(this).next().toggleClass("expand_active");
		jQuery(this).next().toggleClass("expand_deactive");
		if(jQuery(this).parent().parent().children().children(".expand_deactive").size() == 0)
		{
			jQuery("a.uam_info_link_all").text("collaps all");
			jQuery("a.uam_info_link_all").addClass("active");
		}
		if(jQuery(this).parent().parent().children().children(".expand_active").size() == 0)
		{
			jQuery("a.uam_info_link_all").text("expand all");
			jQuery("a.uam_info_link_all").removeClass("active");
		}
	});
	
	jQuery("a.uam_info_link_all").click(function(){
		if(jQuery(this).hasClass("active"))
		{
			jQuery(this).parent().parent().children().children(".expand_active").slideToggle("slow");
			jQuery(this).parent().parent().children().children(".expand_active").toggleClass("expand_deactive");
			jQuery(this).parent().parent().children().children(".expand_active").toggleClass("expand_active");
			jQuery(this).text("expand all");
		}
		else
		{
			jQuery(this).parent().parent().children().children(".expand_deactive").slideToggle("slow");
			jQuery(this).parent().parent().children().children(".expand_deactive").toggleClass("expand_active");
			jQuery(this).parent().parent().children().children(".expand_deactive").toggleClass("expand_deactive");
			jQuery(this).text("collaps all");
		}

		jQuery(this).toggleClass("active");
	});
	
	jQuery(".uma_user_access_group_from").css("display","none");
	
	jQuery(".uma_user_access_group").click(function(){
		jQuery(this).next().slideToggle("slow");
		jQuery(this).next().toggleClass("active");
	});
	
	jQuery(".uam_group_info").css("display","none");
	
	jQuery(".uam_group_info_link").click(function(){
		jQuery(this).parent().next().slideToggle("slow");
		jQuery(this).parent().next().toggleClass("active");
	});
	
	jQuery(".uam_group_lock_info").css("display","none");
	
	jQuery(".uam_group_lock_info_link").click(function(){
		jQuery(this).parent().next().next().slideToggle("slow");
		jQuery(this).parent().next().next().toggleClass("active");
	});

	jQuery(".uam_group_stuff").css("display","none");
	
	jQuery(".uam_group_stuff_link").click(function(){
		jQuery(this).parent().parent().next().children().slideToggle("slow");
		jQuery(this).parent().parent().next().children().toggleClass("active");
	});
	
	function disable_childs(object)
	{
		if(jQuery(object).children("input:checked").length == 0)
		{
			jQuery(object).next(".uam_group_stuff_child").children("li").removeAttr("disabled");
			jQuery(object).next(".uam_group_stuff_child").children("li").children().removeAttr("disabled");
			jQuery(object).next(".uam_group_stuff_child").children("li").children("input").removeAttr("checked");
			
			if(jQuery(object).next(".uam_group_stuff_child").children("ul").children().length != 0)
			{
				new_obj = jQuery(object).next(".uam_group_stuff_child").children();
				disable_childs(new_obj);
			}
		}
		else
		{
			jQuery(object).next(".uam_group_stuff_child").children("li").attr("disabled","disabled");
			jQuery(object).next(".uam_group_stuff_child").children("li").children().attr("disabled","disabled");
			jQuery(object).next(".uam_group_stuff_child").children("li").children("input").attr("checked","checked");
			
			if(jQuery(object).next(".uam_group_stuff_child").children("ul").children().length != 0)
			{
				new_obj = jQuery(object).next(".uam_group_stuff_child").children();
				disable_childs(new_obj);
			}
		}
	}
	
	if(jQuery("#uam_set_lock_recursive").val() == "true")
	{
		var allGroupStuff = jQuery(".uam_group_stuff li");
		
		for(var i = 0; i <= allGroupStuff.length; i++)
		{
			if(jQuery(allGroupStuff[i]).children("input:checked").length != 0 && jQuery(allGroupStuff[i]).next(".uam_group_stuff_child").length != 0)
				disable_childs(allGroupStuff[i]);
		}
		
		jQuery(".uam_group_stuff li").click(function(){
			disable_childs(this);
		});
	}
});