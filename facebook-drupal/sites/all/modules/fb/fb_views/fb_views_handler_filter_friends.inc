<?php
/**
 * @file
 * Filter handler for friends of the current user
 */
class fb_views_handler_filter_friends extends views_handler_filter_boolean_operator {
  function construct() {
    parent::construct();
    $this->value_value = t('Is friend of the current user');
  }

  function query() 
  {
    // We can't learn friends if user is not logged in.
    $fbu = fb_facebook_user();
    if($fbu) 
	{
      $friends = fb_get_friends($fbu);
      // if the user has friends
      if(count($friends) > 0) 
	  {
        $friends_formatted = "('" . implode("', '",$friends) . "')";
        $this->ensure_my_table();
        $this->query->add_where_expression($this->options['group'], $this->table_alias . '.fbu IN ' . $friends_formatted);
      }
	}
  }
 }
