import React from 'react';
import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import ServerSideRender from '@wordpress/server-side-render';
import { PanelBody, SelectControl, BaseControl } from '@wordpress/components';
import { InspectorControls } from '@wordpress/block-editor';

registerBlockType('vk-google-job-posting-manager/create-table', {
	title: __('Job Posting', 'vk-google-job-posting-manager'),
	category: 'vk-blocks-cat',
	attributes: {
		post_id: {
			type: 'number',
			default: 0,
		},
		style: {
			type: 'string',
			default: 'default',
		},
		className: {
			type: 'string',
			default: '',
		},
	},

	edit({ attributes, setAttributes }) {
		const { style } = attributes;

		//Get postID from dom.
		attributes.post_id = document.getElementById('post_ID').value;

		return (
			<>
				<InspectorControls>
					<PanelBody>
						<BaseControl
							id={`vkgjpm-tableStyle`}
							label={__(
								'Table Style',
								'vk-google-job-posting-manager'
							)}
							help={__(
								'The preview will work after publish or save action.',
								'vk-google-job-posting-manager'
							)}
						>
							<SelectControl
								value={style}
								onChange={(value) =>
									setAttributes({ style: value })
								}
								options={[
									{
										value: 'default',
										label: __(
											'Default',
											'vk-google-job-posting-manager'
										),
									},
									{
										value: 'stripe',
										label: __(
											'Stripe',
											'vk-google-job-posting-manager'
										),
									},
								]}
							/>
						</BaseControl>
					</PanelBody>
				</InspectorControls>
				<div>
					<ServerSideRender
						block="vk-google-job-posting-manager/create-table"
						attributes={attributes}
					/>
				</div>
			</>
		);
	},

	save() {
		return null;
	},
});
