var gulp = require('gulp'),
   sass = require('gulp-sass'),
   autoprefixer = require('gulp-autoprefixer'),
   concat = require('gulp-concat'),
   cleanCSS = require('gulp-clean-css'),
   notify = require("gulp-notify"),
   plumber = require('gulp-plumber');

const package = require('./package.json');

gulp.task(
   'sass',
   function() {

      return gulp
         .src(
            package.paths.assets + package.files.scss
         )
         .pipe(
            plumber({
               errorHandler: notify.onError({
                  title: 'Error',
                  message: 'Error Compiling SASS'
               })
            })
         )
         .pipe(
            sass().on('error', sass.logError)
         )
         .pipe(
            autoprefixer({
               browsers: ['last 3 versions'],
               cascade: false
            })
         )
         .pipe(
            concat(package.files.css)
         )
         .pipe(
            gulp.dest(package.paths.dist + 'css')
         )
         .pipe(
            notify({
               message: 'Compiled Sass'
            })
         );

   }
);

/* Minify CSS */

gulp.task(
   'minify_css',
   function() {

      return gulp
         .src(
            package.files.dist + '.css'
         )
         .pipe(
            plumber({
               errorHandler: notify.onError({
                  title: 'Error',
                  message: 'Error Minifying CSS'
               })
            })
         )
         .pipe(
            cleanCSS({
               compatibility: 'ie9'
            })
         )
         .pipe(
            gulp.dest(package.paths.dist)
         )
         .pipe(
            notify({
               message: 'Minified CSS'
            })
         );

   }
);
