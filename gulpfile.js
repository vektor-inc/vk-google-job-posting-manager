var gulp = require('gulp'),
    $ = require('gulp-load-plugins')(),
    webpackStream = require('webpack-stream'),
    webpack = require('webpack'),
    webpackConfig = require('./webpack.config'),
    sass = require('gulp-sass'),
    autoprefixer = require('gulp-autoprefixer'),
    cleanCss = require('gulp-clean-css'),
		replace = require('gulp-replace'),
		runSequence = require('run-sequence');

gulp.task('sass', function () {
		return gulp.src('./assets/_scss/*.scss',{ base: './assets/_scss' })
			.pipe($.plumber({
					errorHandler: $.notify.onError('<%= error.message %>')
			}))
			.pipe($.sass({
					errLogToConsole: true,
					outputStyle: 'compressed',
					includePaths: [
							'./assets/css/'
					]
			}))
			.pipe($.autoprefixer())
			.pipe(gulp.dest('./assets/css/'));
});
gulp.task('sass_block', function () {
    return gulp.src(['./blocks/create-table/*.scss'])
        .pipe($.plumber({
            errorHandler: $.notify.onError('<%= error.message %>')
        }))
        .pipe($.sourcemaps.init({loadMaps: true}))
        .pipe($.sass({
            errLogToConsole: true,
            outputStyle: 'compressed',
            includePaths: [
                './blocks/create-table/'
            ]
        }))
        .pipe($.autoprefixer())
        .pipe($.sourcemaps.write('./map'))
        .pipe(gulp.dest('./blocks/create-table/'))
});

// Transpile and Compile Sass and Bundle it.
gulp.task('js', function () {
    return webpackStream(webpackConfig, webpack)
        .pipe(gulp.dest('./'));
});


// replace_text_domain ////////////////////////////////////////////////
gulp.task('replace_text_domain', function () {
		return gulp.src(['./inc/custom-field-builder/package/*'])
				.pipe(replace('custom_field_builder_textdomain', 'vk-google-job-posting-manager'))
				.pipe(gulp.dest('./inc/custom-field-builder/package/'));
});


// watch
gulp.task('watch', function () {
    gulp.watch('./blocks/create-table/*.js', gulp.parallel('js'));
    gulp.watch('./blocks/create-table/*.scss', gulp.parallel('sass_block'));
		gulp.watch('./assets/_scss/*.scss', gulp.parallel('sass'));
});

// Build
gulp.task('build', gulp.series('js', 'sass', 'replace_text_domain'));

// Default Tasks
gulp.task('default', gulp.series('watch'));

// copy dist ////////////////////////////////////////////////

gulp.task('copy_dist', function() {
    return gulp.src(
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
							"!./.distignore",
							"!./.gitignore",
							"!./Gruntfile.js",
							"!./gulpfile.js",
							"!./**.yml",
							"!./**.json",
							"!./**.dist",
							"!./**.config.js",
            ],
            { base: './' }
        )
        .pipe( gulp.dest( 'dist' ) ); // distディレクトリに出力
} );
// gulp.task('build:dist',function(){
//     /* ここで、CSS とか JS をコンパイルする */
// });

gulp.task('dist', function(cb){
    // return runSequence( 'build:dist', 'copy', cb );
    // return runSequence( 'build:dist', 'copy_dist', cb );
    return runSequence( 'copy_dist', cb );
});
