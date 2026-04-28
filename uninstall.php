<?php
/**
 * Uninstall routine for Magnet Surface Field Calculator.
 */

if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

delete_option('msfc_settings');
