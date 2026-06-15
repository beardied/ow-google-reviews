(function (wp) {
	'use strict';

	var registerBlockType = wp.blocks.registerBlockType;
	var __ = wp.i18n.__;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var el = wp.element.createElement;

	registerBlockType('ow-google-reviews/all-reviews', {
		edit: function () {
			return el(
				'div',
				useBlockProps(),
				el('p', { className: 'owgr-block-preview' }, __('All Google Reviews block — every stored review will display here.', 'ow-google-reviews'))
			);
		},
		save: function () {
			return null;
		}
	});
})(window.wp);
