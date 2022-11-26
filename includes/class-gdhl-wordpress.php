<?php

class GDHL_WordPress {

	public function __construct() {
		add_action( 'init', array( $this, 'init' ) );
	}

	public function init() {
		register_post_status(
			'wc-transit',
			array(
				'label'                     => 'in Transit',
				'public'                    => true,
				'show_in_admin_status_list' => true,
				'show_in_admin_all_list'    => true,
				'exclude_from_search'       => false,
				// translators: count
				'label_count'               => _n_noop( 'in Transit <span class="count">(%s)</span>', 'in Transit <span class="count">(%s)</span>' ),
			)
		);

		register_post_status(
			'wc-delivered',
			array(
				'label'                     => 'Delivered',
				'public'                    => true,
				'show_in_admin_status_list' => true,
				'show_in_admin_all_list'    => true,
				'exclude_from_search'       => false,
				// translators: count
				'label_count'               => _n_noop( 'Delivered <span class="count">(%s)</span>', 'Delivered <span class="count">(%s)</span>' ),
			)
		);
	}
}
