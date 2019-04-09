var gulp = require('gulp'),
    $ = require('gulp-load-plugins')(),
    webpackStream = require('webpack-stream'),
    webpack = require('webpack'),
    webpackConfig = require('./webpack.config'),
    sass = require('gulp-sass'),
    autoprefixer = require('gulp-autoprefixer'),
    cleanCss = require('gulp-clean-css'),
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
			.pipe($.autoprefixer({browsers: ['last 2 version', '> 5%']}))
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
        .pipe($.autoprefixer({browsers: ['last 2 version', '> 5%']}))
        .pipe($.sourcemaps.write('./map'))
        .pipe(gulp.dest('./blocks/create-table/'))
});

// Transpile and Compile Sass and Bundle it.
gulp.task('js', function () {
    return webpackStream(webpackConfig, webpack)
        .pipe(gulp.dest('./'));
});


// watch
gulp.task('watch', function () {
    gulp.watch('./blocks/create-table/*.js', ['js']);
    gulp.watch('./blocks/create-table/*.scss', ['sass_block']);
		gulp.watch('./assets/_scss/*.scss', ['sass']);
});

// Build
gulp.task('build', ['js', 'sass']);

// Default Tasks
gulp.task('default', ['watch']);

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
