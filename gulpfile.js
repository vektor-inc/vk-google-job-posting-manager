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

// copy dist ////////////////////////////////////////////////

gulp.task('dist', (done) => {
	gulp.src(
		[
			'./build/**',
			'./inc/**',
			'./src/**',
			'./vendor/**',
			'./*.txt',
			'./*.png',
			'./*.php',
			'!./tests/**',
			'!./dist/**',
			'!./node_modules/**',
		],
		{ base: './' }
	).pipe(gulp.dest('dist/vk-blocks-pro')); // distディレクトリに出力
	done();
});
