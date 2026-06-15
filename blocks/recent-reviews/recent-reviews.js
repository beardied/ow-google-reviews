(function (wp) {
	'use strict';

	var registerBlockType = wp.blocks.registerBlockType;
	var __ = wp.i18n.__;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var PanelBody = wp.components.PanelBody;
	var RangeControl = wp.components.RangeControl;
	var ToggleControl = wp.components.ToggleControl;
	var TextControl = wp.components.TextControl;
	var el = wp.element.createElement;

	registerBlockType('ow-google-reviews/recent-reviews', {
		edit: function (props) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;

			return el(
				'div',
				useBlockProps(),
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __('Review Settings', 'ow-google-reviews'), initialOpen: true },
						el(RangeControl, {
							label: __('Number of reviews to show', 'ow-google-reviews'),
							value: attributes.count,
							onChange: function (value) { setAttributes({ count: value }); },
							min: 1,
							max: 20
						}),
						el(ToggleControl, {
							label: __('Show "View all reviews" button', 'ow-google-reviews'),
							checked: attributes.showViewAllButton,
							onChange: function (value) { setAttributes({ showViewAllButton: value }); }
						}),
						attributes.showViewAllButton && el(TextControl, {
							label: __('Button URL', 'ow-google-reviews'),
							value: attributes.buttonUrl,
							onChange: function (value) { setAttributes({ buttonUrl: value }); },
							placeholder: 'https://example.com/reviews'
						}),
						attributes.showViewAllButton && el(TextControl, {
							label: __('Button Text', 'ow-google-reviews'),
							value: attributes.buttonText,
							onChange: function (value) { setAttributes({ buttonText: value }); }
						})
					)
				),
				el('p', { className: 'owgr-block-preview' }, __('Recent Google Reviews block — ', 'ow-google-reviews') + attributes.count + __(' review(s) will display here.', 'ow-google-reviews'))
			);
		},
		save: function () {
			return null;
		}
	});
})(window.wp);
