const gulp = require('gulp');
const replace = require('gulp-replace');

// replace_text_domain ////////////////////////////////////////////////
gulp.task('replace_text_domain', function () {
	return gulp
		.src(['./inc/custom-field-builder/package/*'])
		.pipe(
			replace(
				'custom_field_builder_textdomain',
				'vk-google-job-posting-manager'
			)
		)
		.pipe(gulp.dest('./inc/custom-field-builder/package/'));
});

/**
 * Dist
 */
gulp.task('dist', (done) => {
	gulp.src(
		[
			'./assets/**',
			'./blocks/**',
			'./inc/**',
			'./vendor/**',
			'./*.txt',
			'./*.png',
			'./*.php',
			'!./tests/**',
			'!./dist/**',
			'!./node_modules/**',
		],
		{ base: './' }
	).pipe(gulp.dest('dist/vk-google-job-posting-manager')); // distディレクトリに出力
	done();
});
