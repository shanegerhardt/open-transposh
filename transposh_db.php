<?php
/*  Copyright © 2009 Transposh Team (website : http://transposh.org)
 *
 *	This program is free software; you can redistribute it and/or modify
 *	it under the terms of the GNU General Public License as published by
 *	the Free Software Foundation; either version 2 of the License, or
 *	(at your option) any later version.
 *
 *	This program is distributed in the hope that it will be useful,
 *	but WITHOUT ANY WARRANTY; without even the implied warranty of
 *	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *	GNU General Public License for more details.
 *
 *	You should have received a copy of the GNU General Public License
 *	along with this program; if not, write to the Free Software
 *	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */


/**
 * Contains db realated function which are likely to be specific for each environment. 
 * This implementation for use with mysql within wordpress
 * 
 */

require_once("logging.php");
require_once("constants.php");

//
//Constants
//

//Table name in database for storing translations
define("TRANSLATIONS_TABLE", "translations");
define("TRANSLATIONS_LOG", "translations_log");

//Database version
define("DB_VERSION", "1.02");

//Constant used as key in options database
define("TRANSPOSH_DB_VERSION", "transposh_db_version");

/*
 * Fetch translation from db or cache.
 * Returns An array that contains the translated string and it source.
 *   Will return NULL if no translation is available.
 */
function fetch_translation($original)
{
	global $wpdb, $lang;
	$translated = NULL;
	logger("Enter " . __METHOD__ . ": $original", 4);

	//The original is saved in db in its escaped form
	$original = $wpdb->escape(html_entity_decode($original, ENT_NOQUOTES, 'UTF-8'));

	if(ENABLE_APC && function_exists('apc_fetch'))
	{
		$cached = apc_fetch($original .'___'. $lang, $rc);
		if($rc === TRUE)
		{
			logger("Exit from cache " . __METHOD__ . ": $cached", 4);
			return $cached;
		}
	}

	$table_name = $wpdb->prefix . TRANSLATIONS_TABLE;
	$query = "SELECT * FROM $table_name WHERE original = '$original' and lang = '$lang' ";
	$row = $wpdb->get_row($query);

	if($row !== FALSE)
	{
		$translated_text = stripslashes($row->translated);
		$translated = array($translated_text, $row->source);

		logger("db result for $original >>> $translated_text ($lang) ({$row->source})" , 3);
	}

	if(ENABLE_APC && function_exists('apc_store'))
	{
		//If we don't have translation still we want to have it in cache
		$cache_entry = $translated;
		if($cache_entry == NULL)
		{
			$cache_entry = "";
		}

		//update cache
		$rc = apc_store($original .'___'. $lang, $cache_entry, 3600);
		if($rc === TRUE)
		{
			logger("Stored in cache: $original => $translated", 3);
		}
	}

	logger("Exit " . __METHOD__ . ": $translated", 4);
	return $translated;
}


/*
 * A new translation has been posted, update the translation database.
 */
function update_translation()
{
	global $wpdb;

	$ref=getenv('HTTP_REFERER');
	$original =  base64_url_decode($_POST['token']);
	$translation = $_POST['translation'];
	$lang = $_POST['lang'];
	$source = $_POST['source'];

	if(!isset($original) || !isset($translation) || !isset($lang))
	{
		logger("Enter " . __FILE__ . " missing params: $original , $translation, $lang," . $ref, 0);
		return;
	}

	//Check that use is allowed to translate
	if(!is_translator())
	{
		logger("Unauthorized translation attempt " . $_SERVER['REMOTE_ADDR'] , 1);
	}
	
	$table_name = $wpdb->prefix . TRANSLATIONS_TABLE;
	
	//Decode & remove already escaped character to avoid double escaping
	$translation = $wpdb->escape(htmlspecialchars(stripslashes(urldecode($translation))));

	//The original content is encoded as base64 before it is sent (i.e. token), after we
	//decode it should just the same after it was parsed.
	$original = $wpdb->escape(html_entity_decode($original, ENT_NOQUOTES, 'UTF-8'));

	$update = "REPLACE INTO  $table_name (original, translated, lang, source)
                VALUES ('" . $original . "','" . $translation . "','" . $lang . "','" . $source . "')";

	$result = $wpdb->query($update);

	if($result !== FALSE)
	{
		update_transaction_log($original, $translation, $lang, $source);

		//Delete entry from cache
		if(ENABLE_APC && function_exists('apc_store'))
		{
			apc_delete($original .'___'. $lang);
		}

		logger("Inserted to db '$original' , '$translation', '$lang' " , 3);
	}
	else
	{
		logger("Error !!! failed to insert to db $original , $translation, $lang," , 0);
		header("HTTP/1.0 404 Failed to update language database");
	}

	exit;
}

/*
 * Update the transaction log
 */
function update_transaction_log(&$original, &$translation, &$lang, $source)
{
	global $wpdb, $user_ID;
	get_currentuserinfo();

	// log either the user ID or his IP
	if ('' == $user_ID)
	{
		$loguser = $_SERVER['REMOTE_ADDR'];
	}
	else
	{
		$loguser = $user_ID;
	}

	$log = "INSERT INTO ".$wpdb->prefix.TRANSLATIONS_LOG." (original, translated, lang, translated_by, source) ".
			"VALUES ('" . $original . "','" . $translation . "','" . $lang . "','".$loguser."','".$source."')";

	$result = $wpdb->query($log);

	if($result === FALSE)
	{
		logger(mysql_error(),0);
		logger("Error !!! failed to update transaction log:  $loguser, $original ,$translation, $lang, $source" , 0);
	}
}


/*
 * Setup the translation database.
 */
function setup_db()
{
	logger("Enter " . __METHOD__  );
	global $wpdb;
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

	$installed_ver = get_option(TRANSPOSH_DB_VERSION);

	if( $installed_ver != DB_VERSION ) {
		$table_name = $wpdb->prefix . TRANSLATIONS_TABLE;

		logger("Attempting to create table $table_name", 0);
		$sql = "CREATE TABLE $table_name (original VARCHAR(256) NOT NULL,".
				"lang CHAR(5) NOT NULL,".
				"translated VARCHAR(256),".
				"source TINYINT NOT NULL,".
				"PRIMARY KEY (original, lang)) DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci";

		dbDelta($sql);


		$table_name = $wpdb->prefix . TRANSLATIONS_LOG;

		logger("Attempting to create table $table_name", 0);
		$sql = "CREATE TABLE $table_name (original VARCHAR(256) NOT NULL,".
				"lang CHAR(5) NOT NULL,".
				"translated VARCHAR(256),".
				"translated_by VARCHAR(15),".
				"source TINYINT NOT NULL,".
				"timestamp TIMESTAMP,".
				"PRIMARY KEY (original, lang, timestamp)) DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci";

		dbDelta($sql);
		update_option(TRANSPOSH_DB_VERSION, DB_VERSION);
	}

	logger("Exit " . __METHOD__  );
}

?>