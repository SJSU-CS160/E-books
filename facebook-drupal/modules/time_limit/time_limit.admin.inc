<?php
// $Id: time_limit.admin.inc,v 1.1.2.1 2010/03/10 17:23:52 hippich Exp $

/**
 * @file
 * Defines administative functions.
 *
 */



/**
 * Used to define Time Limit module settings form
 */
function time_limit_settings() {
  $form = array();

  $form['time_limit_time'] = array(
    '#type'           => 'textfield',
    '#title'          => t('Time limit'),
    '#description'    => t('(in seconds)'),
    '#default_value'  => variable_get('time_limit_time', 300),
  );

  $form['time_limit_landing_page'] = array(
    '#type'           => 'textfield',
    '#title'          => t('Landing page'),
    '#description'    => t('Use internal path for page on which user will be redirect after time limit.'),
    '#default_value'  => variable_get('time_limit_landing_page', 'user/register'),
  );

  $form['time_limit_message'] = array(
    '#type'           => 'textarea',
    '#title'          => t('Limit message'),
    '#description'    => t('This message will be shown after redirecting user to landing page.'),
    '#default_value'  => variable_get('time_limit_message', 'Please, register or login to continue using of the site.'),
  );

  $form['time_limit_default_exclude'] = array(
    '#type'           => 'radios',
    '#title'          => t('Default'),
    '#options'        => array(
      '0' => t("Show all"),
      '1' => t("Hide all"),
    ),
    '#default_value'  => variable_get('time_limit_default_exclude', 1),
  );

  $form['path_exclude'] = array(
    '#type'           => 'fieldset',
    '#title'          => t("Show/hide pages by path"),
  );

  $form['path_exclude']['time_limit_path_exclude'] = array(
    '#type'           => 'radios',
    '#options'        => array(
      '0' => t("Show"),
      '1' => t("Hide"),
    ),
    '#default_value'  => variable_get('time_limit_path_exclude', 0),
  );

  $form['path_exclude']['time_limit_exclude'] = array(
    '#type'           => 'textarea',
    '#title'          => t('Show/hide paths'),
    '#description'    => t('Place internal paths where module should show/hide. One path per line. Depends on Show/Hide above.'),
    '#default_value'  => variable_get('time_limit_exclude', "user\nuser/register\nuser/login\nuser/password"),
  );

  $form['content_type_exclude'] = array(
    '#type'           => 'fieldset',
    '#title'          => t("Show/hide pages by content type"),
  );

  $form['content_type_exclude']['time_limit_content_type_exclude'] = array(
    '#type'           => 'radios',
    '#options'        => array(
      '0' => t("Show"),
      '1' => t("Hide"),
    ),
    '#default_value'  => variable_get('time_limit_content_type_exclude', 0),
  );

  $options = array();
  $types = node_get_types();
  foreach ($types as $type) {
    $options[$type->type] = $type->name;
  }

  $form['content_type_exclude']['time_limit_content_types'] = array(
    '#type'           => 'checkboxes',
    '#title'          => t("Types"),
    '#options'        => $options,
    '#default_value'  => variable_get('time_limit_content_types', array()),
  );

  return system_settings_form($form);
}
