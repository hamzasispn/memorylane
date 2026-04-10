<?php get_header(); ?>
    <!-- page content -->
     <div x-data="{ checked: false }">
    <button 
        class="p-6 text-lg rounded"
        @click="checked = !checked"
        :class="checked ? 'bg-green-500 text-white' : 'bg-gray-200'"
        class="bg-gray-200 text-gray-800"
    >
        <span x-text="checked ? 'Checked' : 'Click Me'"></span>
    </button>
</div>
<?php get_footer(); ?>