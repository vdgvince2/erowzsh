/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "*.php",
    "inc/*.php",
    "pages/*.php",
    "assets/*.css",
    "assets/*.js",
  ],
  safelist: [
    // mobile grid selector — generated dynamically by JS / PHP loops
    "grid-cols-2","grid-cols-3","grid-cols-4",
    "grid-cols-5","grid-cols-6","grid-cols-7",
    "sm:grid-cols-4","md:grid-cols-5","lg:grid-cols-6","xl:grid-cols-7",
    // line-clamp
    "line-clamp-1","line-clamp-2","line-clamp-3",
    // aspect
    "aspect-square",
    // backdrop
    "backdrop-blur-sm",
  ],
  theme: {
    extend: {},
  },
  plugins: [],
};

