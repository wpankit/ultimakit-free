/**
 * This is the javascript file for the module.
 *
 * @package UltimaKit_
 */

(function ( $ ) {
	'use strict';

	/**
	 * All of the code for your admin-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */

	jQuery(document).ready(function ($) {

	    $('.ultimakit_module_admin_ui').on('click', function (e) {
	        e.preventDefault(); // Prevent the default action of the click event
	        $("#ultimakit_module_admin_ui_modal").modal('show'); // Show the modal
	    });

		if( $('#admin-ui-settings').length > 0 ){

			var adminBodyColor = $( '#body_color' ),
	            sidebarColor = $( '#sidebar_color' ),
	            fontColor = $( '#sidebar_txt_color' ),
	            adminBarColor = $( '#adminbar_color' ),
	            adminBarTextColor = $( '#adminbar_txt_color' ),
	            adminBarIconsColor = $( '#adminbar_icons_color' ),
	            adminSideBarIconsColor = $( '#sidebar_icon_color' ),
	            selectedMenuColor = $( '#sel_menu_item_color' ),
	            selectedMenuTextColor = $( '#sel_menu_txt_color' ),
	            iconToggle = $( '#hide_sidebar_icons' ),
	            adminBarIconToggle = $( '#hide_adminbar_icons' ),
	            hoverdMenuColor = $( '#hover_menu_item_color' ),
	            hoverdMenuTextColor = $( '#hovered_menu_txt_color' ),
	            changeSideBarWidth = $( '#sidebar_width' );
	        
	        function ultimakitUpdatePreview() {
	            var adminBodyCss = 'body { background: ' + adminBodyColor.wpColorPicker( 'color' ) + '!important; }',
	                sidebarCss = '#adminmenu, #adminmenu .wp-submenu, #adminmenuback, #adminmenuwrap { background: ' + sidebarColor.wpColorPicker( 'color' ) + ' !important; }',
	                fontCss = '#adminmenu a, #adminmenu .wp-submenu a { color: ' + fontColor.wpColorPicker( 'color' ) + ' !important; }',
	                adminBarCss = '#wpadminbar { background: ' + adminBarColor.wpColorPicker( 'color' ) + ' !important; }',
	                adminBarIconsCss = '#wpadminbar .ab-icon:before,#wp-admin-bar-site-name>.ab-item:before { color: ' + adminBarIconsColor.wpColorPicker( 'color' ) + ' !important; }',
	                adminSideBarIconCss = '#adminmenu div.wp-menu-image:before { color: ' + adminSideBarIconsColor.wpColorPicker( 'color' ) + ' !important; }',
	                adminBarTextCss = '#wpadminbar .ab-item, #wpadminbar a.ab-item, #wpadminbar>#wp-toolbar span.ab-label, #wpadminbar>#wp-toolbar span.noticon { color: ' + adminBarTextColor.wpColorPicker( 'color' ) + ' !important; }',
	                selectedMenuCss = '#adminmenu .wp-has-current-submenu a.wp-has-current-submenu, .wp-menu-arrow, #adminmenu a.wp-menu-arrow:focus, #adminmenu a.wp-menu-arrow:hover, #adminmenu a:focus .wp-menu-arrow, .folded #adminmenu .wp-has-current-submenu .wp-menu-arrow, .folded #adminmenu a.wp-has-current-submenu .wp-menu-arrow { background: ' + selectedMenuColor.wpColorPicker( 'color' ) + ' !important; }',
	                selectedMenuTextCss = '#adminmenu .wp-has-current-submenu a.wp-has-current-submenu, .wp-menu-arrow, #adminmenu a.wp-menu-arrow:focus, #adminmenu a.wp-menu-arrow:hover, #adminmenu a:focus .wp-menu-arrow, .folded #adminmenu .wp-has-current-submenu .wp-menu-arrow, .folded #adminmenu a.wp-has-current-submenu .wp-menu-arrow { color: ' + selectedMenuTextColor.wpColorPicker( 'color' ) + ' !important; }',
	                iconCss = iconToggle.is(':checked') ? '#adminmenu .wp-menu-image { display: none; } #adminmenu a.menu-top { padding-left: 0 !important; } #adminmenu div.wp-menu-name { padding: 8px !important; } #adminmenu li.menu-top { margin-bottom: 0 !important; }' : '',
	                adminBarIconCss = adminBarIconToggle.is(':checked') ? '#wpadminbar .ab-icon, #wpadminbar .ab-item::before { display: none; }' : '',
	                hoverdMenuCss = '#adminmenu li.menu-top:hover, #adminmenu li.opensub>a.menu-top, #adminmenu li>a.menu-top:focus { background: ' + hoverdMenuColor.wpColorPicker( 'color' ) + ' !important; }',
	                hoverdMenuTextCss = '#adminmenu li.menu-top:hover, #adminmenu li.opensub>a.menu-top, #adminmenu li>a.menu-top:focus { color: ' + hoverdMenuTextColor.wpColorPicker( 'color' ) + ' !important; }',
	                adminSidebarMenuOnCollapse = 'body.wpext-base.folded #adminmenu .wp-menu-image{display: block !important;}',
	                sidebarwidth = 'body:not(.folded) #adminmenuback, body:not(.folded) #adminmenuwrap, body:not(.folded) #adminmenu, body:not(.folded) #adminmenu .wp-submenu {width: '+changeSideBarWidth.val()+'px !important;} body:not(.folded) #wpcontent, body:not(.folded) #wpfooter{ margin-left: '+changeSideBarWidth.val()+'px !important; } body:not(.folded) #adminmenu .wp-not-current-submenu .wp-submenu { left:'+changeSideBarWidth.val()+'px !important; } body:not(.folded) #adminmenu .wp-not-current-submenu .wp-submenu, body:not(.folded) .folded #adminmenu .wp-has-current-submenu .wp-submenu { min-width:'+changeSideBarWidth.val()+'px !important; } ';
	                if ($(window).width() < 768) {
	                    sidebarwidth = '';
	                }
	            $( '#wpext-real-time-css' ).html( sidebarwidth + hoverdMenuTextCss + selectedMenuTextCss + adminSidebarMenuOnCollapse + hoverdMenuCss + adminBodyCss + adminSideBarIconCss + adminBarIconsCss + sidebarCss + fontCss + adminBarCss + adminBarTextCss + iconCss + adminBarIconCss + selectedMenuCss );
	        }

	        hoverdMenuColor.wpColorPicker({
	            palettes: false,
	            change: function() {
	                ultimakitUpdatePreview();
	            }
	        });
	        hoverdMenuTextColor.wpColorPicker({
	            palettes: false,
	            change: function() {
	                ultimakitUpdatePreview();
	            }
	        });
	        selectedMenuTextColor.wpColorPicker({
	            palettes: false,
	            change: function() {
	                ultimakitUpdatePreview();
	            }
	        });
	        adminBodyColor.wpColorPicker({
	            palettes: false,
	            change: function() {
	                ultimakitUpdatePreview();
	            }
	        });

	        adminBarIconsColor.wpColorPicker({
	            palettes: false,
	            change: function() {
	                ultimakitUpdatePreview();
	            }
	        });

	        adminSideBarIconsColor.wpColorPicker({
	            palettes: false,
	            change: function() {
	                ultimakitUpdatePreview();
	            }
	        });


	        sidebarColor.wpColorPicker({
	            palettes: false,
	            change: function() {
	                ultimakitUpdatePreview();
	            }
	        });

	        fontColor.wpColorPicker({
	            palettes: false,
	            change: function() {
	                ultimakitUpdatePreview();
	            }
	        });

	        adminBarColor.wpColorPicker({
	            palettes: false,
	            change: function() {
	                ultimakitUpdatePreview();
	            }
	        });

	        adminBarTextColor.wpColorPicker({
	            palettes: false,
	            change: function() {
	                ultimakitUpdatePreview();
	            }
	        });

	        selectedMenuColor.wpColorPicker({
	            palettes: false,
	            change: function() {
	                ultimakitUpdatePreview();
	            }
	        });

	        iconToggle.change(function() {
	            ultimakitUpdatePreview();
	        });

	        adminBarIconToggle.change(function() {
	            ultimakitUpdatePreview();
	        });
	        changeSideBarWidth.change(function() {
	            ultimakitUpdatePreview();
	        });

	        // Initial update
	        ultimakitUpdatePreview(); 
	    }

	});


})( jQuery );