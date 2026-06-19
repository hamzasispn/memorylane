import { defineConfig } from "vite";
import path from "path";

export default defineConfig({
  root: ".",
  // Relative base so asset URLs baked into the JS/CSS (intl-tel-input's
  // utils.js + flag sprite PNGs) resolve relative to the file's own location
  // instead of the domain root. The theme is served from a deep path
  // (/wp-content/themes/<name>/assets/dist/…) and the folder name differs per
  // environment, so an absolute "/assets/…" base 404s in production.
  base: "./",
  build: {
    outDir: "assets/dist",
    emptyOutDir: true,
    manifest: true,
    rollupOptions: {
      input: {
        main: path.resolve(__dirname, "assets/src/js/main.js"),
        boek: path.resolve(__dirname, "assets/src/js/boek.js"),
      }
    },
  },
});
