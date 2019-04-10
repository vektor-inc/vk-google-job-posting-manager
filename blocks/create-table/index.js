import React from "react";


const {__} = wp.i18n;
const { registerBlockType } = wp.blocks;
const {RangeControl, ServerSideRender, PanelBody, SelectControl} = wp.components;
const {Fragment} = wp.element;
const {InspectorControls} = wp.editor;

registerBlockType('vk-google-job-posting-manager/create-table', {

		title: __( 'Job Posting', 'vk-google-job-posting-manager' ),
		category: 'widgets',
		attributes: {
			id: {
				type: 'number',
				default: 0
			},
			style: {
				type: 'string',
				default: 'default'
			}
		},

		edit({attributes, setAttributes, className}) {
			const {
				id,
				style
			} = attributes;

			return (<Fragment>
				<InspectorControls>
					<PanelBody>
						<RangeControl
							help={__('Please enter the post ID which you want to display', 'vk-google-job-posting-manager')}
							value={id}
							onChange={(value) => {
								setAttributes({id: value});
							}}
							min={0}
							step={1}
						/>
						<SelectControl
							label={__('Table Style', 'vk-google-job-posting-manager')}
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
