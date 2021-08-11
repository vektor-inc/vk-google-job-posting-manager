const gulp = require( 'gulp' );
const replace = require( 'gulp-replace' );

// replace_text_domain ////////////////////////////////////////////////
gulp.task( 'replace_text_domain', function () {
	return gulp
		.src( [ './inc/custom-field-builder/package/*' ] )
		.pipe(
			replace(
				'custom_field_builder_textdomain',
				'vk-google-job-posting-manager'
			)
		)
		.pipe( gulp.dest( './inc/custom-field-builder/package/' ) );
} );

// copy dist ////////////////////////////////////////////////

gulp.task( 'dist', function () {
	return gulp
		.src(
			[
				'./**.php',
				'./**.txt',
				'./**.png',
				'./**.jpg',
				'./**.md',
				'./assets/**',
				'./blocks/**',
				'./inc/**',
				'./vendor/**',
				'./languages/**',
				'!./.distignore',
				'!./.gitignore',
				'!./Gruntfile.js',
				'!./gulpfile.js',
				'!./**.yml',
				'!./**.json',
				'!./**.dist',
				'!./**.config.js',
			],
			{ base: './' }
		)
		.pipe( gulp.dest( 'dist' ) ); // distディレクトリに出力
} );
