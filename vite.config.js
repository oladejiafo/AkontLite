import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
          function ({ addComponents }) {
            addComponents({
              '.btn-outline-primary': {
                padding: '0.5rem 1rem',
                borderWidth: '1px',
                borderColor: '#2dc4b6',
                color: '#2dc4b6',
                backgroundColor: 'rgba(45, 196, 182, 0.1)',
                borderRadius: '0.375rem',
                transition: 'all 0.2s',
              },
            });
          },
    ],
    theme: {
        extend: {
          colors: {
            primary: '#2dc4b6',
            'primary-faded': 'rgba(45, 196, 182, 0.1)',
          },
        },
      }
      
});
