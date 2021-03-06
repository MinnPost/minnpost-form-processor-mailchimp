// Dependencies
const autoprefixer = require("autoprefixer");
const babel = require("gulp-babel");
const browserSync = require("browser-sync").create();
const concat = require("gulp-concat");
const cssnano = require("cssnano");
const fs = require("fs");
const gulp = require("gulp");
const packagejson = JSON.parse(fs.readFileSync("./package.json"));
const mqpacker = require("css-mqpacker");
const postcss = require("gulp-postcss");
const rename = require("gulp-rename");
const sass = require("gulp-sass");
const sassGlob = require("gulp-sass-glob");
const sourcemaps = require("gulp-sourcemaps");
const uglify = require("gulp-uglify");
const wpPot = require("gulp-wp-pot");

// Some config data for our tasks
const config = {
  styles: {
    admin: 'assets/sass/admin.scss',
    front_end: 'assets/sass/front-end.scss',
    srcDir: 'assets/sass',
    dest: 'assets/css'
  },
  scripts: {
    admin: './assets/js/admin/**/*.js',
    front_end: './assets/js/front-end/**/*.js',
    dest: './assets/js'
  },
  languages: {
    src: [
      "./**/*.php",
      "!.git/*",
      "!.svn/*",
      "!bin/**/*",
      "!node_modules/*",
      "!release/**/*",
      "!vendor/**/*"
    ],
    dest: "./languages/" + packagejson.name + ".pot"
  },
  browserSync: {
    active: false,
    localURL: 'mylocalsite.local'
  }
};

function adminstyles() {
  return gulp.src(config.styles.admin)
    .pipe(sourcemaps.init()) // Sourcemaps need to init before compilation
    .pipe(sassGlob()) // Allow for globbed @import statements in SCSS
    .pipe(sass()) // Compile
    .on('error', sass.logError) // Error reporting
    .pipe(postcss([
      autoprefixer(), // Autoprefix resulting CSS
      cssnano() // Minify
    ]))
    .pipe(rename({ // Rename to .min.css
      suffix: '.min'
    }))
    .pipe(sourcemaps.write()) // Write the sourcemap files
    .pipe(gulp.dest(config.styles.dest)) // Drop the resulting CSS file in the specified dir
    .pipe(browserSync.stream());
}

function frontendstyles() {
  return gulp.src(config.styles.front_end)
    .pipe(sourcemaps.init()) // Sourcemaps need to init before compilation
    .pipe(sassGlob()) // Allow for globbed @import statements in SCSS
    .pipe(sass()) // Compile
    .on('error', sass.logError) // Error reporting
    .pipe(postcss([
      autoprefixer(), // Autoprefix resulting CSS
      cssnano() // Minify
    ]))
    .pipe(rename({ // Rename to .min.css
      suffix: '.min'
    }))
    .pipe(sourcemaps.write()) // Write the sourcemap files
    .pipe(gulp.dest(config.styles.dest)) // Drop the resulting CSS file in the specified dir
    .pipe(browserSync.stream());
}

function adminscripts() {
  return gulp.src(config.scripts.admin)
    .pipe(sourcemaps.init())
    .pipe(babel({
      presets: ['@babel/preset-env']
    }))
    .pipe(concat('admin.js')) // Concatenate
    .pipe(uglify()) // Minify + compress
    .pipe(rename({
      suffix: '.min'
    }))
    .pipe(sourcemaps.write())
    .pipe(gulp.dest(config.scripts.dest))
    .pipe(browserSync.stream());
}

function frontendscripts() {
  return gulp.src(config.scripts.front_end)
    .pipe(sourcemaps.init())
    .pipe(babel({
      presets: ['@babel/preset-env']
    }))
    .pipe(concat('front-end.js')) // Concatenate
    .pipe(uglify()) // Minify + compress
    .pipe(rename({
      suffix: '.min'
    }))
    .pipe(sourcemaps.write())
    .pipe(gulp.dest(config.scripts.dest))
    .pipe(browserSync.stream());
}

// Generates translation file.
function translate() {
  return gulp
    .src(config.languages.src)
    .pipe(
      wpPot({
        domain: packagejson.name,
        package: packagejson.name
      })
    )
    .pipe(gulp.dest(config.languages.dest));
}

// Injects changes into browser
function browserSyncTask() {
  if (config.browserSync.active) {
    browserSync.init({
      proxy: config.browserSync.localURL
    });
  }
}

// Reloads browsers that are using browsersync
function browserSyncReload(done) {
  browserSync.reload();
  done();
}

// Watch directories, and run specific tasks on file changes
function watch() {
  gulp.watch(config.styles.srcDir, styles);
  gulp.watch(config.scripts.admin, adminscripts);
  
  // Reload browsersync when PHP files change, if active
  if (config.browserSync.active) {
    gulp.watch('./**/*.php', browserSyncReload);
  }
}

// define complex tasks
//const lint    = gulp.series(adminsasslint, frontendsasslint, adminscriptlint, frontendscriptlint);
const styles  = gulp.series(adminstyles, frontendstyles);
const scripts = gulp.series(adminscripts, frontendscripts);
const build   = gulp.series(gulp.parallel(styles, scripts, translate));

// export tasks
exports.styles    = styles;
exports.scripts   = scripts;
//exports.lint      = lint;
exports.translate = translate;
//exports.changelog = changelog;
exports.watch     = watch;
exports.build     = build;
exports.default   = build;
