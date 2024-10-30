<?php

/**
 * Plugin Name: CSS Adder By Agence-Press
 * Plugin URI: http://www.agence-press.fr/plugins/css-adder
 * Description: Allow to add CSS in any page, post, or custom post type.
 * Author: Team Agence-Press
 * Version: 1.5.0
 * Author URI: http://www.agence-press.fr/
 * License: GPL v3
*/

/*
1.5.0 : Possibilité de choisir les types de posts sur lequels CSS-Adder est actif
1.0.0 : Revision initile
*/

add_action( 'init', 'cssadder_declare_hooks',100 );
function cssadder_declare_hooks() {
	$Config = cssadder_getconfig();
	$PostTypes = get_post_types( Array() , 'objects' );
	foreach ($PostTypes as $PostType => $Properties) { 
		if ( $Properties->public ) {
			if ( isset($Config[$PostType]) && $Config[$PostType]) {
				add_action( 'add_meta_boxes_' . $PostType, 'cssadder_page_metaboxes' );
				add_action( 'save_post_' . $PostType, 'cssadder_save_page' );  
			} //END IF ACTIF
		} //END IF PUBLIC
	} //END FOREACH
}

function cssadder_page_metaboxes() {
	$Config = cssadder_getconfig();
	$PostTypes = get_post_types( Array() , 'objects' );
	foreach ($PostTypes as $PostType => $Properties) { 
		if ( $Properties->public ) {
			if ( isset($Config[$PostType]) && $Config[$PostType]) {
				add_meta_box('cssadder_g_div', 'CSS Adder', 'cssadder_page_g_html', $PostType, 'normal', 'high');
			} //END IF ACTIF
		} //END IF PUBLIC
	} //END FOREACH
}

function cssadder_page_g_html() {
	global $post;
	$css_code = get_post_meta($post->ID,'cssadder_code',true);

	?>
		<textarea name="cssadder_code" id="cssadder_code" style="width:100%;height:30em;"><?php echo wp_kses( $css_code, array( "\'", '\"' ) ); ?></textarea>
	<?php
}

function cssadder_save_page() {
	global $post;
	if (isset($_POST["cssadder_code"])) {

		//Require CSS Tidy to sanitize and validate user input is really Valid CSS Code.
		if (!class_exists('csstidy'))
			include( dirname(__FILE__) . '/csstidy-1.5.2/class.csstidy.php');

		$css = new csstidy();
		$css->parse($_POST["cssadder_code"]);
		$css_code = $css->print->plain();

		update_post_meta($post->ID, "cssadder_code", $css_code);
	}
}

add_action('wp_head', 'cssadder_wp_head');
function cssadder_wp_head() {
	global $post;
	$Config = cssadder_getconfig();

	if ( isset($Config[$post->post_type]) && $Config[$post->post_type]) {
		$css_code = get_post_meta($post->ID,'cssadder_code',true);
		if (!empty($css_code)) {
			echo '<style type="text/css">'. wp_kses( $css_code, array( "\'", '\"' ) ) .'</style>';
		}
	}
}

/****************************************************************************************
	CONFIG
*****************************************************************************************/

function cssadder_getconfig() {
	$DefaultConfig = Array(
		"post" => true,
		"page" => true
		);

	return get_option( 'cssadder_config' , $DefaultConfig );
}

/****************************************************************************************
	ONGLET PARAMETRES
*****************************************************************************************/
add_action('admin_menu', 'cssadder_parametres_admin_actions');
function cssadder_parametres_admin_actions(){
	add_submenu_page( 'options-general.php', 'CSS-Adder', 'CSS-Adder', 'manage_options', 'cssadder_page_options', 'cssadder_parametres' );
}

function cssadder_parametres(){
	$PostTypes = get_post_types( Array() , 'objects' );

	if (isset($_REQUEST['Save_Params'])) {
		$Config = Array();

		foreach ($PostTypes as $PostType => $Properties) { 
			if ( $Properties->public ) {
				if (isset($_REQUEST['AllowOn_' . $PostType])) {
					$Config[$PostType] = true;
				} //END IF ACTIF
			} //END IF PUBLIC
		} //END FOREACH

		update_option( 'cssadder_config' , $Config );

	} //END IF SAVE

	$Config = cssadder_getconfig();

?>
<div class="wrap">
	<div id="icon-options-general" class="icon32"><br></div>
	<h2>Edition des paramètres CSS Adder By Agence-Press</h2>
	<form method="post">
		<p>
			CSS-Adder sera actif sur les types de posts suivants :
		</p>
		<table class="form-table">
			<tbody>
<?php
	foreach ($PostTypes as $PostType => $Properties) { 
		if ( $Properties->public ) {
?>
				<tr valign="top">
					<th scope="row">
						<label for="AllowOn_<?php echo $PostType; ?>"><?php echo $Properties->label; ?></label>
					</th>
					<td>
						<input type="checkbox" id="AllowOn_<?php echo $PostType; ?>" name="AllowOn_<?php echo $PostType; ?>" <?php if ( isset($Config[$PostType]) && $Config[$PostType]) echo 'checked="checked"' ?>>
					</td>
				</tr>
<?php 
		} //END IF
	} //END FOREACH
?>
			</tbody>
		</table>
		<p class="submit">
			<input class="button button-primary" type="submit" name="Save_Params" value="Enregistrer ces paramètres">
		</p>
	</form>
</div>

<?php
}


