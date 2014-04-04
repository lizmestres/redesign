<?php

/**
 * @file
 * Template overrides as well as (pre-)process and alter hooks for the brecht_omega
 * theme.
 */

/**
 * Check if a block is a menu block or not.
 *
 * @param stdClass $block
 *   A block object.
 *
 * @return bool
 *   Given block is a menu block.
 */
function _brecht_omega_is_menu_block($block) {
  $modules = array('menu', 'menu_block');
  if (in_array($block->module, $modules)) {
    return TRUE;
  }

  $modules = array('help', 'powered-by', 'main');
  if ($block->module == 'system' && !in_array($block->delta, $modules)) {
    return TRUE;
  }

  return FALSE;
}
