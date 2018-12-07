<?php
/**
 * Part of the Planet4 Form Builder.
 */

namespace P4FB\Form_Builder\Templates;

use Timber\Post;
use Timber\Timber;

$context                    = Timber::get_context();
$context['post']            = new Post();
$context['form_submit_url'] = admin_url( 'admin_post.php' );
$context['action']          = 'p4fb_form_submit';
$context['nonce_action']    = $context['action'] . '-' . $context['post']->ID;
$context['nonce_name']    = '_p4fb_form';

// process the field options for easier rendering
foreach ( $context['post']->p4_form_fields as $index => $field ) {
	if ( ( 'select' === $field['type'] ) || ( 'checkbox-group' === $field['type'] ) || ( 'radio-group' === $field['type'] ) ) {
		$options     = $field['options'];
		$options     = explode( "\n", $options );
		$new_options = [];
		foreach ( $options as $option ) {
			if ( empty( trim( $option ) ) ) {
				continue;
			}
			$parts = explode( '|', $option, 2 );
			if ( count( $parts ) > 1 ) {
				$new_options[ trim( $parts[0] ) ] = trim( $parts[1] );
			} else {
				$new_options[ trim( $option ) ] = trim( $option );
			}
		}
		$context['post']->p4_form_fields[ $index ]['options'] = $new_options;
	}
}

Timber::render(
	[
		'single-' . $context['post']->post_type . '.twig',
		'single.twig',
	],
	$context
);
