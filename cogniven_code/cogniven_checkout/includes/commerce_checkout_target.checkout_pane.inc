<?php

/*
 * @file
 * Checkout pane callback functions for the virtual goods delivery pane
 */

/**
 * Target pane: settins form callback.
 */
function commerce_checkout_target_settings_form($checkout_pane) {
  $form = array();

  $form['commerce_checkout_target_pane_display'] = array(
    '#type' => 'checkbox',
    '#title' => t('Display the Delivery target pane for authenticated users.'),
    '#description' => t('If checked, the pane will show to authenticated users and allow changes.'),
    '#default_value' => variable_get('commerce_checkout_target_pane_display', FALSE)
  );

  return $form;
}

function commerce_checkout_target_checkout_form($form, &$form_state, $checkout_pane, $order) {
  global $user;

  $pane_form = array();

  // if the pane has been configured to display information...
  if (variable_get('commerce_checkout_target_pane_display', FALSE)) {
    // Note we're not using theme_username() to avoid linking out of checkout.
    $pane_form['target'] = array(
       '#pane_id' => 'target',
       '#type' => 'textfield',
       '#title' => t('Delivery target account username'),
       '#size' => 60,
       '#maxlength' => 60,
       '#default_value' => check_plain($user->name),
    );
  }

  return $pane_form;
}

function commerce_checkout_target_checkout_form_validate($form, &$form_state, $checkout_pane, $order) {
  if (!empty($form_state['values'][$checkout_pane['pane_id']])) {
    $pane_values = $form_state['values'][$checkout_pane['pane_id']];

    // If the e-mail address field was present on the form...
    if (!empty($pane_values['target'])) {
      // Display an error if an invalid user name was given.
        $target = check_plain($pane_values['target']);
        if (user_load_by_name($target) == FALSE) {
           $error = 'Invalid target account specified. No account found with ' . $target . ' name';
           form_set_error($checkout_pane['pane_id'] . '][target][target]', $error);
           return FALSE;
      }
    }
  }
  return TRUE;
}

function commerce_checkout_target_checkout_form_submit($form, &$form_state, $checkout_pane, &$order) {
  global $user;

  if (!empty($form_state['values'][$checkout_pane['pane_id']])) {
    $pane_values = $form_state['values'][$checkout_pane['pane_id']];

    if (!empty($pane_values['target'])) {
      $name = check_plain($pane_values['target']);
    } else {
      $name = $user->name;
    }

    $order->field_target_account = array( 'und' => array( 0 => array( 'value' => $name )));
    $order->field_delivered = array( 'und' => array( 0 => array( 'value' => 0 )));
  }
}

function commerce_checkout_target_review($form, $form_state, $checkout_pane, $order) {
  $content = array();
  global $user;
  $name = $user->name;

  if (!empty($order->field_target_account) && !empty($order->field_target_account['und'])) {
    $name = $order->field_target_account['und'][0]['value'];
  }
	
  // Display the target.
  $content[] = array(
    '#type' => 'item',
    '#title' => t('Delivery target account username'),
    '#markup' => $name,
  );
  return drupal_render($content);
}


?>

