<?php

/**
 * @file
 * Hooks provided by the Cart module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Performs extra processing when an item is added to the shopping cart.
 *
 * Some modules need to be able to hook into the process of adding items to a
 * cart. For example, an inventory system may need to check stock levels and
 * prevent an out of stock item from being added to a customer's cart. This hook
 * lets developers squeeze right in at the end of the process after the product
 * information is all loaded and the product is about to be added to the cart.
 * In the event that a product should not be added to the cart, you simply have
 * to return a failure message described below. This hook may also be used
 * simply to perform some routine action when products are added to the cart.
 *
 * @param $nid
 *   The node ID of the product.
 * @param $qty
 *   The quantity being added.
 * @param $data
 *   The data array, including attributes and model number adjustments.
 *
 * @return
 *   The function can use this data to whatever purpose to see if the item
 *   can be added to the cart or not. The function should return an array
 *   containing the result array. (This is due to the nature of Drupal's
 *   module_invoke_all() function. You must return an array within an array
 *   or other module data will end up getting ignored.) At this moment,
 *   there are only three keys:
 *   - success: TRUE or FALSE for whether the specified quantity of the item
 *     may be added to the cart or not; defaults to TRUE.
 *   - message: The fail message to display in the event of a failure; if
 *     omitted, Ubercart will display a default fail message.
 *   - silent: Return TRUE to suppress the display of any messages; useful
 *     when a module simply needs to do some other processing during an add
 *     to cart or fail silently.
 */
function hook_uc_add_to_cart($nid, $qty, $data) {
  if ($qty > 1) {
    $result[] = array(
      'success' => FALSE,
      'message' => t('Sorry, you can only add one of those at a time.'),
    );
  }
  return $result;
}

/**
 * Adds extra information to a cart item's "data" array.
 *
 * This is effectively the submit handler of any alterations to the Add to Cart
 * form. It provides a standard way to store the extra information so that it
 * can be used by hook_uc_add_to_cart().
 *
 * @param $form_values
 *   The values submitted to the Add to Cart form.
 *
 * @return
 *   An array of data to be merged into the item added to the cart.
 */
function hook_uc_add_to_cart_data($form_values) {
  $node = node_load($form_values['nid']);
  return array('module' => 'uc_product', 'shippable' => $node->shippable);
}

/**
 * Controls the display of an item in the cart.
 *
 * Product type modules allow the creation of nodes that can be added to the
 * cart. The cart determines how they are displayed through this hook. This is
 * especially important for product kits, because it may be displayed as a
 * single unit in the cart even though it is represented as several items.
 *
 * @param $item
 *   The item in the cart to display.
 *
 * @return
 *   A form array containing the following elements:
 *   - "nid"
 *     - #type: value
 *     - #value: The node id of the $item.
 *   - "module"
 *     - #type: value
 *     - #value: The module implementing this hook and the node represented by
 *       $item.
 *   - "remove"
 *     - #type: checkbox
 *     - #value: If selected, removes the $item from the cart.
 *   - "description"
 *     - #type: markup
 *     - #value: Themed markup (usually an unordered list) displaying extra information.
 *   - "title"
 *     - #type: markup
 *     - #value: The displayed title of the $item.
 *   - "#total"
 *     - type: float
 *     - value: Numeric price of $item. Notice the '#' signifying that this is
 *       not a form element but just a value stored in the form array.
 *   - "data"
 *     - #type: hidden
 *     - #value: The serialized $item->data.
 *   - "qty"
 *     - #type: textfield
 *     - #value: The quantity of $item in the cart. When "Update cart" is
 *       clicked, the customer's input is saved to the cart.
 */
function hook_uc_cart_display($item) {
  $node = node_load($item->nid);
  $element = array();
  $element['nid'] = array('#type' => 'value', '#value' => $node->nid);
  $element['module'] = array('#type' => 'value', '#value' => 'uc_product');
  $element['remove'] = array('#type' => 'checkbox');

  $element['title'] = array(
    '#markup' => node_access('view', $node) ? l($item->title, 'node/' . $node->nid) : check_plain($item->title),
  );


  $element['#total'] = $item->price * $item->qty;
  $element['data'] = array('#type' => 'hidden', '#value' => serialize($item->data));
  $element['qty'] = array(
    '#type' => 'textfield',
    '#default_value' => $item->qty,
    '#size' => 5,
    '#maxlength' => 6
  );

  if ($description = uc_product_get_description($item)) {
    $element['description'] = array('#markup' => $description);
  }

  return $element;
}

/**
 * Adds extra data about an item in the cart.
 *
 * Products that are added to a customer's cart are referred as items until the
 * sale is completed. Just think of a grocery store having a bunch of products
 * on the shelves but putting a sign over the express lane saying "15 Items or
 * Less." hook_uc_cart_item() is in charge of acting on items at various times
 * like when they are being added to a cart, saved, loaded, and checked out.
 *
 * Here's the rationale for this hook: Products may change on a live site during
 * a price increase or change to attribute adjustments. If a user has previously
 * added an item to their cart, when they go to checkout or view their cart
 * screen we want the latest pricing and model numbers to show. So, the
 * essential product information is stored in the cart, but when the items in
 * a cart are loaded, modules are given a chance to adjust the data against
 * the latest settings.
 *
 * @param $op
 *   The action that is occurring. Possible values:
 *   - load: Passed for each item when a cart is being loaded in the function
 *     uc_cart_get_contents(). This gives modules the chance to tweak
 *     information for items when the cart is being loaded prior to being
 *     added to an order. No return value is expected.
 *   - view: Passed for each item when it is about to be displayed on the
 *     cart page. Modifications made affect only displayed information and are
 *     not used in any calculations.
 *   - can_ship: Passed when a cart is being scanned for items that are not
 *     shippable items. Ubercart will bypass cart and checkout operations
 *     specifically related to tangible products if nothing in the cart is
 *     shippable. hook_uc_cart_item() functions that check for this op are
 *     expected to return TRUE or FALSE based on whether a product is
 *     shippable or not.
 *   - remove: Passed when an item is removed from the cart.
 *   - checkout: Passed for each item when the cart is being emptied for
 *     checkout.
 *
 * @return
 *   No return value for load or view. TRUE or FALSE for can_ship.
 */
function hook_uc_cart_item($op, $item) {
  switch ($op) {
    case 'load':
      $term = array_shift(taxonomy_node_get_terms_by_vocabulary($item->nid, variable_get('uc_manufacturer_vid', 0)));
      $item->manufacturer = $term->name;
      break;
  }
}

/**
 * Registers callbacks for a cart pane.
 *
 * The default cart view page displays a table of the cart contents and a few
 * simple form features to manage the cart contents. For a module to add
 * information to this page, it must use hook_uc_cart_pane() to define extra
 * panes that may be ordered to appear above or below the default information.
 *
 * @param $items
 *   The current contents of the shopping cart.
 *
 * @return
 *   The function is expected to return an array of pane arrays, keyed by the
 *   internal ID of the pane, each with the following members:
 *   - "title"
 *     - type: string
 *     - value: The name of the cart pane displayed to the user.  Use t().
 *   - "enabled"
 *     - type: boolean
 *     - value: Whether the pane is enabled by default or not. (Defaults to TRUE.)
 *   - "weight"
 *     - type: integer
 *     - value: The weight of the pane to determine its display order. (Defaults
 *         to 0.)
 *   - "body"
 *     - type: array
 *     - value: The body of the pane to be rendered on the cart view screen.
 *
 * The body gets printed to the screen if it is on the cart view page.  For the
 * settings page, the body field is ignored.  You may want your function to
 * check for a NULL argument before processing any queries or foreach() loops.
 */
function hook_uc_cart_pane($items) {
  $body = array();

  if (!is_null($items)) {
    $body = drupal_get_form('uc_cart_view_form', $items) + array(
      '#prefix' => '<div id="cart-form-pane">',
      '#suffix' => '</div>',
    );
  }

  $panes['cart_form'] = array(
    'title' => t('Default cart form'),
    'enabled' => TRUE,
    'weight' => 0,
    'body' => $body,
  );

  return $panes;
}

/**
 * Alters cart pane definitions.
 *
 * @param $panes
 *   The array of pane information in the format defined in hook_uc_cart_pane(),
 *   passed by reference.
 * @param $items
 *   The array of item information.
 */
function hook_uc_cart_pane_alter(&$panes, $items) {
  $panes['cart_form']['body'] = drupal_get_form('my_custom_pane_form_builder', $items);
}

/**
 * Takes action when checkout is completed.
 *
 * @param $order
 *   The resulting order object from the completed checkout.
 * @param $account
 *   The customer that completed checkout, either the current user, or the
 *   account created for an anonymous customer.
 */
function hook_uc_checkout_complete($order, $account) {
  // Get previous records of customer purchases.
  $nids = array();
  $result = db_query("SELECT uid, nid, qty FROM {uc_customer_purchases} WHERE uid = :uid", array(':uid' => $account->uid));
  foreach ($result as $record) {
    $nids[$record->nid] = $record->qty;
  }

  // Update records with new data.
  $record = array('uid' => $account->uid);
  foreach ($order->products as $product) {
    $record['nid'] = $product->nid;
    if (isset($nids[$product->nid])) {
      $record['qty'] = $nids[$product->nid] + $product->qty;
      db_write_record($record, 'uc_customer_purchases', array('uid', 'nid'));
    }
    else {
      $record['qty'] = $product->qty;
      db_write_record($record, 'uc_customer_purchases');
    }
  }
}

/**
 * Registers callbacks for a checkout pane.
 *
 * The checkout screen for Ubercart is a compilation of enabled checkout panes.
 * A checkout pane can be used to display order information, collect data from
 * the customer, or interact with other panes. Panes are defined in enabled
 * modules with hook_uc_checkout_pane() and displayed and processed through
 * specified callback functions. Some of the settings for each pane are
 * configurable from the checkout settings page with defaults being specified
 * in the hooks.
 *
 * The default panes are defined in uc_cart.module in the function
 * uc_cart_checkout_pane(). These include panes to display the contents of the
 * shopping cart and to collect essential site user information, a shipping
 * address, a payment address, and order comments. Other included modules offer
 * panes for shipping and payment purposes as well.
 *
 * @return
 *   An array of checkout pane arrays, keyed by the internal ID of the pane, each
 *   with the following members:
 *   - title:
 *     - type: string
 *     - value: The name of the pane as it appears on the checkout form.
 *   - desc:
 *     - type: string
 *     - value: A short description of the pane for the admin pages.
 *   - callback:
 *     - type: string
 *     - value: The name of the callback function for this pane.  View
 *       @link http://www.ubercart.org/docs/developer/245/checkout this page @endlink
 *       for more documentation and examples of checkout pane callbacks.
 *   - weight:
 *     - type: integer
 *     - value: Default weight of the pane, defining its order on the checkout form.
 *   - enabled:
 *     - type: boolean
 *     - value: Optional. Whether or not the pane is enabled by default. Defaults
 *       to TRUE.
 *   - process:
 *     - type: boolean
 *     - value: Optional. Whether or not this pane needs to be processed when the
 *       checkout form is submitted. Defaults to TRUE.
 *   - collapsible:
 *     - type: boolean
 *     - value: Optional. Whether or not this pane is displayed as a collapsible
 *       fieldset. Defaults to TRUE.
 *   - shippable:
 *     - type: boolean
 *     - value: Optional. If TRUE, the pane is only shown if the cart is shippable.
 *       Defaults to NULL.
 */
function hook_uc_checkout_pane() {
  $panes['cart'] = array(
    'callback' => 'uc_checkout_pane_cart',
    'title' => t('Cart Contents'),
    'desc' => t("Display the contents of a customer's shopping cart."),
    'weight' => 1,
    'process' => FALSE,
    'collapsible' => FALSE,
  );
  return $panes;
}

/**
 * Builds and proceses a pane defined by hook_uc_checkout_pane().
 *
 * @param string $op
 *   The operation the pane is performing. Possible values are "view", "process",
 *   "review", and "settings".
 * @param UcOrder $order
 *   The order being viewed or edited.
 * @param array $form
 *   The order's edit form. NULL for non-edit ops.
 * @param array &$form_state
 *   The form state array of the edit form. NULL for non-edit ops.
 *
 * @return
 *   Varies according to the value of $op:
 *   - view: An array with two keys, "contents" and an optional "description".
 *     "contents" is a form array to collect the checkout data for the pane. The
 *     description provides help text for the pane as a whole.
 *   - process: A boolean indicating that checkout should continue. During this
 *     op, $order should be modified with the values in $form_state['values']['panes'][PANE_ID].
 *   - review: An array containing review sections. A review section contains
 *     "title" and "data" keys which have HTML to be displayed on the checkout
 *     review page.
 *   - settings: A settings form which can be used with system_settings_form().
 */
function uc_checkout_pane_callback($op, $order, $form = NULL, &$form_state = NULL) {
  // uc_checkout_pane_comments()
  switch ($op) {
    case 'view':
      $description = t('Use this area for special instructions or questions regarding your order.');

      if (!empty($order->order_id)) {
        $default = db_query("SELECT message FROM {uc_order_comments} WHERE order_id = :id", array(':id' => $order->order_id))->fetchField();
      }
      else {
        $default = NULL;
      }
      $contents['comments'] = array(
        '#type' => 'textarea',
        '#title' => t('Order comments'),
        '#default_value' => $default,
      );

      return array('description' => $description, 'contents' => $contents);

    case 'process':
      if (strlen($form_state['values']['panes']['comments']['comments']) > 0) {
        db_delete('uc_order_comments')
          ->condition('order_id', $order->order_id)
          ->execute();
        uc_order_comment_save($order->order_id, 0, $form_state['values']['panes']['comments']['comments'], 'order', uc_order_state_default('post_checkout'), TRUE);
      }
      return TRUE;

    case 'review':
      $review = NULL;
      $result = db_query("SELECT message FROM {uc_order_comments} WHERE order_id = :id", array(':id' => $order->order_id));
      if ($comment = $result->fetchObject()) {
        $review[] = array('title' => t('Comment'), 'data' => check_plain($comment->message));
      }
      return $review;
  }
}

/**
 * Alters checkout pane definitions.
 *
 * @param $panes
 *   Array with the panes information as defined in hook_uc_checkout_pane(),
 *   passed by reference.
 */
function hook_uc_checkout_pane_alter(&$panes) {
  $panes['cart']['callback'] = 'my_custom_module_callback';
}

/**
 * Handles requests to update a cart item.
 *
 * @param $nid
 *   Node id of the cart item.
 * @param $data
 *   Array of extra information about the item.
 * @param $qty
 *   The quantity of this item in the cart.
 * @param $cid
 *   The cart id. Defaults to NULL, which indicates that the current user's cart
 *   should be retrieved with uc_cart_get_id().
 */
function hook_uc_update_cart_item($nid, $data = array(), $qty, $cid = NULL) {
  if (!$nid) return NULL;
  $cid = !(is_null($cid) || empty($cid)) ? $cid : uc_cart_get_id();
  if ($qty < 1) {
    uc_cart_remove_item($nid, $cid, $data);
  }
  else {
    db_update('uc_cart_products')
      ->fields(array(
        'qty' => $qty,
        'changed' => REQUEST_TIME,
      ))
      ->condition('nid', $nid)
      ->condition('cart_id', $cid)
      ->condition('data', serialize($data))
      ->execute();
  }

  // Rebuild the items hash
  uc_cart_get_contents(NULL, 'rebuild');
  if (!strpos(request_uri(), 'cart', -4)) {
    drupal_set_message(t('Your item(s) have been updated.'));
  }
}

/**
 * @} End of "addtogroup hooks".
 */
