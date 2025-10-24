import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

// https://vite.dev/config/
export default defineConfig({
  plugins: [react()],
  // Use a relative base so built assets use relative paths. This works for GitHub Pages
  // whether the site is served from the root or from a project subpath.
  base: './'
})
