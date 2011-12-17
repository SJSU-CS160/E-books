<?php
// $Id$

function restricted_content_settings_form() {
  $form['restricted_content_message'] = array(
    '#type' => 'textarea',
    '#title' => t('Message to replace content for restricted users'),
    '#default_value' => restricted_content_var('message'),
    '#wysiyg' => FALSE,
  );
  $form['restricted_content_message_anon'] = array(
    '#type' => 'textarea',
    '#title' => t('Additional message to display for restricted anonymous users'),
    '#default_value' => restricted_content_var('message_anon'),
    '#wysiyg' => FALSE,
  );

  if (module_exists('token')) {
    $form['tokens'] = array(
      '#value' => theme('token_help', 'node'),
    );
  }

  return system_settings_form($form);
}
