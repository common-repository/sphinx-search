<?php
/**
 * The Sphinx search plugin uses the "Search API" plugin to enable basic MySQL fulltext searching.
 * @author Justin Shreve <jshreve4@kent.edu>
 * @version 1.0.0
 */
 
/*
Plugin Name: Sphinx Search Plugin
Plugin URI: http://wordpress.org/
Description: Sphinx Search functionality for WordPress using the Search API
Author: Justin Shreve
Version: 1.0.0
*/

/**
* Sphinx Search
* Takes information from the search api and uses it to query the search index table.
* @version 1.0.0
*/
class sphinx_search
{
		/**
		* Results
		* Contains an array of MySQL results from a search
		* @var array a list of results from a search
		*/
		var $results = array();
		
		/**
		* Flags
		* An array of search parameters
		* @var array search parameters
		*/
		var $flags = array();
		
		/**
		* Options
		* Options specific to each search plugin. You can enable/disable advanced search, search page filters, pagination and search index support.
		* @var array options to be used by the search api
		*/
		var $options = array(
			'advanced' => 0,
			'filters' => 1,
			'sort' => 0,
			'pagination' => 1,
			'index' => 1,
		);
		
		/** 
		* Sphinx Search (Constructor)
		* This function passes along the options array to the search api
		*/
		function sphinx_search() {
			$this->parent->options =& $this->options;
		}

		/** 
		* Search
		* This function pulls everything from the API and database together. It loads the pagination function, the filter code, calls the results function and outputs the
		* results.
		* @return string Final search output
		*/
		function search()
		{
			// Query the database and format results
			$total = $this->find_results();
			
			if( $this->flags['page'] > 1 )
				$start = intval ( ( ( $this->flags['page'] - 1 ) * get_option( 'posts_per_page' ) ) + 1 );
			else
				$start = 1;
				
			// Return the results
			if( $total > 0 )
			{
				// FILTER: search_results_start Starts the results output
				$result_html = apply_filters( 'search_results_start', "<ol class=\"searchresults\" start=\"{$start}\">\n" );
				
				foreach( $this->results as $results ) {
				
					// Result is a post
					if( $results->type == "post" ) {
						// FILTER: search_post_result Lets you change a single row for result output
						$result_html .= apply_filters( 'search_post_result', "<li><strong class='result_type'>" . __( 'Post ' ) . "</strong>: <a href=\"" . get_permalink( $results->object ) . "\" class='result_title'>" . $results->title . "</a>\n<p class=\"result_summary\">". $this->parent->trim_excerpt( $results->content ) ."</p></li>" );
					}
					// Result is a page
					elseif( $results->type == "page" ) {
						// FILTER: search_page_result Lets you change a single row for result output
						$result_html .= apply_filters( 'search_page_result', "<li><strong class='result_type'>" . __( 'Page ' ) . "</strong>: <a href=\"" . get_permalink ( $results->object ) . "\" class='result_title'>" . $results->title . "</a>\n<p class=\"result_summary\">". $this->parent->trim_excerpt( $results->content ) ."</p></li>" );
					}
					// Result is a comment
					else {
						// FILTER: search_comment_result Lets you change a single row for result output
						$result_html .= apply_filters( 'search_comment_result', "<li><strong class='result_type'>" . __( 'Comment ' ) . "</strong>: <a href=\"" . get_comment_link( $results->object ) . "\" class='result_title'>" . get_the_title( $results->parent ) ."</a>\n<p class=\"result_summary\">" . $this->parent->trim_excerpt( $results->content ) ."</p></li>" );
					}
				}
				
				// FILTER: search_results_start Ends the results output
				$result_html .= apply_filters( 'search_results_end', "</ol>\n" );
			}
			
			// No results error
			else {
				// FILTER: search_no_results Allows you to change the error message when no results are returned
				$result_html .= apply_filters( 'search_no_results', "<h2>" . __( ' There are no results for this seach.' ) . "</h2>" );
			}
		
			// Return the search output
			// FILTER: search_results Allows you to edit the results
			return apply_filters( 'search_results', $this->parent->result_search_box() . $result_html . $this->parent->pagination( $total ) );
		}
		
		/** 
		* Find Results
		* This function is what physically queries the database using Sphinx and then loads the full results from the database.
		* @global object WordPress Database Abstraction Layer
		* @return int The number of results
		*/
		function find_results()
		{
			global $wpdb;				
			
			// Load the Sphinx API
			include( 'sphinx-0.9.8.1/api/sphinxapi.php' ); 
			$cl = new SphinxClient();
			$cl->SetMatchMode( SPH_MATCH_EXTENDED );
			
			// limit
			$cl->SetLimits( intval( ( $this->flags['page'] - 1 ) * get_option( 'posts_per_page' ) ), intval( get_option('posts_per_page') ) );
						
			if( is_array( $this->flags['types'] ) ) {
				if( in_array( 'posts', $this->flags['types'] ) && !in_array( 'pages', $this->flags['types'] ) && !in_array( 'comments', $this->flags['types'] ) ) {
					$cl->SetFilter( "isPost", array( 1, 0 ) ); $cl->SetFilter( "isPage", array( 0 ) ); $cl->SetFilter( "isComment" , array( 0 ) );
				}
				elseif( in_array( 'pages', $this->flags['types'] ) && !in_array( 'posts', $this->flags['types'] ) && !in_array( 'comments', $this->flags['types'] ) ) {
					$cl->SetFilter( "isPost", array( 0 ) ); $cl->SetFilter( "isPage", array( 1, 0 ) ); $cl->SetFilter( "isComment" , array( 0 ) );
				}
				elseif( in_array( 'comments', $this->flags['types'] ) && !in_array( 'posts', $this->flags['types'] ) && !in_array( 'pages', $this->flags['types'] ) ) {
					$cl->SetFilter( "isPost", array( 0 ) ); $cl->SetFilter( "isPage", array( 0 ) ); $cl->SetFilter( "isComment" , array( 1, 0 ) );
				}
				elseif( in_array( 'posts', $this->flags['types'] ) && in_array( 'pages', $this->flags['types'] ) && !in_array( 'comments', $this->flags['types'] ) ) {
					$cl->SetFilter( "isPost", array( 1, 0 ) ); $cl->SetFilter( "isPage", array( 1, 0 ) ); $cl->SetFilter( "isComment" , array( 0 ) );
				}
				elseif( in_array( 'posts', $this->flags['types'] ) && !in_array( 'pages', $this->flags['types'] ) && in_array( 'comments', $this->flags['types'] ) ) {
					$cl->SetFilter( "isPost", array( 1, 0 ) ); $cl->SetFilter( "isPage", array( 0 ) ); $cl->SetFilter( "isComment" , array( 1, 0 ) );
				}
				elseif( !in_array( 'posts', $this->flags['types'] ) && in_array( 'pages', $this->flags['types'] ) && in_array( 'comments', $this->flags['types'] ) ) {
					$cl->SetFilter( "isPost", array( 0 ) ); $cl->SetFilter( "isPage", array( 1, 0 ) ); $cl->SetFilter( "isComment" , array( 1, 0 ) );
				}
			}
			
			$result = $cl->Query( $this->flags['string'], 'search_index' );
			
			if ( $result === false )
				return 0;
			
			if ( ! empty($result["matches"]) ) {
			
				$ids = "";
				
				foreach ( $result["matches"] as $doc => $docinfo ) {
					$ids .= "{$doc}' OR id = '";		
				}
				
				$ids = substr( $ids, 0, -10 );

				$this->results = $wpdb->get_results( apply_filters( "search_find_results", "SELECT * FROM  {$wpdb->prefix}search_index WHERE id = '{$ids}" ) );
								
				return $result['total_found'];
			}
			
			else
				return 0;
				
		}
			
}
	
if( function_exists( "do_search_load" ) ) {
	echo '<div id="message" class="updated fade"><p>';
	_e('You may only have one search plugin using the search api enabled at a time. Please disable the active search plugin first.');
	echo '</p>';
	die;
}
else {	
	/**
	* Search Load
	* This function is ran by a filter in the search API.
	* @return object The search plugin object (the class in this file)
	*/
	function do_search_load() {
		return new sphinx_search();
	}
}

register_activation_hook( __FILE__, 'sphinx_activate_self' );

/**
* Activate Self
* This function refreshes the search index when the plugin is activated
*/		
function sphinx_activate_self() {
	include_once( "search.php");
	$index = new search_index();
	$index->all();
	delete_option( 'search_custom_options' );
	delete_option( 'search_help' );
}

// Tell the above function to run in the search api
add_filter('search_load', 'do_search_load' );

/**
* Uninstall Code (Remove all of the data for plugins)
*/
//if ( function_exists('register_uninstall_hook') )
//	register_uninstall_hook(__FILE__, 'mysql_deinstall');
 
/**
* MySQL Deinstall
* This function removes all traces of the search plugin
*/
//function mysql_deinstall() {
//}
?>