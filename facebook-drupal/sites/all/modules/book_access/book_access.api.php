<?php
// $Id: book_access.api.php,v 1.3 2010/11/27 20:27:25 kiam Exp $

/**
 * @file
 * Hooks provided by the Book access module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Allows other modules to change the author access permissions for a book.
 *
 * @param $grants
 *   The array of the grants, in the format @code $grants[$grant] @endcode.
 * @param $context
 *   An array containing two indexes:
 *   - the book ID (bid)
 *   - the node for the book page (node)
 */
 function hook_book_access_author_grants_alter(&$grants, $context) {
   $node = $context['node'];

   if ($node->uid == 1) {
     $grants['grant_view'] = TRUE;
     $grants['grant_update'] = TRUE;
     $grants['grant_delete'] = TRUE;
   }
 }

/**
 * Allows other modules to change the users access permissions for a book.
 *
 * @param $rids
 *   The array containing the list of the role IDs, as returned by
 *   user_roles().
 * @param $grants
 *   The array of the grants, in the format @code $grants[$grant][$rid] @endcode.
 * @param $context
 *   An array containing two indexes:
 *   - the book ID (bid)
 *   - the node for the book page (node)
 */
 function hook_book_access_roles_grants_alter(&$rids, &$grants, $context) {
   if (isset($rids[DRUPAL_AUTHENTICATED_RID])) {
     $grants['grant_view'][DRUPAL_AUTHENTICATED_RID] = TRUE;
     $grants['grant_update'][DRUPAL_AUTHENTICATED_RID] = TRUE;
     $grants['grant_delete'][DRUPAL_AUTHENTICATED_RID] = TRUE;
   }
 }

/**
 * Allows other modules to change the roles access permissions for a book.
 *
 * @param $uids
 *   The array containing the list of the user IDs.
 * @param $grants
 *   The array of the grants, in the format @code $grants[$grant][$uid] @endcode.
 * @param $context
 *   An array containing two indexes:
 *   - the book ID (bid)
 *   - the node for the book page (node)
 */
 function hook_book_access_users_grants_alter(&$uids, &$grants, $context) {
   if (isset($uids[1])) {
     $grants['grant_view'][1] = TRUE;
     $grants['grant_update'][1] = TRUE;
     $grants['grant_delete'][1] = TRUE;
   }
 }

/**
 * @} End of "addtogroup hooks".
 */
