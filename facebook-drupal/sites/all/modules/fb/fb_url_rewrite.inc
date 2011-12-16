<?php
/**
 * @file
 * Performs custom url rewriting for Drupal for Facebook.
 *
 * These function prefix urls for canvas pages and profile tabs.  This allows
 * modules/fb to detect when a canvas page is requested.  This is necessary
 * because the facebook platform does not send us sufficient information to
 * detect this in some cases, particularly when the user is not logged into
 * facebook or has not authorized the app.  In those case, drupal could not
 * tell the difference between a canvas request and a regular HTML page
 * request without this code.  The url prefix allows us to detect that the
 * request is for a canvas page, and which specific application if more than
 * one is hosted.
 *
 * We define our custom_url rewrite function here, and not in
 * fb_canvas.module, because custom_url_rewrite_inbound() must be defined
 * *before* drupal_init_path(), which is called during DRUPAL_BOOTSTRAP_PATH,
 * before modules are loaded.
 *
 * If your application supports canvas pages and/or profile tabs, include this
 * file in your settings.php.
 * I.e. the end of your settings.php might look like:
 *
 * include 'sites/all/modules/fb/fb_url_rewrite.inc'; // For canvas pages support.
 * include 'sites/all/modules/fb/fb_settings.inc'; // Always include this.
 */

/**
 * Returns a list of the values which we prepend to paths when rewriting urls.
 */
function _fb_settings_url_rewrite_prefixes() {
  $prefixes = &drupal_static(__FUNCTION__);
  if (!isset($prefixes)) {
    $prefixes = array(
      FB_SETTINGS_CB,
      FB_SETTINGS_CB_PAGE,
      FB_SETTINGS_CB_TYPE,
      FB_SETTINGS_CB_SESSION,
    );
  }
  return $prefixes;
}

/**
 * Parse a setting from the URL.  This may be called before
 * custom_url_rewrite, so we can't count on fb_settings() to return the value.
 * For internal use only (see fb_session.inc).
 */
function _fb_settings_parse($key) {
  // XXX will this $_GET[q] stuff still work in D7???
  if (isset($_GET['q'])) {
    $path = $_GET['q'];
    $pos = strpos($path, $key . '/');
    if ($pos !== FALSE) {
      // Too soon for arg() function.
      $args = explode('/', $path);
      $i = 0;
      while (isset($args[$i]) && isset($args[$i+1])) {
        if ($args[$i] == $key)
          // Found the value we're interested in.
          return $args[$i+1];
        $i = $i + 2;
      }
    }
  }
}


//// URL Management


/**
 * Implements hook_url_outbound_alter().
 *
 * @param $options
 *   If $options['fb_url_alter'] == FALSE, this function will not alter the
 *   URL.  If used with $options['absolute'] == TRUE, this will generate a
 *   link from a canvas page out to the server's URL.
 */
function fb_url_outbound_alter(&$path, &$options, $original_path) {
  if ((isset($options['external']) && $options['external']) ||
      (isset($options['fb_url_alter']) && $options['fb_url_alter'] === FALSE)) {
    return;
  }

  // For most hooks, fb should come before fb_.... modules.  But in this case we want fb_canvas to act first.
  if (function_exists('fb_canvas_url_outbound_alter')) {
    fb_canvas_url_outbound_alter($path, $options, $original_path);
  }

  $pre = '';

  // Prefix each known value to the URL
  foreach (_fb_settings_url_rewrite_prefixes() as $prefix) {
    if (!isset($options[$prefix]) || $options[$prefix] !== FALSE) {
      if ($value = fb_settings($prefix))
        $pre .= $prefix . '/' . $value . '/';
    }
  }

  if ($pre) {
    if ($path == '<front>') {
      // Do we really have to do this here?
      $path = '';
    }

    $path =
      $pre .
      (!empty($options['prefix']) ? $options['prefix'] : '') .
      $path;

    // We have manually set the prefix, so remove the options prefix.
    $options['prefix'] = '';
  }

  // Since we called fb_canvas_url_alter already, supress it from acting again.
  $options['fb_url_alter'] = FALSE;
}

/**
 * Implements hook_url_inbound_alter().
 *
 * Rewrite URLs for facebook canvas pages, and connect callbacks.
 *
 */
function fb_url_inbound_alter(&$path, $original_path, $path_language) {
  global $language;

  // See if this is a request for us.
  if (strpos($path, FB_SETTINGS_CB . '/') === 0) {
    // Too soon for arg() function.
    $args = explode('/', $path);
    while (count($args) && in_array($args[0], _fb_settings_url_rewrite_prefixes())) {
      $key = array_shift($args);
      $value = array_shift($args);
      if (fb_settings($key) === NULL) // defer to previously set values
        fb_settings($key, $value); // Store for use later.
    }

    // Remove our prefixes
    $path = implode('/', $args);

    // Because Drupal calls hook_url_inbound_alter() later than it should, we have to repeat this work, which path.inc has already done.
    if (!$path) {
      $path = variable_get('site_frontpage', 'node');
    }
    $alias = drupal_lookup_path('source', $path, $path_language);
    if ($alias) {
      $path = $alias;
    }
  }

  $result = $path;
}

