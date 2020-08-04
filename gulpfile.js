const { src, dest, series, parallel, watch } = require('gulp');
const autoprefixer = require('autoprefixer');
const babel = require('gulp-babel');
const concat = require('gulp-concat');
const expect = require('gulp-expect-file');
const postcss = require('gulp-postcss');
const rename = require('gulp-rename');
const sass = require('gulp-sass');
const uglifyEs = require('gulp-uglify-es').default;

function buildScss() {
    var files = ['scss/data-tables.scss'];
    return src(files)
        .pipe(expect(files))
        .pipe(sass({
            outputStyle: 'compressed',
        }).on('error', sass.logError))
        .pipe(postcss([autoprefixer()]))
        .pipe(rename('data-tables.min.css'))
        .pipe(dest('dist'));
}

function buildJs(cb) {
    var files = [
        'js/data-tables.js',
        'js/redirect.js',
    ];
    return src(files)
        .pipe(expect(files))
        .pipe(babel({
            presets: ['@babel/preset-env'],
        }))
        .pipe(uglifyEs())
        .pipe(concat('data-tables.min.js'))
        .pipe(dest('dist'));
}

const build = parallel(buildScss, buildJs);

function watchBuild() {
    return watch([
        'js/**/*.*',
        'scss/**/*.*',
        'examples/**/*.*',
        'classes/**/*.*',
    ], build);
}

exports.watch = series(build, watchBuild);
exports.default = build;