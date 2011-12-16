<?php

/**
 * @file
 * Hooks provided by the Order module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Adds invoice templates to the list of suggested template files.
 *
 * Allows modules to declare new "types" of invoice templates (other than the
 * default 'admin' and 'customer').
 *
 * @return
 *   Array of template names that are available choices when mailing an invoice.
 */
function hook_uc_invoice_templates() {
  return array('admin', 'customer');
}

/**
 * Defines line items that are attached to orders.
 *
 * A line item is a representation of charges, fees, and totals for an order.
 * Default line items include the subtotal and total line items, the tax line
 * item, and the shipping line item. There is also a generic line item that
 * store admins can use to add extra fees and discounts to manually created
 * orders. Module developers will use this hook to define new types of line
 * items for their stores. An example use would be for a module that allows
 * customers to use coupons and wants to represent an entered coupon as a line
 * item.
 *
 * Once a line item has been defined in hook_line_item, Ubercart will begin
 * interacting with it in various parts of the code. One of the primary ways
 * this is done is through the callback function you specify for the line item.
 *
 * @return
 *   Your hook should return an array of associative arrays. Each item in the
 *   array represents a single line item, keyed by the internal ID of the line
 *   item, and with the following members:
 *   - "title"
 *     - type: string
 *     - value: The title of the line item shown to the user in various interfaces.
 *         Use t().
 *   - "callback"
 *     - type: string
 *     - value: Name of the line item's callback function, called for various
 *         operations.
 *   - "weight"
 *     - type: integer
 *     - value: Display order of the line item in lists; "lighter" items are
 *         displayed first.
 *   - "stored"
 *     - type: boolean
 *     - value: Whether or not the line item will be stored in the database.
 *         Should be TRUE for any line item that is modifiable from the order
 *         edit screen.
 *   - "add_list"
 *     - type: boolean
 *     - value: Whether or not a line item should be included in the "Add a Line
 *         Item" select box on the order edit screen.
 *   - "calculated"
 *     - type: boolean
 *     - value: Whether or not the value of this line item should be added to the
 *         order total. (Ex: would be TRUE for a shipping charge line item but
 *         FALSE for the subtotal line item since the product prices are already
 *         taken into account.)
 *   - "display_only"
 *     - type: boolean
 *     - value: Whether or not this line item is simply a display of information
 *         but not calculated anywhere. (Ex: the total line item uses display to
 *         simply show the total of the order at the bottom of the list of line
 *         items.)
 */
function hook_uc_line_item() {
  $items[] = array(
    'id' => 'generic',
    'title' => t('Empty Line'),
    'weight' => 2,
    'default' => FALSE,
    'stored' => TRUE,
    'add_list' => TRUE,
    'calculated' => TRUE,
    'callback' => 'uc_line_item_generic',
  );

  return $items;
}

/**
 * Alters a line item on an order when the order is loaded.
 *
 * @param &$item
 *   The line item array.
 * @param $order
 *   The order object containing the line item.
 */
function hook_uc_line_item_alter(&$item, $order) {
  $account = user_load($order->uid);
  rules_invoke_event('calculate_line_item_discounts', $item, $account);
}

/**
 * Alters the line item definitions declared in hook_line_item().
 *
 * @param &$items
 *   The combined return value of hook_line_item().
 */
function hook_uc_line_item_data_alter(&$items) {
  // Tax amounts are added in to other line items, so the actual tax line
  // items should not be added to the order total.
  $items['tax']['calculated'] = FALSE;

  // Taxes are included already, so the subtotal without taxes doesn't
  // make sense.
  $items['tax_subtotal']['callback'] = NULL;
}


/**
 * Performs actions on orders.
 *
 * An order in Ubercart represents a single transaction. Orders are created
 * during the checkout process where they sit in the database with a status of In
 * Checkout. When a customer completes checkout, the order's status gets updated
 * to show that the sale has gone through. Once an order is created, and even
 * during its creation, it may be acted on by any module to connect extra
 * information to an order. Every time an action occurs to an order, hook_order()
 * gets invoked to let your modules know what's happening and make stuff happen.
 *
 * @param $op
 *   The action being performed.
 * @param $order
 *   This is the order object.
 * @param $arg2
 *   This is variable and is based on the value of $op:
 *   - new: Called when an order is created. $order is a reference to the new
 *     order object, so modules may add to or modify the order at creation.
 *   - presave: Before an order object is saved, the hook gets invoked with this
 *     op to let other modules alter order data before it is written to the
 *     database. $order is a reference to the order object.
 *   - save: When an order object is being saved, the hook gets invoked with
 *     this op to let other modules do any necessary saving. $order is a
 *     reference to the order object.
 *   - load: Called when an order is loaded after the order and product data has
 *     been loaded from the database. Passes $order as the reference to the
 *     order object, so modules may add to or modify the order object when it's
 *     loaded.
 *   - submit: When a sale is being completed and the customer has clicked the
 *     Submit order button from the checkout screen, the hook is invoked with
 *     this op. This gives modules a chance to determine whether or not the
 *     order should be allowed. An example use of this is the credit module
 *     attempting to process payments when an order is submitted and returning
 *     a failure message if the payment failed.
 *     To prevent an order from passing through, you must return an array
 *     resembling the following one with the failure message:
 *     @code
 *       return array(array('pass' => FALSE, 'message' => t('We were unable to process your credit card.')));
 *     @endcode
 *   - can_update: Called before an order's status is changed to make sure the
 *     order can be updated. $order is the order object with the old order
 *     status ID ($order->order_status), and $arg2 is simply the new order
 *     status ID. Return FALSE to stop the update for some reason.
 *   - update: Called when an order's status is changed. $order is the order
 *     object with the old order status ID ($order->order_status), and $arg2 is
 *     the new order status ID.
 *   - can_delete: Called before an order is deleted to verify that the order
 *     may be deleted. Returning FALSE will prevent a delete from happening.
 *     (For example, the payment module returns FALSE by default when an order
 *     has already received payments.)
 *   - delete: Called when an order is deleted and before the rest of the order
 *     information is removed from the database. Passes $order as the order
 *     object to let your module clean up it's tables.
 *   - total: Called when the total for an order is being calculated after the
 *     total of the products has been added. Passes $order as the order object.
 *     Expects in return a value (positive or negative) by which to modify the
 *     order total.
 */
function hook_uc_order($op, $order, $arg2) {
  switch ($op) {
    case 'save':
      // Do something to save payment info!
      break;
  }
}

/**
 * Adds links to local tasks for orders on the admin's list of orders.
 *
 * @param $order
 *   An order object.
 *
 * @return
 *   An array of specialized link arrays. Each link has the following keys:
 *   - name: The title of page being linked.
 *   - url: The link path. Do not use url(), but do use the $order's order_id.
 *   - icon: HTML of an image.
 *   - title: Title attribute text (mouseover tool-tip).
 */
function hook_uc_order_actions($order) {
  $actions = array();
  $module_path = base_path() . drupal_get_path('module', 'uc_shipping');
  if (user_access('fulfill orders')) {
    $result = db_query("SELECT COUNT(nid) FROM {uc_order_products} WHERE order_id = :id AND data LIKE :data", array(':id' => $order->order_id, ':data' => '%s:9:\"shippable\";s:1:\"1\";%'));
    if ($result->fetchField()) {
      $title = t('Package order !order_id products.', array('!order_id' => $order->order_id));
      $actions[] = array(
        'name' => t('Package'),
        'url' => 'admin/store/orders/' . $order->order_id . '/packages',
        'icon' => '<img src="' . $module_path . '/images/package.gif" alt="' . $title . '" />',
        'title' => $title,
      );
      $result = db_query("SELECT COUNT(package_id) FROM {uc_packages} WHERE order_id = :id", array(':id' => $order->order_id));
      if ($result->fetchField()) {
        $title = t('Ship order !order_id packages.', array('!order_id' => $order->order_id));
        $actions[] = array(
          'name' => t('Ship'),
          'url' => 'admin/store/orders/' . $order->order_id . '/shipments',
          'icon' => '<img src="' . $module_path . '/images/ship.gif" alt="' . $title . '" />',
          'title' => $title,
        );
      }
    }
  }
  return $actions;
}

/**
 * Registers callbacks for an order pane.
 *
 * This hook is used to add panes to the order viewing and administration screens.
 * The default panes include areas to display and edit addresses, products,
 * comments, etc. Developers should use this hook when they need to display or
 * modify any custom data pertaining to an order. For example, a store that uses
 * a custom checkout pane to find out a customer's desired delivery date would
 * then create a corresponding order pane to show the data on the order screens.
 *
 * hook_order_pane() works by defining new order panes and providing a little bit
 * of information about them. View the return value section below for information
 * about what parts of an order pane are defined by the hook.
 *
 * The real meat of an order pane is its callback function (which is specified in
 * the hook). The callback function handles what gets displayed on which screen
 * and what data can be manipulated. That is all somewhat out of the scope of
 * this API page, so you'll have to click here to read more about what a callback
 * function should contain.
 *
 * @return
 *   An array of order pane arrays, keyed by the internal ID of the pane, with
 *   the following members:
 *   - callback:
 *     - type: string
 *     - value: The name of the callback function for this pane.  View
 *       @link http://www.ubercart.org/docs/developer/245/checkout this page @endlink
 *       for more documentation and examples of checkout pane callbacks.
 *   - title:
 *     - type: string
 *     - value: The name of the pane as it appears on the order admin form.
 *   - desc:
 *     - type: string
 *     - value: A short description of the pane for the admin pages.
 *   - class:
 *     - type: string
 *     - value: A CSS class that determines the relative position of the pane's
 *       div. Choose "pos-left" to float left against the previous pane or
 *       "abs-left" to start a new line of panes.
 *   - weight:
 *     - type: integer
 *     - value: Default weight of the pane, defining its order on the checkout form.
 *   - show:
 *     - type: array
 *     - value: The list of op values which will show the pane. "view", "edit",
 *       "invoice", and "customer" are possible values.
 */
function hook_uc_order_pane() {
  $panes['admin_comments'] = array(
    'callback' => 'uc_order_pane_admin_comments',
    'title' => t('Admin comments'),
    'desc' => t('View the admin comments, used for administrative notes and instructions.'),
    'class' => 'abs-left',
    'weight' => 9,
    'show' => array('view', 'edit'),
  );
  return $panes;
}

/**
 * Alter order pane definitions.
 *
 * @param $panes
 *   Array with the panes information as defined in hook_uc_order_pane(),
 *   passed by reference.
 */
function hook_uc_order_pane_alter(&$panes) {
  $panes['payment']['callback'] = 'my_custom_module_callback';
}

/**
 * Builds and processes an order pane defined by hook_uc_order_pane().
 *
 * @param string $op
 *   The operation the pane is performing. Possible values are "view",
 *   "customer", "show-title", "edit-form", "edit-title", "edit-theme",
 *   "edit-process", "edit-ops", and any of the strings returned when $op
 *   is "edit-ops".
 * @param UcOrder $order
 *   The order being viewed or edited.
 * @param array $form
 *   The order's edit form. NULL for non-edit ops.
 * @param array &$form_state
 *   The form state array of the edit form. NULL for non-edit ops.
 *
 * @return
 *   Varies according to the value of $op:
 *   - view: A render array showing admin-visible order data.
 *   - customer: A render array showing customer-visible order data.
 *   - show-title: A boolean flag indicating that the title of the pane should
 *     be shown during the "view" and "customer" ops. Defaults to TRUE.
 *   - edit-form: $form with the pane grafted in.
 *   - edit-title: HTML to serve as the pane's title on the edit form.
 *   - edit-theme: The rendered portion of the $form that the pane added.
 *   - edit-process: An array of values to be modified on the order object,
 *     keyed by the object's property, or NULL to signify no change on the order
 *     object.
 *   - edit-ops: An array of possible $op values that this pane may use to do
 *     alternate processing on the edit form.
 *   - edit-ops values: No return value expected.
 */
function uc_order_pane_callback($op, $order, &$form = NULL, &$form_state = NULL) {
  // uc_order_pane_admin_comments()
  switch ($op) {
    case 'view':
      $comments = uc_order_comments_load($order->order_id, TRUE);
      return tapir_get_table('uc_op_admin_comments_view_table', $comments);

    case 'edit-form':
      $form['admin_comment_field'] = array(
        '#type' => 'fieldset',
        '#title' => t('Add an admin comment'),
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
      );
      $form['admin_comment_field']['admin_comment'] = array(
        '#type' => 'textarea',
        '#description' => t('Admin comments are only seen by store administrators.'),
      );
      return $form;

    case 'edit-theme':
      $comments = uc_order_comments_load($form['order_id']['#value'], TRUE);
      if (is_array($comments) && count($comments) > 0) {
        foreach ($comments as $comment) {
          $items[] = '[' . theme('uc_uid', array('uid' => $comment->uid)) . '] ' . filter_xss_admin($comment->message);
        }
      }
      else {
        $items = array(t('No admin comments have been entered for this order.'));
      }
      $output = theme('item_list', array('items' => $items)) . drupal_render($form['admin_comment_field']);
      return $output;

    case 'edit-process':
      if (!is_null($order['admin_comment']) && strlen(trim($order['admin_comment'])) > 0) {
        global $user;
        uc_order_comment_save($order['order_id'], $user->uid, $order['admin_comment']);
      }
      return;
  }
}

/**
 * Allows modules to alter ordered products when they're loaded with an order.
 *
 * @param &$product
 *   The product object as found in the $order object.
 * @param $order
 *   The order object to which the product belongs.
 *
 * @return
 *   Nothing should be returned. Hook implementations should receive the
 *   $product object by reference and alter it directly.
 */
function hook_uc_order_product_alter(&$product, $order) {
  drupal_set_message('hook_order_product_alter(&$product, $order):');
  drupal_set_message('&$product: <pre>' . print_r($product, TRUE) . '</pre>');
  drupal_set_message('$order: <pre>' . print_r($order, TRUE) . '</pre>');
}

/**
 * Responds to order product deletion.
 */
function hook_uc_order_product_delete($order_product_id) {
  // Put back the stock.
  $product = db_query("SELECT model, qty FROM {uc_order_products} WHERE order_product_id = :id", array(':id' => $order_product_id))->fetchObject();
  uc_stock_adjust($product->model, $product->qty);
}

/**
 * Registers static order states.
 *
 * Order states are module-defined categories for order statuses. Each state
 * will have a default status that is used when modules need to move orders to
 * new state, but don't know which status to use.
 *
 * @return
 *   An array of order state definitions. Each definition is an array keyed by
 *   the machine name of the state, with the following members:
 *   - title: The human-readable, translated name.
 *   - weight: The list position of the state.
 *   - scope: Either "specific" or "general".
 */
function hook_uc_order_state() {
  $states['canceled'] = array(
    'title' => t('Canceled'),
    'weight' => -20,
    'scope' => 'specific',
  );
  $states['in_checkout'] = array(
    'title' => t('In checkout'),
    'weight' => -10,
    'scope' => 'specific',
  );
  $states['post_checkout'] = array(
    'title' => t('Post checkout'),
    'weight' => 0,
    'scope' => 'general',
  );
  $states['completed'] = array(
    'title' => t('Completed'),
    'weight' => 20,
    'scope' => 'general',
  );

  return $states;
}

/**
 * @} End of "addtogroup hooks".
 */
