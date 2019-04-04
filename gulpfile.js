var gulp = require('gulp'),
    $ = require('gulp-load-plugins')(),
    webpackStream = require('webpack-stream'),
    webpack = require('webpack'),
    webpackConfig = require('./webpack.config'),
    sass = require('gulp-sass'),
    autoprefixer = require('gulp-autoprefixer'),
    cleanCss = require('gulp-clean-css');

gulp.task('sass', function () {
    return gulp.src(['./blocks/vk-job-posting/*.scss'])
        .pipe($.plumber({
            errorHandler: $.notify.onError('<%= error.message %>')
        }))
        .pipe($.sourcemaps.init({loadMaps: true}))
        .pipe($.sass({
            errLogToConsole: true,
            outputStyle: 'compressed',
            includePaths: [
                './blocks/vk-job-posting/'
            ]
        }))
        .pipe($.autoprefixer({browsers: ['last 2 version', '> 5%']}))
        .pipe($.sourcemaps.write('./map'))
        .pipe(gulp.dest('./blocks/vk-job-posting/'));
});

// Transpile and Compile Sass and Bundle it.
gulp.task('js', function () {
    return webpackStream(webpackConfig, webpack)
        .pipe(gulp.dest('./'));
});


// watch
gulp.task('watch', function () {
    gulp.watch('./blocks/vk-job-posting/*.js', ['js']);
    gulp.watch('./blocks/vk-job-posting/*.scss', ['sass']);
});

// Build
gulp.task('build', ['js', 'sass']);

// Default Tasks
gulp.task('default', ['watch']);
