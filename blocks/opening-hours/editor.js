( function ( blocks, element, components, labels ) {
	'use strict';
	var el = element.createElement;
	blocks.registerBlockType( 'digicyfr/opening-hours', {
		title: labels.title,
		description: labels.description,
		icon: 'clock',
		category: 'widgets',
		edit: function () {
			return el( components.Placeholder, { icon: 'clock', label: labels.title },
				el( 'p', null, labels.preview )
			);
		},
		save: function () { return null; }
	} );
} )( wp.blocks, wp.element, wp.components, digicyfrOpeningHoursLabels );
