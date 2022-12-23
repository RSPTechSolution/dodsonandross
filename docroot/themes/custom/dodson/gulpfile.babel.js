'use strict';

import gulp         from 'gulp';
import autoprefixer from 'gulp-autoprefixer';
import plumber      from 'gulp-plumber';
import sass         from 'gulp-sass';
import sourcemaps   from 'gulp-sourcemaps';
import livereload   from 'gulp-livereload';

gulp.task('default', () => {

  livereload.listen();

  gulp.watch('./sass/**/*.scss', ['sass']);

});

gulp.task('sass', (files) => {
  return gulp.src('./sass/main.scss')
    .pipe(plumber())
    .pipe(sourcemaps.init())
    .pipe(sass.sync({
      outputStyle: 'expanded',
        precision: 10,
        includePaths: ['.']
    }).on('error', sass.logError))
    .pipe(autoprefixer({browsers: ['last 2 versions']}))
    .pipe(sourcemaps.write())
    .pipe(gulp.dest('./css'));

    livereload.changed(files);
});
