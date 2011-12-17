<?php
/**
 * @file
 * Provides Drag'n'Drop Uploads integration with the Drupal Core Image module.
 */

/**
 * Implements hook_dragndrop_uploads_widgets().
 */
function image_dragndrop_uploads_widgets($type, &$widgets) {
  // TODO: Replace with field_info_fields() when http://drupal.org/node/613754 is fixed.
  $instances = field_info_instances('node', $type);
  $fields = array();
  foreach ($instances as $field_name => $instance) {
    $fields[$field_name] = field_info_field($field_name);
    $fields[$field_name]['label'] = $instance['label'];
  }
  foreach ($fields as $field) {
    if ($field['module'] == 'image') {
      $data = serialize(array(
        'field' => $field['field_name'],
        'module' => $field['module'],
      ));
      $widgets[$data] = $field['label'];
    }
  }
}

/**
 * Implements hook_dragndrop_uploads_js_data().
 */
function image_dragndrop_uploads_js_data($data) {
  $field = field_info_field($data['field']);
  $data = array(
    'id' => $field['field_name'],
    'result' => '.output:last',
    'submit' => 'Upload',
    'wrapper' => '.field-name-' . str_replace('_', '-', $field['field_name']),
  );
  return array($field['field_name'] => $data);
}

/**
 * Implements hook_dragndrop_uploads_elements().
 */
function image_dragndrop_uploads_elements(&$type) {
  $type['managed_file']['#after_build'][] = 'image_dragndrop_uploads_process';
}

function image_dragndrop_uploads_process($element, &$form_state) {
  if ($element['#theme'] == 'image_widget') {
    if ($element['#file']) {
      $element['dragndrop_uploads'] = array(
        '#type' => 'hidden',
        '#value' => theme(
          'field_formatter_' . variable_get('dragndrop_uploads_formatter_' . $element['#bundle'], 'image'),
          array('element' => array(
            '#object_type' => $element['#object_type'],
            '#object' => (object) $form_state['values'],
            '#item' => (array) $element['#file'] + array(
              'alt' => '',
              'title' => '',
            ),
            '#formatter' => variable_get('dragndrop_uploads_formatter_' . $element['#bundle'], 'image'),
          ))
        ),
        '#name' => $element['#name'] . '[output]',
        '#id' => $element['#id'] . '-output',
        '#attributes' => array(
          'class' => 'output',
        ),
      );
    }
  }
  return $element;
}
