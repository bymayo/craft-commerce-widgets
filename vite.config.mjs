import { defineConfig } from 'vite'
import tailwindcss from '@tailwindcss/vite'
import FullReload from 'vite-plugin-full-reload'

export default defineConfig({
  plugins: [
    tailwindcss(),
    FullReload([
      'src/templates/**/*.twig',
      'src/resources/src/css/**/*.css'
    ])
  ],
  build: {
    outDir: './src/resources/dist/js', // Specify the output directory
    emptyOutDir: true, // Clear the output directory before building
    target: 'esnext', // Avoid class fields polyfill that creates var $ = Object.defineProperty (conflicts with jQuery)
    watch: process.argv.includes('--watch') ? {
      include: ['src/templates/**/*.twig'],
    } : null,
    rollupOptions: {
      input: './src/resources/src/commerce-widgets.js', // Single input file
      output: {
        entryFileNames: '[name].js', // Keep the original file name
        format: 'iife', // Use IIFE format to avoid conflicts with other scripts
        inlineDynamicImports: true, // Ensure dynamic imports are inlined
        globals: {
          // Define any global variables if needed
        },
      },
    },
  },
  server: {
    watch: {
      include: ['src/templates/**', 'src/resources/src/css/**'], // Watch Twig and CSS files
    },
  },
})