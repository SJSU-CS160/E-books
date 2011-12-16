<?php
// $Id$

/**
 * Implements theme_field__field_type().
 */
function recruiter_bartik_field__taxonomy_term_reference($variables) {
  // Use default output instead of bartiks weird taxnomy field output
  return theme_field($variables);
}