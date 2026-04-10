/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        "./**/*.php",
        "./assets/src/**/*.{js,scss}",
    ],
    theme: {
        extend: {
            colors: {
                'primary': '#152751',
                'button-bg': 'rgba(255, 255, 255, 0.575)',
            },
            backgroundImage: {
                'gradient-tb': 'linear-gradient(to bottom, #cabee2, #cad7dd)',
                'gradient-bt': 'linear-gradient(to top, #cabee2, #cad7dd)',
                'gradient-lr': 'linear-gradient(to right, #cabee2, #cad7dd)',
                'gradient-rl': 'linear-gradient(to left, #cabee2, #cad7dd)',
            },
        },
    },
    plugins: [],
}