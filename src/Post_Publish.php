<?php



/*
 * Provides the side widget in the page/edit pages which will do translations
 */

namespace OpenTransposh;

use OpenTransposh\Core\Constants;
use OpenTransposh\Core\Parser;
use OpenTransposh\Logging\LogService;

/**
 * class that makes changed to the edit page and post page, adding our change to the side ba
 */
class Post_Publish {

	/** @var Plugin Container class */
	private $transposh;

	/** @var bool Did we just edited/saved? */
	private $just_published = false;

	/**
	 *
	 * Construct our class
	 *
	 * @param Plugin $transposh
	 */
	public function __construct( &$transposh ) {
		$this->transposh = &$transposh;
		// we need this anyway because of the change language selection
		add_action( 'edit_post', array( &$this, 'on_edit' ) );
		add_action( 'admin_menu', array( &$this, 'on_admin_menu' ) );
	}

	/**
	 * Admin menu created action, where we create our metaboxes
	 */
	public function on_admin_menu() {
		//add our metaboxs to the post and publish pages
		LogService::legacy_log( 'adding metaboxes for admin pages/post/custom', 4 );
		$post_types = get_post_types();
		foreach ( $post_types as $post_type ) {
			if ( in_array( $post_type, array( 'attachment', 'revision', 'nav_menu_item' ) ) ) {
				continue;
			}
			LogService::legacy_log( $post_type, 5 );
			if ( $this->transposh->options->enable_autoposttranslate ) {
				add_meta_box( 'transposh_postpublish', __( 'Open Transposh', TRANSPOSH_TEXT_DOMAIN ), array(
					&$this,
					"transposh_postpublish_box"
				), $post_type, 'side', 'core' );
			}
			add_meta_box( 'transposh_setlanguage', __( 'Set post language', TRANSPOSH_TEXT_DOMAIN ), array(
				&$this,
				"transposh_setlanguage_box"
			), $post_type, 'advanced', 'core' );
		}
		if ( ! isset( $_GET['post'] ) ) {
			return;
		}
		if ( get_post_meta( $_GET['post'], 'transposh_can_translate', true ) ) { // do isdefined stuff
			$this->just_published = true; // this is later used in the meta boxes //XXXXXXXXXXXXXXXXXXXXXXXXXXXX
			wp_enqueue_script( "transposh_backend", $this->transposh->transposh_plugin_url . TRANSPOSH_DIR_JS . '/admin/backendtranslate.js', array( 'transposh' ), TRANSPOSH_PLUGIN_VER, true );
			$script_params = array(
				'post'             => $_GET['post'],
				'l10n_print_after' =>
					't_be.a_langs = ' . json_encode( Constants::$engines['a']['langs'] ) . ';' .
					't_be.b_langs = ' . json_encode( Constants::$engines['b']['langs'] ) . ';' .
					't_be.g_langs = ' . json_encode( Constants::$engines['g']['langs'] ) . ';' .
					't_be.y_langs = ' . json_encode( Constants::$engines['y']['langs'] ) . ';'
			);
			wp_localize_script( "transposh_backend", "t_be", $script_params );
			// MAKESURE 3.3
			if ( version_compare( $GLOBALS['wp_version'], '3.3', '>=' ) ) {
				wp_enqueue_script( 'jquery-ui-progressbar' );
			} else {
				wp_enqueue_script( 'jqueryui', '//ajax.googleapis.com/ajax/libs/jqueryui/' . JQUERYUI_VER . '/jquery-ui.min.js', array( 'jquery' ), JQUERYUI_VER, true );
			}
			wp_enqueue_style( 'jqueryui', '//ajax.googleapis.com/ajax/libs/jqueryui/' . JQUERYUI_VER . '/themes/ui-lightness/jquery-ui.css', array(), JQUERYUI_VER );

			delete_post_meta( $_GET['post'], 'transposh_can_translate' ); // as we have used the meta - it can go now, another option would have been to put this in the getphrases
		}
	}

	/**
	 * Function to allow mass translate of tags
	 * @return array list of tags
	 */
	public function get_tags() {
		$tags    = get_terms( 'post_tag' ); // Always query top tags
		$phrases = array();
		foreach ( $tags as $tag ) {
			$phrases[] = $tag->name;
		}

		return $phrases;
	}

	/**
	 * Loop through all the post phrases and return them in json formatted script
	 *
	 * @param int $postID
	 */
	public function get_post_phrases( $postID ) {
		// Some security, to avoid others from seeing private posts
		// fake post for tags
		if ( $postID == - 555 ) {
			$phrases = $this->get_tags();
			$title   = "tags";
		} // a normal post
		else {
			if ( ! current_user_can( 'edit_post', $postID ) ) {
				return;
			}
			global $post; // thid is needed because some of the functions below expect it...
			$post = get_post( $postID );
			// Display filters
			$title            = apply_filters( 'the_title', $post->post_title );
			$content          = apply_filters( 'the_content', $post->post_content );
			$the_content_feed = apply_filters( 'the_content_feed', $content );
			$excerpt          = apply_filters( 'get_the_excerpt', $post->post_excerpt );
			$excerpt_rss      = apply_filters( 'the_excerpt_rss', $excerpt );

			//TODO - get comments text

			$parser   = new Parser();
			$phrases  = $parser->get_phrases_list( $content );
			$phrases2 = $parser->get_phrases_list( $title );
			$phrases3 = $parser->get_phrases_list( $the_content_feed );
			$phrases4 = $parser->get_phrases_list( $excerpt );
			$phrases5 = $parser->get_phrases_list( $excerpt_rss );

			// Merge the two arrays for traversing
			$phrases = array_merge( $phrases, $phrases2, $phrases3, $phrases4, $phrases5 );
			LogService::legacy_log( $phrases, 4 );

			// Add phrases from permalink
			if ( $this->transposh->options->enable_url_translate ) {
				$permalink = get_permalink( $postID );
				$permalink = substr( $permalink, strlen( $this->transposh->home_url ) + 1 );
				$parts     = explode( '/', $permalink );
				foreach ( $parts as $part ) {
					if ( ! $part || is_numeric( $part ) ) {
						continue;
					}
					$part      = str_replace( '-', ' ', $part );
					$phrases[] = urldecode( $part );
				}
			}
		}
		// We provide the post title here
		$json['posttitle'] = $title;
		// and all languages we might want to target
		$json['langs'] = array();

		foreach ( $phrases as $key ) {
			foreach ( explode( ',', $this->transposh->options->viewable_languages ) as $lang ) {
				// if this isn't the default language or we specifically allow default language translation, we will seek this out...
				// as we don't normally want to auto-translate the default language -FIX THIS to include only correct stuff, how?
				if ( ! $this->transposh->options->is_default_language( $lang ) || $this->transposh->options->enable_default_translate ) {
					// There is no point in returning phrases, languages pairs that cannot be translated
					if ( in_array( $lang, Constants::$engines['b']['langs'] ) ||
					     in_array( $lang, Constants::$engines['g']['langs'] ) ||
					     in_array( $lang, Constants::$engines['y']['langs'] ) ||
					     in_array( $lang, Constants::$engines['a']['langs'] ) ) {
						[ $source, $translation ] = $this->transposh->database->fetch_translation( $key, $lang );
						if ( ! $translation ) {
							// p stands for phrases, l stands for languages, t is token
							if ( ! @is_array( $json['p'][ $key ]['l'] ) ) {
								$json['p'][ $key ]['l'] = array();
							}
							array_push( $json['p'][ $key ]['l'], $lang );
							if ( ! in_array( $lang, $json['langs'] ) ) {
								array_push( $json['langs'], $lang );
							}
						}
					}
				}
			}
			// only if a languages list was created we'll need to translate this
			if ( @is_array( $json['p'][ $key ]['l'] ) ) {
				//$json['p'][$key]['t'] = $key;//OpenTransposh\Core\transposh_utils::base64_url_encode($key);
				@$json['length'] ++;
			}
		}


		// the header helps with debugging
		header( "Content-type: text/javascript" );
		echo json_encode( $json );
	}

	/**
	 * This is the box that appears on the side
	 */
	public function transposh_postpublish_box() {
		if ( isset( $_GET['post'] ) && get_post_meta( $_GET['post'], 'transposh_can_translate', true ) ) {
			$this->just_published = true;
		}

		if ( $this->just_published ) {
			echo '<div id="tr_loading">Publication happened - loading phrases list...</div>';
		} else {
			echo 'Waiting for publication';
		}
	}

	/**
	 * This is a selection of language box which should hopefully appear below the post edit
	 */
	public function transposh_setlanguage_box() {
		$lang = get_post_meta( $_GET['post'], 'tp_language', true );
		echo '<select name="transposh_tp_language">';
		echo '<option value="">' . __( 'Default' ) . '</option>';
		foreach ( $this->transposh->options->get_sorted_langs() as $langcode => $langrecord ) {
			[ $langname, $langorigname, $flag ] = explode( ",", $langrecord );
			echo '<option value="' . $langcode . ( $langcode == $lang ? '" selected="selected' : '' ) . '">' . $langname . ' - ' . $langorigname . '</option>';
		}
		echo '</select>';
	}

	/**
	 * When this happens, the boxes are not created we now use a meta to inform the next step (cleaner)
	 * we now also update the tp_language meta for the post
	 *
	 * @param int $postID
	 */
	public function on_edit( $postID ) {
		// This should prevent the meta from being added when not needed
		if ( ! isset( $_POST['transposh_tp_language'] ) ) {
			return;
		}
		if ( $this->transposh->options->enable_autoposttranslate ) {
			add_post_meta( $postID, 'transposh_can_translate', 'true', true );
		}
		if ( $_POST['transposh_tp_language'] == '' ) {
			delete_post_meta( $postID, 'tp_language' );
		} else {
			update_post_meta( $postID, 'tp_language', $_POST['transposh_tp_language'] );
			// if a language is set for a post, default language translate must be enabled, so we enable it
			if ( ! $this->transposh->options->enable_default_translate ) {
				$this->transposh->options->enable_default_translate = true;
				$this->transposh->options->update_options();
			}
		}
		LogService::legacy_log( $postID . ' ' . $_POST['transposh_tp_language'] ); //??
	}

}

