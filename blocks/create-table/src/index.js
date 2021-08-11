import React from "react";


const {__} = wp.i18n;
const { registerBlockType } = wp.blocks;
const {ServerSideRender, PanelBody, SelectControl,BaseControl} = wp.components;
const {Fragment} = wp.element;
const {InspectorControls} = wp.blockEditor;

registerBlockType('vk-google-job-posting-manager/create-table', {

		title: __( 'Job Posting', 'vk-google-job-posting-manager' ),
		category: 'vk-blocks-cat',
		attributes: {
			post_id: {
				type: 'number',
				default: 0,
			},
			style: {
				type: 'string',
				default: 'default'
			}
		},

		edit({attributes, setAttributes, className}) {
			const {
				style
			} = attributes;

			//Get postID from dom.
			attributes['post_id'] = jQuery('#post_ID').val();

			return (<Fragment>
				<InspectorControls>
					<PanelBody>
						<BaseControl
							label={__('Table Style', 'vk-google-job-posting-manager')}
							help={__('The preview will work after publish or save action.', 'vk-google-job-posting-manager')}
						>
						<SelectControl
							value={style}
							onChange={(value) => setAttributes({style: value})}
							options={[
								{
									value: 'default',
									label: __('Default', 'vk-google-job-posting-manager'),
								},
								{
									value: 'stripe',
									label: __('Stripe', 'vk-google-job-posting-manager'),
								}
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
			</Fragment>);
		},

		save() {

			return null;
		}

	});
