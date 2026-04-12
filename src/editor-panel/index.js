/**
 * VK Google Job Posting Manager - Editor Panel
 *
 * ブロックエディタのサイドバーに求人情報入力パネルを追加する。
 * PluginSidebar を使い、ツールバーに専用アイコンを表示。
 * 各セクションは PanelBody で折りたたみ可能。
 */

import { registerPlugin } from '@wordpress/plugins';
import { PluginSidebar } from '@wordpress/editor';
import {
	CheckboxControl,
	TextControl,
	TextareaControl,
	SelectControl,
	Button,
	PanelBody,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { useEntityProp } from '@wordpress/core-data';
import { MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';

// ローカライズデータとi18n文字列を取得
const data = window.vgjpmPanelData || {};
const i18n = data.i18n || {};
const currencies = data.currencies || [];

// 雇用形態の選択肢一覧
const employmentTypes = [
	{ value: 'FULL_TIME', label: i18n.fullTime || 'FULL TIME' },
	{ value: 'PART_TIME', label: i18n.partTime || 'PART TIME' },
	{ value: 'CONTRACTOR', label: i18n.contractor || 'CONTRACTOR' },
	{ value: 'TEMPORARY', label: i18n.temporary || 'TEMPORARY' },
	{ value: 'INTERN', label: i18n.intern || 'INTERN' },
	{ value: 'VOLUNTEER', label: i18n.volunteer || 'VOLUNTEER' },
	{ value: 'PER_DIEM', label: i18n.perDiem || 'PER DIEM' },
	{ value: 'OTHER', label: i18n.otherType || 'OTHER' },
];

/**
 * 求人情報サイドバーコンポーネント
 *
 * PluginSidebar 内に6つの PanelBody セクションを配置。
 * ツールバーのアイコンからアクセスし、各セクションは折りたたみ可能。
 */
const JobPostingPanels = () => {
	const postType = useSelect(
		( s ) => s( 'core/editor' ).getCurrentPostType(),
		[]
	);
	const [ meta, setMeta ] = useEntityProp( 'postType', postType, 'meta' );

	/**
	 * メタデータを更新する
	 *
	 * @param {string} key   メタキー
	 * @param {*}      value 設定する値
	 */
	const update = ( key, value ) => setMeta( { ...meta, [ key ]: value } );

	/**
	 * 配列型メタデータの値をトグルする
	 *
	 * @param {string} key   メタキー
	 * @param {string} value トグルする値
	 */
	const toggleArrayValue = ( key, value ) => {
		const arr = Array.isArray( meta?.[ key ] ) ? [ ...meta[ key ] ] : [];
		const idx = arr.indexOf( value );
		if ( idx >= 0 ) {
			arr.splice( idx, 1 );
		} else {
			arr.push( value );
		}
		update( key, arr );
	};

	/**
	 * 配列型メタデータに値が含まれているか判定する
	 *
	 * @param {string} key   メタキー
	 * @param {string} value 検索する値
	 * @return {boolean} 値が含まれていればtrue
	 */
	const isInArray = ( key, value ) => {
		return Array.isArray( meta?.[ key ] ) && meta[ key ].includes( value );
	};

	// ロゴ画像のIDとデータを取得
	const logoId = meta?.vkjp_logo ? parseInt( meta.vkjp_logo, 10 ) : 0;
	const logoImage = useSelect(
		( s ) => ( logoId ? s( 'core' ).getMedia( logoId ) : null ),
		[ logoId ]
	);

	return (
		<PluginSidebar
			name="vgjpm-job-posting"
			title={
				i18n.sidebarTitle ||
				'Google Job Posting Registration Information'
			}
			icon="businessman"
		>
			{ /* Section 1: 求人情報 */ }
			<PanelBody
				title={ i18n.jobInfo || 'Job Information' }
				initialOpen={ true }
			>
				<TextControl
					label={ ( i18n.jobTitle || 'Job Title' ) + ' *' }
					value={ meta?.vkjp_title || '' }
					onChange={ ( v ) => update( 'vkjp_title', v ) }
				/>
				<TextareaControl
					label={
						( i18n.jobDescription || 'Job Description' ) + ' *'
					}
					value={ meta?.vkjp_description || '' }
					onChange={ ( v ) => update( 'vkjp_description', v ) }
					rows={ 6 }
				/>
			</PanelBody>

			{ /* Section 2: 給与 */ }
			<PanelBody title={ i18n.salary || 'Salary' } initialOpen={ false }>
				<TextControl
					label={ i18n.minSalary || 'Minimum Salary' }
					value={ meta?.vkjp_minValue || '' }
					onChange={ ( v ) => update( 'vkjp_minValue', v ) }
				/>
				<TextControl
					label={ i18n.maxSalary || 'Maximum Salary' }
					value={ meta?.vkjp_maxValue || '' }
					onChange={ ( v ) => update( 'vkjp_maxValue', v ) }
				/>
				<SelectControl
					label={ i18n.salaryCycle || 'Salary Cycle' }
					value={ meta?.vkjp_unitText || '' }
					options={ [
						{ value: '', label: '—' },
						{
							value: 'HOUR',
							label: i18n.perHour || 'Per hour',
						},
						{
							value: 'DAY',
							label: i18n.perDay || 'Per Day',
						},
						{
							value: 'WEEK',
							label: i18n.perWeek || 'Per Week',
						},
						{
							value: 'MONTH',
							label: i18n.perMonth || 'Per month',
						},
						{
							value: 'YEAR',
							label: i18n.perYear || 'Per year',
						},
					] }
					onChange={ ( v ) => update( 'vkjp_unitText', v ) }
				/>
				<SelectControl
					label={ i18n.currency || 'Currency' }
					value={ meta?.vkjp_currency || '' }
					options={ currencies }
					onChange={ ( v ) => update( 'vkjp_currency', v ) }
				/>
			</PanelBody>

			{ /* Section 3: 雇用 */ }
			<PanelBody
				title={ i18n.employment || 'Employment' }
				initialOpen={ false }
			>
				<p style={ { marginTop: 0, marginBottom: '8px' } }>
					{ i18n.employmentType || 'Employment Type' }
				</p>
				{ employmentTypes.map( ( { value, label } ) => (
					<div key={ value } style={ { marginBottom: '8px' } }>
						<CheckboxControl
							__nextHasNoMarginBottom
							label={ label }
							checked={ isInArray(
								'vkjp_employmentType',
								value
							) }
							onChange={ () =>
								toggleArrayValue( 'vkjp_employmentType', value )
							}
						/>
					</div>
				) ) }

				<hr style={ { margin: '16px 0' } } />

				<div style={ { marginBottom: '8px' } }>
					<CheckboxControl
						__nextHasNoMarginBottom
						label={ i18n.telecommute || 'TELECOMMUTE' }
						checked={ isInArray(
							'vkjp_jobLocationType',
							'TELECOMMUTE'
						) }
						onChange={ () =>
							toggleArrayValue(
								'vkjp_jobLocationType',
								'TELECOMMUTE'
							)
						}
					/>
				</div>

				<TextControl
					label={
						i18n.applicantLocationRequirements ||
						'Applicant Location Requirements'
					}
					value={
						meta?.vkjp_applicantLocationRequirements_name || ''
					}
					onChange={ ( v ) =>
						update( 'vkjp_applicantLocationRequirements_name', v )
					}
				/>

				<div style={ { marginBottom: '8px' } }>
					<CheckboxControl
						__nextHasNoMarginBottom
						label={ i18n.directApply || 'Direct Apply' }
						checked={ isInArray( 'vkjp_directApply', 'true' ) }
						onChange={ () =>
							toggleArrayValue( 'vkjp_directApply', 'true' )
						}
					/>
				</div>
			</PanelBody>

			{ /* Section 4: 採用組織 */ }
			<PanelBody
				title={ i18n.hiringOrganization || 'Hiring Organization' }
				initialOpen={ false }
			>
				<TextControl
					label={
						( i18n.organizationName || 'Organization Name' ) + ' *'
					}
					value={ meta?.vkjp_name || '' }
					onChange={ ( v ) => update( 'vkjp_name', v ) }
				/>
				<TextControl
					label={ i18n.organizationUrl || 'Organization URL' }
					value={ meta?.vkjp_sameAs || '' }
					onChange={ ( v ) => update( 'vkjp_sameAs', v ) }
					type="url"
				/>
				<p style={ { marginTop: 0, marginBottom: '8px' } }>
					{ i18n.logo || 'Logo' }
				</p>
				<MediaUploadCheck>
					<MediaUpload
						onSelect={ ( media ) =>
							update( 'vkjp_logo', String( media.id ) )
						}
						allowedTypes={ [ 'image' ] }
						value={ logoId }
						render={ ( { open } ) => (
							<div>
								{ logoId > 0 && logoImage && (
									<img
										src={ logoImage.source_url }
										alt=""
										style={ {
											maxWidth: '100%',
											height: 'auto',
											marginBottom: '8px',
										} }
									/>
								) }
								<div
									style={ {
										display: 'flex',
										gap: '8px',
									} }
								>
									<Button
										onClick={ open }
										variant={
											logoId ? 'secondary' : 'primary'
										}
									>
										{ logoId
											? i18n.changeImage || 'Change'
											: i18n.chooseImage ||
											  'Choose Image' }
									</Button>
									{ logoId > 0 && (
										<Button
											onClick={ () =>
												update( 'vkjp_logo', '' )
											}
											isDestructive
											variant="tertiary"
										>
											{ i18n.removeImage || 'Remove' }
										</Button>
									) }
								</div>
							</div>
						) }
					/>
				</MediaUploadCheck>
			</PanelBody>

			{ /* Section 5: 勤務地 */ }
			<PanelBody
				title={ i18n.workLocation || 'Work Location' }
				initialOpen={ false }
			>
				<TextControl
					label={ i18n.postalCode || 'Postal Code' }
					value={ meta?.vkjp_postalCode || '' }
					onChange={ ( v ) => update( 'vkjp_postalCode', v ) }
				/>
				<TextControl
					label={ i18n.country || 'Country' }
					value={ meta?.vkjp_addressCountry || '' }
					onChange={ ( v ) => update( 'vkjp_addressCountry', v ) }
				/>
				<TextControl
					label={ i18n.region || 'Region' }
					value={ meta?.vkjp_addressRegion || '' }
					onChange={ ( v ) => update( 'vkjp_addressRegion', v ) }
				/>
				<TextControl
					label={ i18n.locality || 'Locality' }
					value={ meta?.vkjp_addressLocality || '' }
					onChange={ ( v ) => update( 'vkjp_addressLocality', v ) }
				/>
				<TextControl
					label={ i18n.streetAddress || 'Street Address' }
					value={ meta?.vkjp_streetAddress || '' }
					onChange={ ( v ) => update( 'vkjp_streetAddress', v ) }
				/>
			</PanelBody>

			{ /* Section 6: その他 */ }
			<PanelBody title={ i18n.other || 'Other' } initialOpen={ false }>
				<TextControl
					label={ i18n.validThrough || 'Valid Through' }
					value={ meta?.vkjp_validThrough || '' }
					onChange={ ( v ) => update( 'vkjp_validThrough', v ) }
					type="date"
				/>
				<TextControl
					label={ i18n.identifier || 'Identifier' }
					value={ meta?.vkjp_identifier || '' }
					onChange={ ( v ) => update( 'vkjp_identifier', v ) }
				/>
			</PanelBody>
		</PluginSidebar>
	);
};

registerPlugin( 'vgjpm-editor-panels', { render: JobPostingPanels } );
