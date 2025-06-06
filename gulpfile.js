const gulp = require('gulp');
const rtlcss = require('gulp-rtlcss');
const concat = require('gulp-concat');
const cssbeautify = require('gulp-cssbeautify');
const cssuglify = require('gulp-uglifycss');
const jsuglify = require('gulp-uglify');
const sass = require('gulp-sass')(require('sass'));
const spawn = require('child_process').spawn;

function scssTask() {
	// compile scss to css and minify
	return gulp.src('./assets/css/wp-consent-api.scss')
		.pipe(sass({ outputStyle: 'expanded' }).on('error', sass.logError))
		.pipe(cssbeautify())
		.pipe(gulp.dest('./assets/css'))
		.pipe(cssuglify())
		.pipe(concat('wp-consent-api.min.css'))
		.pipe(gulp.dest('./assets/css'))
		.pipe(rtlcss())
		.pipe(gulp.dest('./assets/css/rtl'));
}

gulp.task('scss', scssTask);

function jsTask() {
	return gulp.src('assets/js/wp-consent-api.js')
		.pipe(concat('wp-consent-api.js'))
		.pipe(gulp.dest('assets/js'))
		.pipe(concat('wp-consent-api.min.js'))
		.pipe(jsuglify())
		.pipe(gulp.dest('assets/js'));
}

gulp.task('js', jsTask);

function defaultTask() {
	gulp.watch('./assets/css/*.scss', { ignoreInitial: false }, gulp.series('scss'));
	gulp.watch('./assets/js/*.js', { ignoreInitial: false }, gulp.series('js'));
	// spawn('npm', ['start'], { cwd: 'settings', stdio: 'inherit' });
}

gulp.task('default', gulp.series(gulp.parallel('scss', 'js'), defaultTask));
