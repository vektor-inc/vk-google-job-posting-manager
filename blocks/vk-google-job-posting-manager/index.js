import React from "react";


const {__} = wp.i18n;
const { registerBlockType } = wp.blocks;
const {RangeControl, ServerSideRender, PanelBody, SelectControl} = wp.components;
const {Fragment} = wp.element;
const {InspectorControls} = wp.editor;

registerBlockType('vk-blocks/job-posting', {

		title: __( 'Job Posting', 'vk-job-posting' ),
		category: 'vk-blocks-cat',
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
							help={"Please enter the post ID which you want to display."}
							value={id}
							onChange={(value) => {
								setAttributes({id: value});
							}}
							min={0}
							step={1}
						/>
						<SelectControl
							label={__('Table Style', 'vk-job-posting')}
							value={style}
							onChange={(value) => setAttributes({style: value})}
							options={[
								{
									value: 'default',
									label: __('Default', 'vk-job-posting'),
								},
								{
									value: 'stripe',
									label: __('Stripe', 'vk-job-posting'),
								}
							]}
						/>
					</PanelBody>
				</InspectorControls>
				<div>
					<ServerSideRender
						block="vk-blocks/job-posting"
						attributes={attributes}
					/>
				</div>
			</Fragment>);
		},

		save() {

			return null;
		}

	});
